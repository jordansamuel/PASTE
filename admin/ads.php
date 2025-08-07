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
    // Get last admin history ID
    $stmt = $pdo->query("SELECT MAX(id) AS last_id FROM admin_history");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $last_id = $row['last_id'];

    if ($last_id) {
        $stmt = $pdo->prepare("SELECT last_date, ip FROM admin_history WHERE id = :last_id");
        $stmt->execute(['last_id' => $last_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $last_date = $row['last_date'];
        $last_ip = $row['ip'];
    }

    if ($last_ip == $ip && $last_date == $date) {
        // No action needed
    } else {
        $stmt = $pdo->prepare("INSERT INTO admin_history (last_date, ip) VALUES (:date, :ip)");
        $stmt->execute(['date' => $date, 'ip' => $ip]);
    }

    // Fetch ad settings
    $stmt = $pdo->query("SELECT text_ads, ads_1, ads_2 FROM ads WHERE id = '1'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $text_ads = trim($row['text_ads']);
    $ads_1 = trim($row['ads_1']);
    $ads_2 = trim($row['ads_2']);
} catch (PDOException $e) {
    die("Unable to connect to database: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Ads</title>
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
                    <li class="col-xs-3 col-sm-2 col-md-1"><a href="dashboard.php"><i class="fa fa-home"></i>Dashboard</a></li>
                    <li class="col-xs-3 col-sm-2 col-md-1"><a href="configuration.php"><i class="fa fa-cogs"></i>Configuration</a></li>
                    <li class="col-xs-3 col-sm-2 col-md-1"><a href="interface.php"><i class="fa fa-eye"></i>Interface</a></li>
                    <li class="col-xs-3 col-sm-2 col-md-1"><a href="admin.php"><i class="fa fa-user"></i>Admin Account</a></li>
                    <li class="col-xs-3 col-sm-2 col-md-1"><a href="pastes.php"><i class="fa fa-clipboard"></i>Pastes</a></li>
                    <li class="col-xs-3 col-sm-2 col-md-1"><a href="users.php"><i class="fa fa-users"></i>Users</a></li>
                    <li class="col-xs-3 col-sm-2 col-md-1"><a href="ipbans.php"><i class="fa fa-ban"></i>IP Bans</a></li>
                    <li class="col-xs-3 col-sm-2 col-md-1"><a href="stats.php"><i class="fa fa-line-chart"></i>Statistics</a></li>
                    <li class="col-xs-3 col-sm-2 col-md-1 menu-active"><a href="ads.php"><i class="fa fa-gbp"></i>Ads</a></li>
                    <li class="col-xs-3 col-sm-2 col-md-1"><a href="pages.php"><i class="fa fa-file"></i>Pages</a></li>
                    <li class="col-xs-3 col-sm-2 col-md-1"><a href="sitemap.php"><i class="fa fa-map-signs"></i>Sitemap</a></li>
                    <li class="col-xs-3 col-sm-2 col-md-1"><a href="tasks.php"><i class="fa fa-tasks"></i>Tasks</a></li>
                  </ul>
                </div>
            </div>

            <?php
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $ads_1 = trim($_POST['ads_1']);
                $ads_2 = trim($_POST['ads_2']);
                $text_ads = trim($_POST['text_ads']);
                
                try {
                    $stmt = $pdo->prepare("UPDATE ads SET text_ads = :text_ads, ads_1 = :ads_1, ads_2 = :ads_2 WHERE id = '1'");
                    $stmt->execute(['text_ads' => $text_ads, 'ads_1' => $ads_1, 'ads_2' => $ads_2]);
                    $msg = '<div class="paste-alert alert3">Ads saved</div>';
                } catch (PDOException $e) {
                    $msg = '<div class="paste-alert alert6">' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
            ?>  

            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-widget">
                        <div class="panel-body">
                            <div class="panel-title">Manage Ads</div>
                            <?php if (isset($msg)) echo $msg; ?>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                <div class="control-group">                                            
                                    <label class="control-label" for="text_ads">Text Ads</label>
                                    <div class="controls">
                                        <textarea placeholder="Ad code" name="text_ads" rows="3" class="span6"><?php echo htmlspecialchars($text_ads); ?></textarea>
                                    </div>            
                                </div>
                                <div class="control-group">                                            
                                    <label class="control-label" for="ads_1">Image Ad - (Sidebar)</label>
                                    <div class="controls">
                                        <textarea placeholder="Ad code" name="ads_1" id="ads_1" rows="3" class="span6"><?php echo htmlspecialchars($ads_1); ?></textarea>
                                    </div>            
                                </div>
                                <div class="control-group">                                            
                                    <label class="control-label" for="ads_2">Image Ad (Footer)</label>
                                    <div class="controls">
                                        <textarea placeholder="Ad code" name="ads_2" id="ads_2" rows="3" class="span6"><?php echo htmlspecialchars($ads_2); ?></textarea>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-default">Save</button>
                            </form>
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
  </body>
</html>