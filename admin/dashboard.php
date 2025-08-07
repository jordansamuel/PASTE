<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
session_start();
require_once('../config.php'); // Moved to top to ensure DB variables are defined

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
        unset($_SESSION['admin_login']);
        unset($_SESSION['admin_id']);
        header("Location: " . htmlspecialchars($baseurl . 'admin/index.php', ENT_QUOTES, 'UTF-8'));
        exit();
    }
} catch (PDOException $e) {
    error_log("dashboard.php: Database connection failed: " . $e->getMessage());
    die("Unable to connect to database: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_login']);
    unset($_SESSION['admin_id']);
    session_destroy();
    header("Location: " . htmlspecialchars($baseurl . 'admin/index.php', ENT_QUOTES, 'UTF-8'));
    exit();
}

$date = date('Y-m-d H:i:s'); // Use DATETIME format for database
$ip = $_SERVER['REMOTE_ADDR'];
require_once('../includes/functions.php');

// Log admin activity
$last_ip = null;
$last_date = null;
$stmt = $pdo->query("SELECT MAX(id) AS last_id FROM admin_history");
$last_id = $stmt->fetch(PDO::FETCH_ASSOC)['last_id'] ?? null;

if ($last_id) {
    $stmt = $pdo->prepare("SELECT last_date, ip FROM admin_history WHERE id = ?");
    $stmt->execute([$last_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $last_date = $row['last_date'];
        $last_ip = $row['ip'];
    }
}

if ($last_ip !== $ip || $last_date !== $date) {
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_history (last_date, ip) VALUES (?, ?)");
        $stmt->execute([$date, $ip]);
    } catch (PDOException $e) {
        error_log("dashboard.php: Failed to log admin activity: " . $e->getMessage());
    }
}

// Fetch page view statistics
$stmt = $pdo->query("SELECT SUM(tpage) AS total_page, SUM(tvisit) AS total_visit FROM page_view");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_page = $row['total_page'] ?? 0;
$total_visit = $row['total_visit'] ?? 0;

$stmt = $pdo->query("SELECT MAX(id) AS last_id FROM page_view");
$page_last_id = $stmt->fetch(PDO::FETCH_ASSOC)['last_id'] ?? null;

$today_page = 0;
$today_visit = 0;
if ($page_last_id) {
    $stmt = $pdo->prepare("SELECT tpage, tvisit FROM page_view WHERE id = ?");
    $stmt->execute([$page_last_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $today_page = $row['tpage'] ?? 0;
    $today_visit = $row['tvisit'] ?? 0;
}

// Count today's users
$c_date = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(id) AS count FROM users WHERE DATE(date) = ?");
$stmt->execute([$c_date]);
$today_users_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Count today's pastes
$stmt = $pdo->prepare("SELECT COUNT(id) AS count FROM pastes WHERE s_date = ?");
$stmt->execute([$c_date]);
$today_pastes_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Fetch recent page views
$ldate = [];
$tpage = [];
$tvisit = [];
for ($loop = 0; $loop <= 6; $loop++) {
    $myid = $page_last_id - $loop;
    $stmt = $pdo->prepare("SELECT date, tpage, tvisit FROM page_view WHERE id = ?");
    $stmt->execute([$myid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $sdate = $row['date'];
        $sdate = str_replace(date('Y'), '', $sdate);
        $sdate = str_replace('January', 'Jan', $sdate);
        $sdate = str_replace('February', 'Feb', $sdate);
        $sdate = str_replace('March', 'Mar', $sdate);
        $sdate = str_replace('April', 'Apr', $sdate);
        $sdate = str_replace('August', 'Aug', $sdate);
        $sdate = str_replace('September', 'Sep', $sdate);
        $sdate = str_replace('October', 'Oct', $sdate);
        $sdate = str_replace('November', 'Nov', $sdate);
        $sdate = str_replace('December', 'Dec', $sdate);
        $ldate[$loop] = $sdate;
        $tpage[$loop] = $row['tpage'];
        $tvisit[$loop] = $row['tvisit'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Dashboard</title>
    <link rel="shortcut icon" href="favicon.ico">
    <link href="css/paste.css" rel="stylesheet" type="text/css" />
  </head>
  <body>
  
    <div id="top" class="clearfix">
        <div class="applogo">
          <a href="../" class="logo">Paste</a>
        </div>
        <ul class="top-right">
            <li class="dropdown link">
                <a href="#" data-toggle="dropdown" class="dropdown-toggle profilebox"><b><?php echo htmlspecialchars($_SESSION['admin_login']); ?></b><span class="caret"></span></a>
                <ul class="dropdown-menu dropdown-menu-list dropdown-menu-right">
                  <li><a href="admin.php">Settings</a></li>
                  <li><a href="?logout">Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>

    <div class="content">
        <div class="container-widget">
            <div class="row">
                <div class="col-md-12">
                    <ul class="panel quick-menu clearfix">
                        <li class="col-xs-3 col-sm-2 col-md-1 menu-active">
                            <a href="<?php echo htmlspecialchars($baseurl . 'admin/dashboard.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-house"></i>Dashboard</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="<?php echo htmlspecialchars($baseurl . 'admin/configuration.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-gear"></i>Configuration</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="<?php echo htmlspecialchars($baseurl . 'admin/interface.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-eye"></i>Interface</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="<?php echo htmlspecialchars($baseurl . 'admin/admin.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-person"></i>Admin Account</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="<?php echo htmlspecialchars($baseurl . 'admin/pastes.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-clipboard"></i>Pastes</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="<?php echo htmlspecialchars($baseurl . 'admin/users.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-people"></i>Users</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="<?php echo htmlspecialchars($baseurl . 'admin/ipbans.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-ban"></i>IP Bans</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="<?php echo htmlspecialchars($baseurl . 'admin/stats.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-graph-up"></i>Statistics</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="<?php echo htmlspecialchars($baseurl . 'admin/ads.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-currency-pound"></i>Ads</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="<?php echo htmlspecialchars($baseurl . 'admin/pages.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-file-earmark"></i>Pages</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="<?php echo htmlspecialchars($baseurl . 'admin/sitemap.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-map"></i>Sitemap</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="<?php echo htmlspecialchars($baseurl . 'admin/tasks.php', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-list-task"></i>Tasks</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-widget">
                        <div class="panel-body">
                            <h4>Overview</h4>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="panel panel-default">
                                        <div class="panel-body text-center">
                                            <h6><i class="bi bi-eye"></i> Views</h6>
                                            <p><span class="badge"><?php echo htmlspecialchars($today_page, ENT_QUOTES, 'UTF-8'); ?></span></p>
                                            <small>Today</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="panel panel-default">
                                        <div class="panel-body text-center">
                                            <h6><i class="bi bi-clipboard"></i> Pastes</h6>
                                            <p><span class="badge"><?php echo htmlspecialchars($today_pastes_count, ENT_QUOTES, 'UTF-8'); ?></span></p>
                                            <small>Today</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="panel panel-default">
                                        <div class="panel-body text-center">
                                            <h6><i class="bi bi-people"></i> Users</h6>
                                            <p><span class="badge"><?php echo htmlspecialchars($today_users_count, ENT_QUOTES, 'UTF-8'); ?></span></p>
                                            <small>Today</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="panel panel-default">
                                        <div class="panel-body text-center">
                                            <h6><i class="bi bi-person-lines-fill"></i> Unique Views</h6>
                                            <p><span class="badge"><?php echo htmlspecialchars($today_visit, ENT_QUOTES, 'UTF-8'); ?></span></p>
                                            <small>Today</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <div class="panel panel-widget">
                        <div class="panel-body">
                            <h4>Recent Pastes</h4>
                            <table class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Date</th>
                                        <th>IP</th>
                                        <th>Views</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT id, title, member, s_date, ip, views, now_time FROM pastes ORDER BY now_time DESC LIMIT 7");
                                    $stmt->execute();
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $title = trim($row['title']);
                                        $p_id = trim($row['id']);
                                        $p_date = trim($row['s_date']);
                                        $p_ip = trim($row['ip']);
                                        $p_member = trim($row['member']);
                                        $p_view = trim($row['views']);
                                        $p_time = trim($row['now_time']);
                                        $nowtime = time();
                                        $p_time = conTime($nowtime - $p_time);
                                        $title = truncate($title, 5, 30);
                                        echo "
                                            <tr>
                                                <td>" . htmlspecialchars($p_id, ENT_QUOTES, 'UTF-8') . "</td>
                                                <td>" . htmlspecialchars($p_member, ENT_QUOTES, 'UTF-8') . "</td>
                                                <td>" . htmlspecialchars($p_date, ENT_QUOTES, 'UTF-8') . "</td>
                                                <td><span class='badge'>" . htmlspecialchars($p_ip, ENT_QUOTES, 'UTF-8') . "</span></td>
                                                <td>" . htmlspecialchars($p_view, ENT_QUOTES, 'UTF-8') . "</td>
                                            </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 col-lg-6">
                    <div class="panel panel-widget">
                        <div class="panel-body">
                            <h4>Recent Users</h4>
                            <table class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Date</th>
                                        <th>IP</th>
                                    </tr>
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
                                                $u_date = $row['date'];
                                                $ip = htmlspecialchars($row['ip'], ENT_QUOTES, 'UTF-8');
                                                $username = htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');
                                                echo "
                                                    <tr>
                                                        <td>" . htmlspecialchars($r_my_id, ENT_QUOTES, 'UTF-8') . "</td>
                                                        <td>$username</td>
                                                        <td>" . htmlspecialchars($u_date, ENT_QUOTES, 'UTF-8') . "</td>
                                                        <td><span class='badge'>$ip</span></td>
                                                    </tr>";
                                            }
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <div class="panel panel-widget">
                        <div class="panel-body">
                            <h4>Admin History</h4>
                            <table class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Last Login Date</th>
                                        <th>IP</th>
                                    </tr>
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
                                                $last_date = $row['last_date'];
                                                $ip = htmlspecialchars($row['ip'], ENT_QUOTES, 'UTF-8');
                                                echo "
                                                    <tr>
                                                        <td>" . htmlspecialchars($c_my_id, ENT_QUOTES, 'UTF-8') . "</td>
                                                        <td>" . htmlspecialchars($last_date, ENT_QUOTES, 'UTF-8') . "</td>
                                                        <td><span class='badge'>$ip</span></td>
                                                    </tr>";
                                            }
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 col-lg-6">
                    <div class="panel panel-widget">
                        <div class="panel-body">
                            <h4>Version Information</h4>
                            <p>
                                <?php
                                $latestversion = @file_get_contents('https://raw.githubusercontent.com/boxlabss/PASTE/releases/version');
                                echo "Latest version: " . htmlspecialchars($latestversion !== false ? $latestversion : 'Unknown', ENT_QUOTES, 'UTF-8') . "&mdash; Installed version: " . htmlspecialchars($currentversion ?? 'Unknown', ENT_QUOTES, 'UTF-8');
                                if ($currentversion && $latestversion && $currentversion == $latestversion) {
                                    echo '<br>You have the latest version';
                                } else {
                                    echo '<br>Your Paste installation is outdated. Get the latest version from <a href="https://sourceforge.net/projects/phpaste/files/latest/download">SourceForge</a>';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row footer">
            <div class="col-md-6 text-left">
                <a href="https://github.com/jordansamuel/PASTE" target="_blank">Updates</a> &mdash; <a href="https://github.com/jordansamuel/PASTE/issues" target="_blank">Bugs</a>
            </div>
            <div class="col-md-6 text-right">
                Powered by <a href="https://phpaste.sourceforge.io" target="_blank">Paste</a>
            </div>
        </div>
    </div>

<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/bootstrap-select.js"></script>
</body>
</html>
<?php $pdo = null; ?>