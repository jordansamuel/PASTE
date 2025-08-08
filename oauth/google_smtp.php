<?php
/*
 * Paste <https://github.com/boxlabss/PASTE>
 * Google OAuth 2.0 for Gmail SMTP with Credential Input
 */
declare(strict_types=1);

session_start([
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'cookie_httponly' => true,
    'use_strict_mode' => true,
]);

ob_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

error_log("oauth/google_smtp.php: Accessed from IP: {$_SERVER['REMOTE_ADDR']}, Session ID: " . session_id() . ", Session: " . json_encode($_SESSION) . ", Query: " . json_encode($_GET));

// Generate or verify CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("oauth/google_smtp.php: Generated CSRF token: {$_SESSION['csrf_token']}");
}

// Check required files and classes
$required_files = [
    '../config.php' => [],
    'vendor/autoload.php' => ['google/apiclient:^2.17']
];
$required_classes = [
    'Google\Client' => 'google/apiclient:^2.17'
];
foreach ($required_files as $file => $packages) {
    if (!file_exists($file)) {
        $message = empty($packages) ? "Missing required file: $file" : "Missing Composer dependencies in " . dirname($file) . ". Run: <code>cd oauth && composer require " . implode(' ', $packages) . "</code>";
        error_log("oauth/google_smtp.php: $message");
        ob_end_clean();
        echo json_encode(['status' => 'error', 'error_code' => 'missing_file', 'message' => $message]);
        exit;
    }
}
require_once '../config.php';
require_once 'vendor/autoload.php';

use Google\Client as Google_Client;

foreach ($required_classes as $class => $packages) {
    if (!class_exists($class)) {
        error_log("oauth/google_smtp.php: $class not found. Run: cd oauth && composer require $packages");
        ob_end_clean();
        echo json_encode(['status' => 'error', 'error_code' => 'missing_dependency', 'message' => "Missing dependency: $class. Run: composer require $packages"]);
        exit;
    }
}

