<?php
declare(strict_types=1);
/*
 * Paste <https://github.com/boxlabss/PASTE>
 * Email sending utility using PHPMailer with Gmail OAuth 2.0
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'cookie_httponly' => true,
        'use_strict_mode' => true,
    ]);
}

// Start output buffering
ob_start();

// Disable display errors
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Check if config.php is included
if (!defined('SECRET')) {
    error_log("mail.php: config.php not included or SECRET not defined");
    ob_end_clean();
    return ['status' => 'error', 'message' => 'Configuration error: config.php not included.'];
}

// Check required files
$required_files = [
    __DIR__ . '/vendor/autoload.php' => ['phpmailer/phpmailer:^6.9'],
    __DIR__ . '/../oauth/vendor/autoload.php' => ['league/oauth2-client:^2.7', 'league/oauth2-google:^4.0'],
];
foreach ($required_files as $file => $packages) {
    if (!file_exists($file)) {
        $message = empty($packages) ? "Missing file: $file" : "Missing dependency in " . dirname($file) . ". Run: <code>cd " . dirname($file) . " && composer require " . implode(' ', $packages) . "</code>";
        error_log("mail.php: $message");
        ob_end_clean();
        return ['status' => 'error', 'message' => $message];
    }
}

// Include Composer autoloaders
require_once __DIR__ . '/vendor/autoload.php'; // PHPMailer
require_once __DIR__ . '/../oauth/vendor/autoload.php'; // OAuth dependencies

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

function send_mail(string $to, string $subject, string $message, string $name, string $csrf_token): array
{
    global $dbhost, $dbuser, $dbpassword, $dbname;

    error_log("mail.php: send_mail called - To: $to, Subject: $subject, Name: $name, CSRF Token: $csrf_token");


    // Validate CSRF token (bypass for installer if site_info is empty)
    try {
        $pdo_temp = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpassword);
        $pdo_temp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo_temp->query("SELECT COUNT(*) FROM site_info");
        $site_info_exists = $stmt->fetchColumn() > 0;
        $pdo_temp = null;
    } catch (PDOException $e) {
        $site_info_exists = false;
    }
    if (!$is_installer && $site_info_exists && (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token'])) {
        error_log("mail.php: CSRF validation failed for email to $to");
        ob_end_clean();
        return ['status' => 'error', 'message' => 'CSRF validation failed. Please try again.'];
    }

    // Validate input
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("mail.php: Invalid or missing recipient email: " . ($to ?? 'null'));
        ob_end_clean();
        return ['status' => 'error', 'message' => 'Invalid or missing recipient email address.'];
    }
    if (empty($subject)) {
        error_log("mail.php: Invalid or missing subject");
        ob_end_clean();
        return ['status' => 'error', 'message' => 'Invalid or missing email subject.'];
    }
    if (empty($message)) {
        error_log("mail.php: Invalid or missing message body");
        ob_end_clean();
        return ['status' => 'error', 'message' => 'Invalid or missing email message body.'];
    }
    $name = filter_var(trim($name), FILTER_SANITIZE_SPECIAL_CHARS);

    try {
        // Connect to database
        $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpassword, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        error_log("mail.php: Database connection established");

        // Check rate limit
        $email_type = str_contains($subject, 'Account Confirmation') ? 'verification' : (str_contains($subject, 'Password Reset') ? 'reset' : 'test');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mail_log WHERE email = ? AND sent_at > ? AND type = ?");
        $stmt->execute([$to, date('Y-m-d H:i:s', strtotime('-1 hour')), $email_type]);
        if ($stmt->fetchColumn() >= 5) {
            error_log("mail.php: Rate limit exceeded for $email_type email to $to");
            return ['status' => 'error', 'message' => 'Too many email requests. Please try again later.'];
        }

        // Fetch baseurl and site info
        $stmt = $pdo->query("SELECT baseurl, site_name, email FROM site_info WHERE id = 1");
        $site_info = $stmt->fetch();
        if (!$site_info || empty($site_info['baseurl'])) {
            error_log("mail.php: Base URL not found in site_info");
            ob_end_clean();
            return ['status' => 'error', 'message' => 'Base URL not configured in Site Info. Run install.php to set it.'];
        }
        $baseurl = rtrim($site_info['baseurl'], '/') . '/';
        $site_name = trim($site_info['site_name'] ?? 'Paste');
        $from_email = trim($site_info['email'] ?? '');

        // Fetch mail settings
        $stmt = $pdo->query("SELECT verification, smtp_host, smtp_username, smtp_password, smtp_port, protocol, auth, socket, oauth_client_id, oauth_client_secret, oauth_refresh_token FROM mail WHERE id = 1");
        $mail_settings = $stmt->fetch();
        if (!$mail_settings) {
            error_log("mail.php: Mail settings not found in database");
            ob_end_clean();
            return ['status' => 'error', 'message' => 'Mail settings not found. Configure in Admin Settings.'];
        }

        $smtp_host = trim($mail_settings['smtp_host'] ?? '');
        $smtp_username = trim($mail_settings['smtp_username'] ?? '');
        $smtp_password = trim($mail_settings['smtp_password'] ?? '');
        $smtp_port = trim($mail_settings['smtp_port'] ?? '');
        $protocol = trim($mail_settings['protocol'] ?? '');
        $auth = trim($mail_settings['auth'] ?? '');
        $socket = trim($mail_settings['socket'] ?? '');
        $oauth_client_id = trim($mail_settings['oauth_client_id'] ?? '');
        $oauth_client_secret = trim($mail_settings['oauth_client_secret'] ?? '');
        $oauth_refresh_token = trim($mail_settings['oauth_refresh_token'] ?? '');
        error_log("mail.php: Mail settings - Host: $smtp_host, Username: $smtp_username, Port: $smtp_port, Protocol: $protocol, Auth: $auth, Socket: $socket, Refresh Token: " . ($oauth_refresh_token ? substr($oauth_refresh_token, 0, 10) . '...' : 'none'));

        // Log email attempt
        $stmt = $pdo->prepare("INSERT INTO mail_log (email, sent_at, type) VALUES (?, ?, ?)");
        $stmt->execute([$to, date('Y-m-d H:i:s'), $email_type]);
        error_log("mail.php: Email attempt logged for $to, type: $email_type");


        // Validate from email
        if (empty($from_email) || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
            $from_email = $smtp_username;
            if (empty($from_email) || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
                error_log("mail.php: Invalid or empty from_email, smtp_username: " . (empty($smtp_username) ? 'Not set' : 'Set'));
                ob_end_clean();
                return ['status' => 'error', 'message' => 'Invalid or missing sender email address. Check Admin Email in Site Info or SMTP User in Mail Settings.'];
            }
        }

        // Initialize PHPMailer
        $mailer = new PHPMailer(true);
        $mailer->CharSet = 'UTF-8';
        $mailer->Encoding = 'base64';
        $mailer->SMTPDebug = defined('SMTP_DEBUG') && SMTP_DEBUG ? 2 : 0;
        $mailer->Debugoutput = function($str, $level) { error_log("mail.php: SMTP Debug [$level]: $str"); };
        $mailer->isSMTP();
        $mailer->Host = $smtp_host;
        $mailer->SMTPAuth = ($auth === 'true');
        $mailer->SMTPSecure = $socket;
        $mailer->Port = (int) $smtp_port;

        // Configure OAuth for Gmail
        if ($mailer->SMTPAuth && $smtp_host === 'smtp.gmail.com') {
            $provider = new Google([
                'clientId' => $oauth_client_id,
                'clientSecret' => $oauth_client_secret,
                'redirectUri' => $baseurl . 'oauth/google_smtp.php',
            ]);
            try {
                $accessToken = $provider->getAccessToken('refresh_token', ['refresh_token' => $oauth_refresh_token]);
                if (!$accessToken) {
                    error_log("mail.php: Failed to obtain OAuth access token for $from_email");
                    ob_end_clean();
                    return ['status' => 'error', 'message' => 'Failed to obtain OAuth access token.'];
                }
                $mailer->AuthType = 'XOAUTH2';
                $mailer->setOAuth(new OAuth([
                    'provider' => $provider,
                    'clientId' => $oauth_client_id,
                    'clientSecret' => $oauth_client_secret,
                    'refreshToken' => $oauth_refresh_token,
                    'userName' => $from_email, // Use admin email
                ]));
            } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
                error_log("mail.php: OAuth error for $from_email: " . $e->getMessage());
                ob_end_clean();
                return ['status' => 'error', 'message' => 'OAuth error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')];
            }
        } else {
            $mailer->Username = $smtp_username;
            $mailer->Password = $smtp_password;
        }

        // Set email details
        $mailer->setFrom($from_email, $site_name);
        $mailer->addAddress($to, $name);
        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = $message;
        $mailer->AltBody = strip_tags($message);

        // Send email
        $mailer->send();
        error_log("mail.php: Email sent successfully to $to");
        ob_end_clean();
        return ['status' => 'success', 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        error_log("mail.php: SMTP error sending to $to: " . $e->getMessage());
        ob_end_clean();
        return ['status' => 'error', 'message' => 'Failed to send email: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')];
    } catch (PDOException $e) {
        error_log("mail.php: Database error: " . $e->getMessage());
        ob_end_clean();
        return ['status' => 'error', 'message' => 'Database error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')];
    } finally {
        $pdo = null;
    }
}
?>