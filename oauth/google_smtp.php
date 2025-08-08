<?php
declare(strict_types=1);
/*
 * Paste <https://github.com/boxlabss/PASTE>
 * Google OAuth 2.0 for Gmail SMTP with Credential Input
 */
session_start([
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'cookie_httponly' => true,
    'use_strict_mode' => true,
]);

ob_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Restrict access to authenticated admins
if (!isset($_SESSION['admin_login']) || !isset($_SESSION['admin_id'])) {
    error_log("Unauthorized access to oauth/google_smtp.php from IP: {$_SERVER['REMOTE_ADDR']}");
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Admin authentication required.',
        'redirect' => '../admin/configuration.php'
    ]);
    exit;
}

// Generate or verify CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check required files
$required_files = [
    '../config.php' => [],
    'vendor/autoload.php' => ['google/apiclient:^2.12']
];
foreach ($required_files as $file => $packages) {
    if (!file_exists($file)) {
        $message = empty($packages) ? "Missing required file: $file" : "Missing Composer dependencies in " . dirname($file) . ". Run: <code>cd oauth && composer require " . implode(' ', $packages) . "</code>";
        error_log("oauth/google_smtp.php: $message");
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit;
    }
}

require_once '../config.php';
require_once 'vendor/autoload.php';

use Google_Client;

