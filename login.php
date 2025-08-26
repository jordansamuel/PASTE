<?php
/*
 * Paste $v3.1 2025/08/16 https://github.com/boxlabss/PASTE
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
$__clean = function () {
    if (ob_get_level() > 0 && ob_get_length() !== false) { @ob_clean(); }
};

require_once 'includes/session.php';
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once 'config.php';
require_once 'includes/password.php';
require_once 'includes/functions.php';
require_once 'mail/mail.php';
require_once 'includes/recaptcha.php';

$error = null;
$success = null;

// OAuth deps (soft)
$oauth_ready = true;
$oauth_autoloader = __DIR__ . '/oauth/vendor/autoload.php';
if (!file_exists($oauth_autoloader)) {
    $oauth_ready = false;
} else {
    require_once $oauth_autoloader;
    if (!class_exists('League\OAuth2\Client\Provider\Google')) $oauth_ready = false;
}
// ensure defined for header.php
$enablegoog = $enablegoog ?? 'no';
$enablefb   = $enablefb   ?? 'no';
if (!$oauth_ready) { $enablegoog = 'no'; $enablefb = 'no'; }

// DB (PDO)
try {
    if (!isset($pdo) || !$pdo instanceof PDO) {
        $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpassword, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
} catch (Throwable $e) {
    error_log("login.php: DB connect failed: " . $e->getMessage());
    $error = "Unable to connect to database.";
}

// Captcha session bootstrap (reads captcha table into $_SESSION) ---
try {
    if (
        empty($_SESSION['cap_e']) ||
        empty($_SESSION['mode']) ||
        empty($_SESSION['recaptcha_version']) ||
        empty($_SESSION['recaptcha_sitekey']) ||
        empty($_SESSION['recaptcha_secretkey'])
    ) {
        $row = $pdo->query("SELECT cap_e, mode, recaptcha_version, recaptcha_sitekey, recaptcha_secretkey FROM captcha WHERE id = 1")
                   ->fetch(PDO::FETCH_ASSOC) ?: [];
        foreach (['cap_e','mode','recaptcha_version','recaptcha_sitekey','recaptcha_secretkey'] as $k) {
            if (!isset($_SESSION[$k]) && isset($row[$k])) {
                $_SESSION[$k] = $row[$k];
            }
        }
    }
} catch (Throwable $e) {
    // best-effort; if this fails, require_human() will no-op unless cap_e==='on' and secrets exist
}

// Site info
try {
    $stmt = $pdo->query("SELECT * FROM site_info WHERE id = 1");
    $site = $stmt->fetch() ?: [];
} catch (Throwable $e) { $site = []; }
$title       = trim($site['title'] ?? 'Paste');
$des         = trim($site['des'] ?? '');
$baseurl     = trim($site['baseurl'] ?? '');
$keyword     = trim($site['keyword'] ?? '');
$site_name   = trim($site['site_name'] ?? 'Paste');
$email       = trim($site['email'] ?? '');
$admin_mail  = $email;
$admin_name  = $site_name;
$mod_rewrite = (string)($site['mod_rewrite'] ?? ($mod_rewrite ?? '1')); // avoid undefined in header

// UI: language & theme
try {
    $iface = $pdo->query("SELECT * FROM interface WHERE id = 1")->fetch() ?: [];
} catch (Throwable $e) { $iface = []; }
$default_lang  = trim($iface['lang'] ?? 'en.php');
$default_theme = trim($iface['theme'] ?? 'default');
require_once("langs/$default_lang");

// Page title (avoid undefined in header)
$p_title = $lang['login/register'] ?? 'Login / Register';

// Ads (optional)
try {
    $ads = $pdo->query("SELECT * FROM ads WHERE id = 1")->fetch() ?: [];
} catch (Throwable $e) { $ads = []; }
$text_ads = trim($ads['text_ads'] ?? '');
$ads_1    = trim($ads['ads_1'] ?? '');
$ads_2    = trim($ads['ads_2'] ?? '');

// CSRF / basics
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf_token = $_SESSION['csrf_token'];

// Ban check
if (isset($pdo) && is_banned($pdo, $ip)) { $error = $lang['banned'] ?? 'You are banned.'; }

// Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    unset($_SESSION['token'], $_SESSION['oauth_uid'], $_SESSION['username'], $_SESSION['platform'], $_SESSION['id'], $_SESSION['oauth2state']);
    @session_regenerate_id(true);
    @session_destroy();
    $__clean();
    header('Location: ' . ($baseurl ?: './'));
    exit;
}

// Already logged in? 
if (isset($_SESSION['token']) && !(isset($_GET['action']) && $_GET['action'] === 'logout')) {
    $__clean();
    header('Location: ./');
    exit;
}

// Mail settings (for verify/forgot)
try {
    $mail = $pdo->query("SELECT * FROM mail WHERE id = 1")->fetch() ?: [];
} catch (Throwable $e) { $mail = []; }
$verification = trim($mail['verification'] ?? 'disabled');

// Page views (best effort)
try {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT id, tpage, tvisit FROM page_view WHERE date = ?");
    $stmt->execute([$today]);
    $row = $stmt->fetch();
    if ($row) {
        $tpage = (int)$row['tpage'] + 1; $tvisit = (int)$row['tvisit'];
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM visitor_ips WHERE ip = ? AND visit_date = ?");
        $stmt->execute([$ip, $today]);
        if ((int)$stmt->fetchColumn() === 0) {
            $tvisit++; $pdo->prepare("INSERT INTO visitor_ips (ip, visit_date) VALUES (?, ?)")->execute([$ip, $today]);
        }
        $pdo->prepare("UPDATE page_view SET tpage = ?, tvisit = ? WHERE id = ?")->execute([$tpage, $tvisit, $row['id']]);
    } else {
        $pdo->prepare("INSERT INTO page_view (date, tpage, tvisit) VALUES (?, 1, 1)")->execute([$today]);
        $pdo->prepare("INSERT INTO visitor_ips (ip, visit_date) VALUES (?, ?)")->execute([$ip, $today]);
    }
} catch (Throwable $e) { /* ignore */ }

