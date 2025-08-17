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
require_once 'includes/session.php';
// Debugging for reCAPTCHA - /index.php?forcefail=1 - index.php?forcepass=1
// Only uncomment if you need to test. 
//if (isset($_GET['forcefail'])) { $_SESSION['forcefail'] = (int)$_GET['forcefail']; }
//if (isset($_GET['forcepass'])) { $_SESSION['forcepass'] = (int)$_GET['forcepass']; }

// production-style error handling
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_log("index.php boot");

// timezone
date_default_timezone_set('UTC');

// core includes
require_once('config.php');
require_once('includes/captcha.php');
require_once('includes/functions.php');

// ip
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// pull config from DB
try {
    // site_info
    $stmt = $pdo->query("SELECT * FROM site_info WHERE id='1'");
    $siteinfo = $stmt->fetch() ?: [];
    $title       = trim($siteinfo['title'] ?? 'Paste');
    $des         = trim($siteinfo['des'] ?? '');
    $baseurl     = trim($siteinfo['baseurl'] ?? '');
    $keyword     = trim($siteinfo['keyword'] ?? '');
    $site_name   = trim($siteinfo['site_name'] ?? 'Paste');
    $ga          = trim($siteinfo['ga'] ?? '');
    $additional_scripts = trim($siteinfo['additional_scripts'] ?? '');

    // interface
    $stmt = $pdo->query("SELECT * FROM interface WHERE id='1'");
    $iface = $stmt->fetch() ?: [];
    $default_lang  = trim($iface['lang'] ?? 'en.php');
    $default_theme = trim($iface['theme'] ?? 'default');
    require_once("langs/$default_lang");

    // ads
    $stmt = $pdo->query("SELECT * FROM ads WHERE id='1'");
    $ads = $stmt->fetch() ?: [];
    $text_ads = trim($ads['text_ads'] ?? '');
    $ads_1    = trim($ads['ads_1'] ?? '');
    $ads_2    = trim($ads['ads_2'] ?? '');

    // sitemap options
    $stmt = $pdo->query("SELECT * FROM sitemap_options WHERE id='1'");
    $sm = $stmt->fetch() ?: [];
    $priority   = $sm['priority']   ?? '0.8';
    $changefreq = $sm['changefreq'] ?? 'daily';

    // captcha settings (from admin/configuration.php)
    $stmt = $pdo->query("SELECT * FROM captcha WHERE id='1'");
    $cap = $stmt->fetch() ?: [];
    $color                = trim($cap['color'] ?? '');
    $mode                 = trim($cap['mode'] ?? 'normal');          // "normal" | "reCAPTCHA"
    $mul                  = trim($cap['mul'] ?? '');
    $allowed              = trim($cap['allowed'] ?? '');
    $cap_e                = trim($cap['cap_e'] ?? 'off');            // "on" | "off"
    $recaptcha_sitekey    = trim($cap['recaptcha_sitekey'] ?? '');
    $recaptcha_secretkey  = trim($cap['recaptcha_secretkey'] ?? '');
    $recaptcha_version    = trim($cap['recaptcha_version'] ?? 'v2'); // "v2" | "v3"

	// Mirror captcha config into session for recaptcha.php (expects these names)
	$_SESSION['cap_e']              = $cap_e;                 // 'on'|'off'
	$_SESSION['mode']               = $mode;                  // 'reCAPTCHA'|'normal'
	$_SESSION['recaptcha_version']  = $recaptcha_version;     // 'v2'|'v3'
	$_SESSION['recaptcha_sitekey']  = $recaptcha_sitekey;     // site key used by client
	$_SESSION['recaptcha_secretkey']= $recaptcha_secretkey;   // secret key used by server

    // permissions
    $stmt = $pdo->query("SELECT * FROM site_permissions WHERE id='1'");
    $perm = $stmt->fetch() ?: [];
    $disableguest = trim($perm['disableguest'] ?? 'off');
    $siteprivate  = trim($perm['siteprivate'] ?? 'off');

    // rewrite flag (from config.php)
    $mod_rewrite = isset($mod_rewrite) ? $mod_rewrite : '0';
} catch (PDOException $e) {
    error_log("index.php: DB error ".$e->getMessage());
    $error = $lang['db_error'] ?? 'Database error.';
    goto OutPut;
}

// set session flags for captcha on initial GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($cap_e === "on") {
        if ($mode === "reCAPTCHA") {
            $_SESSION['captcha_mode'] = ($recaptcha_version === 'v3') ? "recaptcha_v3" : "recaptcha";
            $_SESSION['captcha']      = $recaptcha_sitekey;
        } else {
            $_SESSION['captcha_mode'] = "internal";
            $_SESSION['captcha']      = captcha($color, $mode, $mul, $allowed);
        }
    } else {
        $_SESSION['captcha_mode'] = "none";
    }
}

