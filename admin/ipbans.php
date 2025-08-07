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

    // Handle IP ban
    $msg = '';
    if ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['banip'])) {
        $ban_ip = isset($_POST['ban_ip']) ? trim($_POST['ban_ip']) : (isset($_GET['banip']) ? trim($_GET['banip']) : '');
        if (empty($ban_ip)) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Please enter an IP to ban.</div>';
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM ban_user WHERE ip = ?");
            $stmt->execute([$ban_ip]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0) {
                $msg = '<div class="paste-alert alert1" style="text-align: center;">' . htmlspecialchars($ban_ip) . ' already banned</div>';
            } else {
                $stmt = $pdo->prepare("INSERT INTO ban_user (last_date, ip) VALUES (?, ?)");
                $stmt->execute([$date, $ban_ip]);
                $msg = '<div class="paste-alert alert3" style="text-align: center;">' . htmlspecialchars($ban_ip) . ' added to the banlist</div>';
            }
        }
    }

    // Handle IP deletion
    if (isset($_GET['delete'])) {
        $delete = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
        try {
            $stmt = $pdo->prepare("DELETE FROM ban_user WHERE id = ?");
            $stmt->execute([$delete]);
            $msg = '<div class="paste-alert alert3" style="text-align: center;">IP removed from the banlist</div>';
        } catch (PDOException $e) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Error removing IP: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    // Pagination
    $per_page = 20;
    $page = isset($_GET['page']) ? max(1, filter_var($_GET['page'], FILTER_SANITIZE_NUMBER_INT)) : 1;
    $offset = ($page - 1) * $per_page;

    // Count total IPs for pagination
    $count_query = "SELECT COUNT(*) AS total FROM ban_user";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute();
    $total_ips = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = max(1, ceil($total_ips / $per_page));

    // Fetch IPs for current page
    $per_page_safe = (int)$per_page;
    $offset_safe = (int)$offset;
    $query = "SELECT id, last_date, ip FROM ban_user ORDER BY id DESC LIMIT $per_page_safe OFFSET $offset_safe";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Unable to connect to database: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - IP Bans</title>
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
                        <li class="col-xs-3 col-sm-2 col-md-1 menu-active"><a href="ipbans.php"><i class="fa fa-ban"></i>IP Bans</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="stats.php"><i class="fa fa-line-chart"></i>Statistics</a></li>
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
                            <h4>Ban an IP</h4>
                            <?php if ($msg) echo $msg; ?>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                <div class="form-group">
                                    <input type="text" class="form-control" name="ban_ip" placeholder="Enter an IP address">
                                    <input type="hidden" name="banip" value="banip" />
                                </div>
                                <button type="submit" class="btn btn-default">Add</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-widget">
                        <div class="panel-body">
                            <h4>Banlist</h4>
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date Added</th>
                                        <th>IP</th>
                                        <th>Delete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($ips as $row) {
                                        echo "
                                            <tr>
                                                <td>" . htmlspecialchars($row['last_date']) . "</td>
                                                <td>" . htmlspecialchars($row['ip']) . "</td>
                                                <td><a href='?delete=" . htmlspecialchars($row['id']) . "&page=$page' class='btn btn-danger btn-sm delete-ip' data-id='" . htmlspecialchars($row['id']) . "'>Delete</a></td>
                                            </tr>";
                                    }
                                    if (empty($ips)) {
                                        echo "<tr><td colspan='3'>No IPs found</td></tr>";
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
    <script>
        $(document).ready(function() {
            $(document).on('click', '.delete-ip', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                if (confirm('Are you sure you want to delete this IP from the banlist?')) {
                    window.location.href = href;
                }
            });
        });
    </script>
</body>
</html>
<?php $pdo = null; ?>