// Actions (verify/resend/forgot/reset)
$valid_csrf = static fn($token) => hash_equals($_SESSION['csrf_token'] ?? '', (string)$token);

// verify (GET)
if (isset($_GET['action'], $_GET['code'], $_GET['username']) && $_GET['action'] === 'verify') {
    $u = filter_var((string)$_GET['username'], FILTER_SANITIZE_SPECIAL_CHARS);
    $c = filter_var((string)$_GET['code'], FILTER_SANITIZE_SPECIAL_CHARS);
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND verification_code = ?");
        $stmt->execute([$u, $c]);
        if ($stmt->fetch()) {
            $pdo->prepare("UPDATE users SET verified = '1', verification_code = NULL WHERE username = ?")->execute([$u]);
            $success = $lang['email_verified'] ?? 'Email verified successfully. You can now log in.';
        } else { $error = $lang['invalid_code'] ?? 'Invalid verification code or username.'; }
    } catch (Throwable $e) { $error = "Verification error."; }
}

// resend (POST)
if (isset($_GET['action']) && $_GET['action'] === 'resend'
    && isset($_POST['email'], $_POST['csrf_token']) && $valid_csrf($_POST['csrf_token'])) {
    require_human('resend');
    $email_in = filter_var((string)$_POST['email'], FILTER_SANITIZE_EMAIL);
    try {
        $stmt = $pdo->prepare("SELECT username, full_name, verified FROM users WHERE email_id = ?");
        $stmt->execute([$email_in]);
        $user = $stmt->fetch();
        if ($user && (string)$user['verified'] === '0') {
            $code = bin2hex(random_bytes(16));
            $pdo->prepare("UPDATE users SET verification_code = ? WHERE email_id = ?")->execute([$code, $email_in]);
            $verify_url = rtrim($baseurl, '/') . "/login.php?action=verify&username=" . urlencode($user['username']) . "&code=" . urlencode($code);
            $subject = $lang['mail_acc_con'] ?? 'Account Confirmation';
            $body    = "Hello " . htmlspecialchars((string)$user['full_name']) . ", please verify your account:<br><br><a href='$verify_url' target='_self'>$verify_url</a>";
            send_mail($email_in, $subject, $body, $site_name, $_SESSION['csrf_token']);
            $success = $lang['mail_suc'] ?? 'Verification email sent.';
        } else {
            $success = $lang['mail_suc'] ?? 'Verification email sent.'; // avoid enumeration
        }
    } catch (Throwable $e) { $error = "Could not resend verification."; }
}

