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

    // Summary statistics
    $total_page = 0;
    $total_un = 0;
    $stmt = $pdo->query("SELECT tpage, tvisit FROM page_view");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $total_page += (int)$row['tpage'];
        $total_un += (int)$row['tvisit'];
    }

    $total_pastes = 0;
    $exp_pastes = 0;
    $stmt = $pdo->query("SELECT expiry FROM pastes");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $total_pastes++;
        $p_expiry = trim($row['expiry']);
        if ($p_expiry !== "NULL" && $p_expiry !== "SELF") {
            $input_time = strtotime($p_expiry);
            $current_time = time();
            if ($input_time < $current_time) {
                $exp_pastes++;
            }
        }
    }

    $total_users = 0;
    $total_ban = 0;
    $not_ver = 0;
    $stmt = $pdo->query("SELECT verified FROM users");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $total_users++;
        $p_v = trim($row['verified']);
        if ($p_v == '2') {
            $total_ban++;
        }
        if ($p_v == '0') {
            $not_ver++;
        }
    }

    // Pagination for page views
    $per_page = 20;
    $page = isset($_GET['page']) ? max(1, filter_var($_GET['page'], FILTER_SANITIZE_NUMBER_INT)) : 1;
    $offset = ($page - 1) * $per_page;

    // Count total page views
    $stmt = $pdo->query("SELECT COUNT(id) AS total FROM page_view");
    $total_views = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = max(1, ceil($total_views / $per_page));

    // Fetch page views for current page
    $per_page_safe = (int)$per_page;
    $offset_safe = (int)$offset;
    $stmt = $pdo->prepare("SELECT date, tpage, tvisit FROM page_view ORDER BY id DESC LIMIT $per_page_safe OFFSET $offset_safe");
    $stmt->execute();
    $page_views = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Unable to connect to database: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Statistics</title>
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
                        <li class="col-xs-3 col-sm-2 col-md-1 menu-active"><a href="stats.php"><i class="fa fa-line-chart"></i>Statistics</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="ads.php"><i class="fa fa-gbp"></i>Ads</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="pages.php"><i class="fa fa-file"></i>Pages</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="sitemap.php"><i class="fa fa-map-signs"></i>Sitemap</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="tasks.php"><i class="fa fa-tasks"></i>Tasks</a></li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-widget">
                        <div class="panel-body">
                            <h4>Statistics</h4>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Stats</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Total Pastes</td>
                                        <td><span class="label label-default"><?php echo $total_pastes; ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>Expired Pastes</td>
                                        <td><span class="label label-default"><?php echo $exp_pastes; ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>Total Users</td>
                                        <td><span class="label label-default"><?php echo $total_users; ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>Total Banned Users</td>
                                        <td><span class="label label-warning"><?php echo $total_ban; ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>Unverified Users</td>
                                        <td><span class="label label-warning"><?php echo $not_ver; ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>Total Page Views</td>
                                        <td><span class="label label-default"><?php echo $total_page; ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>Total Unique Visitors</td>
                                        <td><span class="label label-default"><?php echo $total_un; ?></span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-widget">
                        <div class="panel-body">
                            <h4>Page Views</h4>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Unique Visitors</th>
                                        <th>Views</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($page_views as $row) {
                                        echo "
                                            <tr>
                                                <td>" . htmlspecialchars($row['date']) . "</td>
                                                <td>" . htmlspecialchars($row['tvisit']) . "</td>
                                                <td>" . htmlspecialchars($row['tpage']) . "</td>
                                            </tr>";
                                    }
                                    if (empty($page_views)) {
                                        echo "<tr><td colspan='3'>No page views found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <?php
                                    if ($page > 1) {
                                        echo "<li><a href='?page=" . ($page - 1) . "' aria-label='Previous'><span aria-hidden='true'>&laquo;</span></a></li>";
                                    } else {
                                        echo "<li class='disabled'><span aria-hidden='true'>&laquo;</span></li>";
                                    }
                                    for ($i = 1; $i <= $total_pages; $i++) {
                                        echo "<li" . ($i == $page ? " class='active'" : "") . "><a href='?page=$i'>$i</a></li>";
                                    }
                                    if ($page < $total_pages) {
                                        echo "<li><a href='?page=" . ($page + 1) . "' aria-label='Next'><span aria-hidden='true'>&raquo;</span></a></li>";
                                    } else {
                                        echo "<li class='disabled'><span aria-hidden='true'>&raquo;</span></li>";
                                    }
                                    ?>
                                </ul>
                            </nav>
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
</body>
</html>
<?php $pdo = null; ?>