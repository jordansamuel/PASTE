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

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$date = date('Y-m-d H:i:s'); // Use DATETIME format for database
require_once('../config.php');

// Check session and validate admin
if (!isset($_SESSION['admin_login']) || !isset($_SESSION['admin_id'])) {
    error_log("tasks.php: Session validation failed - admin_login or admin_id not set. Session: " . json_encode($_SESSION));
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_login'], $_SESSION['admin_id']);
    session_destroy();
    header("Location: ../index.php");
    exit();
}

try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpassword, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Fetch $baseurl from site_info
    $stmt = $pdo->query("SELECT baseurl FROM site_info WHERE id = 1");
    $row = $stmt->fetch();
    $baseurl = $row['baseurl'] ?? '';

    // Validate admin
    $stmt = $pdo->prepare("SELECT id, user FROM admin WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $row = $stmt->fetch();
    if (!$row || $row['user'] !== $_SESSION['admin_login']) {
        error_log("tasks.php: Admin validation failed - id: {$_SESSION['admin_id']}, user: {$_SESSION['admin_login']}, found: " . ($row ? json_encode($row) : 'null'));
        unset($_SESSION['admin_login'], $_SESSION['admin_id']);
        header("Location: ../index.php");
        exit();
    }

    // Log admin activity
    $stmt = $pdo->query("SELECT MAX(id) AS last_id FROM admin_history");
    $last_id = $stmt->fetch()['last_id'] ?? null;

    $last_date = null; $last_ip = null;
    if ($last_id) {
        $stmt = $pdo->prepare("SELECT last_date, ip FROM admin_history WHERE id = ?");
        $stmt->execute([$last_id]);
        $row = $stmt->fetch();
        $last_date = $row['last_date'] ?? null;
        $last_ip = $row['ip'] ?? null;
    }
    if ($last_ip !== $ip || $last_date !== $date) {
        $stmt = $pdo->prepare("INSERT INTO admin_history (last_date, ip) VALUES (?, ?)");
        $stmt->execute([$date, $ip]);
    }

    // Handle maintenance tasks
    $msg = '';
    $msg_type = 'info';

	if (isset($_GET['expired'])) {
		try {
			$pdo->beginTransaction();

			// Only fetch rows that could possibly expire (not NULL and not SELF)
			$stmt = $pdo->query("
				SELECT id, expiry
				  FROM pastes
				 WHERE expiry IS NOT NULL
				   AND expiry != 'SELF'
			");
			$pastes = $stmt->fetchAll(PDO::FETCH_ASSOC);

			$now = time();

			foreach ($pastes as $row) {
				$raw = isset($row['expiry']) ? trim((string)$row['expiry']) : '';
				$id  = (int)$row['id'];

				// Skip empties
				if ($raw === '') {
					continue;
				}

				// Parse expiry strictly; if parsing fails, DON'T delete
				$ts = strtotime($raw);
				if ($ts === false) {
					continue;
				}

				// Delete only if the parsed time is actually in the past
				if ($ts < $now) {
					$pdo->prepare("DELETE FROM paste_views WHERE paste_id = ?")->execute([$id]);
					$pdo->prepare("DELETE FROM pastes WHERE id = ?")->execute([$id]);
				}
			}

			$pdo->commit();
			$msg = 'All expired pastes and their view logs have been deleted.';
			$msg_type = 'success';
		} catch (PDOException $e) {
			$pdo->rollBack();
			$msg = 'Error deleting expired pastes: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
			$msg_type = 'danger';
		}
	}

    if (isset($_GET['all_pastes'])) {
        try {
            $pdo->beginTransaction();
            $pdo->query("DELETE FROM paste_views");
            $pdo->query("DELETE FROM pastes");
            $pdo->commit();
            $msg = 'All pastes and their view logs have been deleted.';
            $msg_type = 'success';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = 'Error deleting all pastes: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $msg_type = 'danger';
        }
    }

    if (isset($_GET['not_verified'])) {
        try {
            $pdo->beginTransaction();
            // delete views for pastes belonging to unverified users, then pastes, then users
            $stmt = $pdo->query("SELECT username FROM users WHERE verified = '0'");
            $usernames = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($usernames as $u) {
                $pdo->prepare("DELETE pv FROM paste_views pv INNER JOIN pastes p ON pv.paste_id=p.id WHERE p.member = ?")->execute([$u]);
                $pdo->prepare("DELETE FROM pastes WHERE member = ?")->execute([$u]);
            }
            $pdo->prepare("DELETE FROM users WHERE verified = '0'")->execute();
            $pdo->commit();
            $msg = 'All unverified accounts and their pastes have been deleted.';
            $msg_type = 'success';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = 'Error deleting unverified accounts: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $msg_type = 'danger';
        }
    }

    if (isset($_GET['admin_history'])) {
        try {
            $pdo->query("DELETE FROM admin_history");
            $msg = 'Admin history has been cleared.';
            $msg_type = 'success';
        } catch (PDOException $e) {
            $msg = 'Error clearing admin history: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $msg_type = 'danger';
        }
    }

    if (isset($_GET['clear_stats'])) {
        try {
            $pdo->beginTransaction();
            $pdo->query("DELETE FROM page_view");
            $pdo->query("DELETE FROM visitor_ips");
            $pdo->commit();
            $msg = 'Statistics and visitor IPs have been cleared.';
            $msg_type = 'success';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = 'Error clearing statistics and visitor IPs: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $msg_type = 'danger';
        }
    }

    if (isset($_GET['clear_view_logs'])) {
        try {
            $pdo->beginTransaction();
            $pdo->query("DELETE FROM paste_views");
            $pdo->query("DELETE FROM visitor_ips");
            $pdo->commit();
            $msg = 'Paste view logs and visitor IPs have been cleared.';
            $msg_type = 'success';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = 'Error clearing paste view logs and visitor IPs: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $msg_type = 'danger';
        }
    }

    if (isset($_GET['clear_ipbans'])) {
        try {
            $pdo->query("DELETE FROM ban_user");
            $msg = 'All IP bans have been cleared.';
            $msg_type = 'success';
        } catch (PDOException $e) {
            $msg = 'Error clearing IP bans: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $msg_type = 'danger';
        }
    }

    if (isset($_GET['clear_pages'])) {
        try {
            $pdo->query("DELETE FROM pages");
            $msg = 'All pages have been deleted.';
            $msg_type = 'success';
        } catch (PDOException $e) {
            $msg = 'Error deleting all pages: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $msg_type = 'danger';
        }
    }

    if (isset($_GET['delete_all_users'])) {
        try {
            $pdo->beginTransaction();
            $pdo->query("DELETE FROM paste_views");
            $pdo->query("DELETE FROM pastes");
            $pdo->query("DELETE FROM users");
            $pdo->commit();
            $msg = 'All users and their pastes have been deleted.';
            $msg_type = 'success';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = 'Error deleting users: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $msg_type = 'danger';
        }
    }

    if (isset($_GET['clear_mail_logs'])) {
        try {
            $pdo->prepare("UPDATE users SET verification_code = NULL, reset_code = NULL, reset_expiry = NULL WHERE verification_code IS NOT NULL OR reset_code IS NOT NULL")->execute();
            $msg = 'All mail logs have been cleared.';
            $msg_type = 'success';
        } catch (PDOException $e) {
            $msg = 'Error clearing mail logs: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $msg_type = 'danger';
        }
    }
} catch (PDOException $e) {
    die("Unable to connect to database: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Paste - Tasks</title>
<link rel="shortcut icon" href="favicon.ico">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
  :root{
    --bg:#0f1115; --card:#141821; --border:#1f2633; --accent:#0d6efd;
  }
  body{background:var(--bg);color:#e6edf3}
  .navbar{background:#121826!important;position:sticky;top:0;z-index:1030}
  .btn-soft{background:#101521;border:1px solid var(--border);color:#dbe5f5}
  .btn-soft:hover{background:#0e1422;color:#fff}
  .card{background:var(--card);border:1px solid var(--border);border-radius:12px}
  .table{color:#e6edf3}
  .table thead th{background:#101521;color:#c6d4f0;border-color:var(--border)}
  .table td,.table th{border-color:var(--border)}
  .sidebar-desktop{
    position:sticky; top:1rem; background:#121826;border:1px solid var(--border);
    border-radius:12px;padding:12px
  }
  .sidebar-desktop .list-group-item{
    background:transparent;color:#dbe5f5;border:0;border-radius:10px;padding:.65rem .8rem;
  }
  .sidebar-desktop .list-group-item:hover{background:#0e1422}
  .sidebar-desktop .list-group-item.active{background:#0d6efd;color:#fff}
  .offcanvas-nav{width:280px;background:#0f1523;color:#dbe5f5}
  .offcanvas-nav .list-group-item{background:transparent;border:0;color:#dbe5f5}
  .offcanvas-nav .list-group-item:hover{background:#0e1422}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const confirms = {
    'task-expired'          : 'Delete ALL expired pastes?',
    'task-all-pastes'       : 'Delete ALL pastes? This cannot be undone.',
    'task-not-verified'     : 'Delete ALL unverified accounts and their pastes?',
    'task-admin-history'    : 'Clear admin history?',
    'task-clear-stats'      : 'Clear ALL statistics and visitor IPs? This cannot be undone.',
    'task-clear-view-logs'  : 'Clear ALL paste view logs and visitor IPs? This cannot be undone.',
    'task-clear-ipbans'     : 'Clear ALL IP bans?',
    'task-clear-pages'      : 'Delete ALL pages? This cannot be undone.',
    'task-delete-all-users' : 'Delete ALL users and their pastes? This cannot be undone.',
    'task-clear-mail-logs'  : 'Clear ALL mail logs?'
  };
  document.addEventListener('click', function(e){
    const a = e.target.closest('a[class*="task-"]');
    if (!a) return;
    const cls = Object.keys(confirms).find(k => a.classList.contains(k));
    if (!cls) return;
    e.preventDefault();
    if (confirm(confirms[cls])) window.location.href = a.href;
  });
});
</script>
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
            <?php echo htmlspecialchars($_SESSION['admin_login'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
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
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/pages.php'); ?>"><i class="bi bi-file-earmark me-2"></i>Pages</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/sitemap.php'); ?>"><i class="bi bi-map me-2"></i>Sitemap</a>
      <a class="list-group-item active" href="<?php echo htmlspecialchars($baseurl.'admin/tasks.php'); ?>"><i class="bi bi-list-task me-2"></i>Tasks</a>
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
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/pages.php'); ?>"><i class="bi bi-file-earmark me-2"></i>Pages</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/sitemap.php'); ?>"><i class="bi bi-map me-2"></i>Sitemap</a>
          <a class="list-group-item active" href="<?php echo htmlspecialchars($baseurl.'admin/tasks.php'); ?>"><i class="bi bi-list-task me-2"></i>Tasks</a>
        </div>
      </div>
    </div>

    <div class="col-lg-10">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">Maintenance Tasks</h4>
          <?php if (!empty($msg)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($msg_type, ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
              <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
              <thead>
                <tr><th>Task</th><th>Action</th></tr>
              </thead>
              <tbody>
                <tr><td>Delete All Expired Pastes</td><td><a href="?expired" class="btn btn-secondary btn-sm task-expired">Run</a></td></tr>
                <tr><td>Delete All Pastes</td><td><a href="?all_pastes" class="btn btn-danger btn-sm task-all-pastes">Run</a></td></tr>
                <tr><td>Delete Unverified Accounts</td><td><a href="?not_verified" class="btn btn-warning btn-sm task-not-verified">Run</a></td></tr>
                <tr><td>Clear Admin History</td><td><a href="?admin_history" class="btn btn-info btn-sm task-admin-history">Run</a></td></tr>
                <tr><td>Clear Statistics and Visitor IPs</td><td><a href="?clear_stats" class="btn btn-info btn-sm task-clear-stats">Run</a></td></tr>
                <tr><td>Clear Paste View Logs and Visitor IPs</td><td><a href="?clear_view_logs" class="btn btn-info btn-sm task-clear-view-logs">Run</a></td></tr>
                <tr><td>Clear All IP Bans</td><td><a href="?clear_ipbans" class="btn btn-info btn-sm task-clear-ipbans">Run</a></td></tr>
                <tr><td>Delete All Pages</td><td><a href="?clear_pages" class="btn btn-danger btn-sm task-clear-pages">Run</a></td></tr>
                <tr><td>Delete All Users</td><td><a href="?delete_all_users" class="btn btn-danger btn-sm task-delete-all-users">Run</a></td></tr>
                <tr><td>Clear All Mail Logs</td><td><a href="?clear_mail_logs" class="btn btn-info btn-sm task-clear-mail-logs">Run</a></td></tr>
              </tbody>
            </table>
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
</body>
</html>
<?php $pdo = null; ?>
