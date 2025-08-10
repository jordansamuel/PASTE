<?php
/*
 * Paste <https://github.com/boxlabss/PASTE>
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
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
$ip = $_SERVER['REMOTE_ADDR'];
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
    if ($last_ip !== $ip || $last_date !== $date) {
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
                        if ($response['success'] === false && isset($response['error-codes']) && in_array('invalid-input-secret', $response['error-codes'])) {
                            $msg = '<div class="alert alert-danger text-center">Invalid reCAPTCHA Secret Key. Please verify your keys.</div>';
                            error_log("configuration.php: reCAPTCHA test failed: Invalid secret key");
                        } else {
                            if ($recaptcha_version === 'v3' && isset($response['score']) && $response['score'] < 0.5) {
                                $msg = '<div class="alert alert-danger text-center">reCAPTCHA v3 test failed: Score ' . htmlspecialchars($response['score'], ENT_QUOTES, 'UTF-8') . ' is below threshold (0.5).</div>';
                                error_log("configuration.php: reCAPTCHA v3 test failed: Score " . $response['score']);
                            } else {
                                $msg = '<div class="alert alert-success text-center">reCAPTCHA keys are valid' . ($recaptcha_version === 'v3' ? ' (Score: ' . htmlspecialchars($response['score'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . ')' : '') . '.</div>';
                                error_log("configuration.php: reCAPTCHA test successful" . ($recaptcha_version === 'v3' ? ", Score: " . $response['score'] : ""));
                            }
                        }
                    }
                }
                // Return only the message for AJAX
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
                    if ($mail_result['status'] === 'success') {
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
        // For non-AJAX requests, render the message
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            // Continue rendering the page
        }
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
    $pdo = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Configuration</title>
    <link rel="shortcut icon" href="favicon.ico">
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="//cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <style>
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: bold; }
        .nav-link.active { background-color: #007bff; color: white !important; }
        .card { margin-top: 20px; }
        .nav-tabs { justify-content: center; }
        .nav-tabs .nav-link { color: #007bff; }
        .nav-tabs .nav-link.active { background-color: #007bff; color: white; }
        .form-label { font-weight: 500; }
        .footer { text-align: center; padding: 20px 0; margin-top: 20px; }
        .alert { margin-bottom: 20px; }
        .form-check-input:checked { background-color: #007bff; border-color: #007bff; }
    </style>
    <script>
        $(document).ready(function() {
            // Handle reCAPTCHA test button
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
                        setTimeout(function() {
                            $('#message-container').empty();
                        }, 5000);
                    },
                    error: function(xhr, status, error) {
                        $('#message-container').html('<div class="alert alert-danger text-center">Failed to test reCAPTCHA: ' + error + '</div>');
                        setTimeout(function() {
                            $('#message-container').empty();
                        }, 5000);
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('Test reCAPTCHA');
                    }
                });
            });

            // Handle SMTP test button
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
                        setTimeout(function() {
                            $('#message-container').empty();
                        }, 5000);
                    },
                    error: function(xhr, status, error) {
                        $('#message-container').html('<div class="alert alert-danger text-center">Failed to test SMTP: ' + error + '</div>');
                        setTimeout(function() {
                            $('#message-container').empty();
                        }, 5000);
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('Test SMTP');
                    }
                });
            });
        });
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-light">
        <div class="container">
            <a class="navbar-brand" href="../">Paste</a>
            <div class="ms-auto">
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <b><?php echo htmlspecialchars($_SESSION['admin_login'] ?? '', ENT_QUOTES, 'UTF-8'); ?></b>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="admin.php">Settings</a></li>
                            <li><a class="dropdown-item" href="?logout">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <nav class="nav nav-pills flex-wrap my-3">
            <a class="nav-link" href="index.php">Dashboard</a>
            <a class="nav-link active" href="configuration.php">Configuration</a>
            <a class="nav-link" href="interface.php">Interface</a>
            <a class="nav-link" href="admin.php">Admin Account</a>
            <a class="nav-link" href="pastes.php">Pastes</a>
            <a class="nav-link" href="users.php">Users</a>
            <a class="nav-link" href="ipbans.php">IP Bans</a>
            <a class="nav-link" href="stats.php">Statistics</a>
            <a class="nav-link" href="ads.php">Ads</a>
            <a class="nav-link" href="pages.php">Pages</a>
            <a class="nav-link" href="sitemap.php">Sitemap</a>
            <a class="nav-link" href="tasks.php">Tasks</a>
        </nav>

        <div class="card">
            <div class="card-body">
                <div id="message-container"><?php if (isset($msg)) echo $msg; ?></div>
                <ul class="nav nav-tabs" id="configTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="siteinfo-tab" data-bs-toggle="tab" data-bs-target="#siteinfo" role="tab" aria-controls="siteinfo" aria-selected="true">Site Info</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="permissions-tab" data-bs-toggle="tab" data-bs-target="#permissions" role="tab" aria-controls="permissions" aria-selected="false">Permissions</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="captcha-tab" data-bs-toggle="tab" data-bs-target="#captcha" role="tab" aria-controls="captcha" aria-selected="false">Captcha Settings</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="mail-tab" data-bs-toggle="tab" data-bs-target="#mail" role="tab" aria-controls="mail" aria-selected="false">Mail Settings</a>
                    </li>
                </ul>

                <div class="tab-content mt-3">
                    <div class="tab-pane fade show active" id="siteinfo" role="tabpanel" aria-labelledby="siteinfo-tab">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="row mb-3">
                                <label for="site_name" class="col-sm-2 col-form-label">Site Name</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="site_name" name="site_name" placeholder="The name of your site" value="<?php echo htmlspecialchars(isset($_POST['site_name']) ? $_POST['site_name'] : $site_name, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="title" class="col-sm-2 col-form-label">Site Title</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="title" name="title" placeholder="Site title tag" value="<?php echo htmlspecialchars(isset($_POST['title']) ? $_POST['title'] : $title, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="baseurl" class="col-sm-2 col-form-label">Domain name</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" id="baseurl" name="baseurl" placeholder="eg: pastethis.in (no trailing slash)" value="<?php echo htmlspecialchars(isset($_POST['baseurl']) ? $_POST['baseurl'] : $baseurl, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="des" class="col-sm-2 col-form-label">Site Description</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="des" name="des" placeholder="Site description" value="<?php echo htmlspecialchars(isset($_POST['des']) ? $_POST['des'] : $des, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="keyword" class="col-sm-2 col-form-label">Site Keywords</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="keyword" name="keyword" placeholder="Keywords (separated by a comma)" value="<?php echo htmlspecialchars($keyword ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="ga" class="col-sm-2 col-form-label">Google Analytics</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="ga" name="ga" placeholder="Google Analytics ID" value="<?php echo htmlspecialchars($ga ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="email" class="col-sm-2 col-form-label">Admin Email</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : $email, ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="form-text">Used as the From address for emails and for receiving test emails.</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="face" class="col-sm-2 col-form-label">Facebook URL</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="face" name="face" placeholder="Facebook URL" value="<?php echo htmlspecialchars($face ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="twit" class="col-sm-2 col-form-label">Twitter URL</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="twit" name="twit" placeholder="Twitter URL" value="<?php echo htmlspecialchars($twit ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="gplus" class="col-sm-2 col-form-label">Google+ URL</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="gplus" name="gplus" placeholder="Google+ URL" value="<?php echo htmlspecialchars($gplus ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="additional_scripts" class="col-sm-2 col-form-label">Additional Site Scripts</label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" id="additional_scripts" name="additional_scripts" rows="8"><?php echo htmlspecialchars(isset($_POST['additional_scripts']) ? $_POST['additional_scripts'] : $additional_scripts, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                            </div>
                            <input type="hidden" name="manage" value="manage" />
                            <div class="row mb-3">
                                <div class="col-sm-10 offset-sm-2">
                                    <button type="submit" class="btn btn-primary">Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="permissions" role="tabpanel" aria-labelledby="permissions-tab">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="disableguest" id="disableguest" <?php if ($disableguest == 'on') echo 'checked'; ?>>
                                <label class="form-check-label" for="disableguest">Only allow registered users to paste</label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="siteprivate" id="siteprivate" <?php if ($siteprivate == 'on') echo 'checked'; ?>>
                                <label class="form-check-label" for="siteprivate">Make site private (no Recent Pastes for non-members)</label>
                            </div>
                            <input type="hidden" name="permissions" value="permissions" />
                            <div class="row mb-3">
                                <div class="col-sm-10 offset-sm-2">
                                    <button type="submit" class="btn btn-primary">Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="captcha" role="tabpanel" aria-labelledby="captcha-tab">
                        <form id="captcha-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="cap_e" id="cap_e" <?php if ($cap_e == 'on') echo 'checked'; ?>>
                                <label class="form-check-label" for="cap_e">Enable Captcha</label>
                            </div>
                            <div class="row mb-3">
                                <label for="mode" class="col-sm-2 col-form-label">Captcha Type</label>
                                <div class="col-sm-10">
                                    <select class="form-select" id="mode" name="mode">
                                        <option value="Easy" <?php if ($mode == 'Easy') echo 'selected'; ?>>Easy</option>
                                        <option value="Normal" <?php if ($mode == 'Normal') echo 'selected'; ?>>Normal</option>
                                        <option value="Tough" <?php if ($mode == 'Tough') echo 'selected'; ?>>Tough</option>
                                        <option value="reCAPTCHA" <?php if ($mode == 'reCAPTCHA') echo 'selected'; ?>>reCAPTCHA</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="recaptcha_version" class="col-sm-2 col-form-label">reCAPTCHA Version</label>
                                <div class="col-sm-10">
                                    <select class="form-select" id="recaptcha_version" name="recaptcha_version">
                                        <option value="v2" <?php if ($recaptcha_version == 'v2') echo 'selected'; ?>>reCAPTCHA v2</option>
                                        <option value="v3" <?php if ($recaptcha_version == 'v3') echo 'selected'; ?>>reCAPTCHA v3</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="mul" id="mul" <?php if ($mul == 'on') echo 'checked'; ?>>
                                <label class="form-check-label" for="mul">Multiplication Captcha</label>
                            </div>
                            <div class="row mb-3">
                                <label for="allowed" class="col-sm-2 col-form-label">Allowed Characters</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="allowed" name="allowed" value="<?php echo htmlspecialchars($allowed ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="form-text">Characters to use for non-reCAPTCHA captchas</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="color" class="col-sm-2 col-form-label">Captcha Color</label>
                                <div class="col-sm-10">
                                    <input type="color" class="form-control form-control-color" id="color" name="color" value="<?php echo htmlspecialchars($color ?? '#000000', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="recaptcha_sitekey" class="col-sm-2 col-form-label">reCAPTCHA Site Key</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="recaptcha_sitekey" name="recaptcha_sitekey" value="<?php echo htmlspecialchars($recaptcha_sitekey ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="form-text">Obtain from <a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA Admin</a></div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="recaptcha_secretkey" class="col-sm-2 col-form-label">reCAPTCHA Secret Key</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="recaptcha_secretkey" name="recaptcha_secretkey" value="<?php echo htmlspecialchars($recaptcha_secretkey ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <input type="hidden" name="cap" value="cap" />
                            <div class="row mb-3">
                                <div class="col-sm-10 offset-sm-2">
                                    <button type="submit" class="btn btn-primary">Save</button>
                                    <button type="button" id="test-recaptcha" class="btn btn-outline-primary ms-2">Test reCAPTCHA</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="mail" role="tabpanel" aria-labelledby="mail-tab">
                        <form id="mail-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="row mb-3">
                                <label for="verification" class="col-sm-2 col-form-label">Email Verification</label>
                                <div class="col-sm-10">
                                    <select class="form-select" id="verification" name="verification">
                                        <option value="enabled" <?php if ($verification == 'enabled') echo 'selected'; ?>>Enabled</option>
                                        <option value="disabled" <?php if ($verification == 'disabled') echo 'selected'; ?>>Disabled</option>
                                    </select>
                                    <div class="form-text">Send verification email when users register</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="protocol" class="col-sm-2 col-form-label">Mail Protocol</label>
                                <div class="col-sm-10">
                                    <select class="form-select" id="protocol" name="protocol">
                                        <option value="1" <?php if ($protocol == '1') echo 'selected'; ?>>PHP Mail</option>
                                        <option value="2" <?php if ($protocol == '2') echo 'selected'; ?>>SMTP</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="smtp_host" class="col-sm-2 col-form-label">SMTP Host</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" placeholder="smtp.gmail.com" value="<?php echo htmlspecialchars($smtp_host ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="smtp_port" class="col-sm-2 col-form-label">SMTP Port</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="smtp_port" name="smtp_port" placeholder="587" value="<?php echo htmlspecialchars($smtp_port ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="smtp_user" class="col-sm-2 col-form-label">SMTP Username</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="smtp_user" name="smtp_user" placeholder="username@domain.com" value="<?php echo htmlspecialchars($smtp_username ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="form-text">Leave blank if using Gmail SMTP with OAuth</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="smtp_pass" class="col-sm-2 col-form-label">SMTP Password</label>
                                <div class="col-sm-10">
                                    <input type="password" class="form-control" id="smtp_pass" name="smtp_pass" placeholder="SMTP Password" value="<?php echo htmlspecialchars($smtp_password ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="form-text">Leave blank if using Gmail SMTP with OAuth</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="socket" class="col-sm-2 col-form-label">SMTP Security</label>
                                <div class="col-sm-10">
                                    <select class="form-select" id="socket" name="socket">
                                        <option value="tls" <?php if ($socket == 'tls') echo 'selected'; ?>>TLS</option>
                                        <option value="ssl" <?php if ($socket == 'ssl') echo 'selected'; ?>>SSL</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="auth" class="col-sm-2 col-form-label">SMTP Auth</label>
                                <div class="col-sm-10">
                                    <select class="form-select" id="auth" name="auth">
                                        <option value="true" <?php if ($auth == 'true') echo 'selected'; ?>>True</option>
                                        <option value="false" <?php if ($auth == 'false') echo 'selected'; ?>>False</option>
                                    </select>
                                </div>
                            </div>
                            <input type="hidden" name="smtp_code" value="smtp_code" />
                            <div class="row mb-3">
                                <div class="col-sm-10 offset-sm-2">
                                    <button type="submit" class="btn btn-primary">Save</button>
                                    <button type="button" id="test-smtp" class="btn btn-outline-primary ms-2">Test SMTP</button>
                                </div>
                            </div>
                        </form>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="row mb-3">
                                <label for="client_id" class="col-sm-2 col-form-label">Client ID</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="client_id" name="client_id" placeholder="Client ID" value="<?php echo htmlspecialchars($oauth_client_id ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="form-text">Obtain from <a href="https://console.developers.google.com" target="_blank">Google Cloud Console</a></div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="client_secret" class="col-sm-2 col-form-label">Client Secret</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="client_secret" name="client_secret" placeholder="Client Secret" value="<?php echo htmlspecialchars($oauth_client_secret ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="redirect_uri" class="col-sm-2 col-form-label">Redirect URI</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="redirect_uri" readonly value="<?php echo htmlspecialchars($redirect_uri ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="form-text">Use this URI in Google Cloud Console for OAuth configuration</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-2 col-form-label">OAuth Status</label>
                                <div class="col-sm-10">
                                    <div class="form-text"><?php echo htmlspecialchars($oauth_status ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php if (empty($oauth_refresh_token)): ?>
                                        <p><a href="../oauth/google_smtp.php" class="btn btn-primary">Authorize Gmail SMTP</a></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <input type="hidden" name="save_oauth_credentials" value="save_oauth_credentials" />
                            <div class="row mb-3">
                                <div class="col-sm-10 offset-sm-2">
                                    <button type="submit" class="btn btn-primary">Save OAuth Credentials</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer">
            &copy; <?php echo date('Y'); ?> <a href="<?php echo htmlspecialchars($baseurl ?? '', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($site_name ?? '', ENT_QUOTES, 'UTF-8'); ?></a>. All rights reserved.
        </div>
    </div>
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
?>