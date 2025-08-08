<?php
ob_start(); // Start output buffering to prevent headers already sent
session_start();

// Include configuration
require_once '../config.php'; // Contains $dbhost, $dbname, $dbuser, $dbpassword, $enablegoog, OAuth constants

// Fetch $baseurl from site_info table
try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("SELECT baseurl FROM site_info WHERE id = 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $baseurl = $result['baseurl'] ?? '';
} catch (PDOException $e) {
    error_log("google.php: Failed to fetch baseurl from site_info: " . $e->getMessage());
    // Fallback: Calculate baseurl dynamically
    $base_path = rtrim(dirname($_SERVER['PHP_SELF'], 2), '/') . '/';
    $baseurl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $base_path;
}

// Ensure $baseurl has trailing slash
$baseurl = rtrim($baseurl, '/') . '/';

// Include Composer autoload
require_once '../oauth/vendor/autoload.php'; // Adjust path if needed

use League\OAuth2\Client\Provider\Google;

// Ensure required OAuth constants are defined
if (!defined('G_CLIENT_ID') || !defined('G_CLIENT_SECRET') || !defined('G_REDIRECT_URI')) {
    error_log("google.php: Google OAuth constants not defined in config.php");
    header('Location: ' . $baseurl . 'login.php?error=' . urlencode('OAuth configuration error'));
    exit;
}

// Initialize Google OAuth provider
$provider = new Google([
    'clientId'     => G_CLIENT_ID,
    'clientSecret' => G_CLIENT_SECRET,
    'redirectUri'  => G_REDIRECT_URI,
    'accessType'   => 'offline',
    'scopes'       => G_SCOPES,
]);

// Handle OAuth initiation
if (isset($_GET['login']) && $_GET['login'] === '1') {
    $authUrl = $provider->getAuthorizationUrl(['prompt' => 'select_account']);
    // Store state in session for CSRF protection
    $_SESSION['oauth2state'] = $provider->getState();
    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Location: ' . $authUrl);
    exit;
}

// Handle callback from Google
if (isset($_GET['code']) && isset($_GET['state']) && isset($_SESSION['oauth2state']) && $_GET['state'] === $_SESSION['oauth2state']) {
    try {
        // Exchange code for access token
        $accessToken = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
        $resourceOwner = $provider->getResourceOwner($accessToken);
        $user = $resourceOwner->toArray();
        $email = $user['email'] ?? '';
        $name = $user['name'] ?? strstr($email, '@', true);
        $oauth_uid = $user['id'] ?? ''; // Google user ID

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email_id = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            // Update existing user
            $_SESSION['token'] = bin2hex(random_bytes(32));
            $_SESSION['username'] = $existingUser['username'];
            $_SESSION['id'] = $existingUser['id'];
            $_SESSION['platform'] = 'Google'; // Track OAuth platform
            $stmt = $pdo->prepare("UPDATE users SET token = ?, oauth_uid = ?, platform = ? WHERE id = ?");
            $stmt->execute([$_SESSION['token'], $oauth_uid, 'Google', $existingUser['id']]);
        } else {
            // Create new user
            $username = strstr($email, '@', true) . '_' . substr(md5(uniqid()), 0, 4);
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("INSERT INTO users (oauth_uid, username, email_id, full_name, platform, token, verified, date, ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$oauth_uid, $username, $email, $name, 'Google', $token, '1', date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR']]);
            $_SESSION['token'] = $token;
            $_SESSION['username'] = $username;
            $_SESSION['id'] = $pdo->lastInsertId();
            $_SESSION['platform'] = 'Google';
        }

        unset($_SESSION['oauth2state']);
        header('Location: ' . $baseurl);
        exit;
    } catch (Exception $e) {
        error_log("google.php: OAuth error: " . $e->getMessage());
        header('Location: ' . $baseurl . 'login.php?error=' . urlencode('OAuth error'));
        exit;
    }
} elseif (isset($_GET['error'])) {
    error_log("google.php: OAuth error from Google: " . $_GET['error']);
    header('Location: ' . $baseurl . 'login.php?error=' . urlencode('OAuth error'));
    exit;
}

header('Location: ' . $baseurl . 'login.php');
exit;

ob_end_flush(); // Flush output buffer
?>