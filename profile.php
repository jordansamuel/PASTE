<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
require_once('includes/password.php');
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

    $p_title = $lang['myprofile'];

    // Check if IP is banned
    if (is_banned($pdo, $ip)) die($lang['banned']);

    // Site permissions
    $stmt = $pdo->query("SELECT * FROM site_permissions WHERE id = '1'");
    $row = $stmt->fetch();
    $siteprivate = trim($row['siteprivate']);

    if ($_SERVER['REQUEST_METHOD'] != 'POST' && $siteprivate == "on") {
        $privatesite = "on";
    }

    // Check if already logged in
    if (!isset($_SESSION['token'])) {
        header("Location: ./login.php");
        exit;
    }

    // Logout
    if (isset($_GET['logout'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        unset($_SESSION['token']);
        unset($_SESSION['oauth_uid']);
        unset($_SESSION['username']);
        session_destroy();
    }

    $user_username = htmlentities(trim($_SESSION['username']));
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$user_username]);
    $row = $stmt->fetch();
    $user_oauth_uid = $row['oauth_uid'] == '0' ? "None" : $row['oauth_uid'];
    $user_id = $row['id'];
    $user_email_id = $row['email_id'];
    $user_full_name = $row['full_name'];
    $user_platform = trim($row['platform']);
    $user_verified = $row['verified'];
    $user_date = $row['date'];
    $user_ip = $row['ip'];
    $user_password = $row['password'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cpassword'])) {
        $user_new_full = trim(htmlspecialchars($_POST['full']));
        $user_old_pass = $_POST['old_password'];
        $user_new_cpass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        if (password_verify($user_old_pass, $user_password)) {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, password = ? WHERE username = ?");
            $stmt->execute([$user_new_full, $user_new_cpass, $user_username]);
            $success = $lang['profileupdated'];
        } else {
            $error = $lang['oldpasswrong'];
        }
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

    $total_pastes = getTotalPastes($pdo, $user_username);

    // Ads
    $stmt = $pdo->query("SELECT * FROM ads WHERE id = '1'");
    $row = $stmt->fetch();
    $text_ads = trim($row['text_ads']);
    $ads_1 = trim($row['ads_1']);
    $ads_2 = trim($row['ads_2']);

    // Theme
    require_once('theme/' . $default_theme . '/header.php');
    require_once('theme/' . $default_theme . '/profile.php');
    require_once('theme/' . $default_theme . '/footer.php');
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>