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
ob_start(); // Start output buffering

session_start([
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'cookie_httponly' => true,
    'use_strict_mode' => true,
]);

// Production settings
ini_set('display_errors', '0');
ini_set('log_errors', '1');
define('PRODUCTION', false); // Toggle for production mode
$generic_error = PRODUCTION ? "Server error. Please try again later." : null;

// Required functions
require_once('config.php');
require_once('includes/password.php');
require_once('includes/functions.php');
require_once('mail/mail.php');

// Check OAuth dependencies
$oauth_autoloader = __DIR__ . '/oauth/vendor/autoload.php';
if (!file_exists($oauth_autoloader)) {
    $message = "OAuth autoloader not found. Run: <code>cd oauth && composer require google/apiclient:^2.12 league/oauth2-client:^2.7 league/oauth2-google:^4.0</code>";
    error_log("login.php: $message");
    ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    die($generic_error ?: $message);
}
require_once $oauth_autoloader;

// Verify required OAuth classes
$required_classes = [
    'Google_Client' => 'google/apiclient:^2.12',
    'League\OAuth2\Client\Provider\Google' => 'league/oauth2-client:^2.7 league/oauth2-google:^4.0'
];
foreach ($required_classes as $class => $packages) {
    if (!class_exists($class)) {
        error_log("login.php: $class not found. Run: cd oauth && composer require $packages");
        ob_end_clean();
        header('Content-Type: text/html; charset=utf-8');
        die($generic_error ?: "OAuth configuration error. Please contact the administrator.");
    }
}

