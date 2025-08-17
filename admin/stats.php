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

// Guard
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

    // baseurl
    $baseurl = rtrim((string)($pdo->query("SELECT baseurl FROM site_info WHERE id=1")->fetch()['baseurl'] ?? ''), '/') . '/';

    // validate admin
    $st = $pdo->prepare("SELECT id,user FROM admin WHERE id=?");
    $st->execute([$_SESSION['admin_id']]);
    $adm = $st->fetch();
    if (!$adm || $adm['user'] !== $_SESSION['admin_login']) {
        unset($_SESSION['admin_login'], $_SESSION['admin_id']);
        header("Location: " . htmlspecialchars($baseurl.'admin/index.php', ENT_QUOTES, 'UTF-8'));
        exit();
    }

    // admin history (best-effort)
    $last = $pdo->query("SELECT MAX(id) last_id FROM admin_history")->fetch()['last_id'] ?? null;
    $last_ip=null; $last_date=null;
    if ($last) {
        $st = $pdo->prepare("SELECT ip,last_date FROM admin_history WHERE id=?");
        $st->execute([$last]);
        $row = $st->fetch();
        $last_ip = $row['ip'] ?? null;
        $last_date = $row['last_date'] ?? null;
    }
    if (($last_ip ?? '') !== $ip || ($last_date ?? '') !== $date) {
        $st = $pdo->prepare("INSERT INTO admin_history (last_date, ip) VALUES (?, ?)");
        $st->execute([$date, $ip]);
    }

    // Summary stats
    $row = $pdo->query("SELECT SUM(tpage) AS total_page, SUM(tvisit) AS total_visit FROM page_view")->fetch();
    $total_page = (int)($row['total_page'] ?? 0);
    $total_un   = (int)($row['total_visit'] ?? 0);

    $row = $pdo->query("
        SELECT 
            COUNT(*) AS total_pastes,
            SUM(CASE WHEN expiry IS NOT NULL AND expiry <> 'SELF' AND UNIX_TIMESTAMP(expiry) < UNIX_TIMESTAMP() THEN 1 ELSE 0 END) AS exp_pastes
        FROM pastes
    ")->fetch();
    $total_pastes = (int)($row['total_pastes'] ?? 0);
    $exp_pastes   = (int)($row['exp_pastes'] ?? 0);

    $row = $pdo->query("SELECT 
        COUNT(*) AS total_users,
        SUM(CASE WHEN verified='2' THEN 1 ELSE 0 END) AS total_ban,
        SUM(CASE WHEN verified='0' THEN 1 ELSE 0 END) AS not_ver
        FROM users")->fetch();
    $total_users = (int)($row['total_users'] ?? 0);
    $total_ban   = (int)($row['total_ban'] ?? 0);
    $not_ver     = (int)($row['not_ver'] ?? 0);

    $total_paste_views = (int)($pdo->query("SELECT COUNT(*) c FROM paste_views")->fetch()['c'] ?? 0);

    // Monthly tables
    $monthly_site_stats = $pdo->query("
        SELECT DATE_FORMAT(date,'%Y-%m') AS month, SUM(tpage) AS tpage, SUM(tvisit) AS tvisit
          FROM page_view
         GROUP BY DATE_FORMAT(date,'%Y-%m')
         ORDER BY month DESC LIMIT 12
    ")->fetchAll();

    $monthly_paste_stats = $pdo->query("
        SELECT DATE_FORMAT(view_date,'%Y-%m') AS month, COUNT(*) AS total_views, COUNT(DISTINCT ip) AS unique_views
          FROM paste_views
         GROUP BY DATE_FORMAT(view_date,'%Y-%m')
         ORDER BY month DESC LIMIT 12
    ")->fetchAll();

    // Chart data (daily/monthly toggle)
    $view_type = (isset($_GET['view']) && $_GET['view'] === 'monthly') ? 'monthly' : 'daily';
    if ($view_type === 'monthly') {
        $chart_data = $pdo->query("
            SELECT DATE_FORMAT(date,'%Y-%m') AS label, SUM(tpage) AS tpage, SUM(tvisit) AS tvisit
              FROM page_view GROUP BY DATE_FORMAT(date,'%Y-%m') ORDER BY label DESC LIMIT 12
        ")->fetchAll();
        $paste_chart_data = $pdo->query("
            SELECT DATE_FORMAT(view_date,'%Y-%m') AS label, COUNT(*) AS total_views, COUNT(DISTINCT ip) AS unique_views
              FROM paste_views GROUP BY DATE_FORMAT(view_date,'%Y-%m') ORDER BY label DESC LIMIT 12
        ")->fetchAll();
    } else {
        $chart_data = $pdo->query("
            SELECT date AS label, SUM(tpage) AS tpage, SUM(tvisit) AS tvisit
              FROM page_view GROUP BY date ORDER BY date DESC LIMIT 30
        ")->fetchAll();
        $paste_chart_data = $pdo->query("
            SELECT view_date AS label, COUNT(*) AS total_views, COUNT(DISTINCT ip) AS unique_views
              FROM paste_views GROUP BY view_date ORDER BY view_date DESC LIMIT 30
        ")->fetchAll();
    }

    $chart_labels=[]; $chart_views=[]; $chart_unique=[]; $chart_paste_views=[]; $chart_paste_unique=[];
    foreach (array_reverse($chart_data) as $r) {
        $chart_labels[] = $r['label'];
        $chart_views[]  = (int)$r['tpage'];
        $chart_unique[] = (int)$r['tvisit'];
    }
    foreach (array_reverse($paste_chart_data) as $r) {
        $chart_paste_views[]  = (int)$r['total_views'];
        $chart_paste_unique[] = (int)$r['unique_views'];
    }

    // Aggregated table pagination
    $per_page = 20;
    $page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset   = ($page - 1) * $per_page;

    $total_months = (int)($pdo->query("SELECT COUNT(DISTINCT DATE_FORMAT(date,'%Y-%m')) t FROM page_view")->fetch()['t'] ?? 0);
    $total_days   = (int)($pdo->query("SELECT COUNT(DISTINCT date) t FROM page_view")->fetch()['t'] ?? 0);
    $total_views  = ($view_type==='monthly') ? $total_months : $total_days;
    $total_pages  = max(1, (int)ceil($total_views / $per_page));

    if ($view_type==='monthly') {
        $page_views = $pdo->query("
            SELECT DATE_FORMAT(date,'%Y-%m') AS label, SUM(tpage) AS tpage, SUM(tvisit) AS tvisit
              FROM page_view GROUP BY DATE_FORMAT(date,'%Y-%m')
             ORDER BY label DESC LIMIT $per_page OFFSET $offset
        ")->fetchAll();
        $paste_page_views = $pdo->query("
            SELECT DATE_FORMAT(view_date,'%Y-%m') AS label, COUNT(*) AS total_views, COUNT(DISTINCT ip) AS unique_views
              FROM paste_views GROUP BY DATE_FORMAT(view_date,'%Y-%m')
             ORDER BY label DESC LIMIT $per_page OFFSET $offset
        ")->fetchAll();
    } else {
        $page_views = $pdo->query("
            SELECT date AS label, SUM(tpage) AS tpage, SUM(tvisit) AS tvisit
              FROM page_view GROUP BY date
             ORDER BY date DESC LIMIT $per_page OFFSET $offset
        ")->fetchAll();
        $paste_page_views = $pdo->query("
            SELECT view_date AS label, COUNT(*) AS total_views, COUNT(DISTINCT ip) AS unique_views
              FROM paste_views GROUP BY view_date
             ORDER BY view_date DESC LIMIT $per_page OFFSET $offset
        ")->fetchAll();
    }

    // Per-paste stats (with sorting + pagination)
    $paste_per_page = 20;
    $paste_page     = isset($_GET['paste_page']) ? max(1, (int)$_GET['paste_page']) : 1;
    $paste_offset   = ($paste_page - 1) * $paste_per_page;
    $sort           = (isset($_GET['sort']) && in_array($_GET['sort'], ['views','unique'], true)) ? $_GET['sort'] : 'views';
    $sort_col       = $sort === 'views' ? 'total_views' : 'unique_views';

    $total_pastes_with_views = (int)($pdo->query("SELECT COUNT(DISTINCT paste_id) t FROM paste_views")->fetch()['t'] ?? 0);
    $total_paste_pages       = max(1, (int)ceil($total_pastes_with_views / $paste_per_page));

    $st = $pdo->prepare("
        SELECT pv.paste_id, p.title,
               COUNT(*) AS total_views,
               COUNT(DISTINCT pv.ip) AS unique_views
          FROM paste_views pv
          LEFT JOIN pastes p ON p.id = pv.paste_id
         GROUP BY pv.paste_id, p.title
         ORDER BY $sort_col DESC
         LIMIT :lim OFFSET :off
    ");
    $st->bindValue(':lim', $paste_per_page, PDO::PARAM_INT);
    $st->bindValue(':off', $paste_offset, PDO::PARAM_INT);
    $st->execute();
    $paste_stats = $st->fetchAll();

} catch (PDOException $e) {
    die("Unable to connect to database: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

// logout
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit();
}

// helper compact numbers
function fmt_n($n){ if ($n>=1000000) return number_format($n/1000000,1).'M'; if ($n>=1000) return number_format($n/1000,1).'K'; return (string)$n; }
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Paste - Statistics</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<style>
  :root{
    --bg:#0f1115; --card:#141821; --muted:#7f8da3; --border:#1f2633; --accent:#0d6efd;
  }
  body{background:var(--bg);color:#fff;}
  .navbar{background:#121826!important;position:sticky;top:0;z-index:1030}
  .navbar .navbar-brand{font-weight:600}

  /* Desktop sidebar (like pages.php) */
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
  .table{color:#e6edf3}
  .table thead th{background:#101521;color:#c6d4f0;border-color:var(--border)}
  .table td,.table th{border-color:var(--border)}
  .pagination .page-link{color:#c6d4f0;background:#101521;border-color:var(--border)}
  .pagination .page-item.active .page-link{background:#0d6efd;border-color:#0d6efd}
  .btn-soft{background:#101521;border:1px solid var(--border);color:#dbe5f5}
  .btn-soft:hover{background:#0e1422;color:#fff}

  .kpi .badge{font-size:1.05rem}
  .chart-wrap{background:#101521;border:1px solid var(--border);border-radius:12px;padding:10px}

  /* Offcanvas (mobile nav) */
  .offcanvas-nav{width:280px;background:#0f1523;color:#dbe5f5}
  .offcanvas-nav .list-group-item{background:transparent;border:0;color:#dbe5f5}
  .offcanvas-nav .list-group-item:hover{background:#0e1422}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <div class="d-flex align-items-center gap-2">
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
            <?php echo htmlspecialchars($_SESSION['admin_login'], ENT_QUOTES, 'UTF-8'); ?>
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
      <a class="list-group-item active" href="<?php echo htmlspecialchars($baseurl.'admin/stats.php'); ?>"><i class="bi bi-graph-up me-2"></i>Statistics</a>
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
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/pastes.php'); ?>"><i class="bi bi-clipboard me-2"></i>Pastes</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/users.php'); ?>"><i class="bi bi-people me-2"></i>Users</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/ipbans.php'); ?>"><i class="bi bi-ban me-2"></i>IP Bans</a>
          <a class="list-group-item active" href="<?php echo htmlspecialchars($baseurl.'admin/stats.php'); ?>"><i class="bi bi-graph-up me-2"></i>Statistics</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/ads.php'); ?>"><i class="bi bi-currency-pound me-2"></i>Ads</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/pages.php'); ?>"><i class="bi bi-file-earmark me-2"></i>Pages</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/sitemap.php'); ?>"><i class="bi bi-map me-2"></i>Sitemap</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/tasks.php'); ?>"><i class="bi bi-list-task me-2"></i>Tasks</a>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <div class="col-lg-10">
      <!-- KPIs -->
      <div class="row g-2">
        <div class="col-md-3 col-sm-6">
          <div class="card kpi text-center p-3">
            <i class="bi bi-clipboard-data fs-1 mb-1"></i>
            <div class="small text-secondary">Total Pastes</div>
            <div class="badge bg-primary"><?php echo fmt_n($total_pastes); ?></div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card kpi text-center p-3">
            <i class="bi bi-clock-history fs-1 mb-1"></i>
            <div class="small text-secondary">Expired Pastes</div>
            <div class="badge bg-warning text-dark"><?php echo fmt_n($exp_pastes); ?></div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card kpi text-center p-3">
            <i class="bi bi-people fs-1 mb-1"></i>
            <div class="small text-secondary">Total Users</div>
            <div class="badge bg-primary"><?php echo fmt_n($total_users); ?></div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card kpi text-center p-3">
            <i class="bi bi-person-x fs-1 mb-1"></i>
            <div class="small text-secondary">Banned Users</div>
            <div class="badge bg-danger"><?php echo fmt_n($total_ban); ?></div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card kpi text-center p-3">
            <i class="bi bi-person-check fs-1 mb-1"></i>
            <div class="small text-secondary">Unverified Users</div>
            <div class="badge bg-warning text-dark"><?php echo fmt_n($not_ver); ?></div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card kpi text-center p-3">
            <i class="bi bi-eye fs-1 mb-1"></i>
            <div class="small text-secondary">Total Site Views</div>
            <div class="badge bg-primary"><?php echo fmt_n($total_page); ?></div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card kpi text-center p-3">
            <i class="bi bi-person-lines-fill fs-1 mb-1"></i>
            <div class="small text-secondary">Site Unique Visitors</div>
            <div class="badge bg-primary"><?php echo fmt_n($total_un); ?></div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card kpi text-center p-3">
            <i class="bi bi-eye-fill fs-1 mb-1"></i>
            <div class="small text-secondary">Total Paste Views</div>
            <div class="badge bg-primary"><?php echo fmt_n($total_paste_views); ?></div>
          </div>
        </div>
      </div>

      <!-- Monthly summary -->
      <div class="card mt-3">
        <div class="card-body">
          <h4 class="card-title">Monthly Statistics</h4>
          <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
              <thead>
                <tr>
                  <th>Month</th>
                  <th>Site Views</th>
                  <th>Site Unique Visitors</th>
                  <th>Paste Views</th>
                  <th>Paste Unique Visitors</th>
                </tr>
              </thead>
              <tbody>
              <?php
                $monthly_stats = [];
                foreach ($monthly_site_stats as $s) {
                    $monthly_stats[$s['month']] = [
                        'site_views'=>(int)$s['tpage'],
                        'site_unique'=>(int)$s['tvisit'],
                        'paste_views'=>0,
                        'paste_unique'=>0
                    ];
                }
                foreach ($monthly_paste_stats as $p) {
                    if (!isset($monthly_stats[$p['month']])) {
                        $monthly_stats[$p['month']] = ['site_views'=>0,'site_unique'=>0,'paste_views'=>0,'paste_unique'=>0];
                    }
                    $monthly_stats[$p['month']]['paste_views']  = (int)$p['total_views'];
                    $monthly_stats[$p['month']]['paste_unique'] = (int)$p['unique_views'];
                }
                krsort($monthly_stats);
                if ($monthly_stats) {
                    foreach ($monthly_stats as $m=>$vals) {
                        echo '<tr>'.
                             '<td>'.htmlspecialchars($m).'</td>'.
                             '<td>'.number_format($vals['site_views']).'</td>'.
                             '<td>'.number_format($vals['site_unique']).'</td>'.
                             '<td>'.number_format($vals['paste_views']).'</td>'.
                             '<td>'.number_format($vals['paste_unique']).'</td>'.
                             '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5" class="text-center">No monthly statistics found</td></tr>';
                }
              ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Chart -->
      <div class="card mt-3">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <h4 class="card-title mb-0">Views Chart (<?php echo $view_type==='monthly'?'Monthly':'Daily'; ?>)</h4>
            <div class="d-flex align-items-center gap-2">
              <a href="?view=<?php echo $view_type==='monthly'?'daily':'monthly'; ?>&page=<?php echo $page; ?>&paste_page=<?php echo $paste_page; ?>&sort=<?php echo htmlspecialchars($sort, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-soft">
                Switch to <?php echo $view_type==='monthly'?'Daily':'Monthly'; ?> View
              </a>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="toggleSiteViews" checked>
                <label class="form-check-label" for="toggleSiteViews">Site Views</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="toggleSiteUnique" checked>
                <label class="form-check-label" for="toggleSiteUnique">Site Unique</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="togglePasteViews" checked>
                <label class="form-check-label" for="togglePasteViews">Paste Views</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="togglePasteUnique" checked>
                <label class="form-check-label" for="togglePasteUnique">Paste Unique</label>
              </div>
            </div>
          </div>
          <div class="chart-wrap">
            <canvas id="pageViewsChart" height="220"></canvas>
          </div>
        </div>
      </div>

      <!-- Aggregated table -->
      <div class="card mt-3">
        <div class="card-body">
          <h4 class="card-title"><?php echo $view_type==='monthly'?'Monthly':'Daily'; ?> Views Table</h4>
          <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
              <thead>
                <tr>
                  <th><?php echo $view_type==='monthly'?'Month':'Date'; ?></th>
                  <th>Site Views</th>
                  <th>Site Unique Visitors</th>
                  <th>Paste Views</th>
                  <th>Paste Unique Visitors</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $combined=[];
                foreach ($page_views as $r) {
                    $combined[$r['label']] = [
                        'site_views'=>(int)$r['tpage'],
                        'site_unique'=>(int)$r['tvisit'],
                        'paste_views'=>0,'paste_unique'=>0
                    ];
                }
                foreach ($paste_page_views as $r) {
                    if (!isset($combined[$r['label']])) {
                        $combined[$r['label']] = ['site_views'=>0,'site_unique'=>0,'paste_views'=>0,'paste_unique'=>0];
                    }
                    $combined[$r['label']]['paste_views']  = (int)$r['total_views'];
                    $combined[$r['label']]['paste_unique'] = (int)$r['unique_views'];
                }
                krsort($combined);
                if ($combined) {
                    foreach ($combined as $label=>$vals) {
                        echo '<tr>'.
                             '<td>'.htmlspecialchars($label).'</td>'.
                             '<td>'.number_format($vals['site_views']).'</td>'.
                             '<td>'.number_format($vals['site_unique']).'</td>'.
                             '<td>'.number_format($vals['paste_views']).'</td>'.
                             '<td>'.number_format($vals['paste_unique']).'</td>'.
                             '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5" class="text-center">No views found</td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>

          <nav aria-label="Page views navigation">
            <ul class="pagination justify-content-center">
              <?php
              $view_param = $view_type==='monthly' ? '&view=monthly' : '';
              if ($page>1) echo "<li class='page-item'><a class='page-link' href='?page=".($page-1)."$view_param&paste_page=$paste_page&sort=$sort'>&laquo;</a></li>";
              $start = max(1, $page-5); $end = min($total_pages, $page+5);
              if ($start>1) {
                  echo "<li class='page-item'><a class='page-link' href='?page=1$view_param&paste_page=$paste_page&sort=$sort'>1</a></li>";
                  if ($start>2) echo "<li class='page-item disabled'><span class='page-link'>…</span></li>";
              }
              for ($i=$start;$i<=$end;$i++) {
                  echo "<li class='page-item".($i==$page?' active':'')."'><a class='page-link' href='?page=$i$view_param&paste_page=$paste_page&sort=$sort'>$i</a></li>";
              }
              if ($end<$total_pages) {
                  if ($end<$total_pages-1) echo "<li class='page-item disabled'><span class='page-link'>…</span></li>";
                  echo "<li class='page-item'><a class='page-link' href='?page=$total_pages$view_param&paste_page=$paste_page&sort=$sort'>$total_pages</a></li>";
              }
              if ($page<$total_pages) echo "<li class='page-item'><a class='page-link' href='?page=".($page+1)."$view_param&paste_page=$paste_page&sort=$sort'>&raquo;</a></li>";
              ?>
            </ul>
          </nav>
        </div>
      </div>

      <!-- Per-paste -->
      <div class="card mt-3">
        <div class="card-body">
          <h4 class="card-title">Per-Paste Statistics</h4>
          <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
              <thead>
                <tr>
                  <th>Paste ID</th>
                  <th>Title</th>
                  <th><a href="?page=<?php echo $page; ?>&view=<?php echo $view_type; ?>&paste_page=<?php echo $paste_page; ?>&sort=views" class="<?php echo $sort==='views'?'text-primary':''; ?>">Total Views</a></th>
                  <th><a href="?page=<?php echo $page; ?>&view=<?php echo $view_type; ?>&paste_page=<?php echo $paste_page; ?>&sort=unique" class="<?php echo $sort==='unique'?'text-primary':''; ?>">Unique Visitors</a></th>
                </tr>
              </thead>
              <tbody>
                <?php
                if ($paste_stats) {
                    foreach ($paste_stats as $r) {
                        $title = $r['title'] ? htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') : 'Untitled';
                        echo '<tr>'.
                             '<td>'.(int)$r['paste_id'].'</td>'.
                             '<td>'.$title.'</td>'.
                             '<td>'.number_format((int)$r['total_views']).'</td>'.
                             '<td>'.number_format((int)$r['unique_views']).'</td>'.
                             '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4" class="text-center">No paste views found</td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>

          <nav aria-label="Paste views navigation">
            <ul class="pagination justify-content-center">
              <?php
              $view_param = $view_type==='monthly' ? '&view=monthly' : '';
              if ($paste_page>1) echo "<li class='page-item'><a class='page-link' href='?page=$page$view_param&paste_page=".($paste_page-1)."&sort=$sort'>&laquo;</a></li>";
              $pstart = max(1, $paste_page-5); $pend = min($total_paste_pages, $paste_page+5);
              if ($pstart>1) {
                  echo "<li class='page-item'><a class='page-link' href='?page=$page$view_param&paste_page=1&sort=$sort'>1</a></li>";
                  if ($pstart>2) echo "<li class='page-item disabled'><span class='page-link'>…</span></li>";
              }
              for ($i=$pstart;$i<=$pend;$i++) {
                  echo "<li class='page-item".($i==$paste_page?' active':'')."'><a class='page-link' href='?page=$page$view_param&paste_page=$i&sort=$sort'>$i</a></li>";
              }
              if ($pend<$total_paste_pages) {
                  if ($pend<$total_paste_pages-1) echo "<li class='page-item disabled'><span class='page-link'>…</span></li>";
                  echo "<li class='page-item'><a class='page-link' href='?page=$page$view_param&paste_page=$total_paste_pages&sort=$sort'>$total_paste_pages</a></li>";
              }
              if ($paste_page<$total_paste_pages) echo "<li class='page-item'><a class='page-link' href='?page=$page$view_param&paste_page=".($paste_page+1)."&sort=$sort'>&raquo;</a></li>";
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
document.addEventListener('DOMContentLoaded', function () {
  const labels = <?php echo json_encode($chart_labels); ?>;
  const siteViews   = <?php echo json_encode($chart_views); ?>;
  const siteUnique  = <?php echo json_encode($chart_unique); ?>;
  const pasteViews  = <?php echo json_encode($chart_paste_views); ?>;
  const pasteUnique = <?php echo json_encode($chart_paste_unique); ?>;

  const canvas = document.getElementById('pageViewsChart');
  const ctx = canvas.getContext('2d');

  const hexToRgba = (hex, a=1) => {
    const m = hex.replace('#',''); const n = parseInt(m,16);
    return `rgba(${(n>>16)&255},${(n>>8)&255},${n&255},${a})`;
  };
  const makeGrad = (hex, a1=.25, a2=.02) => {
    const g = ctx.createLinearGradient(0,0,0,canvas.height);
    g.addColorStop(0, hexToRgba(hex,a1)); g.addColorStop(1, hexToRgba(hex,a2)); return g;
  };
  const COLORS = { blue:'#0d6efd', green:'#198754', amber:'#ffc107', red:'#dc3545', grid:'#2b3344', tick:'#9fb1d1', text:'#e6edf3' };

  const crosshair = {
    id:'crosshair',
    afterDatasetsDraw(chart){
      const {ctx, tooltip, chartArea} = chart;
      if (!tooltip?._active || !tooltip._active.length) return;
      const {element} = tooltip._active[0];
      ctx.save(); ctx.strokeStyle='rgba(255,255,255,.15)'; ctx.setLineDash([4,4]); ctx.beginPath();
      ctx.moveTo(element.x, chartArea.top); ctx.lineTo(element.x, chartArea.bottom); ctx.stroke(); ctx.restore();
    }
  };

  const datasets = [
    { label:'Site Views', data:siteViews, borderColor:COLORS.blue,  backgroundColor:makeGrad(COLORS.blue),  pointRadius:2, pointHoverRadius:4, borderWidth:2, tension:.35, fill:true },
    { label:'Site Unique Visitors', data:siteUnique, borderColor:COLORS.green, backgroundColor:makeGrad(COLORS.green), pointRadius:2, pointHoverRadius:4, borderWidth:2, tension:.35, fill:true },
    { label:'Paste Views', data:pasteViews, borderColor:COLORS.amber, backgroundColor:makeGrad(COLORS.amber), pointRadius:2, pointHoverRadius:4, borderWidth:2, tension:.35, fill:true },
    { label:'Paste Unique Visitors', data:pasteUnique, borderColor:COLORS.red,   backgroundColor:makeGrad(COLORS.red),   pointRadius:2, pointHoverRadius:4, borderWidth:2, tension:.35, fill:true },
  ];

  const chart = new Chart(ctx, {
    type:'line',
    data:{ labels, datasets },
    options:{
      responsive:true,
      animation:{ duration:500, easing:'easeOutQuart' },
      interaction:{ mode:'index', intersect:false },
      scales:{
        y:{ beginAtZero:true, ticks:{ color:COLORS.tick, callback:v=> v>=1e6?(v/1e6).toFixed(1)+'M':v>=1e3?(v/1e3).toFixed(1)+'K':v }, grid:{ color:COLORS.grid } },
        x:{ ticks:{ color:COLORS.tick }, grid:{ color:'rgba(0,0,0,0)' } }
      },
      plugins:{
        legend:{ labels:{ color:COLORS.text, usePointStyle:true, boxWidth:10 }, position:'top' },
        tooltip:{ callbacks:{ label:(c)=>` ${c.dataset.label}: ${Number(c.parsed.y??0).toLocaleString()}` } }
      }
    },
    plugins:[crosshair]
  });

  // toggles
  const map = { toggleSiteViews:0, toggleSiteUnique:1, togglePasteViews:2, togglePasteUnique:3 };
  Object.keys(map).forEach(id=>{
    const idx = map[id]; const el = document.getElementById(id); if (!el) return;
    el.addEventListener('change', ()=>{ chart.setDatasetVisibility(idx, el.checked); chart.update(); });
  });
});
</script>
</body>
</html>
<?php $pdo = null; ?>