// ban check
if (is_banned($pdo, $ip)) {
    $error = $lang['banned'] ?? 'You are banned from this site.';
    goto OutPut;
}

// guest/private flags for theme
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($disableguest === "on") {
        $noguests = "on";
    }
    if ($siteprivate === "on") {
        $privatesite = "on";
    }
    if (isset($_SESSION['username'])) {
        $noguests = "off";
    }
}

// logout passthrough
if (isset($_GET['logout'])) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? $baseurl));
    unset($_SESSION['token'], $_SESSION['oauth_uid'], $_SESSION['username'], $_SESSION['pic']);
    session_destroy();
}

// page views
try {
    $date = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT id, tpage, tvisit FROM page_view WHERE date = ?");
    $stmt->execute([$date]);
    $pv = $stmt->fetch();
    if ($pv) {
        $page_view_id = (int)$pv['id'];
        $tpage  = (int)$pv['tpage'] + 1;
        $tvisit = (int)$pv['tvisit'];
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM visitor_ips WHERE ip = ? AND visit_date = ?");
        $stmt->execute([$ip, $date]);
        if ((int)$stmt->fetchColumn() === 0) {
            $tvisit += 1;
            $stmt = $pdo->prepare("INSERT INTO visitor_ips (ip, visit_date) VALUES (?, ?)");
            $stmt->execute([$ip, $date]);
        }
        $stmt = $pdo->prepare("UPDATE page_view SET tpage = ?, tvisit = ? WHERE id = ?");
        $stmt->execute([$tpage, $tvisit, $page_view_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO page_view (date, tpage, tvisit) VALUES (?, ?, ?)");
        $stmt->execute([$date, 1, 1]);
        $stmt = $pdo->prepare("INSERT INTO visitor_ips (ip, visit_date) VALUES (?, ?)");
        $stmt->execute([$ip, $date]);
    }
} catch (PDOException $e) {
    error_log("index.php: page view err ".$e->getMessage());
}

// POST: create paste
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // empty content
    if (empty($_POST["paste_data"]) || trim($_POST["paste_data"]) === '') {
        $error = $lang['empty_paste'] ?? 'Paste content cannot be empty.';
        goto OutPut;
    }

    // size check
    if (mb_strlen($_POST["paste_data"], '8bit') > 1024 * 1024 * ($pastelimit ?? 10)) {
        $error = $lang['large_paste'] ?? 'Paste is too large.';
        goto OutPut;
    }

    // require fields
    if (!isset($_POST['title']) || !isset($_POST['paste_data'])) {
        $error = $lang['error'] ?? 'Invalid form submission.';
        goto OutPut;
    }
	
	// --- debug overrides (forcefail/forcepass) ---
	// Persisted earlier via GET -> session. Handle them here to bypass ALL captcha paths.
	$captchaOverridePass = !empty($_SESSION['forcepass']);
	$captchaOverrideFail = !empty($_SESSION['forcefail']);

	// consume them so they only affect one submit
	if ($captchaOverridePass) unset($_SESSION['forcepass']);
	if ($captchaOverrideFail) unset($_SESSION['forcefail']);

	// captcha checks for guests (respect admin config)
	if (!isset($_SESSION['username']) && ($disableguest !== "on")) {

		// 1) debug overrides first
		if ($captchaOverridePass) {
			// Skip ALL captcha checks
			// (do nothing)
		} elseif ($captchaOverrideFail) {
			// Force a visible, soft error like the internal captcha branch does
			$error = $lang['recaptcha_failed'] ?? 'reCAPTCHA verification failed.';
			goto OutPut;
		} else {
			// 2) normal behaviour
			if ($cap_e === "on") {
				if ($mode === "reCAPTCHA") {
					require_once __DIR__ . '/includes/recaptcha.php';
					require_human('create_paste'); // may set $GLOBALS['error']
					if (!empty($error)) { 
						// Map to language string used in main.php alert (soft error)
						$error = $lang['recaptcha_failed'] ?? 'reCAPTCHA failed to verify you\'re not a bot. Refresh and try again.';
						goto OutPut; 
					}
				} else {
					// internal captcha (image)
					$scode    = strtolower(htmlentities(trim($_POST['scode'] ?? '')));
					$cap_code = strtolower($_SESSION['captcha']['code'] ?? '');
					if ($cap_code !== $scode) {
						$error = $lang['image_wrong'] ?? 'Incorrect CAPTCHA code.';
						goto OutPut;
					}
				}
			}
		}
	}

    // sanitize inputs
    $p_title   = trim(htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8')) ?: 'Untitled';
    $p_content = htmlspecialchars($_POST['paste_data'], ENT_QUOTES, 'UTF-8');
    $p_visible = trim(htmlspecialchars($_POST['visibility'] ?? '0', ENT_QUOTES, 'UTF-8'));
    $p_code    = trim(htmlspecialchars($_POST['format'] ?? 'text', ENT_QUOTES, 'UTF-8'));
    $p_expiry  = trim(htmlspecialchars($_POST['paste_expire_date'] ?? 'N', ENT_QUOTES, 'UTF-8'));
    $p_password = trim($_POST['pass'] ?? '') === '' ? 'NONE' : trim($_POST['pass']);
    $p_encrypt = '1';
    $p_member  = (string)($_SESSION['username'] ?? 'Guest');
    $p_date    = date('Y-m-d H:i:s');
    $now_time  = mktime(date("H"), date("i"), date("s"), date("n"), date("j"), date("Y"));
    $s_date    = date('Y-m-d');

    // encrypt content
    try {
        if (!defined('SECRET')) {
            error_log("index.php: SECRET undefined");
            $error = $lang['error'] ?? 'Server configuration error.';
            goto OutPut;
        }
        $p_content = encrypt($p_content, hex2bin(SECRET));
        if ($p_content === null) {
            $error = $lang['error'] ?? 'Encryption failed.';
            goto OutPut;
        }
    } catch (RuntimeException $e) {
        $error = ($lang['error'] ?? 'Error') . ': ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        goto OutPut;
    }

    // hash password if provided
    if ($p_password !== "NONE") {
        $p_password = password_hash($p_password, PASSWORD_DEFAULT);
        if ($p_password === false) {
            $error = $lang['error'] ?? 'Password hashing failed.';
            goto OutPut;
        }
    }

    // expiry
    $expires = match ($p_expiry) {
        '10M' => mktime(date("H"), date("i") + 10, date("s"), date("n"), date("j"), date("Y")),
        '1H'  => mktime(date("H") + 1, date("i"), date("s"), date("n"), date("j"), date("Y")),
        '1D'  => mktime(date("H"), date("i"), date("s"), date("n"), date("j") + 1, date("Y")),
        '1W'  => mktime(date("H"), date("i"), date("s"), date("n"), date("j") + 7, date("Y")),
        '2W'  => mktime(date("H"), date("i"), date("s"), date("n"), date("j") + 14, date("Y")),
        '1M'  => mktime(date("H"), date("i"), date("s"), date("n") + 1, date("j"), date("Y")),
        'self'=> "SELF",
        default => "NULL",
    };

    // insert
    try {
        $stmt = $pdo->prepare("INSERT INTO pastes (title, content, visible, code, expiry, password, encrypt, member, date, ip, now_time, s_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$p_title, $p_content, $p_visible, $p_code, $expires, $p_password, $p_encrypt, $p_member, $p_date, $ip, $now_time, $s_date]);
        $paste_id = $pdo->lastInsertId();

        // sitemap for public
        if ($p_visible === '0') {
            addToSitemap($pdo, (int)$paste_id, $priority, $changefreq, $mod_rewrite == '1');
        }

        // redirect to paste
        $paste_url = ($mod_rewrite == '1') ? ($baseurl . $paste_id) : ($baseurl . 'paste.php?id=' . $paste_id);
        header("Location: " . $paste_url);
        exit;
    } catch (PDOException $e) {
        error_log("index.php: insert err ".$e->getMessage());
        $error = ($lang['paste_db_error'] ?? 'Database error.') . ': ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        goto OutPut;
    }
}

// output: render theme
OutPut:
$themeDir = 'theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8');

require_once $themeDir . '/header.php';

/**
 * Decide which view to render.
 * Hard errors -> errors.php
 * Soft form errors -> $error
 */
$error_text = $error ?? '';
$notfound   = $notfound ?? '';
$needs_pw   = !empty($require_password);

// classify hard vs soft
$error_hard = false;
if ($notfound !== '' || $needs_pw) {
    $error_hard = true; // 404 / password
} elseif ($error_text !== '') {
    $hard_markers = [
        'banned', 'Database error', 'Encryption failed',
        'Password hashing failed', 'Server configuration error',
    ];
    foreach ($hard_markers as $m) {
        if (stripos($error_text, $m) !== false) { $error_hard = true; break; }
    }
}

if ($error_hard) {
    // HARD: render errors.php between header & footer
    $err = $themeDir . '/errors.php';
    if (is_file($err)) {
        $error_msg = $error_text;   // expose to partial
        require $err;
    } else {
        echo '<main class="container py-4"><div class="alert alert-danger" role="alert">'
            . htmlspecialchars($error_text ?: ($notfound ?: ($lang['error'] ?? 'An error occurred.')), ENT_QUOTES, 'UTF-8')
            . '</div></main>';
    }
} else {
    // SOFT: show form with inline alert
    if ($error_text !== '') { $flash_error = $error_text; }
    require_once $themeDir . '/main.php';
}

require_once $themeDir . '/footer.php';