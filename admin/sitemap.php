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
require_once('../includes/functions.php');

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

    // Fetch sitemap options
    $stmt = $pdo->prepare("SELECT priority, changefreq FROM sitemap_options WHERE id = ?");
    $stmt->execute([1]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $priority = $row['priority'] ?? '';
    $changefreq = $row['changefreq'] ?? '';

    // Handle sitemap options update
    $msg = '';
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $priority = filter_var(trim($_POST['priority'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
        $changefreq = filter_var(trim($_POST['changefreq'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
        $stmt = $pdo->prepare("UPDATE sitemap_options SET priority = ?, changefreq = ? WHERE id = ?");
        $stmt->execute([$priority, $changefreq, 1]);
        $msg = '<div class="paste-alert alert3" style="text-align: center;">Sitemap options saved</div>';
    }

    // Handle sitemap rebuild
    if (isset($_GET['re'])) {
        // Pagination for pastes
        $per_page = 20;
        $page = isset($_GET['page']) ? max(1, filter_var($_GET['page'], FILTER_SANITIZE_NUMBER_INT)) : 1;
        $offset = ($page - 1) * $per_page;

        // Count total public pastes
        $stmt = $pdo->prepare("SELECT COUNT(id) AS total FROM pastes WHERE visible = '0'");
        $stmt->execute();
        $total_pastes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = max(1, ceil($total_pastes / $per_page));

        // Initialize sitemap
        if (file_exists('../sitemap.xml')) {
            unlink('../sitemap.xml');
        }
        $protocol = paste_protocol();
        $levelup = dirname(dirname($_SERVER['PHP_SELF']));
        $c_date = date('Y-m-d');
        $data = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>' . $protocol . $_SERVER['SERVER_NAME'] . $levelup . '/</loc>
        <priority>1.0</priority>
        <changefreq>daily</changefreq>
        <lastmod>' . $c_date . '</lastmod>
    </url>
</urlset>';
        file_put_contents("../sitemap.xml", $data);

        // Fetch public pastes for current page
        $per_page_safe = (int)$per_page;
        $offset_safe = (int)$offset;
        $stmt = $pdo->prepare("SELECT id FROM pastes WHERE visible = '0' ORDER BY id DESC LIMIT $per_page_safe OFFSET $offset_safe");
        $stmt->execute();
        $pastes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Append pastes to sitemap
        foreach ($pastes as $row) {
            $paste_id = $row['id'];
            $site_data = file_get_contents("../sitemap.xml");
            $site_data = str_replace("</urlset>", "", $site_data);
            $server_name = $mod_rewrite == "1" ? 
                $protocol . $_SERVER['SERVER_NAME'] . $levelup . "/" . $paste_id :
                $protocol . $_SERVER['SERVER_NAME'] . $levelup . "/paste.php?id=" . $paste_id;
            $c_sitemap = '
    <url>
        <loc>' . htmlspecialchars($server_name) . '</loc>
        <priority>' . htmlspecialchars($priority) . '</priority>
        <changefreq>' . htmlspecialchars($changefreq) . '</changefreq>
        <lastmod>' . $c_date . '</lastmod>
    </url>
</urlset>';
            file_put_contents("../sitemap.xml", $site_data . $c_sitemap);
        }
        $msg = '<div class="paste-alert alert3" style="text-align: center;">sitemap.xml rebuilt</div>';
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
    <title>Paste - Sitemap</title>
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
                        <li class="col-xs-3 col-sm-2 col-md-1 menu-active"><a href="sitemap.php"><i class="fa fa-map-signs"></i>Sitemap</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="tasks.php"><i class="fa fa-tasks"></i>Tasks</a></li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-widget">
                        <div class="panel-body">
                            <h4>Sitemap</h4>
                            <?php if ($msg) echo $msg; ?>
                            <form method="POST" action="sitemap.php">
                                <div class="form-group">
                                    <label for="changefreq">Change Frequency</label>
                                    <input type="text" placeholder="Enter frequency range" name="changefreq" id="changefreq" value="<?php echo htmlspecialchars($changefreq); ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="priority">Priority Level</label>
                                    <input type="text" placeholder="Enter priority..." id="priority" name="priority" value="<?php echo htmlspecialchars($priority); ?>" class="form-control">
                                </div>
                                <button class="btn btn-default" type="submit">Submit</button>
                            </form>
                            <br />
                            <form method="GET" action="sitemap.php">
                                <button class="btn btn-default" name="re" id="re" type="submit">Generate sitemap.xml</button>
                            </form>
                            <?php if (isset($_GET['re']) && $total_pages > 1) { ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination">
                                        <?php
                                        if ($page > 1) {
                                            echo "<li><a href='?re&page=" . ($page - 1) . "' aria-label='Previous'><span aria-hidden='true'>&laquo;</span></a></li>";
                                        } else {
                                            echo "<li class='disabled'><span aria-hidden='true'>&laquo;</span></li>";
                                        }
                                        for ($i = 1; $i <= $total_pages; $i++) {
                                            echo "<li" . ($i == $page ? " class='active'" : "") . "><a href='?re&page=$i'>$i</a></li>";
                                        }
                                        if ($page < $total_pages) {
                                            echo "<li><a href='?re&page=" . ($page + 1) . "' aria-label='Next'><span aria-hidden='true'>&raquo;</span></a></li>";
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
</body>
</html>
<?php $pdo = null; ?>