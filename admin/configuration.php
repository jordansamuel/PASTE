<?php
/*
 * Paste Admin https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 *
 * https://phpaste.sourceforge.io/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in LICENCE for more details.
 */
declare(strict_types=1);

ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'cookie_httponly' => true,
        'use_strict_mode' => true,
        'cookie_samesite' => 'Strict',
    ]);
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
error_log("configuration.php: Session started, ID: " . session_id() . ", CSRF token: {$_SESSION['csrf_token']}, HTTPS: " . (isset($_SERVER['HTTPS']) ? 'on' : 'off'));

if (!isset($_SESSION['admin_login']) || !isset($_SESSION['admin_id'])) {
    error_log("configuration.php: Session validation failed - admin_login or admin_id not set. Session: " . json_encode($_SESSION));
    ob_end_clean();
    header("Location: index.php");
    exit();
}

ini_set('display_errors', '0');
ini_set('log_errors', '1');

$date = date('Y-m-d H:i:s');
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
require_once '../config.php';
require_once '../mail/mail.php';

$oauth_autoloader = __DIR__ . '/../oauth/vendor/autoload.php';
if (!file_exists($oauth_autoloader)) {
    error_log("configuration.php: OAuth autoloader not found");
    ob_end_clean();
    die("OAuth autoloader not found. Run: <code>cd oauth && composer require google/apiclient:^2.17 league/oauth2-client:^2.7 league/oauth2-google:^4.0</code>");
}
require_once $oauth_autoloader;

use Google\Client as Google_Client;

