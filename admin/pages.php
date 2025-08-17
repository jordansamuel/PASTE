<?php
/*
 * Paste Admin https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 *
 * https://phpaste.sourceforge.io/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in LICENCE for more details.
 */
session_start();

if (!isset($_SESSION['admin_login']) || !isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

$date = date('Y-m-d H:i:s');
$ip   = $_SERVER['REMOTE_ADDR'] ?? '';

require_once('../config.php');

try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpassword, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // site baseurl
    $baseurl = (string)($pdo->query("SELECT baseurl FROM site_info WHERE id=1")->fetch()['baseurl'] ?? '');

    $msg = '';
    $page_name = $page_title = $page_content = '';
    $location = '';
    $nav_parent = null;
    $sort_order = 0;
    $is_active = 1;

    // PARENT choices (top-level header/both only)
    $parentChoicesHeader = $pdo->query("
        SELECT id, page_title
          FROM pages
         WHERE is_active = 1
           AND (location='header' OR location='both')
           AND nav_parent IS NULL
         ORDER BY sort_order, page_title
    ")->fetchAll();

    // CREATE / UPDATE
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $page_name    = trim((string)($_POST['page_name'] ?? ''));
        $page_title   = trim((string)($_POST['page_title'] ?? ''));
        $page_content = (string)($_POST['data'] ?? '');

        // placement & meta
        $location     = (string)($_POST['location'] ?? '');
        if (!in_array($location, ['', 'header', 'footer', 'both'], true)) $location = '';
        $nav_parent   = ($_POST['nav_parent'] ?? '') === '' ? null : (int)$_POST['nav_parent'];
        $sort_order   = (int)($_POST['sort_order'] ?? 0);
        $is_active    = !empty($_POST['is_active']) ? 1 : 0;

        if (isset($_POST['editme'])) {
            $edit_id = (int)$_POST['editme'];

            // prevent self-parenting
            if ($nav_parent === $edit_id) $nav_parent = null;

            $stmt = $pdo->prepare("
                UPDATE pages
                   SET last_date = ?,
                       page_name = ?,
                       page_title = ?,
                       page_content = ?,
                       location = ?,
                       nav_parent = ?,
                       sort_order = ?,
                       is_active = ?
                 WHERE id = ?
            ");
            $stmt->execute([$date, $page_name, $page_title, $page_content, $location, $nav_parent, $sort_order, $is_active, $edit_id]);

            $msg = '<div class="alert alert-success text-center">Page updated successfully</div>';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO pages (last_date, page_name, page_title, page_content, location, nav_parent, sort_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$date, $page_name, $page_title, $page_content, $location, $nav_parent, $sort_order, $is_active]);
            $msg = '<div class="alert alert-success text-center">Page created successfully</div>';
        }

        // clear form post-success to avoid double submit
        $page_name = $page_title = $page_content = '';
        $location = '';
        $nav_parent = null;
        $sort_order = 0;
        $is_active = 1;
    }

    // EDIT load
    if (isset($_GET['edit'])) {
        $page_id = (int)$_GET['edit'];
        $row = $pdo->prepare("SELECT * FROM pages WHERE id=?");
        $row->execute([$page_id]);
        if ($r = $row->fetch()) {
            $page_name    = $r['page_name'];
            $page_title   = $r['page_title'];
            $page_content = $r['page_content'];
            $location     = (string)$r['location'];
            $nav_parent   = $r['nav_parent'];
            $sort_order   = (int)$r['sort_order'];
            $is_active    = (int)$r['is_active'];
        }
    }

    // DELETE
    if (isset($_GET['delete'])) {
        $del = (int)$_GET['delete'];

        // prevent deleting a page that has children
        $st = $pdo->prepare("SELECT COUNT(*) FROM pages WHERE nav_parent = ?");
        $st->execute([$del]);
        if ((int)$st->fetchColumn() > 0) {
            $msg = '<div class="alert alert-danger text-center">Please move or delete its sub-pages first.</div>';
        } else {
            $pdo->prepare("DELETE FROM pages WHERE id=?")->execute([$del]);
            $msg = '<div class="alert alert-success text-center">Page deleted successfully</div>';
        }
    }

    // Pagination & list
    $per_page = 20;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $per_page;

    $total = (int)($pdo->query("SELECT COUNT(*) c FROM pages")->fetch()['c'] ?? 0);
    $pages_total = max(1, (int)ceil($total / $per_page));

    $list = $pdo->prepare("
        SELECT p.id, p.last_date, p.page_name, p.page_title,
               p.location, p.nav_parent, p.sort_order, p.is_active
          FROM pages p
         ORDER BY p.id DESC
         LIMIT :lim OFFSET :off
    ");
    $list->bindValue(':lim', $per_page, PDO::PARAM_INT);
    $list->bindValue(':off', $offset, PDO::PARAM_INT);
    $list->execute();
    $pages = $list->fetchAll();

} catch (PDOException $e) {
    die("DB error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Paste - Pages</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<!-- Quill 2 (BSD-3) -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

<style>
  :root{
    --bg: #0f1115;
    --card:#141821;
    --muted:#7f8da3;
    --border:#1f2633;
    --accent:#0d6efd;
    --content:#0f1115;
    --content-text:#e6edf3;
    --toolbar:#101521;
  }
  body{background:var(--bg);color:#fff;}
  .navbar{background:#121826!important;position:sticky;top:0;z-index:1030}
  .navbar .navbar-brand{font-weight:600}

  /* Desktop sidebar */
  .sidebar-desktop{
    position:sticky; top:1rem;
    background:#121826;border:1px solid var(--border);
    border-radius:12px;padding:12px;
  }
  .sidebar-desktop .list-group-item{
    background:transparent;color:#dbe5f5;border:0;border-radius:10px;padding:.65rem .8rem;
  }
  .sidebar-desktop .list-group-item:hover{background:#0e1422}
  .sidebar-desktop .list-group-item.active{background:#0d6efd;color:#fff}

  .card{background:var(--card);border:1px solid var(--border);border-radius:12px}
  .form-control,.form-select{background:#0e1422;border-color:var(--border);color:#e6edf3}
  .form-control:focus,.form-select:focus{border-color:var(--accent);box-shadow:0 0 0 .25rem rgba(13,110,253,.25)}
  .table{color:#e6edf3}
  .table thead th{background:#101521;color:#c6d4f0;border-color:var(--border)}
  .table td,.table th{border-color:var(--border)}
  .pagination .page-link{color:#c6d4f0;background:#101521;border-color:var(--border)}
  .pagination .page-item.active .page-link{background:#0d6efd;border-color:#0d6efd}
  .btn-soft{background:#101521;border:1px solid var(--border);color:#dbe5f5}
  .btn-soft:hover{background:#0e1422;color:#fff}

  /* Quill dark styling override */
  .ql-container.ql-snow{
    border:1px solid var(--border);
    border-radius:8px;
    font-size:16px;
    background:var(--content);
    color:var(--content-text);
    min-height:360px;
  }
  .ql-editor {
    min-height:360px;
    color:var(--content-text);
  }
  .ql-toolbar.ql-snow{
    background:var(--toolbar);
    border:1px solid var(--border);
    border-radius:8px;
  }
  .ql-snow .ql-picker, .ql-snow .ql-stroke{ color:#dbe5f5; stroke:#dbe5f5; }
  .ql-snow .ql-fill{ fill:#dbe5f5; }
  .ql-snow .ql-picker-options{ background:#0e1422; border-color:var(--border); }
  .ql-snow .ql-tooltip{ background:#0e1422; border:1px solid var(--border); color:#e6edf3; }
  .ql-snow .ql-tooltip input[type=text]{ background:#0c1220; color:#e6edf3; border-color:#23304a; }
  .ql-snow .ql-picker-label:hover, .ql-snow .ql-picker-item:hover{ color:#fff; }
  .ql-snow .ql-toolbar button:hover .ql-stroke,
  .ql-snow .ql-toolbar button:hover .ql-fill { color:#fff; stroke:#fff; fill:#fff; }
  .editor-footer {
    display:flex;justify-content:space-between;gap:12px;align-items:center;margin-top:8px;
    color:#9fb1d1;font-size:12px;
  }
  .editor-footer .status-dot{width:8px;height:8px;background:#2bd576;border-radius:50%;display:inline-block;margin-right:6px}
  .editor-footer .stats{opacity:.9}
  /* Offcanvas */
  .offcanvas-nav{width:280px;background:#0f1523;color:#dbe5f5}
  .offcanvas-nav .list-group-item{background:transparent;border:0;color:#dbe5f5}
  .offcanvas-nav .list-group-item:hover{background:#0e1422}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <div class="d-flex align-items-center gap-2">
      <!-- Mobile: open offcanvas -->
      <button class="btn btn-soft d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#navOffcanvas" aria-controls="navOffcanvas">
        <i class="bi bi-list"></i>
      </button>
      <a class="navbar-brand" href="../">Paste</a>
    </div>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <?php echo htmlspecialchars($_SESSION['admin_login']); ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="admin.php">Settings</a></li>
            <li><a class="dropdown-item" href="?logout">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Mobile offcanvas nav -->
<div class="offcanvas offcanvas-start offcanvas-nav" tabindex="-1" id="navOffcanvas">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Admin Menu</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <div class="list-group">
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/dashboard.php'); ?>"><i class="bi bi-house me-2"></i>Dashboard</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/configuration.php'); ?>"><i class="bi bi-gear me-2"></i>Configuration</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/interface.php'); ?>"><i class="bi bi-eye me-2"></i>Interface</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/admin.php'); ?>"><i class="bi bi-person me-2"></i>Admin Account</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/pastes.php'); ?>"><i class="bi bi-clipboard me-2"></i>Pastes</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/users.php'); ?>"><i class="bi bi-people me-2"></i>Users</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/ipbans.php'); ?>"><i class="bi bi-ban me-2"></i>IP Bans</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/stats.php'); ?>"><i class="bi bi-graph-up me-2"></i>Statistics</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/ads.php'); ?>"><i class="bi bi-currency-pound me-2"></i>Ads</a>
      <a class="list-group-item active" href="<?php echo htmlspecialchars($baseurl.'admin/pages.php'); ?>"><i class="bi bi-file-earmark me-2"></i>Pages</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/sitemap.php'); ?>"><i class="bi bi-map me-2"></i>Sitemap</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/tasks.php'); ?>"><i class="bi bi-list-task me-2"></i>Tasks</a>
    </div>
  </div>
</div>

<div class="container-fluid my-2">
  <div class="row g-2">
    <!-- Desktop sidebar -->
    <div class="col-lg-2 d-none d-lg-block">
      <div class="sidebar-desktop">
        <div class="list-group">
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/dashboard.php'); ?>"><i class="bi bi-house me-2"></i>Dashboard</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/configuration.php'); ?>"><i class="bi bi-gear me-2"></i>Configuration</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/interface.php'); ?>"><i class="bi bi-eye me-2"></i>Interface</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/admin.php'); ?>"><i class="bi bi-person me-2"></i>Admin Account</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/pastes.php'); ?>"><i class="bi bi-clipboard me-2"></i>Pastes</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/users.php'); ?>"><i class="bi bi-people me-2"></i>Users</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/ipbans.php'); ?>"><i class="bi bi-ban me-2"></i>IP Bans</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/stats.php'); ?>"><i class="bi bi-graph-up me-2"></i>Statistics</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/ads.php'); ?>"><i class="bi bi-currency-pound me-2"></i>Ads</a>
          <a class="list-group-item active" href="<?php echo htmlspecialchars($baseurl.'admin/pages.php'); ?>"><i class="bi bi-file-earmark me-2"></i>Pages</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/sitemap.php'); ?>"><i class="bi bi-map me-2"></i>Sitemap</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/tasks.php'); ?>"><i class="bi bi-list-task me-2"></i>Tasks</a>
        </div>
      </div>
    </div>

    <div class="col-lg-10">
      <!-- Editor card -->
      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h4 class="card-title mb-0"><?php echo isset($_GET['edit']) ? 'Edit Page' : 'Add a Page'; ?></h4>
            <div class="d-flex gap-2">
              <button type="button" id="preview-btn" class="btn btn-soft"><i class="bi bi-eye"></i> Preview</button>
            </div>
          </div>
          <?php if ($msg) echo $msg; ?>
          <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" id="page-form">
            <div class="row g-2">
              <div class="col-md-6">
                <label for="page_name" class="form-label">Page name (No spaces, e.g. terms_of_service)</label>
                <input class="form-control" id="page_name" name="page_name" type="text"
                       placeholder="Enter page name"
                       value="<?php echo htmlspecialchars($page_name); ?>">
              </div>
              <div class="col-md-6">
                <label for="page_title" class="form-label">Page title</label>
                <input class="form-control" id="page_title" name="page_title" type="text"
                       placeholder="Enter page title"
                       value="<?php echo htmlspecialchars($page_title); ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Show link in</label>
                <select class="form-select" name="location">
                  <?php
                    $loc = (string)$location;
                    $opts = [
                      ''       => '— Don’t show —',
                      'header' => 'Header (main nav)',
                      'footer' => 'Footer',
                      'both'   => 'Header & Footer'
                    ];
                    foreach ($opts as $v=>$label) {
                      $sel = $loc===$v ? 'selected' : '';
                      echo '<option value="'.htmlspecialchars($v).'" '.$sel.'>'.htmlspecialchars($label).'</option>';
                    }
                  ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Sub-navigation of (header)</label>
                <select class="form-select" name="nav_parent">
                  <option value="">— None (top-level) —</option>
                  <?php foreach ($parentChoicesHeader as $p): ?>
                    <option value="<?php echo (int)$p['id']; ?>" <?php echo ($nav_parent == $p['id'])?'selected':''; ?>>
                      <?php echo htmlspecialchars($p['page_title']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text">Choose a parent to make this a dropdown item (header only).</div>
              </div>
              <div class="col-md-2">
                <label class="form-label">Order</label>
                <input class="form-control" type="number" name="sort_order" value="<?php echo (int)$sort_order; ?>">
              </div>
              <div class="col-md-2 d-flex align-items-end">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="isActive" name="is_active" <?php echo $is_active ? 'checked':''; ?>>
                  <label class="form-check-label" for="isActive">Active</label>
                </div>
              </div>
            </div>

            <?php if (isset($_GET['edit'])): ?>
              <input type="hidden" name="editme" value="<?php echo (int)$_GET['edit']; ?>">
            <?php endif; ?>

            <!-- Editor -->
            <div class="mt-3">
              <div id="toolbar">
                <span class="ql-formats">
                  <select class="ql-header">
                    <option selected></option>
                    <option value="2">Heading</option>
                    <option value="3">Subheading</option>
                  </select>
                  <select class="ql-font"></select>
                  <select class="ql-size"></select>
                </span>
                <span class="ql-formats">
                  <button class="ql-bold"></button>
                  <button class="ql-italic"></button>
                  <button class="ql-underline"></button>
                  <button class="ql-strike"></button>
                </span>
                <span class="ql-formats">
                  <button class="ql-blockquote"></button>
                  <button class="ql-code-block"></button>
                </span>
                <span class="ql-formats">
                  <button class="ql-list" value="ordered"></button>
                  <button class="ql-list" value="bullet"></button>
                  <button class="ql-indent" value="-1"></button>
                  <button class="ql-indent" value="1"></button>
                </span>
                <span class="ql-formats">
                  <select class="ql-align"></select>
                </span>
                <span class="ql-formats">
                  <button class="ql-link"></button>
                  <button class="ql-image"></button>
                </span>
                <span class="ql-formats">
                  <button id="insertTable" type="button"><i class="bi bi-table"></i></button>
                  <button id="findReplace" type="button"><i class="bi bi-search"></i></button>
                </span>
                <span class="ql-formats">
                  <button id="clearFormat" type="button"><i class="bi bi-eraser"></i></button>
                </span>
              </div>
              <div id="editor"><?php echo $page_content; ?></div>
              <div class="editor-footer">
                <div><span class="status-dot"></span><span>Ready</span></div>
                <div class="stats" id="editorStats">0 words &middot; 0 characters</div>
              </div>
              <!-- Hidden field to submit HTML -->
              <textarea class="d-none" name="data" id="dataField"></textarea>
            </div>

            <div class="mt-3 d-flex gap-2">
              <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
              <a class="btn btn-soft" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"><i class="bi bi-plus-circle"></i> New</a>
              <button type="button" class="btn btn-soft" id="copyHTML"><i class="bi bi-clipboard"></i> Copy HTML</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Listing -->
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">Pages</h4>
          <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Name</th>
                  <th>Title</th>
                  <th>Location</th>
                  <th>Parent</th>
                  <th>Order</th>
                  <th>Active</th>
                  <th>View</th><th>Edit</th><th>Delete</th>
                </tr>
              </thead>
              <tbody>
                <?php
                if ($pages) {
                  // Build a cache for id=>title lookup
                  $titleMap = [];
                  foreach ($pages as $r) $titleMap[(int)$r['id']] = $r['page_title'];
                  foreach ($pages as $r) {
                    $loc = $r['location'] ?: '—';
                    if ($loc === 'both') $loc = 'Header+Footer';
                    $parentTitle = (isset($r['nav_parent']) && isset($titleMap[(int)$r['nav_parent']])) ? $titleMap[(int)$r['nav_parent']] : '—';
                    echo '<tr>';
                    echo '<td>'.htmlspecialchars($r['last_date']).'</td>';
                    echo '<td>'.htmlspecialchars($r['page_name']).'</td>';
                    echo '<td>'.htmlspecialchars($r['page_title']).'</td>';
                    echo '<td>'.htmlspecialchars($loc).'</td>';
                    echo '<td>'.htmlspecialchars($parentTitle).'</td>';
                    echo '<td>'.(int)$r['sort_order'].'</td>';
                    echo '<td>'.((int)$r['is_active']===1?'Yes':'No').'</td>';
                    echo '<td><a class="btn btn-soft btn-sm" target="_blank" href="../page/'.rawurlencode($r['page_name']).'">View</a></td>';
                    echo '<td><a class="btn btn-soft btn-sm" href="?edit='.(int)$r['id'].'&page='.(int)$page.'">Edit</a></td>';
                    echo '<td><a class="btn btn-danger btn-sm" href="?delete='.(int)$r['id'].'&page='.(int)$page.'" onclick="return confirm(\'Delete this page?\');">Delete</a></td>';
                    echo '</tr>';
                  }
                } else {
                  echo '<tr><td colspan="10" class="text-center">No pages found</td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>

          <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
              <?php
              if ($page > 1) echo '<li class="page-item"><a class="page-link" href="?page='.($page-1).'">&laquo;</a></li>';
              $start = max(1, $page-3); $end = min($pages_total, $page+3);
              for ($i=$start;$i<=$end;$i++){
                echo '<li class="page-item'.($i==$page?' active':'').'"><a class="page-link" href="?page='.$i.'">'.$i.'</a></li>';
              }
              if ($page < $pages_total) echo '<li class="page-item"><a class="page-link" href="?page='.($page+1).'">&raquo;</a></li>';
              ?>
            </ul>
          </nav>
        </div>
      </div>

      <div class="text-muted small mt-3">
        Powered by <a class="text-decoration-none" href="https://phpaste.sourceforge.io" target="_blank">Paste</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
(function(){
  // --- Quill init
  const editorEl = document.getElementById('editor');
  const toolbarEl = document.getElementById('toolbar');
  const q = new Quill(editorEl, {
    theme: 'snow',
    modules: {
      toolbar: {
        container: toolbarEl,
        handlers: {
          image: function(){
            const url = prompt('Paste image URL (https://...)');
            if (!url) return;
            const range = q.getSelection(true);
            q.insertEmbed(range.index, 'image', url, Quill.sources.USER);
            q.setSelection(range.index + 1, 0, Quill.sources.SILENT);
          }
        }
      },
      history: { delay: 800, maxStack: 200 },
      clipboard: true,
      syntax: false
    },
    placeholder: 'Write your page content here...'
  });

  // Preload content if PHP printed HTML (already in #editor)

  // Status & stats
  const statsEl = document.getElementById('editorStats');
  function updateStats(){
    const text = q.getText().trim();
    const words = text ? text.split(/\s+/).length : 0;
    const chars = text.replace(/\s/g,'').length;
    statsEl.textContent = words + ' words · ' + chars + ' characters';
  }
  q.on('text-change', updateStats);
  updateStats();

  // Insert simple table (3x3)
  document.getElementById('insertTable').addEventListener('click', () => {
    const tableHTML = `
      <table style="width:100%;border-collapse:collapse" border="1">
        <tr><th>Header 1</th><th>Header 2</th><th>Header 3</th></tr>
        <tr><td>Cell</td><td>Cell</td><td>Cell</td></tr>
        <tr><td>Cell</td><td>Cell</td><td>Cell</td></tr>
      </table><p></p>`;
    const range = q.getSelection(true) || {index: q.getLength(), length: 0};
    q.clipboard.dangerouslyPasteHTML(range.index, tableHTML);
  });

  // Find / replace (quick prompt)
  document.getElementById('findReplace').addEventListener('click', () => {
    const find = prompt('Find text:');
    if (!find) return;
    const replace = prompt('Replace with (leave empty to just highlight):','');
    const full = q.getText();
    const idx = full.indexOf(find);
    if (idx >= 0) {
      if (replace !== null) {
        // replace in HTML: simpler via HTML string replace
        const div = document.createElement('div');
        div.innerHTML = q.root.innerHTML;
        const walker = document.createTreeWalker(div, NodeFilter.SHOW_TEXT, null);
        let found = false;
        while (walker.nextNode()) {
          const node = walker.currentNode;
          const pos = node.nodeValue.indexOf(find);
          if (pos >= 0) {
            node.nodeValue = node.nodeValue.replace(find, replace);
            found = true;
          }
        }
        if (found) q.root.innerHTML = div.innerHTML;
      } else {
        q.setSelection(idx, find.length);
      }
    } else {
      alert('Not found.');
    }
  });

  // Clear formatting
  document.getElementById('clearFormat').addEventListener('click', () => {
    const r = q.getSelection();
    if (!r) return;
    q.removeFormat(r.index, r.length || 1);
  });

  // Copy HTML
  document.getElementById('copyHTML').addEventListener('click', async () => {
    const html = q.root.innerHTML;
    try {
      await navigator.clipboard.writeText(html);
      alert('HTML copied to clipboard.');
    } catch {
      alert('Copy failed.');
    }
  });

  // Preview
  document.getElementById('preview-btn')?.addEventListener('click', function() {
    const html = q.root.innerHTML;
    const w = window.open('', '_blank', 'width=900,height=700');
    w.document.write('<!doctype html><title>Preview</title><body style="background:#0f1115;color:#e6edf3;padding:24px;font:16px/1.6 system-ui,Segoe UI,Roboto,sans-serif">'+html+'</body>');
    w.document.close();
  });

  // Submit -> put HTML into hidden field
  document.getElementById('page-form').addEventListener('submit', function(){
    document.getElementById('dataField').value = q.root.innerHTML;
  });
})();
</script>
</body>
</html>
<?php $pdo = null; ?>
