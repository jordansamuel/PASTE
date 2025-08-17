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
$ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

require_once('../config.php');

try {
    $pdo = new PDO(
        "mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4",
        $dbuser,
        $dbpassword,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    // site baseurl
    $baseurl = rtrim((string)($pdo->query("SELECT baseurl FROM site_info WHERE id=1")->fetch()['baseurl'] ?? ''), '/') . '/';
    if (!$baseurl) { throw new Exception('Base URL missing.'); }

    // admin history log (lightweight)
    $last = $pdo->query("SELECT MAX(id) last_id FROM admin_history")->fetch();
    if ($last && $last['last_id']) {
        $row = $pdo->prepare("SELECT last_date, ip FROM admin_history WHERE id=?");
        $row->execute([$last['last_id']]);
        $r = $row->fetch();
        $last_date = $r['last_date'] ?? null;
        $last_ip   = $r['ip'] ?? null;
    }
    if (($last_ip ?? '') !== $ip || ($last_date ?? '') !== $date) {
        $pdo->prepare("INSERT INTO admin_history (last_date, ip) VALUES (?, ?)")->execute([$date, $ip]);
    }

} catch (Throwable $e) {
    die("Unable to connect to database: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

/** Helpers **/
function banIpAndDeletePaste(PDO $pdo, int $pasteId, string $nowDate): void {
    // get paste IP
    $st = $pdo->prepare("SELECT ip FROM pastes WHERE id = ?");
    $st->execute([$pasteId]);
    $row = $st->fetch();
    if (!$row) { return; }
    $pasteIp = trim((string)$row['ip']);

    if ($pasteIp !== '') {
        // ensure row exists in ban_user; handle last_date not null
        // try insert; if duplicates/exists, update last_date
        try {
            $ins = $pdo->prepare("INSERT INTO ban_user (ip, last_date) VALUES (?, ?)");
            $ins->execute([$pasteIp, $nowDate]);
        } catch (PDOException $ex) {
            // if unique constraint on ip, update last_date
            $upd = $pdo->prepare("UPDATE ban_user SET last_date = ? WHERE ip = ?");
            $upd->execute([$nowDate, $pasteIp]);
        }
    }

    // delete dependent rows then paste
    $pdo->prepare("DELETE FROM paste_views WHERE paste_id = ?")->execute([$pasteId]);
    $pdo->prepare("DELETE FROM pastes WHERE id = ?")->execute([$pasteId]);
}

function getPasteDetails(PDO $pdo, int $id): ?array {
    $st = $pdo->prepare("SELECT * FROM pastes WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) return null;

    $visible = match ((string)$row['visible']) {
        '0' => "Public",
        '1' => "Unlisted",
        '2' => "Private",
        '3' => "Banned",
        default => "Unknown"
    };
    $encrypt = ($row['encrypt'] === '1') ? "Encrypted" : "Not Encrypted";

    $expiry_raw = $row['expiry'];
    $expiry = ($expiry_raw === null || strtoupper((string)$expiry_raw) === 'NULL' || $expiry_raw === '')
        ? "Never"
        : (strtotime($expiry_raw) < time() ? "Paste is expired" : "Paste is not expired");

    $pass = (strtoupper((string)$row['password']) === 'NONE' || $row['password'] === null || $row['password'] === '')
        ? "Not protected"
        : "Password protected paste";

    $vs = $pdo->prepare("SELECT COUNT(*) AS c FROM paste_views WHERE paste_id = ?");
    $vs->execute([$id]);
    $views = (int)$vs->fetch()['c'];

    return [
        'id'       => $id,
        'title'    => $row['title'] ?? '',
        'member'   => $row['member'] ?? '',
        'visible'  => $visible,
        'password' => $pass,
        'views'    => $views,
        'ip'       => $row['ip'] ?? '',
        'code'     => $row['code'] ?? '',
        'expiry'   => $expiry,
        'encrypt'  => $encrypt,
    ];
}

/** Messages **/
$msg = '';
// Single actions
if (isset($_GET['delete'])) {
    $delid = (int)filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM paste_views WHERE paste_id = ?")->execute([$delid]);
        $pdo->prepare("DELETE FROM pastes WHERE id = ?")->execute([$delid]);
        $pdo->commit();
        $msg = '<div class="alert alert-success text-center">Paste deleted successfully.</div>';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $msg = '<div class="alert alert-danger text-center">Error deleting paste: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
    }
}

if (isset($_GET['ban'])) {
    $ban_id = (int)filter_var($_GET['ban'], FILTER_SANITIZE_NUMBER_INT);
    try {
        $pdo->beginTransaction();
        banIpAndDeletePaste($pdo, $ban_id, $date);
        $pdo->commit();
        $msg = '<div class="alert alert-warning text-center">IP banned and paste deleted.</div>';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $msg = '<div class="alert alert-danger text-center">Error banning IP/deleting paste: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
    }
}

// Bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && !empty($_POST['selected_ids'])) {
    $bulk = $_POST['bulk_action'];
    $ids = array_map('intval', (array)$_POST['selected_ids']);

    if (in_array($bulk, ['bulk_ban_delete', 'bulk_delete'], true) && !empty($ids)) {
        try {
            $pdo->beginTransaction();
            foreach ($ids as $pid) {
                if ($bulk === 'bulk_ban_delete') {
                    banIpAndDeletePaste($pdo, $pid, $date);
                } else {
                    $pdo->prepare("DELETE FROM paste_views WHERE paste_id = ?")->execute([$pid]);
                    $pdo->prepare("DELETE FROM pastes WHERE id = ?")->execute([$pid]);
                }
            }
            $pdo->commit();
            $label = $bulk === 'bulk_ban_delete' ? 'IPs banned & pastes deleted' : 'Pastes deleted';
            $msg = '<div class="alert alert-success text-center">Bulk action complete: ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '.</div>';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = '<div class="alert alert-danger text-center">Bulk action failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }
}

// filters / pagination / search
$per_page = 20;
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset   = ($page - 1) * $per_page;

$visibility_filter = isset($_GET['visibility']) ? (string)$_GET['visibility'] : 'all';
$q = trim((string)($_GET['q'] ?? ''));

$whereParts = [];
$params = [];

if ($visibility_filter !== 'all') {
    $whereParts[] = "p.visible = ?";
    $params[] = $visibility_filter;
}
if ($q !== '') {
    $whereParts[] = "(p.title LIKE ? OR p.member LIKE ? OR p.ip = ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = $q;
}
$where = $whereParts ? (' WHERE ' . implode(' AND ', $whereParts)) : '';

$count_query = "SELECT COUNT(*) AS total FROM pastes p $where";
$st = $pdo->prepare($count_query);
$st->execute($params);
$total_pastes = (int)($st->fetch()['total'] ?? 0);
$total_pages  = max(1, (int)ceil($total_pastes / $per_page));

$per_page_safe = (int)$per_page;
$offset_safe   = (int)$offset;

$sql = "
SELECT 
    p.id, p.member, p.ip, p.visible, p.title, p.now_time,
    COALESCE(v.view_count, 0) AS views
FROM pastes p
LEFT JOIN (
    SELECT paste_id, COUNT(*) AS view_count
    FROM paste_views
    GROUP BY paste_id
) v ON v.paste_id = p.id
$where
ORDER BY p.now_time DESC
LIMIT $per_page_safe OFFSET $offset_safe
";
$st = $pdo->prepare($sql);
$st->execute($params);
$pastes = $st->fetchAll();

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Paste - Pastes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
  :root{
    --bg: #0f1115;
    --card:#141821;
    --muted:#7f8da3;
    --border:#1f2633;
    --accent:#0d6efd;
    --content:#0f1115;
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

  /* Offcanvas */
  .offcanvas-nav{width:280px;background:#0f1523;color:#dbe5f5}
  .offcanvas-nav .list-group-item{background:transparent;border:0;color:#dbe5f5}
  .offcanvas-nav .list-group-item:hover{background:#0e1422}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Single delete
  document.querySelectorAll('.delete-paste').forEach(a => {
    a.addEventListener('click', (e) => {
      e.preventDefault();
      if (confirm('Delete this paste? This also clears its view logs.')) {
        window.location.href = a.getAttribute('href');
      }
    });
  });
  // Ban IP + delete
  document.querySelectorAll('.ban-paste').forEach(a => {
    a.addEventListener('click', (e) => {
      e.preventDefault();
      if (confirm('Ban this paste’s IP and delete the paste?')) {
        window.location.href = a.getAttribute('href');
      }
    });
  });
  // Select all
  const checkAll = document.getElementById('select-all');
  if (checkAll){
    checkAll.addEventListener('change', () => {
      document.querySelectorAll('.row-select').forEach(cb => cb.checked = checkAll.checked);
    });
  }
  // Bulk confirm
  const bulkForm = document.getElementById('bulk-form');
  if (bulkForm){
    bulkForm.addEventListener('submit', (e) => {
      const action = document.getElementById('bulk_action').value;
      const anyChecked = [...document.querySelectorAll('.row-select')].some(cb => cb.checked);
      if (!anyChecked) {
        e.preventDefault();
        alert('Please select at least one paste.');
        return;
      }
      let msg = 'Proceed with bulk action?';
      if (action === 'bulk_ban_delete') msg = 'Ban IPs for selected pastes and delete them?';
      if (action === 'bulk_delete') msg = 'Delete selected pastes (and their view logs)?';
      if (!confirm(msg)) e.preventDefault();
    });
  }
});
</script>
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
      <a class="list-group-item active" href="<?php echo htmlspecialchars($baseurl.'admin/pastes.php'); ?>"><i class="bi bi-clipboard me-2"></i>Pastes</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/users.php'); ?>"><i class="bi bi-people me-2"></i>Users</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/ipbans.php'); ?>"><i class="bi bi-ban me-2"></i>IP Bans</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/stats.php'); ?>"><i class="bi bi-graph-up me-2"></i>Statistics</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/ads.php'); ?>"><i class="bi bi-currency-pound me-2"></i>Ads</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/pages.php'); ?>"><i class="bi bi-file-earmark me-2"></i>Pages</a>
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
          <a class="list-group-item active" href="<?php echo htmlspecialchars($baseurl.'admin/pastes.php'); ?>"><i class="bi bi-clipboard me-2"></i>Pastes</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/users.php'); ?>"><i class="bi bi-people me-2"></i>Users</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/ipbans.php'); ?>"><i class="bi bi-ban me-2"></i>IP Bans</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/stats.php'); ?>"><i class="bi bi-graph-up me-2"></i>Statistics</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/ads.php'); ?>"><i class="bi bi-currency-pound me-2"></i>Ads</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/pages.php'); ?>"><i class="bi bi-file-earmark me-2"></i>Pages</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/sitemap.php'); ?>"><i class="bi bi-map me-2"></i>Sitemap</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/tasks.php'); ?>"><i class="bi bi-list-task me-2"></i>Tasks</a>
        </div>
      </div>
    </div>

    <div class="col-lg-10">
      <?php if ($msg) echo $msg; ?>

      <?php if (isset($_GET['details'])): ?>
        <?php
          $detail_id = (int)filter_var($_GET['details'], FILTER_SANITIZE_NUMBER_INT);
          $detail = getPasteDetails($pdo, $detail_id);
        ?>
        <?php if ($detail): ?>
          <div class="card mb-3">
            <div class="card-body">
              <h4 class="card-title">Details of Paste ID <?php echo htmlspecialchars($detail['id']); ?></h4>
              <table class="table table-striped">
                <tbody>
                  <tr><td>Username</td><td><?php echo htmlspecialchars($detail['member']); ?></td></tr>
                  <tr><td>Paste Title</td><td><?php echo htmlspecialchars($detail['title']); ?></td></tr>
                  <tr><td>Visibility</td><td><?php echo htmlspecialchars($detail['visible']); ?></td></tr>
                  <tr><td>Password</td><td><?php echo htmlspecialchars($detail['password']); ?></td></tr>
                  <tr><td>Views</td><td><?php echo number_format((int)$detail['views']); ?></td></tr>
                  <tr><td>IP</td><td><?php echo htmlspecialchars($detail['ip']); ?></td></tr>
                  <tr><td>Syntax Highlighting</td><td><?php echo htmlspecialchars($detail['code']); ?></td></tr>
                  <tr><td>Expiration</td><td><?php echo htmlspecialchars($detail['expiry']); ?></td></tr>
                  <tr><td>Encrypted Paste</td><td><?php echo htmlspecialchars($detail['encrypt']); ?></td></tr>
                </tbody>
              </table>
              <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-soft">Back</a>
            </div>
          </div>
        <?php else: ?>
          <div class="card mb-3"><div class="card-body"><h4 class="card-title">No paste found</h4></div></div>
        <?php endif; ?>

      <?php else: ?>
        <div class="card mb-3">
          <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
              <h4 class="card-title mb-3">Manage Pastes</h4>
              <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-2 mb-3">
                <div class="col-auto">
                  <label class="visually-hidden" for="visibility">Visibility</label>
                  <select class="form-select" name="visibility" id="visibility" onchange="this.form.submit()">
                    <option value="all" <?php echo $visibility_filter=='all'?'selected':''; ?>>All</option>
                    <option value="0"   <?php echo $visibility_filter==='0'?'selected':''; ?>>Public</option>
                    <option value="1"   <?php echo $visibility_filter==='1'?'selected':''; ?>>Unlisted</option>
                    <option value="2"   <?php echo $visibility_filter==='2'?'selected':''; ?>>Private</option>
                  </select>
                </div>
                <div class="col-auto">
                  <input type="text" class="form-control" name="q" placeholder="Search title / user / IP" value="<?php echo htmlspecialchars($q); ?>">
                </div>
                <div class="col-auto">
                  <button type="submit" class="btn btn-primary">Apply</button>
                </div>
              </form>
            </div>

            <form id="bulk-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
              <div class="row g-2 mb-3">
                <div class="col-auto">
                  <select class="form-select" id="bulk_action" name="bulk_action">
                    <option value="bulk_ban_delete">Ban IP + Delete (selected)</option>
                    <option value="bulk_delete">Delete (selected)</option>
                  </select>
                </div>
                <div class="col-auto">
                  <button type="submit" class="btn btn-danger">Run</button>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                  <thead>
                    <tr>
                      <th style="width:36px"><input type="checkbox" id="select-all"></th>
                      <th>ID</th>
                      <th>Username</th>
                      <th>Title</th>
                      <th>IP</th>
                      <th>Views</th>
                      <th>Visibility</th>
                      <th>Ban IP + Delete</th>
                      <th>Details</th>
                      <th>View</th>
                      <th>Delete</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($pastes)): ?>
                      <?php foreach ($pastes as $row): ?>
                        <?php
                          $visibility = match ((string)$row['visible']) {
                              '0' => 'Public',
                              '1' => 'Unlisted',
                              '2' => 'Private',
                              '3' => 'Banned',
                              default => 'Unknown'
                          };
                          $qs = [];
                          if ($visibility_filter !== 'all') $qs['visibility'] = $visibility_filter;
                          if ($q !== '') $qs['q'] = $q;
                          $qsBase = $qs ? '&'.http_build_query($qs) : '';
                        ?>
                        <tr>
                          <td><input type="checkbox" class="row-select" name="selected_ids[]" value="<?php echo (int)$row['id']; ?>"></td>
                          <td><?php echo (int)$row['id']; ?></td>
                          <td><?php echo htmlspecialchars($row['member']); ?></td>
                          <td><?php echo htmlspecialchars($row['title']); ?></td>
                          <td><span class="badge bg-primary"><?php echo htmlspecialchars($row['ip']); ?></span></td>
                          <td><?php echo number_format((int)$row['views']); ?></td>
                          <td><?php echo htmlspecialchars($visibility); ?></td>
                          <td><a href="?ban=<?php echo (int)$row['id']; ?>&page=<?php echo (int)$page . $qsBase; ?>" class="btn btn-warning btn-sm ban-paste">Ban IP + Delete</a></td>
                          <td><a href="?details=<?php echo (int)$row['id']; ?>" class="btn btn-soft btn-sm">Details</a></td>
                          <td><a href="../paste.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-soft btn-sm" target="_blank">View</a></td>
                          <td><a href="?delete=<?php echo (int)$row['id']; ?>&page=<?php echo (int)$page . $qsBase; ?>" class="btn btn-danger btn-sm delete-paste">Delete</a></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="11" class="text-center">No pastes found</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </form>

            <nav aria-label="Page navigation">
              <ul class="pagination justify-content-center">
                <?php
                $params = [];
                if ($visibility_filter !== 'all') $params['visibility'] = $visibility_filter;
                if ($q !== '') $params['q'] = $q;
                $paramStr = function($p) use ($params) {
                    $merged = array_merge($params, $p);
                    return $merged ? ('&'.http_build_query($merged)) : '';
                };

                if ($page > 1) {
                  echo '<li class="page-item"><a class="page-link" href="?page='.($page-1).$paramStr([]).'">&laquo;</a></li>';
                } else {
                  echo '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
                }
                $start = max(1, $page-3); $end = min($total_pages, $page+3);
                for ($i=$start; $i<=$end; $i++){
                  echo '<li class="page-item'.($i==$page?' active':'').'"><a class="page-link" href="?page='.$i.$paramStr([]).'">'.$i.'</a></li>';
                }
                if ($page < $total_pages) {
                  echo '<li class="page-item"><a class="page-link" href="?page='.($page+1).$paramStr([]).'">&raquo;</a></li>';
                } else {
                  echo '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
                }
                ?>
              </ul>
            </nav>

          </div>
        </div>
      <?php endif; ?>

      <div class="text-muted small mt-3">
        Powered by <a class="text-decoration-none" href="https://phpaste.sourceforge.io" target="_blank">Paste</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<?php
// logout handler (optional)
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit();
}
?>
</body>
</html>
<?php $pdo = null; ?>
