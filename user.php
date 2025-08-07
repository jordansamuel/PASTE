<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
session_start();

require_once('config.php');
require_once('includes/functions.php');

// UTF-8
header('Content-Type: text/html; charset=utf-8');

$date = date('Y-m-d H:i:s');
$ip = $_SERVER['REMOTE_ADDR'];
$data_ip = file_get_contents('tmp/temp.tdata');

// Database Connection (PDO from config.php)
global $pdo;

try {
    // Get site info
    $stmt = $pdo->query("SELECT * FROM site_info WHERE id = '1'");
    $row = $stmt->fetch();
    $title = trim($row['title']);
    $des = trim($row['des']);
    $baseurl = trim($row['baseurl']);
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
    $default_lang = trim($row['lang']);
    $default_theme = trim($row['theme']);
    require_once("langs/$default_lang");

    // Check if IP is banned
    if (is_banned($pdo, $ip)) die($lang['banned']);

    // Site permissions
    $stmt = $pdo->query("SELECT * FROM site_permissions WHERE id = '1'");
    $row = $stmt->fetch();
    $siteprivate = trim($row['siteprivate']);

    if ($_SERVER['REQUEST_METHOD'] != 'POST' && $siteprivate == "1") {
        $privatesite = "1";
    }

    // Validate username
    if (isset($_GET['user'])) {
        $profile_username = trim($_GET['user']);
        if (!existingUser($pdo, $profile_username)) {
            header("Location: ../");
            exit;
        }
    } else {
        header("Location: ../");
        exit;
    }

		$p_title = $profile_username . $lang['user_public_pastes'];

		// Stats for the profile page
		$stmt = $pdo->prepare("SELECT count(*) FROM pastes WHERE member = ?");
		$stmt->execute([$profile_username]);
		$profile_total_pastes = $stmt->fetchColumn() ?? 0;

		$stmt = $pdo->prepare("SELECT count(*) FROM pastes WHERE member = ? AND visible = 0");
		$stmt->execute([$profile_username]);
		$profile_total_public = $stmt->fetchColumn() ?? 0;

		$stmt = $pdo->prepare("SELECT count(*) FROM pastes WHERE member = ? AND visible = 1");
		$stmt->execute([$profile_username]);
		$profile_total_unlisted = $stmt->fetchColumn() ?? 0;

		$stmt = $pdo->prepare("SELECT count(*) FROM pastes WHERE member = ? AND visible = 2");
		$stmt->execute([$profile_username]);
		$profile_total_private = $stmt->fetchColumn() ?? 0;

		$stmt = $pdo->prepare("SELECT COALESCE(SUM(views), 0) FROM pastes WHERE member = ?");
		$stmt->execute([$profile_username]);
		$profile_total_paste_views = $stmt->fetchColumn() ?? 0;

		$stmt = $pdo->prepare("SELECT date FROM users WHERE username = ?");
		$stmt->execute([$profile_username]);
		$profile_join_date = $stmt->fetchColumn() ?? ''; // Default to empty string if null

    // Logout
    if (isset($_GET['logout'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        unset($_SESSION['token']);
        unset($_SESSION['oauth_uid']);
        unset($_SESSION['username']);
        session_destroy();
    }

    // Page views
    $stmt = $pdo->query("SELECT MAX(id) AS last_id FROM page_view");
    $row = $stmt->fetch();
    $last_id = $row['last_id'];

    if ($last_id) {
        $stmt = $pdo->prepare("SELECT * FROM page_view WHERE id = ?");
        $stmt->execute([$last_id]);
        $row = $stmt->fetch();
        $last_date = $row['date'];

        if ($last_date == $date) {
            if (str_contains($data_ip, $ip)) {
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

    // Delete paste
    if (isset($_GET['del']) && $_SESSION['token']) {
        $paste_id = htmlentities(trim($_GET['id']));
        $user_username = trim($_SESSION['username']);
        $stmt = $pdo->prepare("SELECT * FROM pastes WHERE id = ? AND member = ?");
        $stmt->execute([$paste_id, $user_username]);
        if ($stmt->rowCount() == 0) {
            $error = $lang['delete_error_invalid'];
        } else {
            $stmt = $pdo->prepare("DELETE FROM pastes WHERE id = ? AND member = ?");
            $stmt->execute([$paste_id, $user_username]);
            $success = $lang['pastedeleted'];
        }
    } elseif (isset($_GET['del'])) {
        $error = $lang['not_logged_in'];
    }

    // Ads
    $stmt = $pdo->query("SELECT * FROM ads WHERE id = '1'");
    $row = $stmt->fetch();
    $text_ads = trim($row['text_ads']);
    $ads_1 = trim($row['ads_1']);
    $ads_2 = trim($row['ads_2']);

    // Theme
    require_once('theme/' . $default_theme . '/header.php');
    require_once('theme/' . $default_theme . '/user_profile.php');
    require_once('theme/' . $default_theme . '/footer.php');
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>