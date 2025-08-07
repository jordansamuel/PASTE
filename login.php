<?php
/*
 * Paste <//github.com/jordansamuel/PASTE>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in GPL.txt for more details.
 */
declare(strict_types=1);

ob_start(); // Start output buffering
session_start([
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
]);

// Required functions
require_once('config.php');
require_once('includes/password.php');
require_once('includes/functions.php');
require_once('mail/mail.php');

// Start database connection
try {
    $dsn = "mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbuser, $dbpassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Unable to connect to database: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

// Current Date & User IP
$date = date('Y-m-d H:i:s'); // Use DATETIME format for database
$ip = $_SERVER['REMOTE_ADDR'];
$tmp_dir = 'tmp';
$tmp_file = "$tmp_dir/temp.tdata";
if (!is_dir($tmp_dir) && !mkdir($tmp_dir, 0755, true)) {
    error_log("Failed to create tmp directory");
}
$data_ip = file_exists($tmp_file) ? file_get_contents($tmp_file) : '';

// Check if already logged in
if (isset($_SESSION['token'])) {
    header("Location: ./");
    exit;
}

// Get site info
try {
    $stmt = $pdo->query("SELECT * FROM site_info WHERE id = 1");
    $site_info = $stmt->fetch();
    if ($site_info) {
        $title = trim($site_info['title']);
        $des = trim($site_info['des']);
        $baseurl = trim($site_info['baseurl']);
        $keyword = trim($site_info['keyword']);
        $site_name = trim($site_info['site_name']);
        $email = trim($site_info['email']);
        $twit = trim($site_info['twit']);
        $face = trim($site_info['face']);
        $gplus = trim($site_info['gplus']);
        $ga = trim($site_info['ga']);
        $additional_scripts = trim($site_info['additional_scripts']);
    } else {
        error_log("Site info not found");
        die("Site info not found.");
    }
} catch (PDOException $e) {
    error_log("Failed to fetch site info: " . $e->getMessage());
    die("Failed to fetch site info: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
$admin_mail = $email;
$admin_name = $site_name;

// Email information
try {
    $stmt = $pdo->query("SELECT * FROM mail WHERE id = 1");
    $mail = $stmt->fetch();
    if ($mail) {
        $verification = trim($mail['verification']);
        $smtp_host = trim($mail['smtp_host']);
        $smtp_user = trim($mail['smtp_username']);
        $smtp_pass = trim($mail['smtp_password']);
        $smtp_port = trim($mail['smtp_port']);
        $smtp_protocol = trim($mail['protocol']);
        $smtp_auth = trim($mail['auth']);
        $smtp_sec = trim($mail['socket']);
        $mail_type = $smtp_protocol; // Use protocol from mail table
    } else {
        error_log("Mail settings not found");
        die("Mail settings not found.");
    }
} catch (PDOException $e) {
    error_log("Failed to fetch mail settings: " . $e->getMessage());
    die("Failed to fetch mail settings: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

// Set theme and language
try {
    $stmt = $pdo->query("SELECT * FROM interface WHERE id = 1");
    $interface = $stmt->fetch();
    if ($interface) {
        $default_lang = trim($interface['lang']);
        $default_theme = trim($interface['theme']);
    } else {
        error_log("Interface settings not found");
        die("Interface settings not found.");
    }
} catch (PDOException $e) {
    error_log("Failed to fetch interface settings: " . $e->getMessage());
    die("Failed to fetch interface settings: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
require_once("langs/$default_lang");

// Page title
$p_title = $lang['login/register'] ?? 'Login / Register';

// Ads
try {
    $stmt = $pdo->query("SELECT * FROM ads WHERE id = 1");
    $ads = $stmt->fetch();
    $text_ads = $ads ? trim($ads['text_ads']) : '';
    $ads_1 = $ads ? trim($ads['ads_1']) : '';
    $ads_2 = $ads ? trim($ads['ads_2']) : '';
} catch (PDOException $e) {
    error_log("Failed to fetch ads: " . $e->getMessage());
    $text_ads = $ads_1 = $ads_2 = '';
}

// Check if IP is banned
if (is_banned($pdo, $ip)) {
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
            if (str_contains_polyfill($data_ip, $ip)) {
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
                    error_log("Cannot write to $tmp_file: directory not writable");
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
                error_log("Cannot write to $tmp_file: directory not writable");
            }
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO page_view (date, tpage, tvisit) VALUES (?, 1, 1)");
        $stmt->execute([$date]);
        if (is_writable($tmp_dir)) {
            file_put_contents($tmp_file, $ip);
        } else {
            error_log("Cannot write to $tmp_file: directory not writable");
        }
    }
} catch (PDOException $e) {
    error_log("Failed to update page views: " . $e->getMessage());
}

// Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['token']);
    unset($_SESSION['oauth_uid']);
    unset($_SESSION['username']);
    session_destroy();
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? $baseurl));
    exit;
}

// Resend verification
if (isset($_GET['resend']) && isset($_POST['email'])) {
    $email = htmlentities(trim($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email_id = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            if ($user['verified'] == '0') {
                $username = $user['username'];
                $db_email_id = $user['email_id'];
                $db_full_name = $user['full_name'];
                $protocol = paste_protocol();
                $verify_url = $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/verify.php?username=$username&code=" . hash('sha256', '4et4$55765' . $db_email_id . 'd94ereg');
                $sent_mail = $email;
                $subject = $lang['mail_acc_con'] ?? 'Account Confirmation';
                $body = "
                    Hello $db_full_name, Please verify your account by clicking the link below.<br /><br />
                    <a href='$verify_url' target='_self'>$verify_url</a> <br /> <br />
                    After confirming your account you can log in using your <b>$username</b> and the password you used when signing up.
                ";
                if ($mail_type == '1') {
                    default_mail($admin_mail, $admin_name, $sent_mail, $subject, $body);
                } else {
                    smtp_mail($subject, $body, $sent_mail, $admin_mail, $admin_name, $smtp_auth, $smtp_user, $smtp_pass, $smtp_host, $smtp_port, $smtp_sec);
                }
                $success = $lang['mail_suc'] ?? 'Verification email sent.';
            } else {
                $error = $lang['email_ver'] ?? 'Email already verified.';
            }
        } else {
            $error = $lang['email_not'] ?? 'Email not found.';
        }
    } catch (PDOException $e) {
        $error = "Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

// Forgot password
if (isset($_GET['forgot']) && isset($_POST['email'])) {
    $email = htmlentities(trim($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email_id = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $username = $user['username'];
            $new_pass = bin2hex(random_bytes(8));
            $new_pass_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
            $stmt->execute([$new_pass_hash, $username]);
            $success = $lang['pass_change'] ?? 'Password reset successful. Check your email.';
            $sent_mail = $email;
            $subject = "$site_name Password Reset";
            $body = "
                Hello, <br /><br />
                Your new password is: $new_pass <br /> <br />
                You can now login and change your password.
            ";
            if ($mail_type == '1') {
                default_mail($admin_mail, $admin_name, $sent_mail, $subject, $body);
            } else {
                smtp_mail($subject, $body, $sent_mail, $admin_mail, $admin_name, $smtp_auth, $smtp_user, $smtp_pass, $smtp_host, $smtp_port, $smtp_sec);
            }
        } else {
            sleep(rand(0, 2));
            $success = $lang['pass_change'] ?? 'Password reset successful. Check your email.';
        }
    } catch (PDOException $e) {
        $error = "Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    } catch (Exception $e) {
        $error = "Error generating password: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

// Form processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SESSION['token'])) {
        header("Location: ./");
        exit;
    }
    // Login
    if (isset($_POST['signin'])) {
        $username = htmlentities(trim($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
        $password = $_POST['password'] ?? '';
        if ($username && $password) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                if ($user) {
                    if (password_verify($password, $user['password'])) {
                        if ($user['verified'] === '1') {
                            $_SESSION['token'] = hash('sha256', $user['id'] . $username . random_bytes(16));
                            $_SESSION['oauth_uid'] = $user['oauth_uid'];
                            $_SESSION['username'] = $username;
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
                $error = "Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        } else {
            $error = $lang['missingfields'] ?? 'Please fill in all fields.';
        }
    }
    // Register
    if (isset($_POST['signup'])) {
        $username = htmlentities(trim($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
        $password = $_POST['password'] ?? '';
        $email = htmlentities(trim($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');
        $full_name = htmlentities(trim($_POST['full'] ?? ''), ENT_QUOTES, 'UTF-8');
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
                            $stmt = $pdo->prepare("INSERT INTO users (oauth_uid, username, email_id, full_name, platform, password, verified, picture, date, ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute(['0', $username, $email, $full_name, 'Direct', $password_hash, $verified, 'NONE', $date, $ip]);
                            if ($verification !== 'disabled') {
                                $protocol = paste_protocol();
                                $verify_url = $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/verify.php?username=$username&code=" . hash('sha256', '4et4$55765' . $email . 'd94ereg');
                                $sent_mail = $email;
                                $subject = $lang['mail_acc_con'] ?? 'Account Confirmation';
                                $body = "
                                    Hello $full_name, Your $site_name account has been created. Please verify your account by clicking the link below.<br /><br />
                                    <a href='$verify_url' target='_self'>$verify_url</a> <br /> <br />
                                    After confirming your account you can log in using your <b>$username</b> and the password you used when signing up.
                                ";
                                if ($mail_type == '1') {
                                    default_mail($admin_mail, $admin_name, $sent_mail, $subject, $body);
                                } else {
                                    smtp_mail($subject, $body, $sent_mail, $admin_mail, $admin_name, $smtp_auth, $smtp_user, $smtp_pass, $smtp_host, $smtp_port, $smtp_sec);
                                }
                            }
                            $success = $lang['registered'] ?? 'Registration successful.';
                            header('Location: login.php');
                            exit;
                        }
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
if (isset($_GET['signup'])) {
    $p_title = $lang['login/register'] ?? 'Login / Register';
}

// OAuth handling
if (isset($_GET['login']) && ($enablegoog == 'yes' || $enablefb == 'yes')) {
    require_once('theme/default/oauth.php');
}

// Theme
require_once('theme/' . $default_theme . '/header.php');
require_once('theme/' . $default_theme . '/login.php');
require_once('theme/' . $default_theme . '/footer.php');
ob_end_flush();
?>