try {
    // Connect to database
    global $dbhost, $dbuser, $dbpassword, $dbname;
    if (!isset($dbhost, $dbuser, $dbpassword, $dbname)) {
        throw new Exception("Database configuration missing in config.php.");
    }
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Fetch baseurl from site_info
    $stmt = $pdo->query("SELECT baseurl FROM site_info WHERE id = 1");
    $site_info = $stmt->fetch();
    if (!$site_info || empty($site_info['baseurl'])) {
        throw new Exception("Base URL not found in site_info. Run install.php to set it.");
    }
    $baseurl = rtrim($site_info['baseurl'], '/') . '/';
    $redirect_uri = $baseurl . 'oauth/google_smtp.php';

    // Fetch existing mail settings
    $stmt = $pdo->query("SELECT oauth_client_id, oauth_client_secret, oauth_refresh_token FROM mail WHERE id = 1");
    $mail_settings = $stmt->fetch();
    $client_id = trim($mail_settings['oauth_client_id'] ?? '');
    $client_secret = trim($mail_settings['oauth_client_secret'] ?? '');
    $refresh_token = trim($mail_settings['oauth_refresh_token'] ?? '');

    // Handle form submission for OAuth credentials
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_credentials'])) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("CSRF validation failed for POST request.");
        }
        $client_id = trim($_POST['client_id'] ?? '');
        $client_secret = trim($_POST['client_secret'] ?? '');
        if (empty($client_id) || empty($client_secret)) {
            throw new Exception("Please fill in both Client ID and Client Secret.");
        }
        $stmt = $pdo->prepare("UPDATE mail SET oauth_client_id = ?, oauth_client_secret = ? WHERE id = 1");
        $stmt->execute([$client_id, $client_secret]);
        error_log("oauth/google_smtp.php: OAuth credentials saved successfully for client_id: $client_id");
        ob_end_clean();
        echo json_encode([
            'status' => 'success',
            'message' => 'OAuth credentials saved. Click "Authorize Gmail SMTP" to proceed.',
            'reload' => true
        ]);
        exit;
    }

    // Handle OAuth flow
    if (isset($_GET['start']) && !empty($client_id) && !empty($client_secret)) {
        $client = new Google_Client();
        $client->setClientId($client_id);
        $client->setClientSecret($client_secret);
        $client->setRedirectUri($redirect_uri);
        $client->addScope('https://www.googleapis.com/auth/gmail.send');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $client->setState($_SESSION['csrf_token']);

        $auth_url = $client->createAuthUrl();
        error_log("oauth/google_smtp.php: Initiating OAuth flow, redirecting to: $auth_url");
        ob_end_clean();
        echo json_encode(['status' => 'success', 'redirect' => $auth_url]);
        exit;
    } elseif (isset($_GET['code'])) {
        if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['csrf_token']) {
            throw new Exception("CSRF validation failed for OAuth callback.");
        }
        if (empty($client_id) || empty($client_secret)) {
            throw new Exception("OAuth Client ID or Secret not set in mail settings.");
        }
        $client = new Google_Client();
        $client->setClientId($client_id);
        $client->setClientSecret($client_secret);
        $client->setRedirectUri($redirect_uri);
        $client->addScope('https://www.googleapis.com/auth/gmail.send');

        $access_token = $client->authenticate($_GET['code']);
        if (!$access_token) {
            throw new Exception("Failed to obtain access token.");
        }
        $refresh_token = $access_token['refresh_token'] ?? null;
        if (!$refresh_token) {
            throw new Exception("No refresh token received. Ensure 'access_type=offline' and 'prompt=consent' are set.");
        }
        $stmt = $pdo->prepare("UPDATE mail SET oauth_refresh_token = ? WHERE id = 1");
        $stmt->execute([$refresh_token]);
        error_log("oauth/google_smtp.php: OAuth refresh token saved successfully.");
        ob_end_clean();
        echo json_encode([
            'status' => 'success',
            'message' => 'OAuth refresh token saved successfully.',
            'redirect' => '../admin/configuration.php'
        ]);
        exit;
    } elseif (isset($_GET['start']) && (empty($client_id) || empty($client_secret))) {
        throw new Exception("Please save OAuth Client ID and Secret first.");
    }

    // Display form if no action
    ob_end_flush();
} catch (Exception $e) {
    error_log("oauth/google_smtp.php: Error: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'OAuth error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
        'reload' => true
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Google OAuth Setup for Gmail SMTP</title>
    <link rel="shortcut icon" href="../admin/favicon.ico">
    <link href="../admin/css/paste.css" rel="stylesheet" type="text/css" />
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
                    <li><a href="../admin/admin.php">Settings</a></li>
                    <li><a href="../admin/?logout">Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>

    <div class="content">
        <div class="container-widget">
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-widget">
                        <div class="panel-body">
                            <h2>Google OAuth 2.0 Setup for Gmail SMTP</h2>
                            <form class="form-horizontal" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div class="form-group">
                                    <label class="col-sm-2 control-label form-label">Client ID</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" name="client_id" placeholder="Google OAuth Client ID" value="<?php echo htmlspecialchars($client_id); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label form-label">Client Secret</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" name="client_secret" placeholder="Google OAuth Client Secret" value="<?php echo htmlspecialchars($client_secret); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-offset-2 col-sm-10">
                                        <button type="submit" name="save_credentials" class="btn btn-default">Save Credentials</button>
                                        <a href="?start=1" class="btn btn-info">Authorize Gmail SMTP</a>
                                    </div>
                                </div>
                            </form>
                            <p><a href="https://console.developers.google.com" target="_blank">Create or manage your Google OAuth credentials</a></p>
                            <p>Redirect URI for Google Cloud Console: <code><?php echo htmlspecialchars($redirect_uri); ?></code></p>
                            <?php if ($refresh_token): ?>
                                <p><strong>Refresh Token Status:</strong> A refresh token is saved in the database.</p>
                            <?php else: ?>
                                <p><strong>Refresh Token Status:</strong> No refresh token saved. Complete the OAuth flow to obtain one.</p>
                            <?php endif; ?>
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

    <script type="text/javascript" src="../admin/js/jquery.min.js"></script>
    <script type="text/javascript" src="../admin/js/bootstrap.min.js"></script>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            window.addEventListener('message', function(event) {
                if (event.data.status) {
                    if (event.data.status === 'success') {
                        if (event.data.redirect) {
                            window.location.href = event.data.redirect;
                        } else if (event.data.reload) {
                            window.location.reload();
                        }
                    } else if (event.data.status === 'error') {
                        document.querySelector('.panel-body').insertAdjacentHTML('afterbegin', '<div class="paste-alert alert6" style="text-align: center;">' + event.data.message + '</div>');
                        if (event.data.redirect) {
                            setTimeout(() => window.location.href = event.data.redirect, 2000);
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
<?php $pdo = null; ?>