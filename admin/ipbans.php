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

    // Base URL
    $baseurl = rtrim((string)($pdo->query("SELECT baseurl FROM site_info WHERE id=1")->fetch()['baseurl'] ?? ''), '/') . '/';
    if (!$baseurl) {
        throw new Exception('Base URL missing. Go to /admin/configuration.php');
    }

    // Log admin activity (lightweight)
    $last = $pdo->query("SELECT MAX(id) last_id FROM admin_history")->fetch();
    if ($last && $last['last_id']) {
        $st = $pdo->prepare("SELECT last_date, ip FROM admin_history WHERE id=?");
        $st->execute([$last['last_id']]);
        $row = $st->fetch();
        $last_date = $row['last_date'] ?? null;
        $last_ip   = $row['ip'] ?? null;
    }
    if (($last_ip ?? '') !== $ip || ($last_date ?? '') !== $date) {
        $pdo->prepare("INSERT INTO admin_history (last_date, ip) VALUES (?, ?)")->execute([$date, $ip]);
    }

} catch (Throwable $e) {
    die("Unable to connect to database: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

/* Actions */
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['banip'])) {
    $ban_ip = isset($_POST['ban_ip']) ? trim((string)$_POST['ban_ip']) : (isset($_GET['banip']) ? trim((string)$_GET['banip']) : '');
    if ($ban_ip === '') {
        $msg = '<div class="alert alert-danger text-center">Please enter an IP to ban.</div>';
    } else {
        try {
            // If already banned, just update last_date (keeps “last seen” fresh)
            $exists = $pdo->prepare("SELECT id FROM ban_user WHERE ip = ? LIMIT 1");
            $exists->execute([$ban_ip]);
            if ($row = $exists->fetch()) {
                $pdo->prepare("UPDATE ban_user SET last_date=? WHERE id=?")->execute([$date, (int)$row['id']]);
                $msg = '<div class="alert alert-warning text-center">'.htmlspecialchars($ban_ip).' is already banned — updated date.</div>';
            } else {
                // Insert including last_date to avoid NOT NULL errors
                $pdo->prepare("INSERT INTO ban_user (last_date, ip) VALUES (?, ?)")->execute([$date, $ban_ip]);
                $msg = '<div class="alert alert-success text-center">'.htmlspecialchars($ban_ip).' added to the banlist.</div>';
            }
        } catch (PDOException $e) {
            $msg = '<div class="alert alert-danger text-center">Error banning IP: '.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8').'</div>';
        }
    }
}

if (isset($_GET['delete'])) {
    $delete = (int)filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
    try {
        $pdo->prepare("DELETE FROM ban_user WHERE id = ?")->execute([$delete]);
        $msg = '<div class="alert alert-success text-center">IP removed from the banlist.</div>';
    } catch (PDOException $e) {
        $msg = '<div class="alert alert-danger text-center">Error removing IP: '.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8').'</div>';
    }
}

/* Pagination */
$per_page = 20;
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset   = ($page - 1) * $per_page;

$total_ips = (int)($pdo->query("SELECT COUNT(*) AS total FROM ban_user")->fetch()['total'] ?? 0);
$total_pages = max(1, (int)ceil($total_ips / $per_page));

$per_page_safe = (int)$per_page;
$offset_safe   = (int)$offset;

$st = $pdo->prepare("SELECT id, last_date, ip FROM ban_user ORDER BY id DESC LIMIT $per_page_safe OFFSET $offset_safe");
$st->execute();
$ips = $st->fetchAll();

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Paste - IP Bans</title>
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
  .form-control{background:#0e1422;border-color:var(--border);color:#e6edf3}
  .form-control:focus{border-color:var(--accent);box-shadow:0 0 0 .25rem rgba(13,110,253,.25)}
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
document.addEventListener('DOMContentLoaded', function(){
  // Delete confirm (no jQuery)
  document.querySelectorAll('.delete-ip').forEach(a => {
    a.addEventListener('click', (e) => {
      e.preventDefault();
      if (confirm('Delete this IP from the banlist?')) {
        window.location.href = a.getAttribute('href');
      }
    });
  });
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
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/pastes.php'); ?>"><i class="bi bi-clipboard me-2"></i>Pastes</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/users.php'); ?>"><i class="bi bi-people me-2"></i>Users</a>
      <a class="list-group-item active" href="<?php echo htmlspecialchars($baseurl.'admin/ipbans.php'); ?>"><i class="bi bi-ban me-2"></i>IP Bans</a>
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
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/pastes.php'); ?>"><i class="bi bi-clipboard me-2"></i>Pastes</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/users.php'); ?>"><i class="bi bi-people me-2"></i>Users</a>
          <a class="list-group-item active" href="<?php echo htmlspecialchars($baseurl.'admin/ipbans.php'); ?>"><i class="bi bi-ban me-2"></i>IP Bans</a>
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

      <div class="card mb-3">
        <div class="card-body">
          <h4 class="card-title">Ban an IP</h4>
          <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-2">
            <div class="col-sm-8 col-md-9">
              <input type="text" class="form-control" name="ban_ip" placeholder="Enter an IP address">
            </div>
            <div class="col-sm-4 col-md-3 d-grid">
              <button class="btn btn-primary" type="submit">Add</button>
            </div>
            <input type="hidden" name="banip" value="banip">
          </form>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <h4 class="card-title">Banlist</h4>
          <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
              <thead>
                <tr>
                  <th>Date Added</th>
                  <th>IP</th>
                  <th style="width:120px">Delete</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($ips)): ?>
                  <?php foreach ($ips as $r): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($r['last_date']); ?></td>
                      <td><span class="badge bg-primary"><?php echo htmlspecialchars($r['ip']); ?></span></td>
                      <td><a href="?delete=<?php echo (int)$r['id']; ?>&page=<?php echo (int)$page; ?>" class="btn btn-danger btn-sm delete-ip">Delete</a></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="3" class="text-center">No IPs found</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
              <?php
              if ($page > 1) {
                  echo '<li class="page-item"><a class="page-link" href="?page='.($page-1).'">&laquo;</a></li>';
              } else {
                  echo '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
              }
              $start = max(1, $page-3); $end = min($total_pages, $page+3);
              for ($i=$start; $i<=$end; $i++){
                  echo '<li class="page-item'.($i==$page?' active':'').'"><a class="page-link" href="?page='.$i.'">'.$i.'</a></li>';
              }
              if ($page < $total_pages) {
                  echo '<li class="page-item"><a class="page-link" href="?page='.($page+1).'">&raquo;</a></li>';
              } else {
                  echo '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
              }
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
<?php
// logout handler
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