// Start database connection
try {
    $dsn = "mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbuser, $dbpassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log("login.php: Database connection failed: " . $e->getMessage());
    ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    die($generic_error ?: "Unable to connect to database: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

// Get site info
try {
    $stmt = $pdo->query("SELECT * FROM site_info WHERE id = 1");
    $site_info = $stmt->fetch();
    if ($site_info) {
        $title = trim($site_info['title'] ?? '');
        $des = trim($site_info['des'] ?? '');
        $baseurl = trim($site_info['baseurl'] ?? '');
        $keyword = trim($site_info['keyword'] ?? '');
        $site_name = trim($site_info['site_name'] ?? '');
        $email = trim($site_info['email'] ?? '');
        $twit = trim($site_info['twit'] ?? '');
        $face = trim($site_info['face'] ?? '');
        $gplus = trim($site_info['gplus'] ?? '');
        $ga = trim($site_info['ga'] ?? '');
        $additional_scripts = trim($site_info['additional_scripts'] ?? '');
    } else {
        error_log("login.php: Site info not found");
        ob_end_clean();
        header('Content-Type: text/html; charset=utf-8');
        die($generic_error ?: "Site info not found.");
    }
} catch (PDOException $e) {
    error_log("login.php: Failed to fetch site info: " . $e->getMessage());
    ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    die($generic_error ?: "Failed to fetch site info: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
$admin_mail = $email;
$admin_name = $site_name;

// Current Date & User IP
$date = date('Y-m-d H:i:s'); // Use DATETIME format for database
$ip = $_SERVER['REMOTE_ADDR'];
$tmp_dir = 'tmp';
$tmp_file = "$tmp_dir/temp.tdata";
if (!is_dir($tmp_dir) && !mkdir($tmp_dir, 0755, true)) {
    error_log("login.php: Failed to create tmp directory");
}

// Generate or verify CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    error_log("login.php: Pre-logout session: " . json_encode($_SESSION));
    if (ob_get_length() > 0) {
        error_log("login.php: Output buffer content before logout redirect: " . ob_get_contents());
    }
    $_SESSION = [];
    unset($_SESSION['token']);
    unset($_SESSION['oauth_uid']);
    unset($_SESSION['username']);
    unset($_SESSION['platform']);
    unset($_SESSION['id']);
    unset($_SESSION['oauth2state']);
    session_regenerate_id(true);
    session_destroy();
    ob_end_clean();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Location: ' . $baseurl);
    error_log("login.php: Redirecting to $baseurl after logout");
    exit;
}

// Check if already logged in (skip for logout action)
if (isset($_SESSION['token']) && !(isset($_GET['action']) && $_GET['action'] === 'logout')) {
    ob_end_clean();
    header("Location: ./");
    exit;
}

// Email information
try {
    $stmt = $pdo->query("SELECT * FROM mail WHERE id = 1");
    $mail = $stmt->fetch();
    if ($mail) {
        $required_fields = ['verification', 'smtp_host', 'smtp_username', 'smtp_password', 'smtp_port', 'protocol', 'auth', 'socket', 'oauth_client_id', 'oauth_client_secret', 'oauth_refresh_token'];
        foreach ($required_fields as $field) {
            if (!array_key_exists($field, $mail)) {
                error_log("login.php: Missing required field '$field' in mail table");
                ob_end_clean();
                header('Content-Type: text/html; charset=utf-8');
                die($generic_error ?: "Mail settings incomplete: Missing field '$field'. Please update the mail table.");
            }
        }
        $verification = trim($mail['verification'] ?? '');
        $smtp_host = trim($mail['smtp_host'] ?? '');
        $smtp_user = trim($mail['smtp_username'] ?? '');
        $smtp_pass = trim($mail['smtp_password'] ?? '');
        $smtp_port = trim($mail['smtp_port'] ?? '');
        $smtp_protocol = trim($mail['protocol'] ?? ''); // Fixed with null coalescing
        $smtp_auth = trim($mail['auth'] ?? '');
        $smtp_sec = trim($mail['socket'] ?? '');
        $oauth_client_id = trim($mail['oauth_client_id'] ?? '');
        $oauth_client_secret = trim($mail['oauth_client_secret'] ?? '');
        $oauth_refresh_token = trim($mail['oauth_refresh_token'] ?? '');
        $mail_type = $smtp_protocol; // Use protocol from mail table
    } else {
        error_log("login.php: Mail settings not found");
        ob_end_clean();
        header('Content-Type: text/html; charset=utf-8');
        die($generic_error ?: "Mail settings not found.");
    }
} catch (PDOException $e) {
    error_log("login.php: Failed to fetch mail settings: " . $e->getMessage());
    ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    die($generic_error ?: "Failed to fetch mail settings: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

// Set theme and language
try {
    $stmt = $pdo->query("SELECT * FROM interface WHERE id = 1");
    $interface = $stmt->fetch();
    if ($interface) {
        $default_lang = trim($interface['lang'] ?? '');
        $default_theme = trim($interface['theme'] ?? '');
    } else {
        error_log("login.php: Interface settings not found");
        ob_end_clean();
        header('Content-Type: text/html; charset=utf-8');
        die($generic_error ?: "Interface settings not found.");
    }
} catch (PDOException $e) {
    error_log("login.php: Failed to fetch interface settings: " . $e->getMessage());
    ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    die($generic_error ?: "Failed to fetch interface settings: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
require_once("langs/$default_lang");

// Page title
$p_title = $lang['login/register'] ?? 'Login / Register';

// Ads
try {
    $stmt = $pdo->query("SELECT * FROM ads WHERE id = 1");
    $ads = $stmt->fetch();
    $text_ads = $ads ? trim($ads['text_ads'] ?? '') : '';
    $ads_1 = $ads ? trim($ads['ads_1'] ?? '') : '';
    $ads_2 = $ads ? trim($ads['ads_2'] ?? '') : '';
} catch (PDOException $e) {
    error_log("login.php: Failed to fetch ads: " . $e->getMessage());
    $text_ads = $ads_1 = $ads_2 = '';
}

// Check if IP is banned
if (is_banned($pdo, $ip)) {
    ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    die($lang['banned'] ?? 'You are banned.');
}

// Page views
try {
    $stmt = $pdo->query("SELECT MAX(id) as last_id FROM page_view");
    $last_id = $stmt->fetchColumn();

    if ($last_id) {
        $stmt = $pdo->prepare("SELECT * FROM page_view WHERE id = ?");
        $stmt->execute([$last_id]);
        $page_view = $stmt->fetch();
        $last_date = $page_view['date'];

        if ($last_date == $date) {
            $data_ip = file_exists($tmp_file) ? file_get_contents($tmp_file) : '';
            if (str_contains($data_ip, $ip)) {
                $last_tpage = (int) $page_view['tpage'];
                $last_tpage += 1;
                $stmt = $pdo->prepare("UPDATE page_view SET tpage = ? WHERE id = ?");
                $stmt->execute([$last_tpage, $last_id]);
            } else {
                $last_tpage = (int) $page_view['tpage'];
                $last_tvisit = (int) $page_view['tvisit'];
                $last_tpage += 1;
                $last_tvisit += 1;
                $stmt = $pdo->prepare("UPDATE page_view SET tpage = ?, tvisit = ? WHERE id = ?");
                $stmt->execute([$last_tpage, $last_tvisit, $last_id]);
                if (is_writable($tmp_dir)) {
                    file_put_contents($tmp_file, $data_ip . "\r\n" . $ip);
                } else {
                    error_log("login.php: Cannot write to $tmp_file: directory not writable");
                }
            }
        } else {
            if (file_exists($tmp_file) && is_writable($tmp_file)) {
                unlink($tmp_file);
            }
            $data_ip = "";
            $stmt = $pdo->prepare("INSERT INTO page_view (date, tpage, tvisit) VALUES (?, 1, 1)");
            $stmt->execute([$date]);
            if (is_writable($tmp_dir)) {
                file_put_contents($tmp_file, $ip);
            } else {
                error_log("login.php: Cannot write to $tmp_file: directory not writable");
            }
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO page_view (date, tpage, tvisit) VALUES (?, 1, 1)");
        $stmt->execute([$date]);
        if (is_writable($tmp_dir)) {
            file_put_contents($tmp_file, $ip);
        } else {
            error_log("login.php: Cannot write to $tmp_file: directory not writable");
        }
    }
} catch (PDOException $e) {
    error_log("login.php: Failed to update page views: " . $e->getMessage());
}

// Verify email
if (isset($_GET['action']) && $_GET['action'] === 'verify' && isset($_GET['code']) && isset($_GET['username'])) {
    $username = filter_var(trim($_GET['username']), FILTER_SANITIZE_SPECIAL_CHARS);
    $code = filter_var(trim($_GET['code']), FILTER_SANITIZE_SPECIAL_CHARS);
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND verification_code = ?");
        $stmt->execute([$username, $code]);
        $user = $stmt->fetch();
        if ($user) {
            $stmt = $pdo->prepare("UPDATE users SET verified = '1', verification_code = NULL WHERE username = ?");
            $stmt->execute([$username]);
            $success = $lang['email_verified'] ?? 'Email verified successfully. You can now log in.';
        } else {
            $error = $lang['invalid_code'] ?? 'Invalid verification code or username.';
        }
    } catch (PDOException $e) {
        error_log("login.php: Verification error: " . $e->getMessage());
        $error = $generic_error ?: "Verification error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

// Resend verification
if (isset($_GET['action']) && $_GET['action'] === 'resend' && isset($_POST['email']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email_id = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            if ($user['verified'] === '0') {
                $username = $user['username'];
                $db_full_name = $user['full_name'];
                $verification_code = bin2hex(random_bytes(16));
                $stmt = $pdo->prepare("UPDATE users SET verification_code = ? WHERE email_id = ?");
                $stmt->execute([$verification_code, $email]);
                $verify_url = $baseurl . "login.php?action=verify&username=" . urlencode($username) . "&code=" . urlencode($verification_code);
                $subject = $lang['mail_acc_con'] ?? 'Account Confirmation';
                $body = "
                    Hello $db_full_name, please verify your account by clicking the link below.<br /><br />
                    <a href='$verify_url' target='_self'>$verify_url</a> <br /><br />
                    After confirming your account, you can log in using your <b>$username</b> and the password you used when signing up.
                ";
                $mail_result = send_mail($email, $subject, $body, $admin_name, $csrf_token);
                if ($mail_result['status'] === 'success') {
                    $success = $lang['mail_suc'] ?? 'Verification email sent.';
                } else {
                    $error = $lang['mail_error'] ?? 'Failed to send verification email: ' . htmlspecialchars($mail_result['message'], ENT_QUOTES, 'UTF-8');
                }
            } else {
                $error = $lang['email_ver'] ?? 'Email already verified.';
            }
        } else {
            $error = $lang['email_not'] ?? 'Email not found.';
        }
    } catch (PDOException $e) {
        error_log("login.php: Resend verification error: " . $e->getMessage());
        $error = $generic_error ?: "Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    } catch (Exception $e) {
        error_log("login.php: Error generating verification code: " . $e->getMessage());
        $error = $generic_error ?: "Error generating verification code: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

// Forgot password
if (isset($_GET['action']) && $_GET['action'] === 'forgot' && isset($_POST['email']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email_id = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $username = $user['username'];
            $reset_code = bin2hex(random_bytes(16));
            $reset_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $stmt = $pdo->prepare("UPDATE users SET reset_code = ?, reset_expiry = ? WHERE email_id = ?");
            $stmt->execute([$reset_code, $reset_expiry, $email]);
            $reset_url = $baseurl . "login.php?action=reset&username=" . urlencode($username) . "&code=" . urlencode($reset_code);
            $subject = "$site_name Password Reset";
            $body = "
                Hello, <br /><br />
                To reset your password, click the link below:<br /><br />
                <a href='$reset_url' target='_self'>$reset_url</a> <br /><br />
                This link will expire in 1 hour.
            ";
            $mail_result = send_mail($email, $subject, $body, $admin_name, $csrf_token);
            if ($mail_result['status'] === 'success') {
                $success = $lang['pass_change'] ?? 'Password reset link sent. Check your email.';
            } else {
                $error = $lang['mail_error'] ?? 'Failed to send password reset email: ' . htmlspecialchars($mail_result['message'], ENT_QUOTES, 'UTF-8');
            }
        } else {
            sleep(rand(0, 2)); // Prevent timing attacks
            $success = $lang['pass_change'] ?? 'Password reset link sent. Check your email.';
        }
    } catch (PDOException $e) {
        error_log("login.php: Forgot password error: " . $e->getMessage());
        $error = $generic_error ?: "Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    } catch (Exception $e) {
        error_log("login.php: Error generating reset code: " . $e->getMessage());
        $error = $generic_error ?: "Error generating reset code: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

// Reset password
if (isset($_GET['action']) && $_GET['action'] === 'reset' && isset($_GET['username']) && isset($_GET['code']) && isset($_POST['password']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $username = filter_var(trim($_GET['username']), FILTER_SANITIZE_SPECIAL_CHARS);
    $code = filter_var(trim($_GET['code']), FILTER_SANITIZE_SPECIAL_CHARS);
    $new_password = $_POST['password'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND reset_code = ? AND reset_expiry > ?");
        $stmt->execute([$username, $code, $date]);
        $user = $stmt->fetch();
        if ($user) {
            $new_pass_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_expiry = NULL WHERE username = ?");
            $stmt->execute([$new_pass_hash, $username]);
            $success = $lang['pass_reset'] ?? 'Password reset successful. You can now log in.';
        } else {
            $error = $lang['invalid_code'] ?? 'Invalid or expired reset code.';
        }
    } catch (PDOException $e) {
        error_log("login.php: Password reset error: " . $e->getMessage());
        $error = $generic_error ?: "Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

// Form processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    if (isset($_SESSION['token'])) {
        ob_end_clean();
        header("Location: ./");
        exit;
    }
    // Login
    if (isset($_POST['signin'])) {
        $username = filter_var(trim($_POST['username'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
        $password = $_POST['password'] ?? '';
        if ($username && $password) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                if ($user) {
                    if (password_verify($password, $user['password'])) {
                        if ($user['verified'] === '1') {
                            $new_token = bin2hex(random_bytes(32));
                            $stmt = $pdo->prepare("UPDATE users SET token = ? WHERE username = ?");
                            $stmt->execute([$new_token, $username]);
                            $_SESSION['token'] = $new_token;
                            $_SESSION['oauth_uid'] = $user['oauth_uid'];
                            $_SESSION['username'] = $username;
                            ob_end_clean();
                            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? $baseurl));
                            exit;
                        } elseif ($user['verified'] === '2') {
                            $error = $lang['banned'] ?? 'Your account is banned.';
                        } else {
                            $error = $lang['notverified'] ?? 'Account not verified.';
                        }
                    } else {
                        $error = $lang['incorrect'] ?? 'Incorrect username or password.';
                    }
                } else {
                    $error = $lang['incorrect'] ?? 'Incorrect username or password.';
                }
            } catch (PDOException $e) {
                error_log("login.php: Login error: " . $e->getMessage());
                $error = $generic_error ?: "Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        } else {
            $error = $lang['missingfields'] ?? 'Please fill in all fields.';
        }
    }
    // Register
    if (isset($_POST['signup'])) {
        $username = filter_var(trim($_POST['username'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
        $password = $_POST['password'] ?? '';
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $full_name = filter_var(trim($_POST['full'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
        if ($username && $password && $email && $full_name) {
            if (isValidUsername($username)) {
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = $lang['userexists'] ?? 'Username already exists.';
                    } else {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email_id = ?");
                        $stmt->execute([$email]);
                        if ($stmt->fetchColumn() > 0) {
                            $error = $lang['emailexists'] ?? 'Email already exists.';
                        } else {
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            $verified = $verification === 'disabled' ? '1' : '0';
                            $verification_code = $verification === 'disabled' ? null : bin2hex(random_bytes(16));
                            $stmt = $pdo->prepare("INSERT INTO users (oauth_uid, username, email_id, full_name, platform, password, verified, picture, date, ip, verification_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute(['0', $username, $email, $full_name, 'Direct', $password_hash, $verified, 'NONE', $date, $ip, $verification_code]);
                            if ($verification !== 'disabled') {
                                $verify_url = $baseurl . "login.php?action=verify&username=" . urlencode($username) . "&code=" . urlencode($verification_code);
                                $subject = $lang['mail_acc_con'] ?? 'Account Confirmation';
                                $body = "
                                    Hello $full_name, your $site_name account has been created. Please verify your account by clicking the link below.<br /><br />
                                    <a href='$verify_url' target='_self'>$verify_url</a> <br /><br />
                                    After confirming your account, you can log in using your <b>$username</b> and the password you used when signing up.
                                ";
                                $mail_result = send_mail($email, $subject, $body, $admin_name, $csrf_token);
                                if ($mail_result['status'] !== 'success') {
                                    $error = $lang['mail_error'] ?? 'Failed to send verification email: ' . htmlspecialchars($mail_result['message'], ENT_QUOTES, 'UTF-8');
                                }
                            }
                            if (!isset($error)) {
                                $success = $lang['registered'] ?? 'Registration successful.' . ($verification !== 'disabled' ? ' Please check your email to verify your account.' : '');
                                ob_end_clean();
                                header('Location: login.php');
                                exit;
                            }
                        }
                    }
                } catch (PDOException $e) {
                    error_log("login.php: Registration error: " . $e->getMessage());
                    $error = $generic_error ?: "Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                } catch (Exception $e) {
                    error_log("login.php: Error generating verification code: " . $e->getMessage());
                    $error = $generic_error ?: "Error generating verification code: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                }
            } else {
                $error = $lang['usrinvalid'] ?? 'Invalid username. Use only letters, numbers, .#$';
            }
        } else {
            $error = $lang['missingfields'] ?? 'Please fill in all fields.';
        }
    }
}

// Handle GET signup
if (isset($_GET['action']) && $_GET['action'] === 'signup') {
    $p_title = $lang['login/register'] ?? 'Login / Register';
}

// OAuth handling
if (isset($_GET['login']) && ($enablegoog === 'yes' || $enablefb === 'yes')) {
    if ($_GET['login'] === 'google' && $enablegoog === 'yes') {
        ob_end_clean();
        header("Location: oauth/google.php?login=1");
        exit;
    } elseif ($_GET['login'] === 'facebook' && $enablefb === 'yes') {
        ob_end_clean();
        header("Location: oauth/facebook.php?login=1");
        exit;
    } else {
        error_log("login.php: Invalid OAuth provider or disabled in config");
        $error = $generic_error ?: "Invalid OAuth provider or disabled in config.";
    }
}

// Theme
ob_end_clean();
header('Content-Type: text/html; charset=utf-8');
require_once("theme/$default_theme/header.php");
require_once("theme/$default_theme/login.php");
require_once("theme/$default_theme/footer.php");
?>