// forgot (POST)
if (isset($_GET['action']) && $_GET['action'] === 'forgot'
    && isset($_POST['email'], $_POST['csrf_token']) && $valid_csrf($_POST['csrf_token'])) {
    require_human('forgot');
    $email_in = filter_var((string)$_POST['email'], FILTER_SANITIZE_EMAIL);
    try {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE email_id = ?");
        $stmt->execute([$email_in]);
        $user = $stmt->fetch();
        if ($user) {
            $code = bin2hex(random_bytes(16));
            $exp  = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $pdo->prepare("UPDATE users SET reset_code = ?, reset_expiry = ? WHERE email_id = ?")->execute([$code, $exp, $email_in]);
            $reset_url = rtrim($baseurl, '/') . "/login.php?action=reset&username=" . urlencode($user['username']) . "&code=" . urlencode($code);
            $subject = "$site_name Password Reset";
            $body    = "To reset your password, click:<br><br><a href='$reset_url' target='_self'>$reset_url</a><br><br>This link expires in 1 hour.";
            send_mail($email_in, $subject, $body, $site_name, $_SESSION['csrf_token']);
        }
        $success = $lang['pass_change'] ?? 'Password reset link sent. Check your email.';
    } catch (Throwable $e) { $error = "Could not start reset process."; }
}

// reset (POST)
if (isset($_GET['action'], $_GET['username'], $_GET['code']) && $_GET['action'] === 'reset'
    && isset($_POST['password'], $_POST['csrf_token']) && $valid_csrf($_POST['csrf_token'])) {
    require_human('reset');
    $u = filter_var((string)$_GET['username'], FILTER_SANITIZE_SPECIAL_CHARS);
    $c = filter_var((string)$_GET['code'], FILTER_SANITIZE_SPECIAL_CHARS);
    $pw = (string)$_POST['password'];
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND reset_code = ? AND reset_expiry > ?");
        $stmt->execute([$u, $c, date('Y-m-d H:i:s')]);
        if ($stmt->fetch()) {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_expiry = NULL WHERE username = ?")->execute([$hash, $u]);
            $success = $lang['pass_reset'] ?? 'Password reset successful. You can now log in.';
        } else { $error = $lang['invalid_code'] ?? 'Invalid or expired reset code.'; }
    } catch (Throwable $e) { $error = "Could not reset password."; }
}

// Login / Signup (POST) — hard gate with reCAPTCHA
$valid_post = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
    && isset($_POST['csrf_token']) && $valid_csrf($_POST['csrf_token']));

