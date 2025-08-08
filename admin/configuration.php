<?php
/*
 * Paste <https://github.com/boxlabss/PASTE>
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'cookie_httponly' => true,
        'use_strict_mode' => true,
    ]);
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check session and validate admin
if (!isset($_SESSION['admin_login']) || !isset($_SESSION['admin_id'])) {
    error_log("configuration.php: Session validation failed - admin_login or admin_id not set. Session: " . json_encode($_SESSION));
    header("Location: ../index.php");
    exit();
}

$date = date('Y-m-d H:i:s');
$ip = $_SERVER['REMOTE_ADDR'];
require_once '../config.php';
require_once '../mail/mail.php';

// Check OAuth autoloader
$oauth_autoloader = __DIR__ . '/../oauth/vendor/autoload.php';
if (!file_exists($oauth_autoloader)) {
    error_log("configuration.php: OAuth autoloader not found");
    die("OAuth autoloader not found. Run: <code>cd oauth && composer require google/apiclient:^2.12 phpmailer/phpmailer:^6.6 league/oauth2-client:^2.7 league/oauth2-google:^4.0</code>");
}
require_once $oauth_autoloader;

// Verify required OAuth classes
$required_classes = [
    'Google_Client' => 'google/apiclient:^2.12',
    'PHPMailer\PHPMailer\PHPMailer' => 'phpmailer/phpmailer:^6.6',
    'League\OAuth2\Client\Provider\Google' => 'league/oauth2-client:^2.7 league/oauth2-google:^4.0'
];
foreach ($required_classes as $class => $packages) {
    if (!class_exists($class)) {
        error_log("configuration.php: $class not found. Run: cd oauth && composer require $packages");
        $msg = '<div class="paste-alert alert6" style="text-align: center;">OAuth configuration error. Please contact the administrator.</div>';
    }
}

try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Log admin activity
    $stmt = $pdo->query("SELECT MAX(id) AS last_id FROM admin_history");
    $last_id = $stmt->fetch()['last_id'] ?? null;

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

    // Fetch site info
    $stmt = $pdo->query("SELECT * FROM site_info WHERE id = 1");
    $row = $stmt->fetch();
    $title = trim($row['title'] ?? '');
    $des = trim($row['des'] ?? '');
    $baseurl = trim($row['baseurl'] ?? '');
    $keyword = trim($row['keyword'] ?? '');
    $site_name = trim($row['site_name'] ?? '');
    $email = trim($row['email'] ?? '');
    $twit = trim($row['twit'] ?? '');
    $face = trim($row['face'] ?? '');
    $gplus = trim($row['gplus'] ?? '');
    $ga = trim($row['ga'] ?? '');
    $additional_scripts = trim($row['additional_scripts'] ?? '');

    // Fetch captcha settings
    $stmt = $pdo->query("SELECT * FROM captcha WHERE id = 1");
    $row = $stmt->fetch();
    $cap_e = $row['cap_e'] ?? '';
    $mode = $row['mode'] ?? '';
    $mul = $row['mul'] ?? '';
    $allowed = $row['allowed'] ?? '';
    $color = $row['color'] ?? '';
    $recaptcha_sitekey = $row['recaptcha_sitekey'] ?? '';
    $recaptcha_secretkey = $row['recaptcha_secretkey'] ?? '';

    // Fetch site permissions
    $stmt = $pdo->query("SELECT * FROM site_permissions WHERE id = 1");
    $row = $stmt->fetch();
    $disableguest = trim($row['disableguest'] ?? '');
    $siteprivate = trim($row['siteprivate'] ?? '');

    // Fetch mail settings
    $stmt = $pdo->query("SELECT * FROM mail WHERE id = 1");
    $row = $stmt->fetch();
    $verification = trim($row['verification'] ?? '');
    $smtp_host = trim($row['smtp_host'] ?? '');
    $smtp_username = trim($row['smtp_username'] ?? '');
    $smtp_password = trim($row['smtp_password'] ?? '');
    $smtp_port = trim($row['smtp_port'] ?? '');
    $protocol = trim($row['protocol'] ?? '');
    $auth = trim($row['auth'] ?? '');
    $socket = trim($row['socket'] ?? '');
    $oauth_client_id = trim($row['oauth_client_id'] ?? '');
    $oauth_client_secret = trim($row['oauth_client_secret'] ?? '');
    $oauth_refresh_token = trim($row['oauth_refresh_token'] ?? '');

    $msg = '';
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">CSRF validation failed.</div>';
        } else {
            if (isset($_POST['manage'])) {
                $site_name = filter_var(trim($_POST['site_name'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
                $title = filter_var(trim($_POST['title'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
                $baseurl = filter_var(trim($_POST['baseurl'] ?? ''), FILTER_SANITIZE_URL);
                $des = filter_var(trim($_POST['des'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
                $keyword = htmlspecialchars(trim($_POST['keyword'] ?? ''));
                $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
                $twit = htmlspecialchars(trim($_POST['twit'] ?? ''));
                $face = htmlspecialchars(trim($_POST['face'] ?? ''));
                $gplus = htmlspecialchars(trim($_POST['gplus'] ?? ''));
                $ga = htmlspecialchars(trim($_POST['ga'] ?? ''));
                $additional_scripts = filter_var(trim($_POST['additional_scripts'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);

                try {
                    $stmt = $pdo->prepare("UPDATE site_info SET title = ?, des = ?, baseurl = ?, keyword = ?, site_name = ?, email = ?, twit = ?, face = ?, gplus = ?, ga = ?, additional_scripts = ? WHERE id = 1");
                    $stmt->execute([$title, $des, $baseurl, $keyword, $site_name, $email, $twit, $face, $gplus, $ga, $additional_scripts]);
                    $msg = '<div class="paste-alert alert3" style="text-align: center;">Configuration saved</div>';
                } catch (PDOException $e) {
                    error_log("configuration.php: Site info update error: " . $e->getMessage());
                    $msg = '<div class="paste-alert alert6" style="text-align: center;">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
                }
            }
            if (isset($_POST['cap'])) {
                $cap_e = trim($_POST['cap_e'] ?? '');
                $mode = trim($_POST['mode'] ?? '');
                $mul = trim($_POST['mul'] ?? '');
                $allowed = trim($_POST['allowed'] ?? '');
                $color = trim($_POST['color'] ?? '');
                $recaptcha_sitekey = trim($_POST['recaptcha_sitekey'] ?? '');
                $recaptcha_secretkey = trim($_POST['recaptcha_secretkey'] ?? '');

                try {
                    $stmt = $pdo->prepare("UPDATE captcha SET cap_e = ?, mode = ?, mul = ?, allowed = ?, color = ?, recaptcha_sitekey = ?, recaptcha_secretkey = ? WHERE id = 1");
                    $stmt->execute([$cap_e, $mode, $mul, $allowed, $color, $recaptcha_sitekey, $recaptcha_secretkey]);
                    $msg = '<div class="paste-alert alert3" style="text-align: center;">Captcha settings saved</div>';
                } catch (PDOException $e) {
                    error_log("configuration.php: Captcha update error: " . $e->getMessage());
                    $msg = '<div class="paste-alert alert6" style="text-align: center;">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
                }
            }
            if (isset($_POST['permissions'])) {
                $disableguest = trim($_POST['disableguest'] ?? '');
                $siteprivate = trim($_POST['siteprivate'] ?? '');

                try {
                    $stmt = $pdo->prepare("UPDATE site_permissions SET disableguest = ?, siteprivate = ? WHERE id = 1");
                    $stmt->execute([$disableguest, $siteprivate]);
                    $msg = '<div class="paste-alert alert3" style="text-align: center;">Site permissions saved</div>';
                } catch (PDOException $e) {
                    error_log("configuration.php: Permissions update error: " . $e->getMessage());
                    $msg = '<div class="paste-alert alert6" style="text-align: center;">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
                }
            }
            if (isset($_POST['smtp_code'])) {
                $verification = trim($_POST['verification'] ?? '');
                $smtp_host = trim($_POST['smtp_host'] ?? '');
                $smtp_port = trim($_POST['smtp_port'] ?? '');
                $smtp_username = trim($_POST['smtp_user'] ?? '');
                $smtp_password = trim($_POST['smtp_pass'] ?? '');
                $socket = trim($_POST['socket'] ?? '');
                $auth = trim($_POST['auth'] ?? '');
                $protocol = trim($_POST['protocol'] ?? '');

                try {
                    $stmt = $pdo->prepare("UPDATE mail SET verification = ?, smtp_host = ?, smtp_port = ?, smtp_username = ?, smtp_password = ?, socket = ?, protocol = ?, auth = ? WHERE id = 1");
                    $stmt->execute([$verification, $smtp_host, $smtp_port, $smtp_username, $smtp_password, $socket, $protocol, $auth]);
                    $msg = '<div class="paste-alert alert3" style="text-align: center;">Mail settings updated</div>';
                } catch (PDOException $e) {
                    error_log("configuration.php: Mail settings update error: " . $e->getMessage());
                    $msg = '<div class="paste-alert alert6" style="text-align: center;">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
                }

                // Test SMTP if requested
                if (isset($_POST['test_smtp'])) {
                    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $msg = '<div class="paste-alert alert6" style="text-align: center;">Invalid or missing Admin Email in Site Info. Please set a valid email address.</div>';
                    } elseif ($protocol === '2' && empty($oauth_refresh_token)) {
                        $msg = '<div class="paste-alert alert6" style="text-align: center;">OAuth refresh token missing. Please configure Gmail OAuth.</div>';
                    } elseif (empty($smtp_username) || !filter_var($smtp_username, FILTER_VALIDATE_EMAIL)) {
                        $msg = '<div class="paste-alert alert6" style="text-align: center;">Invalid or missing SMTP User in Mail Settings. Please set a valid email address.</div>';
                    } else {
                        $mail_result = send_mail($email, "Test Email from Pastebin", "This is a test email sent from your Pastebin installation.", $site_name, $email, $_SESSION['csrf_token']);
                        if ($mail_result['status'] === 'success') {
                            $msg = '<div class="paste-alert alert3" style="text-align: center;">Test email sent successfully</div>';
                        } else {
                            $msg = '<div class="paste-alert alert6" style="text-align: center;">Failed to send test email: ' . htmlspecialchars($mail_result['message'], ENT_QUOTES, 'UTF-8') . '</div>';
                        }
                    }
                }
            }
        }
    }

    // Handle OAuth messages
    if (isset($_GET['msg'])) {
        $msg = '<div class="paste-alert alert3" style="text-align: center;">' . htmlspecialchars(urldecode($_GET['msg']), ENT_QUOTES, 'UTF-8') . '</div>';
    }
    if (isset($_GET['error'])) {
        $msg = '<div class="paste-alert alert6" style="text-align: center;">' . htmlspecialchars(urldecode($_GET['error']), ENT_QUOTES, 'UTF-8') . '</div>';
    }

} catch (PDOException $e) {
    error_log("configuration.php: Database error: " . $e->getMessage());
    die("Unable to connect to database: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Configuration</title>
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
                        <li class="col-xs-3 col-sm-2 col-md-1">
                            <a href="dashboard.php"><i class="fa fa-home"></i>Dashboard</a>
                        </li>
                        <li class="col-xs-3 col-sm-2 col-md-1 menu-active">
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
                            <?php if (isset($msg)) echo $msg; ?>
                            <div role="tabpanel">
                                <ul class="nav nav-tabs nav-line" role="tablist" style="text-align: center;">
                                    <li role="presentation" class="active"><a href="#siteinfo" aria-controls="siteinfo" role="tab" data-toggle="tab">Site Info</a></li>
                                    <li role="presentation"><a href="#permissions" aria-controls="permissions" role="tab" data-toggle="tab">Permissions</a></li>
                                    <li role="presentation"><a href="#captcha" aria-controls="captcha" role="tab" data-toggle="tab">Captcha Settings</a></li>
                                    <li role="presentation"><a href="#mail" aria-controls="mail" role="tab" data-toggle="tab">Mail Settings</a></li>
                                </ul>

                                <div class="tab-content">
                                    <div role="tabpanel" class="tab-pane active" id="siteinfo">
                                        <form class="form-horizontal" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">Site Name</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="site_name" placeholder="The name of your site" value="<?php echo htmlspecialchars(isset($_POST['site_name']) ? $_POST['site_name'] : $site_name); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">Site Title</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="title" placeholder="Site title tag" value="<?php echo htmlspecialchars(isset($_POST['title']) ? $_POST['title'] : $title); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">Domain name</label>
                                                <div class="col-sm-1" style="padding:5px;">
                                                    <span class="badge">
                                                        <?php echo isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://'; ?>
                                                    </span>
                                                </div>
                                                <div class="col-sm-5">
                                                    <input type="text" class="form-control" name="baseurl" placeholder="eg: pastethis.in (no trailing slash)" value="<?php echo htmlspecialchars(isset($_POST['baseurl']) ? $_POST['baseurl'] : $baseurl); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">Site Description</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="des" placeholder="Site description" value="<?php echo htmlspecialchars(isset($_POST['des']) ? $_POST['des'] : $des); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">Site Keywords</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="keyword" placeholder="Keywords (separated by a comma)" value="<?php echo htmlspecialchars($keyword); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">Google Analytics</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="ga" placeholder="Google Analytics ID" value="<?php echo htmlspecialchars($ga); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">Admin Email</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="email" placeholder="Email" value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : $email); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">Facebook URL</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="face" placeholder="Facebook URL" value="<?php echo htmlspecialchars($face); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">Twitter URL</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="twit" placeholder="Twitter URL" value="<?php echo htmlspecialchars($twit); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">Google+ URL</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="gplus" placeholder="Google+ URL" value="<?php echo htmlspecialchars($gplus); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">Additional Site Scripts</label>
                                                <div class="col-sm-10">
                                                    <textarea class="form-control" id="additional_scripts" name="additional_scripts" rows="8"><?php echo htmlspecialchars(isset($_POST['additional_scripts']) ? $_POST['additional_scripts'] : $additional_scripts); ?></textarea>
                                                </div>
                                            </div>
                                            <input type="hidden" name="manage" value="manage" />
                                            <div class="form-group">
                                                <div class="col-sm-offset-2 col-sm-10">
                                                    <button type="submit" class="btn btn-default">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div role="tabpanel" class="tab-pane" id="permissions">
                                        <form class="form-horizontal" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <div class="checkbox checkbox-primary">
                                                <input <?php if ($disableguest == 'on') echo 'checked="true"'; ?> type="checkbox" name="disableguest" id="disableguest">
                                                <label for="disableguest">Only allow registered users to paste</label>
                                            </div>
                                            <div class="checkbox checkbox-primary">
                                                <input <?php if ($siteprivate == 'on') echo 'checked="true"'; ?> type="checkbox" name="siteprivate" id="siteprivate">
                                                <label for="siteprivate">Make site private (no Recent Pastes or Archives)</label>
                                            </div>
                                            <br />
                                            <input type="hidden" name="permissions" value="permissions" />
                                            <div class="form-group">
                                                <div class="col-sm-offset-2 col-sm-10">
                                                    <button type="submit" class="btn btn-default">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div role="tabpanel" class="tab-pane" id="captcha">
                                        <form class="form-horizontal" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <div class="checkbox checkbox-primary">
                                                <input <?php if ($cap_e == 'on') echo 'checked="true"'; ?> type="checkbox" name="cap_e" id="cap_e">
                                                <label for="cap_e">Enable Captcha</label>
                                            </div>
                                            <br />
                                            <div class="form-group row">
                                                <label for="mode" class="col-sm-1 col-form-label">Captcha Type</label>
                                                <div class="col-sm-10">
                                                    <select class="selectpicker" name="mode">
                                                        <?php
                                                        $options = ['reCAPTCHA', 'Easy', 'Normal', 'Tough'];
                                                        foreach ($options as $option) {
                                                            $selected = ($mode == $option) ? 'selected' : '';
                                                            echo "<option $selected>$option</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <hr />
                                            <div class="panel-title">Internal Captcha Settings:</div>
                                            <div class="checkbox checkbox-primary">
                                                <input <?php if ($mul == 'on') echo 'checked="true"'; ?> type="checkbox" name="mul" id="mul">
                                                <label for="mul">Enable multiple backgrounds</label>
                                            </div>
                                            <br />
                                            <div class="form-group row">
                                                <label for="allowed" class="col-sm-1 col-form-label">Captcha Characters</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" id="allowed" name="allowed" placeholder="Allowed Characters" value="<?php echo htmlspecialchars($allowed); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label for="color" class="col-sm-1 col-form-label">Captcha Text Colour</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" id="color" name="color" placeholder="Captcha Text Colour" value="<?php echo htmlspecialchars($color); ?>">
                                                </div>
                                            </div>
                                            <hr />
                                            <div class="panel-title">reCAPTCHA Settings:</div>
                                            <div class="form-group row">
                                                <label for="recaptcha_sitekey" class="col-sm-1 col-form-label">Site Key</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" id="recaptcha_sitekey" name="recaptcha_sitekey" placeholder="Site Key" value="<?php echo htmlspecialchars($recaptcha_sitekey); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label for="recaptcha_secretkey" class="col-sm-1 col-form-label">Secret Key</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" id="recaptcha_secretkey" name="recaptcha_secretkey" placeholder="Secret Key" value="<?php echo htmlspecialchars($recaptcha_secretkey); ?>">
                                                </div>
                                            </div>
                                            <input type="hidden" name="cap" value="cap" />
                                            <div class="form-group">
                                                <div class="col-sm-offset-2 col-sm-10">
                                                    <button type="submit" class="btn btn-default">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div role="tabpanel" class="tab-pane" id="mail">
                                        <form class="form-horizontal" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <div class="form-group">
                                                <div class="panel-title">Registration Settings</div>
                                                <label class="col-sm-2 control-label form-label">Email Verification</label>
                                                <div class="col-sm-10">
                                                    <select class="selectpicker" name="verification">
                                                        <?php
                                                        $options = ['enabled', 'disabled'];
                                                        foreach ($options as $option) {
                                                            $selected = ($verification == $option) ? 'selected' : '';
                                                            echo "<option value=\"$option\" $selected>" . ucfirst($option) . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="panel-title">Mail Settings</div>
                                                <label class="col-sm-2 control-label form-label">Mail Protocol</label>
                                                <div class="col-sm-10">
                                                    <select class="selectpicker" name="protocol">
                                                        <?php
                                                        $options = ['1' => 'PHP Mail', '2' => 'SMTP'];
                                                        foreach ($options as $value => $label) {
                                                            $selected = ($protocol == $value) ? 'selected' : '';
                                                            echo "<option value=\"$value\" $selected>$label</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">SMTP Auth</label>
                                                <div class="col-sm-10">
                                                    <select class="selectpicker" name="auth">
                                                        <?php
                                                        $options = ['true' => 'True', 'false' => 'False'];
                                                        foreach ($options as $value => $label) {
                                                            $selected = ($auth == $value) ? 'selected' : '';
                                                            echo "<option value=\"$value\" $selected>$label</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">SMTP Protocol</label>
                                                <div class="col-sm-10">
                                                    <select class="selectpicker" name="socket">
                                                        <?php
                                                        $options = ['tls' => 'TLS', 'ssl' => 'SSL'];
                                                        foreach ($options as $value => $label) {
                                                            $selected = ($socket == $value) ? 'selected' : '';
                                                            echo "<option value=\"$value\" $selected>$label</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">SMTP Host</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" placeholder="eg smtp.gmail.com" name="smtp_host" value="<?php echo htmlspecialchars($smtp_host); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">SMTP Port</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="smtp_port" placeholder="eg 465 for SSL or 587 for TLS" value="<?php echo htmlspecialchars($smtp_port); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">SMTP User</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" name="smtp_user" placeholder="eg user@gmail.com" value="<?php echo htmlspecialchars($smtp_username); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">SMTP Password</label>
                                                <div class="col-sm-10">
                                                    <input type="password" class="form-control" id="smtp_pass" name="smtp_pass" placeholder="Email password (leave blank for OAuth)" value="<?php echo htmlspecialchars($smtp_password); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label form-label">OAuth Status</label>
                                                <div class="col-sm-10">
                                                    <?php if (!empty($oauth_refresh_token)): ?>
                                                        <p class="text-success">OAuth refresh token is set.</p>
                                                    <?php else: ?>
                                                        <p class="text-danger">OAuth refresh token not set. Configure Gmail OAuth to enable SMTP.</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="col-sm-offset-2 col-sm-10">
                                                    <button type="button" id="smtppasstoggle" class="btn btn-default" style="margin-bottom: 2%;">Toggle Password</button>
                                                    <a href="../oauth/google_smtp.php" class="btn btn-info">Configure Gmail OAuth</a>
                                                    <button type="submit" name="test_smtp" class="btn btn-info">Test SMTP</button>
                                                </div>
                                            </div>
                                            <input type="hidden" name="smtp_code" value="smtp">
                                            <div class="form-group">
                                                <div class="col-sm-offset-2 col-sm-10">
                                                    <button type="submit" class="btn btn-default">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row footer">
            <div class="col-md-6 text-left">
                <a href="https://github.com/boxlabss/PASTE" target="_blank">Updates</a> &mdash; <a href="https://github.com/boxlabss/PASTE/issues" target="_blank">Bugs</a>
            </div>
            <div class="col-md-6 text-right">
                Powered by <a href="https://phpaste.sourceforge.io" target="_blank">Paste</a>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/bootstrap-select.js"></script>
    <script>
        $(document).ready(function() {
            $('.selectpicker').selectpicker();
            $('#smtppasstoggle').on('click', function() {
                var smtpPass = $('#smtp_pass');
                if (smtpPass.attr('type') === 'password') {
                    smtpPass.attr('type', 'text');
                } else {
                    smtpPass.attr('type', 'password');
                }
            });
        });
        window.onerror = function(message, source, lineno, colno, error) {
            console.error('JavaScript Error: ' + message + ' at ' + source + ':' + lineno + ':' + colno);
        };
    </script>
</body>
</html>
<?php $pdo = null; ?>