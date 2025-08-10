<?php
/*
 * Paste <old repo: https://github.com/jordansamuel/PASTE> new: https://github.com/boxlabss/PASTE
 * Demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/ - https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
session_start();

// Set default timezone
date_default_timezone_set('UTC');

$directory = 'install';
if (file_exists($directory)) {
    header("Location: install");
    exit();
}

require_once('config.php');
require_once('includes/captcha.php');
require_once('includes/functions.php');
//require_once('includes/password.php'); // php5.5 - obsolete

$stmt = $pdo->query("SELECT * FROM site_info WHERE id='1'");
$row = $stmt->fetch();
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

$stmt = $pdo->query("SELECT * FROM interface WHERE id='1'");
$row = $stmt->fetch();
$default_lang = trim($row['lang'] ?? 'en.php');
$default_theme = trim($row['theme'] ?? 'default');
require_once("langs/$default_lang");

$date = date('Y-m-d');
$ip = $_SERVER['REMOTE_ADDR'];
$data_ip = file_get_contents('tmp/temp.tdata');

$stmt = $pdo->query("SELECT * FROM ads WHERE id='1'");
$row = $stmt->fetch();
$text_ads = trim($row['text_ads'] ?? '');
$ads_1 = trim($row['ads_1'] ?? '');
$ads_2 = trim($row['ads_2'] ?? '');

$stmt = $pdo->query("SELECT * FROM sitemap_options WHERE id='1'");
$row = $stmt->fetch();
$priority = $row['priority'] ?? '0.8';
$changefreq = $row['changefreq'] ?? 'daily';

$stmt = $pdo->query("SELECT * FROM captcha WHERE id='1'");
$row = $stmt->fetch();
$color = trim($row['color'] ?? '');
$mode = trim($row['mode'] ?? 'normal');
$mul = trim($row['mul'] ?? '');
$allowed = trim($row['allowed'] ?? '');
$cap_e = trim($row['cap_e'] ?? 'off');
$recaptcha_sitekey = trim($row['recaptcha_sitekey'] ?? '');
$recaptcha_secretkey = trim($row['recaptcha_secretkey'] ?? '');
$recaptcha_version = trim($row['recaptcha_version'] ?? 'v2');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    if ($cap_e == "on") {
        if ($mode == "reCAPTCHA") {
            $_SESSION['captcha_mode'] = "recaptcha";
            $_SESSION['captcha'] = $recaptcha_sitekey;
        } else {
            $_SESSION['captcha_mode'] = "internal";
            $_SESSION['captcha'] = captcha($color, $mode, $mul, $allowed);
        }
    } else {
        $_SESSION['captcha_mode'] = "none";
    }
}

if (is_banned($pdo, $ip)) {
    die(htmlspecialchars($lang['banned'] ?? 'You are banned from this site.', ENT_QUOTES, 'UTF-8'));
}

$stmt = $pdo->query("SELECT * FROM site_permissions WHERE id='1'");
$row = $stmt->fetch();
$disableguest = trim($row['disableguest'] ?? 'off');
$siteprivate = trim($row['siteprivate'] ?? 'off');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    if ($disableguest == "on") {
        $noguests = "on";
    }
    if ($siteprivate == "on") {
        $privatesite = "on";
    }
    if (isset($_SESSION['username'])) {
        $noguests = "off";
    }
}

if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
    $callback_stripslashes = function (&$val) {
        $val = stripslashes($val);
    };
    array_walk($_GET, $callback_stripslashes);
    array_walk($_POST, $callback_stripslashes);
    array_walk($_COOKIE, $callback_stripslashes);
}

if (isset($_GET['logout'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    unset($_SESSION['token']);
    unset($_SESSION['oauth_uid']);
    unset($_SESSION['username']);
    unset($_SESSION['pic']);
    session_destroy();
}

$stmt = $pdo->query("SELECT MAX(id) AS last_id FROM page_view");
$row = $stmt->fetch();
$last_id = $row['last_id'] ?? null;

if ($last_id) {
    $stmt = $pdo->prepare("SELECT * FROM page_view WHERE id = ?");
    $stmt->execute([$last_id]);
    $row = $stmt->fetch();
    $last_date = $row['date'] ?? '';

    if ($last_date == $date) {
        if (str_contains_polyfill($data_ip, $ip)) {
            $stmt = $pdo->prepare("SELECT tpage FROM page_view WHERE id = ?");
            $stmt->execute([$last_id]);
            $last_tpage = trim($stmt->fetchColumn()) + 1;
            $stmt = $pdo->prepare("UPDATE page_view SET tpage = ? WHERE id = ?");
            $stmt->execute([$last_tpage, $last_id]);
        } else {
            $stmt = $pdo->prepare("SELECT tpage, tvisit FROM page_view WHERE id = ?");
            $stmt->execute([$last_id]);
            $row = $stmt->fetch();
            $last_tpage = trim($row['tpage']) + 1;
            $last_tvisit = trim($row['tvisit']) + 1;
            $stmt = $pdo->prepare("UPDATE page_view SET tpage = ?, tvisit = ? WHERE id = ?");
            $stmt->execute([$last_tpage, $last_tvisit, $last_id]);
            file_put_contents('tmp/temp.tdata', $data_ip . "\r\n" . $ip);
        }
    } else {
        unlink("tmp/temp.tdata");
        $data_ip = "";
        $stmt = $pdo->prepare("INSERT INTO page_view (date, tpage, tvisit) VALUES (?, '1', '1')");
        $stmt->execute([$date]);
        file_put_contents('tmp/temp.tdata', $data_ip . "\r\n" . $ip);
    }
} else {
    unlink("tmp/temp.tdata");
    $data_ip = "";
    $stmt = $pdo->prepare("INSERT INTO page_view (date, tpage, tvisit) VALUES (?, '1', '1')");
    $stmt->execute([$date]);
    file_put_contents('tmp/temp.tdata', $data_ip . "\r\n" . $ip);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST["paste_data"]) || trim($_POST["paste_data"]) == '') {
        $error = $lang['empty_paste'] ?? 'Paste content cannot be empty.';
        goto OutPut;
    }

    if (mb_strlen($_POST["paste_data"], '8bit') > 1024 * 1024 * ($pastelimit ?? 10)) {
        $error = $lang['large_paste'] ?? 'Paste is too large.';
        goto OutPut;
    }

    if (isset($_POST['title']) && isset($_POST['paste_data'])) {
        if ($cap_e == "on" && !isset($_SESSION['username'])) {
            if ($mode == "reCAPTCHA") {
                // Check if g-recaptcha-response is set and not empty
                if (!isset($_POST['g-recaptcha-response']) || empty($_POST['g-recaptcha-response'])) {
                    error_log("reCAPTCHA error: g-recaptcha-response missing or empty");
                    $error = $lang['recaptcha_missing'] ?? 'Please complete the reCAPTCHA.';
                    goto OutPut;
                }

                // Verify reCAPTCHA with Google's API
                $recaptcha_url = "https://www.google.com/recaptcha/api/siteverify";
                $recaptcha_data = [
                    'secret' => $recaptcha_secretkey,
                    'response' => $_POST['g-recaptcha-response'],
                    'remoteip' => $_SERVER['REMOTE_ADDR']
                ];

                // Try cURL first
                $response = false;
                if (function_exists('curl_init')) {
                    $ch = curl_init($recaptcha_url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($recaptcha_data));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    $response = curl_exec($ch);
                    $curl_error = curl_error($ch);
                    curl_close($ch);

                    if ($curl_error) {
                        error_log("reCAPTCHA cURL error: " . $curl_error);
                    }
                }

                // Fallback to file_get_contents if cURL fails or is unavailable
                if ($response === false) {
                    $response = @file_get_contents($recaptcha_url . '?' . http_build_query($recaptcha_data));
                    if ($response === false) {
                        error_log("reCAPTCHA file_get_contents failed");
                        $error = $lang['recaptcha_error'] ?? 'Failed to verify reCAPTCHA. Please try again.';
                        goto OutPut;
                    }
                }

                $response = json_decode($response, true);

                if ($response === null || !isset($response['success'])) {
                    error_log("reCAPTCHA API response invalid: " . print_r($response, true));
                    $error = $lang['recaptcha_error'] ?? 'Invalid reCAPTCHA response from server.';
                    goto OutPut;
                }

                if ($response['success'] === false) {
                    $error_codes = $response['error-codes'] ?? [];
                    $error_message = !empty($error_codes) ? ($lang[$error_codes[0]] ?? implode(', ', $error_codes)) : ($lang['recaptcha_failed'] ?? 'reCAPTCHA verification failed.');
                    error_log("reCAPTCHA verification failed: " . $error_message);
                    $error = $error_message;
                    goto OutPut;
                }

                // For v3, check the score (if applicable)
                if ($recaptcha_version === 'v3' && ($response['score'] ?? 0) < 0.5) {
                    error_log("reCAPTCHA v3 low score: " . ($response['score'] ?? 0));
                    $error = $lang['low_score'] ?? 'Your action was flagged as potentially automated. Please try again.';
                    goto OutPut;
                }
            } else {
                $scode = strtolower(htmlentities(trim($_POST['scode'] ?? '')));
                $cap_code = strtolower($_SESSION['captcha']['code'] ?? '');
                if ($cap_code != $scode) {
                    $error = $lang['image_wrong'] ?? 'Incorrect CAPTCHA code.';
                    goto OutPut;
                }
            }
        }

        $p_title = trim(htmlspecialchars($_POST['title'] ?? '')) ?: 'Untitled';
        $p_content = htmlspecialchars($_POST['paste_data']);
        $p_visible = trim(htmlspecialchars($_POST['visibility'] ?? '0'));
        $p_code = trim(htmlspecialchars($_POST['format'] ?? 'text'));
        $p_expiry = trim(htmlspecialchars($_POST['paste_expire_date'] ?? 'N'));
        $p_password = trim($_POST['pass'] ?? '') === '' ? 'NONE' : trim($_POST['pass']);
        $p_encrypt = '1'; // Always encrypt
        $p_member = (string) ($_SESSION['username'] ?? 'Guest');
        $p_date = date('Y-m-d H:i:s');
        $now_time = mktime(date("H"), date("i"), date("s"), date("n"), date("j"), date("Y"));
        $date = date('Y-m-d');

        // Encrypt content
        try {
            $p_content = encrypt($p_content, hex2bin(SECRET));
            if ($p_content === null) {
                $error = $lang['error'] ?? 'Encryption failed.';
                goto OutPut;
            }
        } catch (RuntimeException $e) {
            $error = ($lang['error'] ?? 'Error') . ': ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            goto OutPut;
        }

        if ($p_password != "NONE") {
            $p_password = password_hash($p_password, PASSWORD_DEFAULT);
            if ($p_password === false) {
                $error = $lang['error'] ?? 'Password hashing failed.';
                goto OutPut;
            }
        }

        $expires = match ($p_expiry) {
            '10M' => mktime(date("H"), date("i") + 10, date("s"), date("n"), date("j"), date("Y")),
            '1H' => mktime(date("H") + 1, date("i"), date("s"), date("n"), date("j"), date("Y")),
            '1D' => mktime(date("H"), date("i"), date("s"), date("n"), date("j") + 1, date("Y")),
            '1W' => mktime(date("H"), date("i"), date("s"), date("n"), date("j") + 7, date("Y")),
            '2W' => mktime(date("H"), date("i"), date("s"), date("n"), date("j") + 14, date("Y")),
            '1M' => mktime(date("H"), date("i"), date("s"), date("n") + 1, date("j"), date("Y")),
            'self' => "SELF",
            default => "NULL",
        };

        try {
            if (isset($_POST['edit'])) {
                $edit_paste_id = (int) ($_POST['paste_id'] ?? 0);
                $stmt = $pdo->prepare("UPDATE pastes SET title = ?, content = ?, visible = ?, code = ?, expiry = ?, password = ?, encrypt = ?, member = ?, date = ?, ip = ? WHERE id = ?");
                $stmt->execute([$p_title, $p_content, $p_visible, $p_code, $expires, $p_password, $p_encrypt, $p_member, $p_date, $ip, $edit_paste_id]);
                $success = $edit_paste_id;
            } else {
                $stmt = $pdo->prepare("INSERT INTO pastes (title, content, visible, code, expiry, password, encrypt, member, date, ip, now_time, views, s_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '0', ?)");
                $stmt->execute([$p_title, $p_content, $p_visible, $p_code, $expires, $p_password, $p_encrypt, $p_member, $p_date, $ip, $now_time, $date]);
                $success = $pdo->lastInsertId();
            }

            if ($p_visible == '0') {
                addToSitemap($pdo, (int) $success, $priority, $changefreq, $mod_rewrite);
            }

            $paste_url = $mod_rewrite == '1' ? "/$success" : "paste.php?id=$success";
            header("Location: $paste_url");
            exit;
        } catch (PDOException $e) {
            error_log("Database error in INSERT/UPDATE: " . $e->getMessage() . " | Query: " . $stmt->queryString);
            $error = ($lang['paste_db_error'] ?? 'Database error.') . ': ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            goto OutPut;
        }
    } else {
        $error = $lang['error'] ?? 'Invalid form submission.';
    }
}

OutPut:
require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/header.php');
require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/main.php');
require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/footer.php');
?>