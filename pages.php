<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
require_once 'includes/session.php';
require_once 'config.php';
require_once 'includes/functions.php';

// UTF-8
header('Content-Type: text/html; charset=utf-8');

$date = date('Y-m-d');
$ip = $_SERVER['REMOTE_ADDR'];
$data_ip = @file_get_contents('tmp/temp.tdata') ?: '';

// Database Connection (PDO from config.php)
global $pdo;

try {
    // Get site info
    $stmt = $pdo->query("SELECT * FROM site_info WHERE id = '1'");
    $row = $stmt->fetch();
    if (!$row) {
        throw new Exception("Site configuration not found.");
    }
    $title = trim($row['title']);
    $des = trim($row['des']);
    $baseurl = rtrim(trim($row['baseurl']));
    $keyword = trim($row['keyword']);
    $site_name = trim($row['site_name']);
    $email = trim($row['email']);
    $twit = trim($row['twit']);
    $face = trim($row['face']);
    $gplus = trim($row['gplus']);
    $ga = trim($row['ga']);
    $additional_scripts = trim($row['additional_scripts']);

    // Set theme and language
    $stmt = $pdo->query("SELECT * FROM interface WHERE id = '1'");
    $row = $stmt->fetch();
    if (!$row) {
        throw new Exception("Interface configuration not found.");
    }
    $default_lang = trim($row['lang']);
    $default_theme = trim($row['theme']);
    require_once("langs/$default_lang");

    // Check if IP is banned
    if (is_banned($pdo, $ip)) die($lang['banned']);

    // Logout
    if (isset($_GET['logout'])) {
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? $baseurl));
        unset($_SESSION['token']);
        unset($_SESSION['oauth_uid']);
        unset($_SESSION['username']);
        session_destroy();
        exit;
    }

	// Page views
	$date = date('Y-m-d');
	$ip = $_SERVER['REMOTE_ADDR'];

	try {
		// Fetch or create the page_view record for today
		$stmt = $pdo->prepare("SELECT id, tpage, tvisit FROM page_view WHERE date = ?");
		$stmt->execute([$date]);
		$row = $stmt->fetch();

		if ($row) {
			// Record exists for today
			$page_view_id = $row['id'];
			$tpage = (int)$row['tpage'] + 1; // Increment total page views
			$tvisit = (int)$row['tvisit'];

			// Check if this IP has visited today
			$stmt = $pdo->prepare("SELECT COUNT(*) FROM visitor_ips WHERE ip = ? AND visit_date = ?");
			$stmt->execute([$ip, $date]);
			if ($stmt->fetchColumn() == 0) {
				// New unique visitor
				$tvisit += 1;
				$stmt = $pdo->prepare("INSERT INTO visitor_ips (ip, visit_date) VALUES (?, ?)");
				$stmt->execute([$ip, $date]);
			}

			// Update page_view with new counts
			$stmt = $pdo->prepare("UPDATE page_view SET tpage = ?, tvisit = ? WHERE id = ?");
			$stmt->execute([$tpage, $tvisit, $page_view_id]);
		} else {
			// No record for today: create one
			$tpage = 1;
			$tvisit = 1;
			$stmt = $pdo->prepare("INSERT INTO page_view (date, tpage, tvisit) VALUES (?, ?, ?)");
			$stmt->execute([$date, $tpage, $tvisit]);

			// Log the visitor's IP
			$stmt = $pdo->prepare("INSERT INTO visitor_ips (ip, visit_date) VALUES (?, ?)");
			$stmt->execute([$ip, $date]);
		}
	} catch (PDOException $e) {
		error_log("Page view tracking error: " . $e->getMessage());
	}

    // Ads
    $stmt = $pdo->query("SELECT * FROM ads WHERE id = '1'");
    $row = $stmt->fetch();
    if (!$row) {
        $text_ads = $ads_1 = $ads_2 = '';
    } else {
        $text_ads = trim($row['text_ads']);
        $ads_1 = trim($row['ads_1']);
        $ads_2 = trim($row['ads_2']);
    }

	// Accept both ?p=slug and ?page=slug (mod_rewrite typically maps to ?p=)
	$page_name = isset($_GET['p']) ? trim($_GET['p']) : (isset($_GET['page']) ? trim($_GET['page']) : '');
	if ($page_name !== '') {
		$stmt = $pdo->prepare("
			SELECT page_title, page_content, last_date
			FROM pages
			WHERE page_name = ? AND is_active = 1
			LIMIT 1
		");
		$stmt->execute([$page_name]);
		$row = $stmt->fetch();
		if ($row) {
			$page_title   = $row['page_title'];
			$page_content = $row['page_content'];
			$last_date    = $row['last_date'];
			$stats        = "OK";
			$p_title      = $page_title;
		} else {
			$page_title   = "Error";
			$page_content = "<div class='alert alert-danger text-center'>Page not found or inactive.</div>";
			$last_date    = $date;
			$stats        = null;
			$p_title      = "Error";
		}
	}

    // Theme
    require_once('theme/' . $default_theme . '/header.php');
    require_once('theme/' . $default_theme . '/pages.php');
    require_once('theme/' . $default_theme . '/footer.php');
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>