if ($valid_post) {
    // LOGIN
    if (isset($_POST['signin'])) {
        require_human('login');
        $u = filter_var((string)($_POST['username'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
        $p = (string)($_POST['password'] ?? '');
        if ($u !== '' && $p !== '') {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$u]);
                $user = $stmt->fetch();
                if ($user && password_verify($p, (string)$user['password'])) {
                    if ((string)$user['verified'] === '1') {
                        $new_token = bin2hex(random_bytes(32));
                        $pdo->prepare("UPDATE users SET token = ? WHERE username = ?")->execute([$new_token, $u]);
                        $_SESSION['token']     = $new_token;
                        $_SESSION['oauth_uid'] = $user['oauth_uid'];
                        $_SESSION['username']  = $u;
                        $__clean();
                        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? $baseurl ?: './'));
                        exit;
                    }
                    $error = ((string)$user['verified'] === '2')
                        ? ($lang['banned'] ?? 'Your account is banned.')
                        : ($lang['notverified'] ?? 'Account not verified.');
                } else { $error = $lang['incorrect'] ?? 'Incorrect username or password.'; }
            } catch (Throwable $e) { $error = "Login failed due to a server error."; }
        } else { $error = $lang['missingfields'] ?? 'Please fill in all fields.'; }
    }

    // SIGNUP
    if (isset($_POST['signup'])) {
        require_human('signup');
        $u  = filter_var((string)($_POST['username'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
        $p  = (string)($_POST['password'] ?? '');
        $em = filter_var((string)($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $fn = filter_var((string)($_POST['full'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);

        if ($u && $p && $em && $fn) {
            if (isValidUsername($u)) {
                try {
                    // username unique
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                    $stmt->execute([$u]);
                    if ((int)$stmt->fetchColumn() > 0) {
                        $error = $lang['userexists'] ?? 'Username already exists.';
                    } else {
                        // email unique
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email_id = ?");
                        $stmt->execute([$em]);
                        if ((int)$stmt->fetchColumn() > 0) {
                            $error = $lang['emailexists'] ?? 'Email already exists.';
                        } else {
                            $hash     = password_hash($p, PASSWORD_DEFAULT);
                            $verified = ($verification === 'disabled') ? '1' : '0';
                            $vcode    = ($verification === 'disabled') ? null : bin2hex(random_bytes(16));
                            $pdo->prepare("
                                INSERT INTO users (oauth_uid, username, email_id, full_name, platform, password, verified, picture, date, ip, verification_code)
                                VALUES ('0', ?, ?, ?, 'Direct', ?, ?, 'NONE', ?, ?, ?)
                            ")->execute([$u, $em, $fn, $hash, $verified, date('Y-m-d H:i:s'), $ip, $vcode]);

                            if ($verification !== 'disabled') {
                                $verify_url = rtrim($baseurl, '/') . "/login.php?action=verify&username=" . urlencode($u) . "&code=" . urlencode($vcode);
                                $subject = $lang['mail_acc_con'] ?? 'Account Confirmation';
                                $body    = "Hello $fn, verify your $site_name account:<br><br><a href='$verify_url' target='_self'>$verify_url</a>";
                                $res = send_mail($em, $subject, $body, $site_name, $_SESSION['csrf_token']);
                                if (($res['status'] ?? 'error') !== 'success') {
                                    $error = ($lang['mail_error'] ?? 'Failed to send verification email.');
                                }
                            }
                            if (!$error) {
                                $success = ($lang['registered'] ?? 'Registration successful.')
                                    . ($verification !== 'disabled' ? ' Please check your email to verify your account.' : '');
                            }
                        }
                    }
                } catch (Throwable $e) { $error = "Registration failed due to a server error."; }
            } else { $error = $lang['usrinvalid'] ?? 'Invalid username. Use only letters, numbers, .#$'; }
        } else { $error = $lang['missingfields'] ?? 'Please fill in all fields.'; }
    }
}

// Mirror messages for theme (which reads $_GET) 
if (!empty($error))   { $_GET['error']   = $error; }
if (!empty($success)) { $_GET['success'] = $success; }

// OAuth launch (only if enabled & deps ok) -----
if ($oauth_ready && isset($_GET['login']) && ($enablegoog === 'yes' || $enablefb === 'yes')) {
    if ($_GET['login'] === 'google' && $enablegoog === 'yes') {
        $__clean(); header("Location: oauth/google.php?login=1"); exit;
    }
    if ($_GET['login'] === 'facebook' && $enablefb === 'yes') {
        $__clean(); header("Location: oauth/facebook.php?login=1"); exit;
    }
    $_GET['error'] = "Invalid OAuth provider or disabled in config.";
}

// Render--
$__clean();
header('Content-Type: text/html; charset=utf-8');
require_once("theme/$default_theme/header.php");
require_once("theme/$default_theme/login.php");
require_once("theme/$default_theme/footer.php");
