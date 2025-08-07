<?php
// Check for PHPMailer files
$required_files = [
    'Exception.php',
    'PHPMailer.php',
    'SMTP.php'
];
foreach ($required_files as $file) {
    if (!file_exists(__DIR__ . '/PHPMailer/src/' . $file)) {
        error_log("Missing PHPMailer file: /mail/PHPMailer/src/$file");
        return ['status' => 'error', 'message' => 'Missing PHPMailer file ' . htmlspecialchars($file) . '. Please reinstall PHPMailer 6.9.1 in /mail/PHPMailer/src/'];
    }
}

// Include manual PHPMailer files
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

// Include Composer autoloader for Google Client
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    error_log("Composer autoloader not found in /mail/vendor/autoload.php");
    return ['status' => 'error', 'message' => 'Google API Client not installed. Run: <code>composer require google/apiclient:^2.12 league/oauth2-client</code> in /mail'];
}

// Verify required classes
if (!class_exists('Google\Client')) {
    error_log("Google\Client class not found. Ensure google/apiclient is installed.");
    return ['status' => 'error', 'message' => 'Google API Client Library not installed. Run: <code>composer require google/apiclient:^2.12</code> in /mail'];
}
if (!class_exists('League\OAuth2\Client\Grant\RefreshToken')) {
    error_log("League\OAuth2\Client\Grant\RefreshToken class not found. Ensure league/oauth2-client is installed.");
    return ['status' => 'error', 'message' => 'OAuth2 Client Library not installed. Run: <code>composer require league/oauth2-client</code> in /mail'];
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

function send_mail($to = null, $subject = null, $message = null, $name = null) {
    global $dbhost, $dbuser, $dbpassword, $dbname;

    try {
        $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        $stmt = $pdo->query("SELECT * FROM mail WHERE id = 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

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

        error_log("SMTP settings: host=$smtp_host, port=$smtp_port, socket=$socket, auth=$auth, protocol=$protocol, username=" . ($smtp_username ?: 'Not set') . ", oauth_client_id=" . (empty($oauth_client_id) ? 'Not set' : substr($oauth_client_id, 0, 10) . '...') . ", oauth_client_secret=" . (empty($oauth_client_secret) ? 'Not set' : 'Set') . ", oauth_refresh_token=" . (empty($oauth_refresh_token) ? 'Not set' : substr($oauth_refresh_token, 0, 10) . '...'));

        if ($smtp_host === 'smtp.gmail.com' && $auth === 'true' && (empty($oauth_client_id) || empty($oauth_client_secret) || empty($oauth_refresh_token))) {
            error_log("Missing OAuth credentials for Gmail SMTP: client_id=" . (empty($oauth_client_id) ? 'empty' : 'set') . ", client_secret=" . (empty($oauth_client_secret) ? 'empty' : 'set') . ", refresh_token=" . (empty($oauth_refresh_token) ? 'empty' : 'set'));
            return ['status' => 'error', 'message' => 'Missing OAuth credentials for Gmail SMTP. Please complete OAuth authorization in Admin Settings.'];
        }

        if ($protocol !== '2') {
            error_log("Invalid mail protocol: expected SMTP (2), got $protocol");
            return ['status' => 'error', 'message' => 'Mail protocol must be set to SMTP in Mail Settings.'];
        }

        $site_info = $pdo->query("SELECT * FROM site_info WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
        $site_name = trim($site_info['site_name'] ?? 'Paste');
        $from_email = trim($site_info['email'] ?? $smtp_username);

        if (empty($from_email) || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid or empty from_email: '$from_email', smtp_username: '$smtp_username'");
            return ['status' => 'error', 'message' => 'Invalid or missing sender email address. Check Admin Email in Site Info or SMTP User in Mail Settings.'];
        }

        $mailer = new PHPMailer(true);
        $mailer->SMTPDebug = 0; // Set to 2 for debugging
        $mailer->Debugoutput = function($str, $level) { error_log("SMTP Debug [$level]: $str"); };
        $mailer->isSMTP();
        $mailer->Host = $smtp_host;
        $mailer->SMTPAuth = ($auth === 'true');
        $mailer->SMTPSecure = $socket;
        $mailer->Port = $smtp_port;

        if ($mailer->SMTPAuth && $smtp_host === 'smtp.gmail.com') {
            $mailer->AuthType = 'XOAUTH2';
            $mailer->setOAuth(
                new OAuth([
                    'provider' => new Google([
                        'clientId' => $oauth_client_id,
                        'clientSecret' => $oauth_client_secret,
                    ]),
                    'clientId' => $oauth_client_id,
                    'clientSecret' => $oauth_client_secret,
                    'refreshToken' => $oauth_refresh_token,
                    'userName' => $smtp_username,
                ])
            );
        } else {
            $mailer->Username = $smtp_username;
            $mailer->Password = $smtp_password;
        }

        $mailer->setFrom($from_email, $site_name);
        $mailer->addAddress($to, $name);
        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = $message;

        $mailer->send();
        error_log("Email sent successfully to $to");
        return ['status' => 'success', 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        error_log("SMTP error in mail.php: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'Failed to send email: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')];
    } catch (PDOException $e) {
        error_log("Database error in mail.php: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'Database error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')];
    }
}
?>