try {
    // Connect to database
    if (!isset($dbhost, $dbuser, $dbpassword, $dbname)) {
        throw new Exception("Database configuration missing in config.php.", 1001);
    }
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    error_log("oauth/google_smtp.php: Database connection established");

    // Validate admin session only for OAuth initiation
    if (isset($_GET['start'])) {
        if (!isset($_SESSION['admin_login']) || !isset($_SESSION['admin_id'])) {
            error_log("oauth/google_smtp.php: Unauthorized access for OAuth start - no admin session. IP: {$_SERVER['REMOTE_ADDR']}");
            ob_end_clean();
            echo json_encode([
                'status' => 'error',
                'error_code' => 'auth_failed',
                'message' => 'Admin authentication required.',
                'redirect' => '../admin/login.php'
            ]);
            exit;
        }
        $stmt = $pdo->prepare("SELECT id FROM admin WHERE user = ?");
        $stmt->execute([$_SESSION['admin_login']]);
        $admin = $stmt->fetch();
        if (!$admin || $admin['id'] != $_SESSION['admin_id']) {
            error_log("oauth/google_smtp.php: Invalid admin session for admin_login: {$_SESSION['admin_login']}, admin_id: {$_SESSION['admin_id']}");
            $_SESSION = [];
            session_destroy();
            ob_end_clean();
            echo json_encode([
                'status' => 'error',
                'error_code' => 'invalid_session',
                'message' => 'Invalid admin session. Please log in again.',
                'redirect' => '../admin/login.php'
            ]);
            exit;
        }
    }

    // Fetch baseurl from site_info
    $stmt = $pdo->query("SELECT baseurl FROM site_info WHERE id = 1");
    $site_info = $stmt->fetch() ?: [];
    $baseurl = trim($site_info['baseurl'] ?? '');
    if (empty($baseurl)) {
        throw new Exception("Base URL not found in site_info. Run install.php to set it.", 1002);
    }
    $baseurl = rtrim($baseurl, '/') . '/';
    $redirect_uri = $baseurl . 'oauth/google_smtp.php';
    error_log("oauth/google_smtp.php: Base URL: $baseurl, Redirect URI: $redirect_uri");

    // Ensure mail table has a record
    $stmt = $pdo->query("SELECT COUNT(*) FROM mail WHERE id = 1");
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO mail (id, verification, smtp_host, smtp_port, smtp_username, smtp_password, socket, protocol, auth, oauth_client_id, oauth_client_secret, oauth_refresh_token) VALUES (1, '', '', '', '', '', '', '', '', '', '', '')");
        $stmt->execute();
        error_log("oauth/google_smtp.php: Created default mail record with id = 1");
    }

    // Fetch and validate mail settings
    $stmt = $pdo->query("SELECT oauth_client_id, oauth_client_secret, oauth_refresh_token FROM mail WHERE id = 1");
    $mail_settings = $stmt->fetch() ?: [];
    $required_fields = ['oauth_client_id', 'oauth_client_secret', 'oauth_refresh_token'];
    foreach ($required_fields as $field) {
        if (!array_key_exists($field, $mail_settings)) {
            $mail_settings[$field] = '';
        }
    }
    $client_id = trim($mail_settings['oauth_client_id'] ?? '');
    $client_secret = trim($mail_settings['oauth_client_secret'] ?? '');
    $refresh_token = trim($mail_settings['oauth_refresh_token'] ?? '');
    error_log("oauth/google_smtp.php: Mail settings - Client ID: $client_id, Refresh Token: " . ($refresh_token ? substr($refresh_token, 0, 10) . '...' : 'none'));

    // Handle OAuth flow
    if (isset($_GET['start'])) {
        if (empty($client_id) || empty($client_secret)) {
            throw new Exception("Please save OAuth Client ID and Secret first.", 1011);
        }
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
        header('Location: ' . $auth_url);
        exit;
    } elseif (isset($_GET['code'])) {
        if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['csrf_token']) {
            error_log("oauth/google_smtp.php: CSRF validation failed for OAuth callback. Received: " . ($_GET['state'] ?? 'none') . ", Expected: {$_SESSION['csrf_token']}");
            throw new Exception("CSRF validation failed for OAuth callback.", 1007);
        }
        if (empty($client_id) || empty($client_secret)) {
            throw new Exception("OAuth Client ID or Secret not set in mail settings.", 1008);
        }
        $client = new Google_Client();
        $client->setClientId($client_id);
        $client->setClientSecret($client_secret);
        $client->setRedirectUri($redirect_uri);
        $client->addScope('https://www.googleapis.com/auth/gmail.send');
        $access_token = $client->authenticate($_GET['code']);
        if (!$access_token) {
            throw new Exception("Failed to obtain access token. Check Client ID, Client Secret, and Redirect URI.", 1009);
        }
        $refresh_token = $access_token['refresh_token'] ?? null;
        if (!$refresh_token) {
            throw new Exception("No refresh token received. Ensure 'access_type=offline' and 'prompt=consent' are set in Google Cloud Console.", 1010);
        }
        $stmt = $pdo->prepare("UPDATE mail SET oauth_refresh_token = ? WHERE id = 1");
        $rows_affected = $stmt->execute([$refresh_token]);
        error_log("oauth/google_smtp.php: OAuth refresh token update attempted. Rows affected: $rows_affected, refresh_token: " . substr($refresh_token, 0, 10) . "...");
        if ($rows_affected === 0) {
            throw new Exception("Failed to update refresh token in database. No rows affected. Check if mail table record exists with id=1.", 1013);
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        error_log("oauth/google_smtp.php: OAuth refresh token saved successfully, new CSRF token: {$_SESSION['csrf_token']}");
        ob_end_clean();
        header('Location: ../admin/configuration.php?msg=' . urlencode('OAuth refresh token saved successfully.'));
        exit;
    } elseif (isset($_GET['refresh'])) {
        // Handle token refresh requests from mail.php
        if (empty($client_id) || empty($client_secret) || empty($refresh_token)) {
            error_log("oauth/google_smtp.php: Missing OAuth credentials for refresh");
            ob_end_clean();
            echo json_encode(['status' => 'error', 'error_code' => 'missing_credentials', 'message' => 'OAuth credentials missing.']);
            exit;
        }
        $client = new Google_Client();
        $client->setClientId($client_id);
        $client->setClientSecret($client_secret);
        $client->setRedirectUri($redirect_uri);
        $client->addScope('https://www.googleapis.com/auth/gmail.send');
        $client->setAccessToken(['refresh_token' => $refresh_token]);
        $new_token = $client->fetchAccessTokenWithRefreshToken($refresh_token);
        if (isset($new_token['error'])) {
            error_log("oauth/google_smtp.php: Token refresh failed: " . json_encode($new_token));
            ob_end_clean();
            echo json_encode(['status' => 'error', 'error_code' => 'refresh_failed', 'message' => 'Token refresh failed: ' . ($new_token['error_description'] ?? 'Unknown error')]);
            exit;
        }
        error_log("oauth/google_smtp.php: Token refreshed successfully: " . json_encode($new_token));
        ob_end_clean();
        echo json_encode(['status' => 'success', 'access_token' => $new_token]);
        exit;
    }

    // Return current settings for AJAX requests
    ob_end_clean();
    echo json_encode([
        'status' => 'success',
        'error_code' => null,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'refresh_token' => $refresh_token ? true : false,
        'redirect_uri' => $redirect_uri
    ]);
    exit;
} catch (Exception $e) {
    $error_code = $e->getCode() ?: 1000;
    error_log("oauth/google_smtp.php: Error (code $error_code): " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'error_code' => 'error_' . $error_code,
        'message' => 'OAuth error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
        'redirect' => '../admin/configuration.php?error=' . urlencode($e->getMessage())
    ]);
    exit;
} finally {
    $pdo = null;
}
?> 