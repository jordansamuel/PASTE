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

// Handle paste deletion
$msg = '';
if (isset($_GET['delete'])) {
    $delid = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
    try {
        $stmt = $pdo->prepare("DELETE FROM pastes WHERE id = ?");
        $stmt->execute([$delid]);
        $msg = '<div class="paste-alert alert3" style="text-align: center;">Paste deleted successfully</div>';
    } catch (PDOException $e) {
        $msg = '<div class="paste-alert alert6" style="text-align: center;">Error deleting paste: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Handle paste ban
if (isset($_GET['ban'])) {
    $ban_id = filter_var($_GET['ban'], FILTER_SANITIZE_NUMBER_INT);
    try {
        $stmt = $pdo->prepare("UPDATE pastes SET visible = '3' WHERE id = ?");
        $stmt->execute([$ban_id]);
        $msg = '<div class="paste-alert alert3" style="text-align: center;">Paste banned successfully</div>';
    } catch (PDOException $e) {
        $msg = '<div class="paste-alert alert6" style="text-align: center;">Error banning paste: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Handle paste unban
if (isset($_GET['unban'])) {
    $unban_id = filter_var($_GET['unban'], FILTER_SANITIZE_NUMBER_INT);
    try {
        $stmt = $pdo->prepare("UPDATE pastes SET visible = '0' WHERE id = ?");
        $stmt->execute([$unban_id]);
        $msg = '<div class="paste-alert alert3" style="text-align: center;">Paste unbanned successfully</div>';
    } catch (PDOException $e) {
        $msg = '<div class="paste-alert alert6" style="text-align: center;">Error unbanning paste: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Pagination and filtering
$per_page = 20;
$page = isset($_GET['page']) ? max(1, filter_var($_GET['page'], FILTER_SANITIZE_NUMBER_INT)) : 1;
$offset = ($page - 1) * $per_page;

$visibility_filter = isset($_GET['visibility']) ? $_GET['visibility'] : 'all';
$where = '';
$params = [];
if ($visibility_filter !== 'all') {
    $where = " WHERE visible = ?";
    $params[] = $visibility_filter;
}

// Count total pastes for pagination
$count_query = "SELECT COUNT(*) AS total FROM pastes" . $where;
try {
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_pastes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $total_pastes = 0;
}
$total_pages = max(1, ceil($total_pastes / $per_page));

// Fetch pastes for current page
$per_page_safe = (int)$per_page;
$offset_safe = (int)$offset;
$query = "SELECT id, member, ip, visible, title FROM pastes" . $where . " ORDER BY now_time DESC LIMIT $per_page_safe OFFSET $offset_safe";
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $pastes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $msg = '<div class="paste-alert alert6" style="text-align: center;">Error fetching pastes: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $pastes = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Pastes</title>
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
                        <li class="col-xs-3 col-sm-2 col-md-1 menu-active">
                            <a href="pastes.php"><i class="fa fa-clipboard"></i>Pastes</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1">
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
                            <?php if (isset($_GET['details'])) {
                                $detail_id = filter_var($_GET['details'], FILTER_SANITIZE_NUMBER_INT);
                                $stmt = $pdo->prepare("SELECT * FROM pastes WHERE id = ?");
                                $stmt->execute([$detail_id]);
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($row) {
                                    $p_title = htmlspecialchars($row['title']);
                                    $p_visible = $row['visible'];
                                    $p_code = htmlspecialchars($row['code']);
                                    $p_expiry = $row['expiry'];
                                    $p_password = $row['password'];
                                    $p_member = htmlspecialchars($row['member']);
                                    $p_encrypt = $row['encrypt'];
                                    $p_views = $row['views'];
                                    $p_ip = htmlspecialchars($row['ip']);
                                    $encrypt = ($p_encrypt == "" || $p_encrypt == null || $p_encrypt == '0') ? "Not Encrypted" : "Encrypted";
                                    $expiry = ($p_expiry == "NULL") ? "Never" : (strtotime($p_expiry) < time() ? "Paste is expired" : "Paste is not expired");
                                    $pass = ($p_password == "NONE") ? "Not protected" : "Password protected paste";
                                    $visible = match ($p_visible) {
                                        '0' => "Public",
                                        '1' => "Unlisted",
                                        '2' => "Private",
                                        '3' => "Banned",
                                        default => "Unknown"
                                    };
                            ?>
                                <h4>Details of Paste ID <?php echo $detail_id; ?></h4>
                                <table class="table table-striped">
                                    <tbody>
                                        <tr><td>Username</td><td><?php echo $p_member; ?></td></tr>
                                        <tr><td>Paste Title</td><td><?php echo $p_title; ?></td></tr>
                                        <tr><td>Visibility</td><td><?php echo $visible; ?></td></tr>
                                        <tr><td>Password</td><td><?php echo $pass; ?></td></tr>
                                        <tr><td>Views</td><td><?php echo $p_views; ?></td></tr>
                                        <tr><td>IP</td><td><?php echo $p_ip; ?></td></tr>
                                        <tr><td>Syntax Highlighting</td><td><?php echo $p_code; ?></td></tr>
                                        <tr><td>Expiration</td><td><?php echo $expiry; ?></td></tr>
                                        <tr><td>Encrypted Paste</td><td><?php echo $encrypt; ?></td></tr>
                                    </tbody>
                                </table>
                            <?php } else { ?>
                                <h4>No paste found</h4>
                            <?php }
                            } else { ?>
                                <h4>Manage Pastes</h4>
                                <?php if ($msg) echo $msg; ?>
                                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                    <div class="form-group">
                                        <label for="visibility">Filter by Visibility</label>
                                        <select class="selectpicker" name="visibility" onchange="this.form.submit()">
                                            <option value="all" <?php echo $visibility_filter == 'all' ? 'selected' : ''; ?>>All</option>
                                            <option value="0" <?php echo $visibility_filter == '0' ? 'selected' : ''; ?>>Public</option>
                                            <option value="1" <?php echo $visibility_filter == '1' ? 'selected' : ''; ?>>Unlisted</option>
                                            <option value="2" <?php echo $visibility_filter == '2' ? 'selected' : ''; ?>>Private</option>
                                            <option value="3" <?php echo $visibility_filter == '3' ? 'selected' : ''; ?>>Banned</option>
                                        </select>
                                    </div>
                                </form>
                                <table class="table table-hover table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Title</th>
                                            <th>IP</th>
                                            <th>Visibility</th>
                                            <th>Ban Paste</th>
                                            <th>More Details</th>
                                            <th>View Paste</th>
                                            <th>Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($pastes as $row) {
                                            $visibility = match ($row['visible']) {
                                                '0' => 'Public',
                                                '1' => 'Unlisted',
                                                '2' => 'Private',
                                                '3' => 'Banned',
                                                default => 'Unknown'
                                            };
                                            $ban_action = $row['visible'] == '3' ? 'unban' : 'ban';
                                            $ban_label = $row['visible'] == '3' ? 'Unban' : 'Ban';
                                            echo "
                                                <tr>
                                                    <td>" . htmlspecialchars($row['id']) . "</td>
                                                    <td>" . htmlspecialchars($row['member']) . "</td>
                                                    <td>" . htmlspecialchars($row['title']) . "</td>
                                                    <td><span class='badge'>" . htmlspecialchars($row['ip']) . "</span></td>
                                                    <td>" . htmlspecialchars($visibility) . "</td>
                                                    <td><a href='?$ban_action=" . htmlspecialchars($row['id']) . "&page=$page&visibility=$visibility_filter' class='btn btn-default btn-sm ban-paste' data-id='" . htmlspecialchars($row['id']) . "'>$ban_label</a></td>
                                                    <td><a href='?details=" . htmlspecialchars($row['id']) . "' class='btn btn-default btn-sm'>Details</a></td>
                                                    <td><a href='../paste.php?id=" . htmlspecialchars($row['id']) . "' class='btn btn-default btn-sm'>View</a></td>
                                                    <td><a href='?delete=" . htmlspecialchars($row['id']) . "&page=$page&visibility=$visibility_filter' class='btn btn-default btn-sm delete-paste' data-id='" . htmlspecialchars($row['id']) . "'>Delete</a></td>
                                                </tr>";
                                        }
                                        if (empty($pastes)) {
                                            echo "<tr><td colspan='9'>No pastes found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination">
                                        <?php
                                        $params = $visibility_filter !== 'all' ? "&visibility=$visibility_filter" : '';
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
            $(document).on('click', '.delete-paste', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                if (confirm('Are you sure you want to delete this paste?')) {
                    window.location.href = href;
                }
            });
            $(document).on('click', '.ban-paste', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                const action = href.includes('ban=') ? 'ban' : 'unban';
                if (confirm('Are you sure you want to ' + action + ' this paste?')) {
                    window.location.href = href;
                }
            });
        });
    </script>
</body>
</html>
<?php $pdo = null; ?>