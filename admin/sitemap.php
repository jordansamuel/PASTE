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

// Guard: admin session
if (!isset($_SESSION['admin_login']) || !isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

$date = date('Y-m-d H:i:s');
$ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

require_once('../config.php'); // expects $dbhost,$dbuser,$dbpassword,$dbname,$mod_rewrite

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

    // Fetch baseurl
    $row = $pdo->query("SELECT baseurl FROM site_info WHERE id=1")->fetch();
    if (!$row || empty($row['baseurl'])) {
        throw new Exception("Base URL not found in site_info. Go to /admin/configuration.php");
    }
    $baseurl = rtrim((string)$row['baseurl'], '/');

    // Validate admin
    $st = $pdo->prepare("SELECT id, user FROM admin WHERE id=?");
    $st->execute([$_SESSION['admin_id']]);
    $adm = $st->fetch();
    if (!$adm || $adm['user'] !== $_SESSION['admin_login']) {
        unset($_SESSION['admin_login'], $_SESSION['admin_id']);
        header("Location: " . htmlspecialchars($baseurl . '/admin/index.php', ENT_QUOTES, 'UTF-8'));
        exit();
    }

    // Log admin activity
    $st = $pdo->query("SELECT MAX(id) last_id FROM admin_history");
    $last_id = $st->fetch()['last_id'] ?? null;
    $last_ip = $last_date = null;
    if ($last_id) {
        $st = $pdo->prepare("SELECT ip,last_date FROM admin_history WHERE id=?");
        $st->execute([$last_id]);
        $h = $st->fetch();
        $last_ip = $h['ip'] ?? null;
        $last_date = $h['last_date'] ?? null;
    }
    if ($last_ip !== $ip || $last_date !== $date) {
        $st = $pdo->prepare("INSERT INTO admin_history(last_date,ip) VALUES(?,?)");
        $st->execute([$date,$ip]);
    }

    // Load current sitemap options (create row if missing)
    $st = $pdo->prepare("SELECT priority, changefreq FROM sitemap_options WHERE id=1");
    $st->execute();
    $opt = $st->fetch() ?: ['priority'=>'0.5','changefreq'=>'weekly'];
    $priority   = (string)($opt['priority'] ?? '0.5');
    $changefreq = (string)($opt['changefreq'] ?? 'weekly');

    $msg = '';
    $msg_type = 'info';
    $written_count = null;

    // Save options
    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_options'])) {
        // validate priority
        $p = trim((string)($_POST['priority'] ?? '0.5'));
        if ($p === '' || !is_numeric($p)) $p = '0.5';
        $p = max(0.0, min(1.0, (float)$p));

        // validate changefreq
        $allowed_cf = ['always','hourly','daily','weekly','monthly','yearly','never'];
        $cf = strtolower(trim((string)($_POST['changefreq'] ?? 'weekly')));
        if (!in_array($cf, $allowed_cf, true)) $cf = 'weekly';

        // upsert options
        $pdo->beginTransaction();
        $exists = $pdo->query("SELECT 1 FROM sitemap_options WHERE id=1")->fetchColumn();
        if ($exists) {
            $st = $pdo->prepare("UPDATE sitemap_options SET priority=?, changefreq=? WHERE id=1");
            $st->execute([number_format($p,1,'.',''), $cf]);
        } else {
            $st = $pdo->prepare("INSERT INTO sitemap_options(id,priority,changefreq) VALUES(1,?,?)");
            $st->execute([number_format($p,1,'.',''), $cf]);
        }
        $pdo->commit();

        $priority = number_format($p,1,'.','');
        $changefreq = $cf;
        $msg = 'Sitemap options saved.';
        $msg_type = 'success';
    }

    // Rebuild sitemap
    if (isset($_GET['rebuild'])) {
        $today = date('Y-m-d');

        // prepare temp file
        $tmp_path   = dirname(__DIR__) . '/sitemap.xml.tmp';
        $final_path = dirname(__DIR__) . '/sitemap.xml';

        $fh = fopen($tmp_path, 'wb');
        if (!$fh) {
            throw new Exception("Unable to open temporary sitemap file for writing.");
        }

        // XML header + open urlset
        fwrite($fh, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
        fwrite($fh, "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n");

        // Homepage
        $home = htmlspecialchars($baseurl . '/', ENT_QUOTES, 'UTF-8');
        fwrite($fh, "  <url>\n");
        fwrite($fh, "    <loc>{$home}</loc>\n");
        fwrite($fh, "    <priority>1.0</priority>\n");
        fwrite($fh, "    <changefreq>daily</changefreq>\n");
        fwrite($fh, "    <lastmod>{$today}</lastmod>\n");
        fwrite($fh, "  </url>\n");

        // Pull options fresh (in case just saved)
        $st = $pdo->prepare("SELECT priority, changefreq FROM sitemap_options WHERE id=1");
        $st->execute();
        $opt = $st->fetch() ?: ['priority'=>'0.5','changefreq'=>'weekly'];
        $item_priority   = number_format((float)$opt['priority'],1,'.','');
        $item_changefreq = in_array($opt['changefreq'], ['always','hourly','daily','weekly','monthly','yearly','never'], true)
            ? $opt['changefreq'] : 'weekly';

        // Count public pastes
        $total_public = (int)$pdo->query("SELECT COUNT(*) FROM pastes WHERE visible='0'")->fetchColumn();

        // Stream in chunks
        $limit   = 500;
        $written = 1; // homepage
        for ($offset=0; $offset < $total_public; $offset += $limit) {
            $st = $pdo->prepare("SELECT id FROM pastes WHERE visible='0' ORDER BY id DESC LIMIT :lim OFFSET :off");
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->bindValue(':off', $offset, PDO::PARAM_INT);
            $st->execute();
            $rows = $st->fetchAll();

            foreach ($rows as $r) {
                $id = (int)$r['id'];
                if ((string)$mod_rewrite === "1") {
                    $url = $baseurl . '/' . rawurlencode((string)$id);
                } else {
                    $url = $baseurl . '/paste.php?id=' . urlencode((string)$id);
                }
                $loc = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                fwrite($fh, "  <url>\n");
                fwrite($fh, "    <loc>{$loc}</loc>\n");
                fwrite($fh, "    <priority>{$item_priority}</priority>\n");
                fwrite($fh, "    <changefreq>{$item_changefreq}</changefreq>\n");
                fwrite($fh, "    <lastmod>{$today}</lastmod>\n");
                fwrite($fh, "  </url>\n");
                $written++;
            }
        }

        // Close urlset
        fwrite($fh, "</urlset>\n");
        fclose($fh);

        // Atomic replace
        if (!rename($tmp_path, $final_path)) {
            @unlink($tmp_path);
            throw new Exception("Failed to move temporary sitemap into place.");
        }

        $msg = 'sitemap.xml rebuilt successfully. URLs written: ' . number_format($written);
        $msg_type = 'success';
        $written_count = $written;
    }

} catch (PDOException $e) {
    die("Unable to connect to database: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
} catch (Exception $e) {
    $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    $msg_type = 'danger';
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Paste - Sitemap</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
  :root{
    --bg: #0f1115;
    --card:#141821;
    --muted:#7f8da3;
    --border:#1f2633;
    --accent:#0d6efd;
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

  .stat-chip{display:inline-flex; align-items:center; gap:.5rem; padding:.4rem .6rem; background:#222733; border:1px solid #31384a; border-radius:10px}
  .stat-chip i{opacity:.9}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const btn = document.getElementById('rebuildBtn');
  if (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (confirm('Rebuild sitemap.xml now? This will overwrite the existing file.')) {
        const u = new URL(window.location.href);
        u.searchParams.set('rebuild', '1');
        window.location.href = u.toString();
      }
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
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/dashboard.php'); ?>"><i class="bi bi-house me-2"></i>Dashboard</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/configuration.php'); ?>"><i class="bi bi-gear me-2"></i>Configuration</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/interface.php'); ?>"><i class="bi bi-eye me-2"></i>Interface</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/admin.php'); ?>"><i class="bi bi-person me-2"></i>Admin Account</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/pastes.php'); ?>"><i class="bi bi-clipboard me-2"></i>Pastes</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/users.php'); ?>"><i class="bi bi-people me-2"></i>Users</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/ipbans.php'); ?>"><i class="bi bi-ban me-2"></i>IP Bans</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/stats.php'); ?>"><i class="bi bi-graph-up me-2"></i>Statistics</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/ads.php'); ?>"><i class="bi bi-currency-pound me-2"></i>Ads</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/pages.php'); ?>"><i class="bi bi-file-earmark me-2"></i>Pages</a>
      <a class="list-group-item active" href="<?php echo htmlspecialchars($baseurl.'/admin/sitemap.php'); ?>"><i class="bi bi-map me-2"></i>Sitemap</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/tasks.php'); ?>"><i class="bi bi-list-task me-2"></i>Tasks</a>
    </div>
  </div>
</div>

<div class="container-fluid my-2">
  <div class="row g-2">
    <!-- Desktop sidebar -->
    <div class="col-lg-2 d-none d-lg-block">
      <div class="sidebar-desktop">
        <div class="list-group">
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/dashboard.php'); ?>"><i class="bi bi-house me-2"></i>Dashboard</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/configuration.php'); ?>"><i class="bi bi-gear me-2"></i>Configuration</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/interface.php'); ?>"><i class="bi bi-eye me-2"></i>Interface</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/admin.php'); ?>"><i class="bi bi-person me-2"></i>Admin Account</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/pastes.php'); ?>"><i class="bi bi-clipboard me-2"></i>Pastes</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/users.php'); ?>"><i class="bi bi-people me-2"></i>Users</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/ipbans.php'); ?>"><i class="bi bi-ban me-2"></i>IP Bans</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/stats.php'); ?>"><i class="bi bi-graph-up me-2"></i>Statistics</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/ads.php'); ?>"><i class="bi bi-currency-pound me-2"></i>Ads</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/pages.php'); ?>"><i class="bi bi-file-earmark me-2"></i>Pages</a>
          <a class="list-group-item active" href="<?php echo htmlspecialchars($baseurl.'/admin/sitemap.php'); ?>"><i class="bi bi-map me-2"></i>Sitemap</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'/admin/tasks.php'); ?>"><i class="bi bi-list-task me-2"></i>Tasks</a>
        </div>
      </div>
    </div>

    <div class="col-lg-10">
      <?php if (!empty($msg)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($msg_type); ?> alert-dismissible fade show" role="alert">
          <?php echo $msg; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <div class="card mb-3">
        <div class="card-body">
          <h4 class="card-title mb-3">Sitemap Options</h4>
          <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label">Change Frequency</label>
                <select name="changefreq" class="form-select">
                  <?php
                  $opts = ['always','hourly','daily','weekly','monthly','yearly','never'];
                  foreach ($opts as $o) {
                      $sel = ($changefreq === $o) ? 'selected' : '';
                      echo '<option value="'.htmlspecialchars($o).'" '.$sel.'>'.ucfirst($o).'</option>';
                  }
                  ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Priority (0.0 – 1.0)</label>
                <input type="number" step="0.1" min="0" max="1" name="priority" class="form-control"
                       value="<?php echo htmlspecialchars($priority); ?>">
              </div>
              <div class="col-md-4 d-flex align-items-end">
                <button type="submit" name="save_options" class="btn btn-primary w-100">
                  <i class="bi bi-save"></i> Save Options
                </button>
              </div>
            </div>
          </form>
          <hr class="border-secondary my-4">
          <div class="d-flex flex-wrap gap-2">
            <div class="stat-chip"><i class="bi bi-globe2"></i> <span>Base URL:</span> <strong><?php echo htmlspecialchars($baseurl); ?></strong></div>
            <div class="stat-chip"><i class="bi bi-sliders"></i> <span>Rewrite:</span> <strong><?php echo ((string)$mod_rewrite==="1"?'On':'Off'); ?></strong></div>
            <?php if ($written_count !== null): ?>
              <div class="stat-chip"><i class="bi bi-list-check"></i> <span>URLs written:</span> <strong><?php echo number_format($written_count); ?></strong></div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <h4 class="card-title mb-3">Generate</h4>
          <p class="text-muted">Rebuilds <code>sitemap.xml</code> with public pastes and the homepage. Existing file will be replaced.</p>
          <div class="row g-2">
            <div class="col-sm-6 d-grid">
              <a href="#" id="rebuildBtn" class="btn btn-soft"><i class="bi bi-arrow-repeat"></i> Rebuild sitemap.xml</a>
            </div>
            <div class="col-sm-6 d-grid">
              <a class="btn btn-outline-primary" href="../sitemap.xml" target="_blank"><i class="bi bi-box-arrow-up-right"></i> View sitemap.xml</a>
            </div>
          </div>
        </div>
      </div>

      <div class="text-muted small mt-3">
        Powered by <a class="text-decoration-none" href="https://phpaste.sourceforge.io" target="_blank">Paste</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<?php
// Handle logout from dropdown
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit();
}
$pdo = null;
?>
</body>
</html>
