<?php
declare(strict_types=1);
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE> new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/ - https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
session_start([
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'cookie_httponly' => true,
    'use_strict_mode' => true,
]);

ob_start();
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/vendor/autoload.php';

use Google\Client as Google_Client;

try {
    // Restrict to admins
    if (!isset($_SESSION['admin_login']) || !isset($_SESSION['admin_id'])) {
        error_log("oauth/google_smtp.php: Unauthorized access attempt from {$_SERVER['REMOTE_ADDR']}");
        header('Content-Type: application/json; charset=utf-8');
        ob_end_clean();
        echo json_encode([
            'status' => 'error',
            'message' => 'Admin authentication required.',
            'redirect' => '../admin/configuration.php'
        ]);
        exit;
    }

    // CSRF token generation
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Ensure config.php exists
    if (!file_exists(__DIR__ . '/../config.php')) {
        throw new Exception("Missing config.php at ../config.php");
    }
    require_once __DIR__ . '/../config.php';

    // Connect to DB
    if (!isset($dbhost, $dbuser, $dbpassword, $dbname)) {
        throw new Exception("Database configuration missing in config.php.");
    }
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Fetch baseurl for redirect URI
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

    // Handle saving client credentials via AJAX POST
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
        error_log("oauth/google_smtp.php: OAuth credentials saved for client_id={$client_id}");
        header('Content-Type: application/json; charset=utf-8');
        ob_end_clean();
        echo json_encode(['status' => 'success', 'message' => 'OAuth credentials saved. Click "Authorize Gmail SMTP" to proceed.', 'reload' => true]);
        exit;
    }

    // Initialize Google client when needed
    if ((isset($_GET['start']) && !empty($client_id) && !empty($client_secret)) || isset($_GET['code'])) {
        $gclient = new Google_Client();
        $gclient->setClientId($client_id);
        $gclient->setClientSecret($client_secret);
        $gclient->setRedirectUri($redirect_uri);

        // IMPORTANT: use full Gmail scope for SMTP access
        $gclient->setScopes(['https://mail.google.com/']);
        $gclient->setAccessType('offline'); // request refresh token
        $gclient->setPrompt('consent');     // ensure refresh token is returned
        $gclient->setState($_SESSION['csrf_token']);
    }

    // Start OAuth flow: redirect to Google consent screen
    if (isset($_GET['start'])) {
        if (empty($client_id) || empty($client_secret)) {
            throw new Exception("Please save OAuth Client ID and Secret first.");
        }
        $authUrl = $gclient->createAuthUrl();
        error_log("oauth/google_smtp.php: Redirecting to Google OAuth: $authUrl");
        ob_end_clean();
        header('Location: ' . $authUrl);
        exit;
    }

    // OAuth callback: exchange code for tokens
    if (isset($_GET['code'])) {
        if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['csrf_token']) {
            throw new Exception("CSRF validation failed for OAuth callback.");
        }
        if (empty($client_id) || empty($client_secret)) {
            throw new Exception("OAuth Client ID or Secret not set in mail settings.");
        }

        $token = $gclient->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($token['error'])) {
            // Provide a safe message for admin and log details
            error_log("oauth/google_smtp.php: Token error: " . json_encode($token));
            throw new Exception("Failed to obtain access token: " . htmlspecialchars($token['error_description'] ?? $token['error']));
        }

        $new_refresh = $token['refresh_token'] ?? null;
        if (!$new_refresh) {
            // If Google didn't return a refresh token, likely user previously authorized without 'prompt=consent'
            throw new Exception("No refresh token received. Ensure you've used the provided 'Authorize Gmail SMTP' button which forces a fresh consent screen.");
        }

        // Save refresh token to DB
        $stmt = $pdo->prepare("UPDATE mail SET oauth_refresh_token = ? WHERE id = 1");
        $stmt->execute([$new_refresh]);
        error_log("oauth/google_smtp.php: OAuth refresh token saved to DB.");
        ob_end_clean();
        header('Location: ../admin/configuration.php');
        exit;
    }

    // Render HTML
    header('Content-Type: text/html; charset=UTF-8');
    ob_end_flush();

} catch (Exception $e) {
    error_log("oauth/google_smtp.php: Error: " . $e->getMessage());
    header('Content-Type: application/json; charset=utf-8');
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'OAuth error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
        'reload' => true
    ]);
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Paste - Google OAuth Setup for Gmail SMTP</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="shortcut icon" href="../admin/favicon.ico">
    <link href="../admin/css/paste.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <div id="top" class="clearfix">
        <div class="applogo"><a href="../" class="logo">Paste</a></div>
        <ul class="top-right">
            <li class="dropdown link">
                <a href="#" class="profilebox"><b><?php echo htmlspecialchars($_SESSION['admin_login']); ?></b></a>
            </li>
        </ul>
    </div>

    <div class="content">
        <div class="container-widget">
            <div class="panel panel-widget">
                <div class="panel-body">
                    <h2>Google OAuth 2.0 Setup for Gmail SMTP</h2>

                    <form class="form-horizontal" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Client ID</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="client_id" placeholder="Google OAuth Client ID" value="<?php echo htmlspecialchars($client_id); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Client Secret</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="client_secret" placeholder="Google OAuth Client Secret" value="<?php echo htmlspecialchars($client_secret); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <button type="submit" name="save_credentials" class="btn btn-default">Save Credentials</button>
                                <?php if (!empty($client_id) && !empty($client_secret)): ?>
                                    <a href="?start=1" class="btn btn-info">Authorize Gmail SMTP</a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-info" disabled>Authorize Gmail SMTP (save creds first)</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <p><a href="https://console.developers.google.com" target="_blank" rel="noreferrer">Create or manage your Google OAuth credentials</a></p>
                    <p>Redirect URI for Google Cloud Console: <code><?php echo htmlspecialchars($redirect_uri); ?></code></p>

                    <?php if (!empty($refresh_token)): ?>
                        <p><strong>Refresh Token Status:</strong> A refresh token is saved in the database.</p>
                    <?php else: ?>
                        <p><strong>Refresh Token Status:</strong> No refresh token saved. Click "Authorize Gmail SMTP" to obtain one (you'll be redirected to Google).</p>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="row footer">
            <div class="col-md-6 text-left">
                <a href="https://github.com/boxlabss/PASTE" target="_blank" rel="noreferrer">Updates</a> &mdash; <a href="https://github.com/boxlabss/PASTE/issues" target="_blank" rel="noreferrer">Bugs</a>
            </div>
            <div class="col-md-6 text-right">
                Powered by <a href="https://phpaste.sourceforge.io" target="_blank" rel="noreferrer">Paste</a>
            </div>
        </div>
    </div>

    <script src="../admin/js/jquery.min.js"></script>
    <script src="../admin/js/bootstrap.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // AJAX form submit
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[name="save_credentials"]');
            btn.disabled = true;
            const formData = new FormData(this);
            fetch(this.action, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    if (data.status === 'success') {
                        if (data.reload) window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                        if (data.reload) window.location.reload();
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    alert('Request failed: ' + err.message);
                });
        });
    });
    </script>
</body>
</html>
<?php $pdo = null; ?>
