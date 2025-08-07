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

$date = date('jS F Y');
$ip = $_SERVER['REMOTE_ADDR'];
require_once('../config.php');

try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Unable to connect to database: " . $e->getMessage());
}

// Log admin activity
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

// Handle user deletion
$msg = '';
if (isset($_GET['delete'])) {
    $delid = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delid]);
        $msg = '<div class="paste-alert alert3" style="text-align: center;">User deleted successfully</div>';
    } catch (PDOException $e) {
        $msg = '<div class="paste-alert alert6" style="text-align: center;">Error deleting user: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Handle user ban
if (isset($_GET['ban'])) {
    $ban_id = filter_var($_GET['ban'], FILTER_SANITIZE_NUMBER_INT);
    try {
        $stmt = $pdo->prepare("UPDATE users SET verified = '2' WHERE id = ?");
        $stmt->execute([$ban_id]);
        $msg = '<div class="paste-alert alert3" style="text-align: center;">User banned successfully</div>';
    } catch (PDOException $e) {
        $msg = '<div class="paste-alert alert6" style="text-align: center;">Error banning user: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Handle user unban
if (isset($_GET['unban'])) {
    $unban_id = filter_var($_GET['unban'], FILTER_SANITIZE_NUMBER_INT);
    try {
        $stmt = $pdo->prepare("UPDATE users SET verified = '1' WHERE id = ?");
        $stmt->execute([$unban_id]);
        $msg = '<div class="paste-alert alert3" style="text-align: center;">User unbanned successfully</div>';
    } catch (PDOException $e) {
        $msg = '<div class="paste-alert alert6" style="text-align: center;">Error unbanning user: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Pagination and filtering
$per_page = 20;
$page = isset($_GET['page']) ? max(1, filter_var($_GET['page'], FILTER_SANITIZE_NUMBER_INT)) : 1;
$offset = ($page - 1) * $per_page;

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$where = '';
$params = [];
if ($status_filter !== 'all') {
    $where = " WHERE verified = ?";
    $params[] = $status_filter;
}

// Count total users for pagination
$count_query = "SELECT COUNT(*) AS total FROM users" . $where;
try {
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $total_users = 0;
}
$total_pages = max(1, ceil($total_users / $per_page));

// Fetch users for current page
// Concatenate LIMIT and OFFSET as sanitized integers to avoid PDO placeholder issues
$per_page_safe = (int)$per_page;
$offset_safe = (int)$offset;
$query = "SELECT id, username, email_id, full_name, platform, verified, date, ip, oauth_uid FROM users" . $where . " ORDER BY id DESC LIMIT $per_page_safe OFFSET $offset_safe";
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $msg = '<div class="paste-alert alert6" style="text-align: center;">Error fetching users: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Users</title>
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
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="dashboard.php"><i class="fa fa-home"></i>Dashboard</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="configuration.php"><i class="fa fa-cogs"></i>Configuration</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="interface.php"><i class="fa fa-eye"></i>Interface</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="admin.php"><i class="fa fa-user"></i>Admin Account</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="pastes.php"><i class="fa fa-clipboard"></i>Pastes</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1 menu-active">
                            <a href="users.php"><i class="fa fa-users"></i>Users</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="ipbans.php"><i class="fa fa-ban"></i>IP Bans</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="stats.php"><i class="fa fa-line-chart"></i>Statistics</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="ads.php"><i class="fa fa-gbp"></i>Ads</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="pages.php"><i class="fa fa-file"></i>Pages</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="sitemap.php"><i class="fa fa-map-signs"></i>Sitemap</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="tasks.php"><i class="fa fa-tasks"></i>Tasks</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-widget">
                        <div class="panel-body">
                            <?php
                            if (isset($_GET['details'])) {
                                $detail_id = filter_var($_GET['details'], FILTER_SANITIZE_NUMBER_INT);
                                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                                $stmt->execute([$detail_id]);
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($row) {
                                    $user_oauth_uid = $row['oauth_uid'] == '0' ? 'None' : htmlspecialchars($row['oauth_uid']);
                                    $user_username = htmlspecialchars($row['username']);
                                    $user_email_id = htmlspecialchars($row['email_id']);
                                    $user_full_name = htmlspecialchars($row['full_name']);
                                    $user_platform = htmlspecialchars(trim($row['platform']));
                                    $user_verified = match ($row['verified']) {
                                        '0' => 'Unverified',
                                        '1' => 'Verified',
                                        '2' => 'Banned',
                                        default => 'Unknown'
                                    };
                                    $user_date = htmlspecialchars($row['date']);
                                    $user_ip = htmlspecialchars($row['ip']);
                            ?>
                                <h4><?php echo $user_username; ?> Details</h4>
                                <table class="table table-striped table-bordered">
                                    <tbody>
                                        <tr><td>Username</td><td><?php echo $user_username; ?></td></tr>
                                        <tr><td>Email ID</td><td><?php echo $user_email_id; ?></td></tr>
                                        <tr><td>Platform</td><td><?php echo $user_platform; ?></td></tr>
                                        <tr><td>OAUTH ID</td><td><?php echo $user_oauth_uid; ?></td></tr>
                                        <tr><td>Status</td><td><?php echo $user_verified; ?></td></tr>
                                        <tr><td>User IP</td><td><?php echo $user_ip; ?></td></tr>
                                        <tr><td>Date Registered</td><td><?php echo $user_date; ?></td></tr>
                                        <tr><td>Full Name</td><td><?php echo $user_full_name; ?></td></tr>
                                    </tbody>
                                </table>
                            <?php } else { ?>
                                <h4>No user found</h4>
                            <?php }
                            } else { ?>
                                <h4>Manage Users</h4>
                                <?php if ($msg) echo $msg; ?>
                                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                    <div class="form-group">
                                        <label for="status">Filter by Status</label>
                                        <select class="selectpicker" name="status" onchange="this.form.submit()">
                                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All</option>
                                            <option value="0" <?php echo $status_filter == '0' ? 'selected' : ''; ?>>Unverified</option>
                                            <option value="1" <?php echo $status_filter == '1' ? 'selected' : ''; ?>>Verified</option>
                                            <option value="2" <?php echo $status_filter == '2' ? 'selected' : ''; ?>>Banned</option>
                                        </select>
                                    </div>
                                </form>
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Email ID</th>
                                            <th>Date Registered</th>
                                            <th>Platform</th>
                                            <th>OAUTH ID</th>
                                            <th>Status</th>
                                            <th>Ban User</th>
                                            <th>Profile</th>
                                            <th>Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($users as $row) {
                                            $user_oauth_uid = $row['oauth_uid'] == '0' ? 'None' : htmlspecialchars($row['oauth_uid']);
                                            $user_verified = match ($row['verified']) {
                                                '0' => 'Unverified',
                                                '1' => 'Verified',
                                                '2' => 'Banned',
                                                default => 'Unknown'
                                            };
                                            $ban_action = $row['verified'] == '2' ? 'unban' : 'ban';
                                            $ban_label = $row['verified'] == '2' ? 'Unban' : 'Ban';
                                            echo "
                                                <tr>
                                                    <td>" . htmlspecialchars($row['username']) . "</td>
                                                    <td>" . htmlspecialchars($row['email_id']) . "</td>
                                                    <td>" . htmlspecialchars($row['date']) . "</td>
                                                    <td>" . htmlspecialchars(trim($row['platform'])) . "</td>
                                                    <td>" . $user_oauth_uid . "</td>
                                                    <td>" . $user_verified . "</td>
                                                    <td><a href='?$ban_action=" . htmlspecialchars($row['id']) . "&page=$page&status=$status_filter' class='btn btn-default btn-sm ban-user' data-id='" . htmlspecialchars($row['id']) . "'>$ban_label</a></td>
                                                    <td><a href='?details=" . htmlspecialchars($row['id']) . "' class='btn btn-default btn-sm'>Details</a></td>
                                                    <td><a href='?delete=" . htmlspecialchars($row['id']) . "&page=$page&status=$status_filter' class='btn btn-default btn-sm delete-user' data-id='" . htmlspecialchars($row['id']) . "'>Delete</a></td>
                                                </tr>";
                                        }
                                        if (empty($users)) {
                                            echo "<tr><td colspan='9'>No users found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination">
                                        <?php
                                        $params = $status_filter !== 'all' ? "&status=$status_filter" : '';
                                        if ($page > 1) {
                                            echo "<li><a href='?page=" . ($page - 1) . "$params' aria-label='Previous'><span aria-hidden='true'>&laquo;</span></a></li>";
                                        } else {
                                            echo "<li class='disabled'><span aria-hidden='true'>&laquo;</span></li>";
                                        }
                                        for ($i = 1; $i <= $total_pages; $i++) {
                                            echo "<li" . ($i == $page ? " class='active'" : "") . "><a href='?page=$i$params'>$i</a></li>";
                                        }
                                        if ($page < $total_pages) {
                                            echo "<li><a href='?page=" . ($page + 1) . "$params' aria-label='Next'><span aria-hidden='true'>&raquo;</span></a></li>";
                                        } else {
                                            echo "<li class='disabled'><span aria-hidden='true'>&raquo;</span></li>";
                                        }
                                        ?>
                                    </ul>
                                </nav>
                            <?php } ?>
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
            $('.selectpicker').selectpicker();
            $(document).on('click', '.delete-user', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                if (confirm('Are you sure you want to delete this user?')) {
                    window.location.href = href;
                }
            });
            $(document).on('click', '.ban-user', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                const action = href.includes('ban=') ? 'ban' : 'unban';
                if (confirm('Are you sure you want to ' + action + ' this user?')) {
                    window.location.href = href;
                }
            });
        });
    </script>
</body>
</html>
<?php $pdo = null; ?>