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
require_once('../config.php');

// Fetch $baseurl from site_info
try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT baseurl FROM site_info WHERE id = 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $baseurl = $row['baseurl'] ?? '';
} catch (PDOException $e) {
    error_log("dashboard.php: Failed to fetch baseurl: " . $e->getMessage());
    die("Unable to fetch site configuration: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

if (!isset($_SESSION['admin_login']) || !isset($_SESSION['admin_id'])) {
    error_log("dashboard.php: Session validation failed - admin_login or admin_id not set. Session: " . json_encode($_SESSION));
    header("Location: " . htmlspecialchars($baseurl . 'admin/index.php', ENT_QUOTES, 'UTF-8'));
    exit();
}

try {
    // Validate admin
    $stmt = $pdo->prepare("SELECT id, user FROM admin WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || $row['user'] !== $_SESSION['admin_login']) {
        error_log("dashboard.php: Admin validation failed - id: {$_SESSION['admin_id']}, user: {$_SESSION['admin_login']}, found: " . ($row ? json_encode($row) : 'null'));
        unset($_SESSION['admin_login'], $_SESSION['admin_id']);
        header("Location: " . htmlspecialchars($baseurl . 'admin/index.php', ENT_QUOTES, 'UTF-8'));
        exit();
    }
} catch (PDOException $e) {
    error_log("dashboard.php: Database connection failed: " . $e->getMessage());
    die("Unable to connect to database: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_login'], $_SESSION['admin_id']);
    session_destroy();
    header("Location: " . htmlspecialchars($baseurl . 'admin/index.php', ENT_QUOTES, 'UTF-8'));
    exit();
}

$date = date('Y-m-d H:i:s');
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
require_once('../includes/functions.php');

// Log admin activity
$last_ip = null; $last_date = null;
$stmt = $pdo->query("SELECT MAX(id) AS last_id FROM admin_history");
$last_id = $stmt->fetch(PDO::FETCH_ASSOC)['last_id'] ?? null;

if ($last_id) {
    $stmt = $pdo->prepare("SELECT last_date, ip FROM admin_history WHERE id = ?");
    $stmt->execute([$last_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) { $last_date = $row['last_date'] ?? ''; $last_ip = $row['ip'] ?? ''; }
}
if ($last_ip !== $ip || $last_date !== $date) {
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_history (last_date, ip) VALUES (?, ?)");
        $stmt->execute([$date, $ip]);
    } catch (PDOException $e) { error_log("dashboard.php: Failed to log admin activity: " . $e->getMessage()); }
}

// Stats
$stmt = $pdo->query("SELECT SUM(tpage) AS total_page, SUM(tvisit) AS total_visit FROM page_view");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_page = (int) ($row['total_page'] ?? 0);
$total_visit = (int) ($row['total_visit'] ?? 0);

$stmt = $pdo->query("SELECT MAX(id) AS last_id FROM page_view");
$page_last_id = $stmt->fetch(PDO::FETCH_ASSOC)['last_id'] ?? null;

$today_page = 0; $today_visit = 0;
if ($page_last_id) {
    $stmt = $pdo->prepare("SELECT tpage, tvisit FROM page_view WHERE id = ?");
    $stmt->execute([$page_last_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $today_page = (int) ($row['tpage'] ?? 0);
    $today_visit = (int) ($row['tvisit'] ?? 0);
}

// Count today's users & pastes
$c_date = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(id) AS count FROM users WHERE DATE(date) = ?");
$stmt->execute([$c_date]);
$today_users_count = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

$stmt = $pdo->prepare("SELECT COUNT(id) AS count FROM pastes WHERE s_date = ?");
$stmt->execute([$c_date]);
$today_pastes_count = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

// Recent past 7 page_view rows (labels)
$ldate = []; $tpage = []; $tvisit = [];
for ($loop = 0; $loop <= 6; $loop++) {
    $myid = $page_last_id - $loop;
    $stmt = $pdo->prepare("SELECT date, tpage, tvisit FROM page_view WHERE id = ?");
    $stmt->execute([$myid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $sdate = $row['date'];
        $sdate = str_replace(date('Y'), '', $sdate);
        $sdate = str_replace(['January','February','March','April','August','September','October','November','December'],
                             ['Jan','Feb','Mar','Apr','Aug','Sep','Oct','Nov','Dec'], $sdate);
        $ldate[$loop] = $sdate;
        $tpage[$loop] = (int) ($row['tpage'] ?? 0);
        $tvisit[$loop] = (int) ($row['tvisit'] ?? 0);
    }
}

// Mail logs (last 10)
$stmt = $pdo->prepare("
  SELECT
    ml.id,
    ml.email,
    ml.sent_at,
    ml.type,             -- 'verification' | 'reset' | 'test'
    u.username
  FROM mail_log ml
  LEFT JOIN users u ON u.email_id = ml.email
  ORDER BY ml.sent_at DESC
  LIMIT 10
");
$stmt->execute();
$mail_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Paste - Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
  :root{
    --bg:#0f1115; --card:#141821; --muted:#7f8da3; --border:#1f2633; --accent:#0d6efd;
  }
  body{background:var(--bg);color:#e6edf3;}
  .navbar{background:#121826!important;position:sticky;top:0;z-index:1030}
  .navbar .navbar-brand{font-weight:600}
  .sidebar-desktop{position:sticky; top:1rem; background:#121826;border:1px solid var(--border);border-radius:12px;padding:12px}
  .sidebar-desktop .list-group-item{background:transparent;color:#dbe5f5;border:0;border-radius:10px;padding:.65rem .8rem}
  .sidebar-desktop .list-group-item:hover{background:#0e1422}
  .sidebar-desktop .list-group-item.active{background:#0d6efd;color:#fff}
  .card{background:var(--card);border:1px solid var(--border);border-radius:12px}
  .table{color:#e6edf3}
  .table thead th{background:#101521;color:#c6d4f0;border-color:var(--border)}
  .table td,.table th{border-color:var(--border)}
  .badge{background:#0d6efd}
  .btn-soft{background:#101521;border:1px solid var(--border);color:#dbe5f5}
  .btn-soft:hover{background:#0e1422;color:#fff}
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
      <a class="list-group-item active" href="<?php echo htmlspecialchars($baseurl.'admin/dashboard.php'); ?>"><i class="bi bi-house me-2"></i>Dashboard</a>
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
          <a class="list-group-item active" href="<?php echo htmlspecialchars($baseurl.'admin/dashboard.php'); ?>"><i class="bi bi-house me-2"></i>Dashboard</a>
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
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/tasks.php'); ?>"><i class="bi bi-list-task me-2"></i>Tasks</a>
        </div>
      </div>
    </div>

    <div class="col-lg-10">
      <!-- Overview -->
      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h4 class="card-title mb-0">Overview</h4>
            <a class="btn btn-soft btn-sm" href="<?php echo htmlspecialchars($baseurl.'admin/stats.php'); ?>"><i class="bi bi-graph-up"></i> View stats</a>
          </div>
          <div class="row g-2">
            <div class="col-md-3">
              <div class="card h-100 text-center">
                <div class="card-body">
                  <div class="small text-secondary">Views (today)</div>
                  <div class="display-6"><?php echo htmlspecialchars($today_page, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card h-100 text-center">
                <div class="card-body">
                  <div class="small text-secondary">Pastes (today)</div>
                  <div class="display-6"><?php echo htmlspecialchars($today_pastes_count, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card h-100 text-center">
                <div class="card-body">
                  <div class="small text-secondary">Users (today)</div>
                  <div class="display-6"><?php echo htmlspecialchars($today_users_count, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card h-100 text-center">
                <div class="card-body">
                  <div class="small text-secondary">Unique Views (today)</div>
                  <div class="display-6"><?php echo htmlspecialchars($today_visit, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Pastes & Recent Users -->
      <div class="row g-2">
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-body">
              <h4 class="card-title">Recent Pastes</h4>
              <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                  <thead>
                    <tr>
                      <th>ID</th><th>Username</th><th>Date</th><th>IP</th><th>Views</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT p.id, p.title, p.member, p.s_date, p.ip, 
                               COALESCE(COUNT(pv.id), 0) AS views, 
                               UNIX_TIMESTAMP(p.date) AS now_time
                        FROM pastes p
                        LEFT JOIN paste_views pv ON p.id = pv.paste_id
                        GROUP BY p.id, p.title, p.member, p.s_date, p.ip, p.date
                        ORDER BY now_time DESC LIMIT 7
                    ");
                    $stmt->execute();
                    if ($stmt->rowCount() > 0) {
                      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $p_id = trim($row['id'] ?? '');
                        $p_member = trim($row['member'] ?? 'Guest');
                        $p_date = trim($row['s_date'] ?? '');
                        $p_ip = trim($row['ip'] ?? '');
                        $p_view = (int) ($row['views'] ?? 0);
                        echo "<tr>
                                <td>".htmlspecialchars($p_id, ENT_QUOTES, 'UTF-8')."</td>
                                <td>".htmlspecialchars($p_member, ENT_QUOTES, 'UTF-8')."</td>
                                <td>".htmlspecialchars($p_date, ENT_QUOTES, 'UTF-8')."</td>
                                <td><span class='badge'>".htmlspecialchars($p_ip, ENT_QUOTES, 'UTF-8')."</span></td>
                                <td>".htmlspecialchars($p_view, ENT_QUOTES, 'UTF-8')."</td>
                              </tr>";
                      }
                    } else {
                      echo "<tr><td colspan='5' class='text-center'>No recent pastes found.</td></tr>";
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-body">
              <h4 class="card-title">Recent Users</h4>
              <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                  <thead>
                    <tr><th>ID</th><th>Username</th><th>Date</th><th>IP</th></tr>
                  </thead>
                  <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT MAX(id) AS last_id FROM users");
                    $last_id = $stmt->fetch(PDO::FETCH_ASSOC)['last_id'] ?? null;
                    if ($last_id) {
                      for ($uloop = 0; $uloop <= 6; $uloop++) {
                        $r_my_id = $last_id - $uloop;
                        $stmt = $pdo->prepare("SELECT username, date, ip FROM users WHERE id = ?");
                        $stmt->execute([$r_my_id]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($row) {
                          $u_date = $row['date'] ?? '';
                          $ip = htmlspecialchars($row['ip'] ?? '', ENT_QUOTES, 'UTF-8');
                          $username = htmlspecialchars($row['username'] ?? '', ENT_QUOTES, 'UTF-8');
                          echo "<tr>
                                  <td>".htmlspecialchars($r_my_id, ENT_QUOTES, 'UTF-8')."</td>
                                  <td>$username</td>
                                  <td>".htmlspecialchars($u_date, ENT_QUOTES, 'UTF-8')."</td>
                                  <td><span class='badge'>$ip</span></td>
                                </tr>";
                        }
                      }
                    } else {
                      echo "<tr><td colspan='4' class='text-center'>No recent users found.</td></tr>";
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Admin history & Version info -->
      <div class="row g-2 mt-0">
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-body">
              <h4 class="card-title">Admin History</h4>
              <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                  <thead>
                    <tr><th>ID</th><th>Last Login Date</th><th>IP</th></tr>
                  </thead>
                  <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT MAX(id) AS last_id FROM admin_history");
                    $last_id = $stmt->fetch(PDO::FETCH_ASSOC)['last_id'] ?? null;

                    if ($last_id) {
                      for ($cloop = 0; $cloop <= 6; $cloop++) {
                        $c_my_id = $last_id - $cloop;
                        $stmt = $pdo->prepare("SELECT last_date, ip FROM admin_history WHERE id = ?");
                        $stmt->execute([$c_my_id]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($row) {
                          $last_date = $row['last_date'] ?? '';
                          $ip = htmlspecialchars($row['ip'] ?? '', ENT_QUOTES, 'UTF-8');
                          echo "<tr>
                                  <td>".htmlspecialchars($c_my_id, ENT_QUOTES, 'UTF-8')."</td>
                                  <td>".htmlspecialchars($last_date, ENT_QUOTES, 'UTF-8')."</td>
                                  <td><span class='badge'>$ip</span></td>
                                </tr>";
                        }
                      }
                    } else {
                      echo "<tr><td colspan='3' class='text-center'>No admin history found.</td></tr>";
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-body">
              <h4 class="card-title">Version Information</h4>
              <p class="mb-1">
                <?php
                $latestversion = @file_get_contents('https://raw.githubusercontent.com/boxlabss/PASTE/releases/version');
                echo "Latest version: " . htmlspecialchars($latestversion !== false ? $latestversion : 'Unknown', ENT_QUOTES, 'UTF-8') . 
                     " &mdash; Installed version: " . htmlspecialchars($currentversion ?? 'Unknown', ENT_QUOTES, 'UTF-8');
                ?>
              </p>
              <div class="small text-secondary">
                <?php
                if (!empty($currentversion) && !empty($latestversion) && $currentversion == $latestversion) {
                    echo 'You have the latest version.';
                } else {
                    echo 'Your Paste installation is outdated. Get the latest version from 
                          <a class="link-primary" href="https://sourceforge.net/projects/phpaste/files/latest/download" target="_blank" rel="noopener">SourceForge</a>.';
                }
                ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Mail logs -->
      <div class="card mt-3">
        <div class="card-body">
          <h4 class="card-title">Recent Mail Logs</h4>
          <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
				<thead>
				  <tr><th>ID</th><th>Username</th><th>Email</th><th>Type</th><th>Sent</th></tr>
				</thead>
				<tbody>
				<?php if ($mail_logs): foreach ($mail_logs as $row): ?>
				  <tr>
					<td><?= htmlspecialchars($row['id']) ?></td>
					<td><?= htmlspecialchars($row['username'] ?? '') ?></td>
					<td><?= htmlspecialchars($row['email']) ?></td>
					<td>
					  <?php
						$icon = match ($row['type']) {
						  'verification' => '<i class="bi bi-envelope-check me-2"></i>',
						  'reset'        => '<i class="bi bi-key me-2"></i>',
						  default        => '<i class="bi bi-envelope me-2"></i>',
						};
						echo $icon . htmlspecialchars(ucfirst($row['type']));
					  ?>
					</td>
					<td><?= htmlspecialchars($row['sent_at']) ?></td>
				  </tr>
				<?php endforeach; else: ?>
				  <tr><td colspan="5" class="text-center">No recent mail logs found.</td></tr>
				<?php endif; ?>
				</tbody>
            </table>
          </div>
          <div class="text-muted small mt-2">
            <a class="text-decoration-none" href="https://github.com/jordansamuel/PASTE" target="_blank" rel="noopener">Updates</a>
            &mdash;
            <a class="text-decoration-none" href="https://github.com/jordansamuel/PASTE/issues" target="_blank" rel="noopener">Bugs</a>
            <span class="float-end">Powered by <a class="text-decoration-none" href="https://phpaste.sourceforge.io" target="_blank" rel="noopener">Paste</a></span>
          </div>
        </div>
      </div>

    </div><!-- /col-lg-10 -->
  </div><!-- /row -->
</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
<?php $pdo = null; ?>
