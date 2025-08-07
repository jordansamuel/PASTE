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

// Check session and validate admin
if (!isset($_SESSION['admin_login']) || !isset($_SESSION['admin_id'])) {
    error_log("admin.php: Session validation failed - admin_login or admin_id not set. Session: " . json_encode($_SESSION));
    header("Location: ../index.php");
    exit();
}

$date = date('Y-m-d H:i:s'); // Use DATETIME format for database
$ip = $_SERVER['REMOTE_ADDR'];
require_once('../config.php');

try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Log admin login if not already logged for this date and IP
    $stmt = $pdo->query("SELECT MAX(id) AS last_id FROM admin_history");
    $last_id = $stmt->fetch(PDO::FETCH_ASSOC)['last_id'] ?? null;

    if ($last_id) {
        $stmt = $pdo->prepare("SELECT last_date, ip FROM admin_history WHERE id = ?");
        $stmt->execute([$last_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $last_date = $row['last_date'] ?? null;
        $last_ip = $row['ip'] ?? null;
    }

    if ($last_ip !== $ip || $last_date !== $date) {
        $stmt = $pdo->prepare("INSERT INTO admin_history (last_date, ip) VALUES (?, ?)");
        $stmt->execute([$date, $ip]);
    }

    // Handle maintenance tasks
    $msg = '';
    if (isset($_GET['expired'])) {
        try {
            $stmt = $pdo->query("SELECT id, expiry FROM pastes");
            $pastes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($pastes as $row) {
                $p_expiry = trim($row['expiry']);
                $p_id = $row['id'];
                if ($p_expiry !== "NULL" && $p_expiry !== "SELF") {
                    $input_time = strtotime($p_expiry);
                    $current_time = time();
                    if ($input_time < $current_time) {
                        $stmt = $pdo->prepare("DELETE FROM pastes WHERE id = ?");
                        $stmt->execute([$p_id]);
                    }
                }
            }
            $msg = '<div class="paste-alert alert3" style="text-align: center;">All expired pastes have been deleted</div>';
        } catch (PDOException $e) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Error deleting expired pastes: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    if (isset($_GET['all_pastes'])) {
        try {
            $pdo->query("DELETE FROM pastes");
            $msg = '<div class="paste-alert alert3" style="text-align: center;">All pastes have been deleted</div>';
        } catch (PDOException $e) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Error deleting all pastes: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    if (isset($_GET['not_verified'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE verified = ?");
            $stmt->execute(['0']);
            $msg = '<div class="paste-alert alert3" style="text-align: center;">All unverified accounts have been deleted</div>';
        } catch (PDOException $e) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Error deleting unverified accounts: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    if (isset($_GET['admin_history'])) {
        try {
            $pdo->query("DELETE FROM admin_history");
            $msg = '<div class="paste-alert alert3" style="text-align: center;">Admin history has been cleared</div>';
        } catch (PDOException $e) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Error clearing admin history: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    if (isset($_GET['clear_stats'])) {
        try {
            $pdo->query("DELETE FROM page_view");
            $msg = '<div class="paste-alert alert3" style="text-align: center;">Statistics have been cleared</div>';
        } catch (PDOException $e) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Error clearing statistics: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    if (isset($_GET['clear_ipbans'])) {
        try {
            $pdo->query("DELETE FROM ban_user");
            $msg = '<div class="paste-alert alert3" style="text-align: center;">All IP bans have been cleared</div>';
        } catch (PDOException $e) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Error clearing IP bans: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    if (isset($_GET['clear_pages'])) {
        try {
            $pdo->query("DELETE FROM pages");
            $msg = '<div class="paste-alert alert3" style="text-align: center;">All pages have been deleted</div>';
        } catch (PDOException $e) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Error deleting all pages: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    if (isset($_GET['delete_all_users'])) {
        try {
            $pdo->query("DELETE FROM users");
            $msg = '<div class="paste-alert alert3" style="text-align: center;">All users have been deleted</div>';
        } catch (PDOException $e) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Error deleting users: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
} catch (PDOException $e) {
    die("Unable to connect to database: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Tasks</title>
    <link rel="shortcut icon" href="favicon.ico">
    <link href="css/paste.css" rel="stylesheet" type="text/css" />
    <link href="css/bootstrap-select.min.css" rel="stylesheet" type="text/css" />
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
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="dashboard.php"><i class="fa fa-home"></i>Dashboard</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="configuration.php"><i class="fa fa-cogs"></i>Configuration</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="interface.php"><i class="fa fa-eye"></i>Interface</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="admin.php"><i class="fa fa-user"></i>Admin Account</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="pastes.php"><i class="fa fa-clipboard"></i>Pastes</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="users.php"><i class="fa fa-users"></i>Users</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="ipbans.php"><i class="fa fa-ban"></i>IP Bans</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="stats.php"><i class="fa fa-line-chart"></i>Statistics</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="ads.php"><i class="fa fa-gbp"></i>Ads</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="pages.php"><i class="fa fa-file"></i>Pages</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="sitemap.php"><i class="fa fa-map-signs"></i>Sitemap</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1 menu-active"><a href="tasks.php"><i class="fa fa-tasks"></i>Tasks</a></li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-widget">
                        <div class="panel-body">
                            <h4>Maintenance Tasks</h4>
                            <?php if ($msg) echo $msg; ?>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Delete All Expired Pastes</td>
                                        <td><a href="?expired" class="btn btn-default btn-sm task-expired">Run</a></td>
                                    </tr>
                                    <tr>
                                        <td>Delete All Pastes</td>
                                        <td><a href="?all_pastes" class="btn btn-danger btn-sm task-all-pastes">Run</a></td>
                                    </tr>
                                    <tr>
                                        <td>Delete Unverified Accounts</td>
                                        <td><a href="?not_verified" class="btn btn-warning btn-sm task-not-verified">Run</a></td>
                                    </tr>
                                    <tr>
                                        <td>Clear Admin History</td>
                                        <td><a href="?admin_history" class="btn btn-info btn-sm task-admin-history">Run</a></td>
                                    </tr>
                                    <tr>
                                        <td>Clear Statistics</td>
                                        <td><a href="?clear_stats" class="btn btn-info btn-sm task-clear-stats">Run</a></td>
                                    </tr>
                                    <tr>
                                        <td>Clear All IP Bans</td>
                                        <td><a href="?clear_ipbans" class="btn btn-info btn-sm task-clear-ipbans">Run</a></td>
                                    </tr>
                                    <tr>
                                        <td>Delete All Pages</td>
                                        <td><a href="?clear_pages" class="btn btn-danger btn-sm task-clear-pages">Run</a></td>
                                    </tr>
                                    <tr>
                                        <td>Delete All Users</td>
                                        <td><a href="?delete_all_users" class="btn btn-danger btn-sm task-delete-all-users">Run</a></td>
                                    </tr>
                                </tbody>
                            </table>
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
    </div>

    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/bootstrap-select.js"></script>
    <script>
        $(document).ready(function() {
            $(document).on('click', '.task-expired', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                if (confirm('Are you sure you want to delete all expired pastes?')) {
                    window.location.href = href;
                }
            });
            $(document).on('click', '.task-all-pastes', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                if (confirm('Are you sure you want to delete all pastes? This action cannot be undone.')) {
                    window.location.href = href;
                }
            });
            $(document).on('click', '.task-not-verified', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                if (confirm('Are you sure you want to delete all unverified accounts?')) {
                    window.location.href = href;
                }
            });
            $(document).on('click', '.task-admin-history', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                if (confirm('Are you sure you want to clear admin history?')) {
                    window.location.href = href;
                }
            });
            $(document).on('click', '.task-clear-stats', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                if (confirm('Are you sure you want to clear all statistics? This action cannot be undone.')) {
                    window.location.href = href;
                }
            });
            $(document).on('click', '.task-clear-ipbans', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                if (confirm('Are you sure you want to clear all IP bans?')) {
                    window.location.href = href;
                }
            });
            $(document).on('click', '.task-clear-pages', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                if (confirm('Are you sure you want to delete all pages? This action cannot be undone.')) {
                    window.location.href = href;
                }
            });
            $(document).on('click', '.task-delete-all-users', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                if (confirm('Are you sure you want to delete all users? This action cannot be undone.')) {
                    window.location.href = href;
                }
            });
        });
    </script>
</body>
</html>
<?php $pdo = null; ?>