$required_classes = [
    'Google\Client' => 'google/apiclient:^2.17',
    'PHPMailer\PHPMailer\PHPMailer' => 'phpmailer/phpmailer:^6.9',
    'League\OAuth2\Client\Provider\Google' => 'league/oauth2-client:^2.7 league/oauth2-google:^4.0'
];
foreach ($required_classes as $class => $packages) {
    if (!class_exists($class)) {
        error_log("configuration.php: $class not found. Run: cd oauth && composer require $packages");
        ob_end_clean();
        die('<div class="alert alert-danger text-center">OAuth configuration error: ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . ' not found. Run: composer require ' . htmlspecialchars($packages, ENT_QUOTES, 'UTF-8') . '</div>');
    }
}

try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $stmt = $pdo->prepare("SELECT id FROM admin WHERE user = ?");
    $stmt->execute([$_SESSION['admin_login']]);
    $admin = $stmt->fetch();
    if (!$admin || $admin['id'] != $_SESSION['admin_id']) {
        error_log("configuration.php: Invalid admin session for admin_login: {$_SESSION['admin_login']}, admin_id: {$_SESSION['admin_id']}");
        $_SESSION = [];
        session_destroy();
        ob_end_clean();
        header('Location: index.php');
        exit;
    }

    $stmt = $pdo->query("SELECT MAX(id) AS last_id FROM admin_history");
    $last_id = $stmt->fetch()['last_id'] ?? null;
    if ($last_id) {
        $stmt = $pdo->prepare("SELECT last_date, ip FROM admin_history WHERE id = ?");
        $stmt->execute([$last_id]);
        $row = $stmt->fetch();
        $last_date = $row['last_date'] ?? null;
        $last_ip = $row['ip'] ?? null;
    }
    if (($last_ip ?? '') !== $ip || ($last_date ?? '') !== $date) {
        $stmt = $pdo->prepare("INSERT INTO admin_history (last_date, ip) VALUES (?, ?)");
        $stmt->execute([$date, $ip]);
    }

    $stmt = $pdo->query("SELECT * FROM site_info WHERE id = 1");
    $row = $stmt->fetch() ?: [];
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

    $stmt = $pdo->query("SELECT * FROM captcha WHERE id = 1");
    $row = $stmt->fetch() ?: [];
    $cap_e = $row['cap_e'] ?? '';
    $mode = $row['mode'] ?? '';
    $recaptcha_version = $row['recaptcha_version'] ?? 'v2';
    $mul = $row['mul'] ?? '';
    $allowed = $row['allowed'] ?? '';
    $color = $row['color'] ?? '';
    $recaptcha_sitekey = $row['recaptcha_sitekey'] ?? '';
    $recaptcha_secretkey = $row['recaptcha_secretkey'] ?? '';

    $stmt = $pdo->query("SELECT * FROM site_permissions WHERE id = 1");
    $row = $stmt->fetch() ?: [];
    $disableguest = trim($row['disableguest'] ?? '');
    $siteprivate = trim($row['siteprivate'] ?? '');

    $stmt = $pdo->query("SELECT * FROM mail WHERE id = 1");
    $row = $stmt->fetch() ?: [];
    $required_fields = ['verification', 'smtp_host', 'smtp_username', 'smtp_password', 'smtp_port', 'protocol', 'auth', 'socket', 'oauth_client_id', 'oauth_client_secret', 'oauth_refresh_token'];
    foreach ($required_fields as $field) {
        if (!array_key_exists($field, $row)) {
            $row[$field] = '';
        }
    }
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
    $oauth_status = $oauth_refresh_token ? 'OAuth refresh token is set.' : 'OAuth refresh token not set. Configure Gmail OAuth if using smtp.gmail.com.';
    $redirect_uri = $baseurl ? rtrim($baseurl, '/') . '/oauth/google_smtp.php' : '';

    $msg = '';
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        error_log("configuration.php: POST request received with CSRF token: " . ($_POST['csrf_token'] ?? 'none') . ", Session CSRF: {$_SESSION['csrf_token']}, Session ID: " . session_id());
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            error_log("configuration.php: CSRF validation failed. Received: " . ($_POST['csrf_token'] ?? 'none') . ", Expected: {$_SESSION['csrf_token']}, Session: " . json_encode($_SESSION));
            $msg = '<div class="alert alert-danger text-center">CSRF validation failed. Please try again.</div>';
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => $msg]);
                exit;
            }
        } else {
            error_log("configuration.php: CSRF validation passed");
            if (isset($_POST['test_recaptcha'])) {
                error_log("configuration.php: Test reCAPTCHA requested");
                $recaptcha_sitekey = trim($_POST['recaptcha_sitekey'] ?? '');
                $recaptcha_secretkey = trim($_POST['recaptcha_secretkey'] ?? '');
                $recaptcha_version = trim($_POST['recaptcha_version'] ?? 'v2');
                if (empty($recaptcha_sitekey) || empty($recaptcha_secretkey)) {
                    $msg = '<div class="alert alert-danger text-center">reCAPTCHA Site Key and Secret Key are required for testing.</div>';
                    error_log("configuration.php: Missing reCAPTCHA keys for test");
                } else {
                    $verify_url = "https://www.google.com/recaptcha/api/siteverify?secret=" . urlencode($recaptcha_secretkey) . "&response=test";
                    $ch = curl_init($verify_url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                    $response = curl_exec($ch);
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curl_error = curl_error($ch);
                    curl_close($ch);
                    if ($response === false || $http_code != 200) {
                        $msg = '<div class="alert alert-danger text-center">Failed to verify reCAPTCHA keys: ' . htmlspecialchars($curl_error ?: 'No response', ENT_QUOTES, 'UTF-8') . '</div>';
                        error_log("configuration.php: reCAPTCHA test failed: HTTP Code: $http_code, Error: " . ($curl_error ?: 'No response'));
                    } else {
                        $response = json_decode($response, true);
                        if (($response['success'] ?? null) === false && isset($response['error-codes']) && in_array('invalid-input-secret', $response['error-codes'])) {
                            $msg = '<div class="alert alert-danger text-center">Invalid reCAPTCHA Secret Key. Please verify your keys.</div>';
                            error_log("configuration.php: reCAPTCHA test failed: Invalid secret key");
                        } else {
                            if ($recaptcha_version === 'v3' && isset($response['score']) && $response['score'] < 0.5) {
                                $msg = '<div class="alert alert-danger text-center">reCAPTCHA v3 test failed: Score ' . htmlspecialchars((string)$response['score'], ENT_QUOTES, 'UTF-8') . ' is below threshold (0.5).</div>';
                                error_log("configuration.php: reCAPTCHA v3 test failed: Score " . $response['score']);
                            } else {
                                $msg = '<div class="alert alert-success text-center">reCAPTCHA keys are valid' . ($recaptcha_version === 'v3' ? ' (Score: ' . htmlspecialchars((string)($response['score'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') . ')' : '') . '.</div>';
                                error_log("configuration.php: reCAPTCHA test successful" . ($recaptcha_version === 'v3' ? ", Score: " . $response['score'] : ""));
                            }
                        }
                    }
                }
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    ob_end_clean();
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['message' => $msg]);
                    exit;
                }
            } elseif (isset($_POST['test_smtp'])) {
                error_log("configuration.php: Test SMTP requested");
                header('Content-Type: application/json; charset=utf-8');
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    error_log("configuration.php: Invalid or missing admin email: $email");
                    ob_end_clean();
                    echo json_encode(['status' => 'error', 'message' => '<div class="alert alert-danger text-center">Invalid or missing Admin Email in Site Info. Please set a valid email address.</div>']);
                    exit;
                } elseif ($protocol === '2' && $smtp_host === 'smtp.gmail.com' && (empty($oauth_client_id) || empty($oauth_client_secret) || empty($oauth_refresh_token))) {
                    error_log("configuration.php: Missing OAuth credentials for Gmail SMTP");
                    ob_end_clean();
                    echo json_encode(['status' => 'error', 'message' => '<div class="alert alert-danger text-center">OAuth credentials missing for Gmail SMTP. Please configure Client ID, Client Secret, and authorize Gmail SMTP.</div>']);
                    exit;
                } elseif ($protocol === '2' && $smtp_host !== 'smtp.gmail.com' && $auth === 'true' && (empty($smtp_username) || empty($smtp_password))) {
                    error_log("configuration.php: Missing SMTP username or password for $smtp_host");
                    ob_end_clean();
                    echo json_encode(['status' => 'error', 'message' => '<div class="alert alert-danger text-center">SMTP Username and Password are required for non-Gmail SMTP servers with authentication.</div>']);
                    exit;
                } elseif ($protocol === '1' && !ini_get('sendmail_path')) {
                    error_log("configuration.php: sendmail_path not configured in php.ini");
                    ob_end_clean();
                    echo json_encode(['status' => 'error', 'message' => '<div class="alert alert-danger text-center">PHP Mail selected, but sendmail_path is not configured in php.ini.</div>']);
                    exit;
                } else {
                    $test_message = "
                        <html>
                        <head><style>body { font-family: Arial, sans-serif; color: #333; }</style></head>
                        <body>
                            <div style='text-align: center;'>
                                <img src='$baseurl/images/logo.png' alt='$site_name Logo'>
                                <h2>Test Email from $site_name</h2>
                            </div>
                            <p>This is a test email sent from your Pastebin installation to verify mail settings.</p>
                        </body>
                        </html>";
                    $mail_result = send_mail($email, "Test Email from $site_name", $test_message, $site_name, $_SESSION['csrf_token']);
                    error_log("configuration.php: Test SMTP result: " . json_encode($mail_result));
                    ob_end_clean();
                    if (($mail_result['status'] ?? 'error') === 'success') {
                        echo json_encode(['status' => 'success', 'message' => '<div class="alert alert-success text-center">Test email sent successfully to ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '.</div>']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => '<div class="alert alert-danger text-center">Failed to send test email: ' . htmlspecialchars($mail_result['message'] ?? 'Unknown error', ENT_QUOTES, 'UTF-8') . '</div>']);
                    }
                    exit;
                }
            } elseif (isset($_POST['save_oauth_credentials'])) {
                $client_id = trim($_POST['client_id'] ?? '');
                $client_secret = trim($_POST['client_secret'] ?? '');
                if (empty($client_id) || empty($client_secret)) {
                    $msg = '<div class="alert alert-danger text-center">Please fill in both Client ID and Client Secret.</div>';
                    error_log("configuration.php: Missing OAuth Client ID or Secret");
                } elseif (!preg_match('/^[0-9a-zA-Z\-]+\.apps\.googleusercontent\.com$/', $client_id)) {
                    $msg = '<div class="alert alert-danger text-center">Invalid Client ID format. It should look like \'1234567890-abcdef.apps.googleusercontent.com\'.</div>';
                    error_log("configuration.php: Invalid OAuth Client ID format: $client_id");
                } elseif (!preg_match('/^[0-9a-zA-Z\-_]+$/', $client_secret)) {
                    $msg = '<div class="alert alert-danger text-center">Invalid Client Secret format. It should contain only letters, numbers, hyphens, and underscores.</div>';
                    error_log("configuration.php: Invalid OAuth Client Secret format: $client_secret");
                } else {
                    try {
                        $stmt = $pdo->prepare("UPDATE mail SET oauth_client_id = ?, oauth_client_secret = ? WHERE id = 1");
                        $rows_affected = $stmt->execute([$client_id, $client_secret]);
                        error_log("configuration.php: OAuth credentials update attempted. Rows affected: $rows_affected, client_id: $client_id");
                        if ($rows_affected === 0) {
                            $msg = '<div class="alert alert-danger text-center">Failed to update OAuth credentials in database. No rows affected.</div>';
                        } else {
                            $oauth_client_id = $client_id;
                            $oauth_client_secret = $client_secret;
                            $msg = '<div class="alert alert-success text-center">OAuth credentials saved successfully.</div>';
                        }
                    } catch (PDOException $e) {
                        error_log("configuration.php: OAuth credentials update error: " . $e->getMessage());
                        $msg = '<div class="alert alert-danger text-center">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
                    }
                }
            } elseif (isset($_POST['cap'])) {
                $cap_e = trim($_POST['cap_e'] ?? '');
                $mode = trim($_POST['mode'] ?? '');
                $recaptcha_version = trim($_POST['recaptcha_version'] ?? 'v2');
                $mul = trim($_POST['mul'] ?? '');
                $allowed = trim($_POST['allowed'] ?? '');
                $color = trim($_POST['color'] ?? '');
                $recaptcha_sitekey = trim($_POST['recaptcha_sitekey'] ?? '');
                $recaptcha_secretkey = trim($_POST['recaptcha_secretkey'] ?? '');
                if ($cap_e == 'on' && $mode == 'reCAPTCHA' && (empty($recaptcha_sitekey) || empty($recaptcha_secretkey))) {
                    $msg = '<div class="alert alert-danger text-center">reCAPTCHA Site Key and Secret Key are required when reCAPTCHA is enabled.</div>';
                    error_log("configuration.php: Missing reCAPTCHA keys for mode: $mode");
                } else {
                    try {
                        $stmt = $pdo->prepare("UPDATE captcha SET cap_e = ?, mode = ?, recaptcha_version = ?, mul = ?, allowed = ?, color = ?, recaptcha_sitekey = ?, recaptcha_secretkey = ? WHERE id = 1");
                        $stmt->execute([$cap_e, $mode, $recaptcha_version, $mul, $allowed, $color, $recaptcha_sitekey, $recaptcha_secretkey]);
                        $msg = '<div class="alert alert-success text-center">Captcha settings saved</div>';
                        error_log("configuration.php: Captcha settings updated successfully");
                    } catch (PDOException $e) {
                        error_log("configuration.php: Captcha update error: " . $e->getMessage());
                        $msg = '<div class="alert alert-danger text-center">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
                    }
                }
            } elseif (isset($_POST['manage'])) {
                $site_name = filter_var(trim($_POST['site_name'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
                $title = filter_var(trim($_POST['title'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
                $baseurl = filter_var(trim($_POST['baseurl'] ?? ''), FILTER_SANITIZE_URL);
                $des = filter_var(trim($_POST['des'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
                $keyword = htmlspecialchars(trim($_POST['keyword'] ?? ''), ENT_QUOTES, 'UTF-8');
                $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
                $twit = htmlspecialchars(trim($_POST['twit'] ?? ''), ENT_QUOTES, 'UTF-8');
                $face = htmlspecialchars(trim($_POST['face'] ?? ''), ENT_QUOTES, 'UTF-8');
                $gplus = htmlspecialchars(trim($_POST['gplus'] ?? ''), ENT_QUOTES, 'UTF-8');
                $ga = htmlspecialchars(trim($_POST['ga'] ?? ''), ENT_QUOTES, 'UTF-8');
                $additional_scripts = filter_var(trim($_POST['additional_scripts'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
                try {
                    $stmt = $pdo->prepare("UPDATE site_info SET title = ?, des = ?, baseurl = ?, keyword = ?, site_name = ?, email = ?, twit = ?, face = ?, gplus = ?, ga = ?, additional_scripts = ? WHERE id = 1");
                    $stmt->execute([$title, $des, $baseurl, $keyword, $site_name, $email, $twit, $face, $gplus, $ga, $additional_scripts]);
                    $msg = '<div class="alert alert-success text-center">Configuration saved</div>';
                    error_log("configuration.php: Site info updated successfully");
                } catch (PDOException $e) {
                    error_log("configuration.php: Site info update error: " . $e->getMessage());
                    $msg = '<div class="alert alert-danger text-center">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
                }
            } elseif (isset($_POST['permissions'])) {
                $disableguest = trim($_POST['disableguest'] ?? '');
                $siteprivate = trim($_POST['siteprivate'] ?? '');
                try {
                    $stmt = $pdo->prepare("UPDATE site_permissions SET disableguest = ?, siteprivate = ? WHERE id = 1");
                    $stmt->execute([$disableguest, $siteprivate]);
                    $msg = '<div class="alert alert-success text-center">Site permissions saved</div>';
                    error_log("configuration.php: Site permissions updated successfully");
                } catch (PDOException $e) {
                    error_log("configuration.php: Permissions update error: " . $e->getMessage());
                    $msg = '<div class="alert alert-danger text-center">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
                }
            } elseif (isset($_POST['smtp_code'])) {
                $verification = trim($_POST['verification'] ?? '');
                $smtp_host = trim($_POST['smtp_host'] ?? '');
                $smtp_port = trim($_POST['smtp_port'] ?? '');
                $smtp_username = trim($_POST['smtp_user'] ?? '');
                $smtp_password = trim($_POST['smtp_pass'] ?? '');
                $socket = trim($_POST['socket'] ?? '');
                $auth = trim($_POST['auth'] ?? '');
                $protocol = trim($_POST['protocol'] ?? '');
                if ($protocol === '2' && $smtp_host !== 'smtp.gmail.com' && $auth === 'true' && (empty($smtp_username) || empty($smtp_password))) {
                    $msg = '<div class="alert alert-danger text-center">SMTP Username and Password are required for non-Gmail SMTP servers with authentication.</div>';
                    error_log("configuration.php: Missing SMTP username or password for $smtp_host");
                } elseif ($protocol === '1' && !ini_get('sendmail_path')) {
                    $msg = '<div class="alert alert-danger text-center">PHP Mail selected, but sendmail_path is not configured in php.ini.</div>';
                    error_log("configuration.php: sendmail_path not configured in php.ini");
                } elseif ($protocol === '2' && empty($smtp_host)) {
                    $msg = '<div class="alert alert-danger text-center">SMTP Host is required for SMTP protocol.</div>';
                    error_log("configuration.php: Missing SMTP host");
                } elseif ($protocol === '2' && empty($smtp_port)) {
                    $msg = '<div class="alert alert-danger text-center">SMTP Port is required for SMTP protocol.</div>';
                    error_log("configuration.php: Missing SMTP port");
                } else {
                    try {
                        $stmt = $pdo->prepare("UPDATE mail SET verification = ?, smtp_host = ?, smtp_port = ?, smtp_username = ?, smtp_password = ?, socket = ?, protocol = ?, auth = ? WHERE id = 1");
                        $stmt->execute([$verification, $smtp_host, $smtp_port, $smtp_username, $smtp_password, $socket, $protocol, $auth]);
                        $msg = '<div class="alert alert-success text-center">Mail settings updated</div>';
                        error_log("configuration.php: Mail settings updated successfully");
                    } catch (PDOException $e) {
                        error_log("configuration.php: Mail settings update error: " . $e->getMessage());
                        $msg = '<div class="alert alert-danger text-center">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
                    }
                }
            }
            if (strpos($msg, 'alert-success') !== false) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                error_log("configuration.php: CSRF token regenerated: {$_SESSION['csrf_token']}, Session ID: " . session_id());
            }
        }
        // For non-AJAX requests, fall through to render
    }

    if (isset($_GET['msg'])) {
        $msg = '<div class="alert alert-success text-center">' . htmlspecialchars(urldecode($_GET['msg'] ?? ''), ENT_QUOTES, 'UTF-8') . '</div>';
    } elseif (isset($_GET['error'])) {
        $msg = '<div class="alert alert-danger text-center">' . htmlspecialchars(urldecode($_GET['error'] ?? ''), ENT_QUOTES, 'UTF-8') . '</div>';
    }

} catch (PDOException $e) {
    error_log("configuration.php: Database error: " . $e->getMessage());
    ob_end_clean();
    die("Unable to connect to database: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
} finally {
    // Keep variables like $baseurl/$site_name in scope for HTML; we only close PDO connection here.
    $pdo = null;
}

// --- Active tab persistence (server-side default) ---
$activeTab = $_POST['active_tab'] ?? $_GET['tab'] ?? '';
$validTabs = ['siteinfo','permissions','captcha','mail'];
if (!in_array($activeTab, $validTabs, true)) {
    // Also allow hash from URL if present (e.g. #mail) on first paint:
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '#') !== false) {
        $hash = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '#') + 1);
        if (in_array($hash, $validTabs, true)) {
            $activeTab = $hash;
        }
    }
    if (!$activeTab) $activeTab = 'siteinfo';
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paste - Configuration</title>
    <link rel="shortcut icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <style>
      :root{
        --bg:#0f1115; --card:#141821; --muted:#7f8da3; --border:#1f2633; --accent:#0d6efd;
      }
      body{background:var(--bg);color:#e6edf3;}
      .navbar{background:#121826!important;position:sticky;top:0;z-index:1030}
      .navbar .navbar-brand{font-weight:600}
      .offcanvas-nav{width:280px;background:#0f1523;color:#dbe5f5}
      .offcanvas-nav .list-group-item{background:transparent;border:0;color:#dbe5f5}
      .offcanvas-nav .list-group-item:hover{background:#0e1422}
      .sidebar-desktop{position:sticky; top:1rem; background:#121826;border:1px solid var(--border);border-radius:12px;padding:12px}
      .sidebar-desktop .list-group-item{background:transparent;color:#dbe5f5;border:0;border-radius:10px;padding:.65rem .8rem}
      .sidebar-desktop .list-group-item:hover{background:#0e1422}
      .sidebar-desktop .list-group-item.active{background:#0d6efd;color:#fff}
      .main-content{padding:16px}
      .card{background:var(--card);border:1px solid var(--border);border-radius:12px}
      .form-control,.form-select{background:#0e1422;border-color:var(--border);color:#e6edf3}
      .form-control:focus,.form-select:focus{border-color:var(--accent);box-shadow:0 0 0 .25rem rgba(13,110,253,.25)}
      .btn-outline-primary{border-color:#0d6efd;color:#0d6efd}
      .btn-outline-primary:hover{background:#0d6efd;color:#fff}
      .nav-tabs .nav-link{color:#c6d4f0}
      .nav-tabs .nav-link.active{color:#fff;background:#101521;border-color:var(--border) var(--border) transparent}
      .table{color:#e6edf3}
      .table thead th{background:#101521;color:#c6d4f0;border-color:var(--border)}
      .table td,.table th{border-color:var(--border)}
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-outline-primary d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#navOffcanvas" aria-controls="navOffcanvas">
        <i class="bi bi-list"></i>
      </button>
      <a class="navbar-brand" href="../">Paste</a>
    </div>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <?php echo htmlspecialchars($_SESSION['admin_login'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="admin.php">Settings</a></li>
            <li><a class="dropdown-item" href="?logout">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Mobile offcanvas nav -->
<div class="offcanvas offcanvas-start offcanvas-nav" tabindex="-1" id="navOffcanvas">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Admin Menu</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <div class="list-group">
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/dashboard.php'); ?>"><i class="bi bi-house me-2"></i>Dashboard</a>
      <a class="list-group-item active" href="<?php echo htmlspecialchars($baseurl.'admin/configuration.php'); ?>"><i class="bi bi-gear me-2"></i>Configuration</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/interface.php'); ?>"><i class="bi bi-eye me-2"></i>Interface</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/admin.php'); ?>"><i class="bi bi-person me-2"></i>Admin Account</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/pastes.php'); ?>"><i class="bi bi-clipboard me-2"></i>Pastes</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/users.php'); ?>"><i class="bi bi-people me-2"></i>Users</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/ipbans.php'); ?>"><i class="bi bi-ban me-2"></i>IP Bans</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/stats.php'); ?>"><i class="bi bi-graph-up me-2"></i>Statistics</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/ads.php'); ?>"><i class="bi bi-currency-pound me-2"></i>Ads</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/pages.php'); ?>"><i class="bi bi-file-earmark me-2"></i>Pages</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/sitemap.php'); ?>"><i class="bi bi-map me-2"></i>Sitemap</a>
      <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/tasks.php'); ?>"><i class="bi bi-list-task me-2"></i>Tasks</a>
    </div>
  </div>
</div>

<div class="container-fluid my-2">
  <div class="row g-2">
    <!-- Desktop sidebar -->
    <div class="col-lg-2 d-none d-lg-block">
      <div class="sidebar-desktop">
        <div class="list-group">
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/dashboard.php'); ?>"><i class="bi bi-house me-2"></i>Dashboard</a>
          <a class="list-group-item active" href="<?php echo htmlspecialchars($baseurl.'admin/configuration.php'); ?>"><i class="bi bi-gear me-2"></i>Configuration</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/interface.php'); ?>"><i class="bi bi-eye me-2"></i>Interface</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/admin.php'); ?>"><i class="bi bi-person me-2"></i>Admin Account</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/pastes.php'); ?>"><i class="bi bi-clipboard me-2"></i>Pastes</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/users.php'); ?>"><i class="bi bi-people me-2"></i>Users</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/ipbans.php'); ?>"><i class="bi bi-ban me-2"></i>IP Bans</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/stats.php'); ?>"><i class="bi bi-graph-up me-2"></i>Statistics</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/ads.php'); ?>"><i class="bi bi-currency-pound me-2"></i>Ads</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/pages.php'); ?>"><i class="bi bi-file-earmark me-2"></i>Pages</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/sitemap.php'); ?>"><i class="bi bi-map me-2"></i>Sitemap</a>
          <a class="list-group-item" href="<?php echo htmlspecialchars($baseurl.'admin/tasks.php'); ?>"><i class="bi bi-list-task me-2"></i>Tasks</a>
        </div>
      </div>
    </div>

    <div class="col-lg-10">
      <div class="card">
        <div class="card-body">
          <div id="message-container"><?php if (isset($msg)) echo $msg; ?></div>

          <ul class="nav nav-tabs mb-3" id="configTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link <?php echo $activeTab==='siteinfo'?'active':''; ?>" id="siteinfo-tab" data-bs-toggle="tab" data-bs-target="#siteinfo" type="button" role="tab" aria-controls="siteinfo" aria-selected="<?php echo $activeTab==='siteinfo'?'true':'false'; ?>">Site Info</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link <?php echo $activeTab==='permissions'?'active':''; ?>" id="permissions-tab" data-bs-toggle="tab" data-bs-target="#permissions" type="button" role="tab" aria-controls="permissions" aria-selected="<?php echo $activeTab==='permissions'?'true':'false'; ?>">Permissions</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link <?php echo $activeTab==='captcha'?'active':''; ?>" id="captcha-tab" data-bs-toggle="tab" data-bs-target="#captcha" type="button" role="tab" aria-controls="captcha" aria-selected="<?php echo $activeTab==='captcha'?'true':'false'; ?>">Captcha Settings</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link <?php echo $activeTab==='mail'?'active':''; ?>" id="mail-tab" data-bs-toggle="tab" data-bs-target="#mail" type="button" role="tab" aria-controls="mail" aria-selected="<?php echo $activeTab==='mail'?'true':'false'; ?>">Mail Settings</button>
            </li>
          </ul>

          <div class="tab-content">
            <!-- Site Info -->
            <div class="tab-pane fade <?php echo $activeTab==='siteinfo'?'show active':''; ?>" id="siteinfo" role="tabpanel" aria-labelledby="siteinfo-tab">
              <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="active_tab" value="<?php echo htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="mb-3">
                  <label for="site_name" class="form-label">Site Name</label>
                  <input type="text" class="form-control" id="site_name" name="site_name" placeholder="The name of your site" value="<?php echo htmlspecialchars(isset($_POST['site_name']) ? $_POST['site_name'] : $site_name, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-3">
                  <label for="title" class="form-label">Site Title</label>
                  <input type="text" class="form-control" id="title" name="title" placeholder="Site title tag" value="<?php echo htmlspecialchars(isset($_POST['title']) ? $_POST['title'] : $title, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-3">
                  <label for="baseurl" class="form-label">Domain name</label>
                  <input type="text" class="form-control" id="baseurl" name="baseurl" placeholder="eg: pastethis.in (no trailing slash)" value="<?php echo htmlspecialchars(isset($_POST['baseurl']) ? $_POST['baseurl'] : $baseurl, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-3">
                  <label for="des" class="form-label">Site Description</label>
                  <input type="text" class="form-control" id="des" name="des" placeholder="Site description" value="<?php echo htmlspecialchars(isset($_POST['des']) ? $_POST['des'] : $des, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-3">
                  <label for="keyword" class="form-label">Site Keywords</label>
                  <input type="text" class="form-control" id="keyword" name="keyword" placeholder="Keywords (separated by a comma)" value="<?php echo htmlspecialchars($keyword ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-3">
                  <label for="ga" class="form-label">Google Analytics</label>
                  <input type="text" class="form-control" id="ga" name="ga" placeholder="Google Analytics ID" value="<?php echo htmlspecialchars($ga ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-3">
                  <label for="email" class="form-label">Admin Email</label>
                  <input type="text" class="form-control" id="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : $email, ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="form-text">Used as the From address for emails and for receiving test emails.</div>
                </div>
                <div class="mb-3">
                  <label for="face" class="form-label">Facebook URL</label>
                  <input type="text" class="form-control" id="face" name="face" placeholder="Facebook URL" value="<?php echo htmlspecialchars($face ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-3">
                  <label for="twit" class="form-label">Twitter URL</label>
                  <input type="text" class="form-control" id="twit" name="twit" placeholder="Twitter URL" value="<?php echo htmlspecialchars($twit ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-3">
                  <label for="gplus" class="form-label">Google+ URL</label>
                  <input type="text" class="form-control" id="gplus" name="gplus" placeholder="Google+ URL" value="<?php echo htmlspecialchars($gplus ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-3">
                  <label for="additional_scripts" class="form-label">Additional Site Scripts</label>
                  <textarea class="form-control" id="additional_scripts" name="additional_scripts" rows="8"><?php echo htmlspecialchars(isset($_POST['additional_scripts']) ? $_POST['additional_scripts'] : $additional_scripts, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <input type="hidden" name="manage" value="manage" />
                <button type="submit" class="btn btn-primary">Save</button>
              </form>
            </div>

            <!-- Permissions -->
            <div class="tab-pane fade <?php echo $activeTab==='permissions'?'show active':''; ?>" id="permissions" role="tabpanel" aria-labelledby="permissions-tab">
              <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="active_tab" value="<?php echo htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-check mb-3">
                  <input class="form-check-input" type="checkbox" name="disableguest" id="disableguest" <?php if ($disableguest == 'on') echo 'checked'; ?>>
                  <label class="form-check-label" for="disableguest">Only allow registered users to paste</label>
                </div>
                <div class="form-check mb-3">
                  <input class="form-check-input" type="checkbox" name="siteprivate" id="siteprivate" <?php if ($siteprivate == 'on') echo 'checked'; ?>>
                  <label class="form-check-label" for="siteprivate">Make site private (no Recent Pastes for non-members)</label>
                </div>
                <input type="hidden" name="permissions" value="permissions" />
                <button type="submit" class="btn btn-primary">Save</button>
              </form>
            </div>

            <!-- Captcha -->
            <div class="tab-pane fade <?php echo $activeTab==='captcha'?'show active':''; ?>" id="captcha" role="tabpanel" aria-labelledby="captcha-tab">
              <form id="captcha-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="active_tab" value="<?php echo htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-check mb-3">
                  <input class="form-check-input" type="checkbox" name="cap_e" id="cap_e" <?php if ($cap_e == 'on') echo 'checked'; ?>>
                  <label class="form-check-label" for="cap_e">Enable Captcha</label>
                </div>
                <div class="mb-3">
                  <label for="mode" class="form-label">Captcha Type</label>
                  <select class="form-select" id="mode" name="mode">
                    <option value="Easy" <?php if ($mode == 'Easy') echo 'selected'; ?>>Easy</option>
                    <option value="Normal" <?php if ($mode == 'Normal') echo 'selected'; ?>>Normal</option>
                    <option value="Tough" <?php if ($mode == 'Tough') echo 'selected'; ?>>Tough</option>
                    <option value="reCAPTCHA" <?php if ($mode == 'reCAPTCHA') echo 'selected'; ?>>reCAPTCHA</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label for="recaptcha_version" class="form-label">reCAPTCHA Version</label>
                  <select class="form-select" id="recaptcha_version" name="recaptcha_version">
                    <option value="v2" <?php if ($recaptcha_version == 'v2') echo 'selected'; ?>>reCAPTCHA v2</option>
                    <option value="v3" <?php if ($recaptcha_version == 'v3') echo 'selected'; ?>>reCAPTCHA v3</option>
                  </select>
                </div>
                <div class="form-check mb-3">
                  <input class="form-check-input" type="checkbox" name="mul" id="mul" <?php if ($mul == 'on') echo 'checked'; ?>>
                  <label class="form-check-label" for="mul">Multiplication Captcha</label>
                </div>
                <div class="mb-3">
                  <label for="allowed" class="form-label">Allowed Characters</label>
                  <input type="text" class="form-control" id="allowed" name="allowed" value="<?php echo htmlspecialchars($allowed ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="form-text">Characters to use for non-reCAPTCHA captchas</div>
                </div>
                <div class="mb-3">
                  <label for="color" class="form-label">Captcha Color</label>
                  <input type="color" class="form-control form-control-color" id="color" name="color" value="<?php echo htmlspecialchars($color ?? '#000000', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-3">
                  <label for="recaptcha_sitekey" class="form-label">reCAPTCHA Site Key</label>
                  <input type="text" class="form-control" id="recaptcha_sitekey" name="recaptcha_sitekey" value="<?php echo htmlspecialchars($recaptcha_sitekey ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="form-text">Obtain from <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener">Google reCAPTCHA Admin</a></div>
                </div>
                <div class="mb-3">
                  <label for="recaptcha_secretkey" class="form-label">reCAPTCHA Secret Key</label>
                  <input type="text" class="form-control" id="recaptcha_secretkey" name="recaptcha_secretkey" value="<?php echo htmlspecialchars($recaptcha_secretkey ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <input type="hidden" name="cap" value="cap" />
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="button" id="test-recaptcha" class="btn btn-outline-primary ms-2">Test reCAPTCHA</button>
              </form>
            </div>

            <!-- Mail -->
            <div class="tab-pane fade <?php echo $activeTab==='mail'?'show active':''; ?>" id="mail" role="tabpanel" aria-labelledby="mail-tab">
              <form id="mail-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="active_tab" value="<?php echo htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="mb-3">
                  <label for="verification" class="form-label">Email Verification</label>
                  <select class="form-select" id="verification" name="verification">
                    <option value="enabled" <?php if ($verification == 'enabled') echo 'selected'; ?>>Enabled</option>
                    <option value="disabled" <?php if ($verification == 'disabled') echo 'selected'; ?>>Disabled</option>
                  </select>
                  <div class="form-text">Send verification email when users register</div>
                </div>
                <div class="mb-3">
                  <label for="protocol" class="form-label">Mail Protocol</label>
                  <select class="form-select" id="protocol" name="protocol">
                    <option value="1" <?php if ($protocol == '1') echo 'selected'; ?>>PHP Mail</option>
                    <option value="2" <?php if ($protocol == '2') echo 'selected'; ?>>SMTP</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label for="smtp_host" class="form-label">SMTP Host</label>
                  <input type="text" class="form-control" id="smtp_host" name="smtp_host" placeholder="smtp.gmail.com" value="<?php echo htmlspecialchars($smtp_host ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-3">
                  <label for="smtp_port" class="form-label">SMTP Port</label>
                  <input type="text" class="form-control" id="smtp_port" name="smtp_port" placeholder="587" value="<?php echo htmlspecialchars($smtp_port ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-3">
                  <label for="smtp_user" class="form-label">SMTP Username</label>
                  <input type="text" class="form-control" id="smtp_user" name="smtp_user" placeholder="username@domain.com" value="<?php echo htmlspecialchars($smtp_username ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="form-text">Leave blank if using Gmail SMTP with OAuth</div>
                </div>
                <div class="mb-3">
                  <label for="smtp_pass" class="form-label">SMTP Password</label>
                  <input type="password" class="form-control" id="smtp_pass" name="smtp_pass" placeholder="SMTP Password" value="<?php echo htmlspecialchars($smtp_password ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="form-text">Leave blank if using Gmail SMTP with OAuth</div>
                </div>
                <div class="mb-3">
                  <label for="socket" class="form-label">SMTP Security</label>
                  <select class="form-select" id="socket" name="socket">
                    <option value="tls" <?php if ($socket == 'tls') echo 'selected'; ?>>TLS</option>
                    <option value="ssl" <?php if ($socket == 'ssl') echo 'selected'; ?>>SSL</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label for="auth" class="form-label">SMTP Auth</label>
                  <select class="form-select" id="auth" name="auth">
                    <option value="true" <?php if ($auth == 'true') echo 'selected'; ?>>True</option>
                    <option value="false" <?php if ($auth == 'false') echo 'selected'; ?>>False</option>
                  </select>
                </div>
                <input type="hidden" name="smtp_code" value="smtp_code" />
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="button" id="test-smtp" class="btn btn-outline-primary ms-2">Test SMTP</button>
              </form>

              <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="active_tab" value="<?php echo htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="mb-3">
                  <label for="client_id" class="form-label">Client ID</label>
                  <input type="text" class="form-control" id="client_id" name="client_id" placeholder="Client ID" value="<?php echo htmlspecialchars($oauth_client_id ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="form-text">Obtain from <a href="https://console.developers.google.com" target="_blank" rel="noopener">Google Cloud Console</a></div>
                </div>
                <div class="mb-3">
                  <label for="client_secret" class="form-label">Client Secret</label>
                  <input type="text" class="form-control" id="client_secret" name="client_secret" placeholder="Client Secret" value="<?php echo htmlspecialchars($oauth_client_secret ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-3">
                  <label for="redirect_uri" class="form-label">Redirect URI</label>
                  <input type="text" class="form-control" id="redirect_uri" readonly value="<?php echo htmlspecialchars($redirect_uri ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="form-text">Use this URI in Google Cloud Console for OAuth configuration</div>
                </div>
                <div class="mb-3">
                  <label class="form-label">OAuth Status</label>
                  <div class="form-text"><?php echo htmlspecialchars($oauth_status ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                  <?php if (empty($oauth_refresh_token)): ?>
                    <p class="mt-2"><a href="../oauth/google_smtp.php" class="btn btn-primary">Authorize Gmail SMTP</a></p>
                  <?php endif; ?>
                </div>
                <input type="hidden" name="save_oauth_credentials" value="save_oauth_credentials" />
                <button type="submit" class="btn btn-primary">Save OAuth Credentials</button>
              </form>
            </div>
          </div><!-- /.tab-content -->
        </div>
      </div>

      <div class="text-muted small mt-3">
        Powered by <a class="text-decoration-none" href="https://phpaste.sourceforge.io" target="_blank">Paste</a>
      </div>
    </div><!-- /.col-lg-10 -->
  </div><!-- /.row -->
</div><!-- /.container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
  // reCAPTCHA TEST
  $('#test-recaptcha').on('click', function(e) {
    e.preventDefault();
    var $button = $(this);
    $button.prop('disabled', true).text('Testing...');
    var formData = $('#captcha-form').serialize() + '&test_recaptcha=1&csrf_token=' + encodeURIComponent($('input[name="csrf_token"]').val());
    $.ajax({
      url: '<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8'); ?>',
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function(response) {
        $('#message-container').html(response.message);
        setTimeout(function() { $('#message-container').empty(); }, 6000);
      },
      error: function(xhr, status, error) {
        $('#message-container').html('<div class="alert alert-danger text-center">Failed to test reCAPTCHA: ' + error + '</div>');
        setTimeout(function() { $('#message-container').empty(); }, 6000);
      },
      complete: function() {
        $button.prop('disabled', false).text('Test reCAPTCHA');
      }
    });
  });

  // SMTP TEST
  $('#test-smtp').on('click', function(e) {
    e.preventDefault();
    var $button = $(this);
    $button.prop('disabled', true).text('Testing...');
    var formData = $('#mail-form').serialize() + '&test_smtp=1&csrf_token=' + encodeURIComponent($('input[name="csrf_token"]').val());
    $.ajax({
      url: '<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8'); ?>',
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function(response) {
        $('#message-container').html(response.message);
        setTimeout(function() { $('#message-container').empty(); }, 6000);
      },
      error: function(xhr, status, error) {
        $('#message-container').html('<div class="alert alert-danger text-center">Failed to test SMTP: ' + error + '</div>');
        setTimeout(function() { $('#message-container').empty(); }, 6000);
      },
      complete: function() {
        $button.prop('disabled', false).text('Test SMTP');
      }
    });
  });

  // Keep active tab after Save + support URL hashes
  document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.getElementById('configTabs');
    const setHiddenInputs = (tabId) => {
      document.querySelectorAll('form input[name="active_tab"]').forEach(i => i.value = tabId);
    };
    const initial = '<?php echo htmlspecialchars($activeTab, ENT_QUOTES, "UTF-8"); ?>';
    setHiddenInputs(initial);

    tabs?.addEventListener('shown.bs.tab', (e) => {
      const id = e.target?.getAttribute('data-bs-target')?.replace('#','') || 'siteinfo';
      setHiddenInputs(id);
      history.replaceState(null, '', '#' + id);
      try { localStorage.setItem('config.activeTab', id); } catch(e){}
    });

    // If URL has hash on load, Bootstrap will handle via markup; localStorage fallback not required here.
  });
</script>
</body>
</html>
<?php
if (isset($_GET['logout'])) {
    error_log("configuration.php: Admin logout requested for admin_login: {$_SESSION['admin_login']}, Session ID: " . session_id());
    $_SESSION = [];
    session_destroy();
    ob_end_clean();
    header('Location: index.php');
    exit();
}
ob_end_flush();
