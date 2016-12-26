<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE>
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

session_start();

$directory = 'install';

if (file_exists($directory)) {
    header("Location: install");
    exit();
}

// Required functions
require_once('config.php');
include_once( 'includes/db.php' );
require_once('includes/captcha.php');
require_once('includes/functions.php');

// PHP <5.5 compatibility
require_once('includes/password.php');

// UTF-8
header('Content-Type: text/html; charset=utf-8');

// TODO remove
$con = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);

global $pastedb;

// Get site info
$query  = "SELECT * FROM site_info";
$result = $pastedb->get_row( $query );

$title       = $result->title;
$des         = $result->des;
$baseurl     = $result->baseurl;
$keyword     = $result->keyword;
$site_name   = $result->site_name;
$email       = $result->email;
$twit        = $result->twit;
$face        = $result->face;
$gplus       = $result->gplus;
$ga          = $result->ga;
$additional_scripts  = $result->additional_scripts;

// Set theme and language
$query  = "SELECT * FROM interface";
$result = $pastedb->get_row( $query );

$default_lang  = $result->lang;
$default_theme = $result->theme;

require_once("langs/$default_lang");

// Current date & user IP
$date    = date('jS F Y');
$ip      = $_SERVER['REMOTE_ADDR'];
$data_ip = file_get_contents('tmp/temp.tdata');

// Ads
$query  = "SELECT * FROM ads WHERE id='1'";
$result = $pastedb->get_row( $query );

$text_ads = $result->text_ads;
$ads_1    = $result->ads_1;
$ads_2    = $result->ads_2;


// Sitemap
$query  = "Select * From sitemap_options WHERE id='1'";
$result = $pastedb->get_row($query);

$priority   = $result->priority;
$changefreq = $result->changefreq;


// Captcha
$query  = "SELECT * FROM captcha where id='1'";
$result = $pastedb->get_row($query);

$color   = $result->color;
$mode    = $result->mode;
$mul     = $result->mul;
$allowed = $result->allowed;
$cap_e   = $result->cap_e;
$recaptcha_sitekey   = $result->recaptcha_sitekey;
$recaptcha_secretkey   = $result->recaptcha_secretkey;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
} else {
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

// Check if IP is banned
$query  = "SELECT * FROM ban_user WHERE ip='$ip' LIMIT 1";
$result = $pastedb->get_row($query);

if ( isset( $result->ip ) ) {
  die( $lang['banned'] ); // "You have been banned from ".$site_name;
}


// Site permissions
$query  = "SELECT * FROM site_permissions where id='1'";
$result = $pastedb->get_row( $query );

$disableguest = $result->disableguest;
$siteprivate  = $result->siteprivate;


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
} else {
    if ($disableguest == "on") {
        $noguests = "on";
	}
	if ($siteprivate =="on") {
		$privatesite = "on";
    }
    else {
      $privatesite = "off";
    }
	if (isset($_SESSION['username'])) {
		$noguests = "off";
	}
}

// Escape from quotes
if (get_magic_quotes_gpc()) {
    function callback_stripslashes(&$val, $name)
    {
        if (get_magic_quotes_gpc())
            $val = stripslashes($val);
    }
    if (count($_GET))
        array_walk($_GET, 'callback_stripslashes');
    if (count($_POST))
        array_walk($_POST, 'callback_stripslashes');
    if (count($_COOKIE))
        array_walk($_COOKIE, 'callback_stripslashes');
}

// Logout
if (isset($_GET['logout'])) {
	header('Location: ' . $_SERVER['HTTP_REFERER']);
    unset($_SESSION['token']);
    unset($_SESSION['oauth_uid']);
    unset($_SESSION['username']);
    unset($_SESSION['pic']);
    session_destroy();
}

// Page views
$query = "SELECT @last_id := MAX(id) as last_id FROM page_view";

$result = $pastedb->get_row($query);

$last_id = $result->last_id;


$query  = sprintf( "SELECT * FROM page_view WHERE id=%s", $last_id );
$result = $pastedb->get_row( $query );

$last_date = $result->date;

if ($last_date == $date) {
    if (str_contains($data_ip, $ip)) {

      $last_tpage = $result->tpage;
      $last_tpage++;

      // IP already exists, Update view count
      $query = sprintf( "UPDATE page_view SET tpage=%s WHERE id=%s", $last_tpage, $last_id );
      $pastedb->query( $query );

    } else {

          $last_tpage  = $result->tpage;
          $last_tvisit = $result->tvisit;
          $last_tpage++;
          $last_tvisit++;

          // Update both tpage and tvisit.
          $query = sprintf( "UPDATE page_view SET tpage=%a,tvisit=%s WHERE id=%s",
                    $last_tpage,
                    $last_tvisit,
                    $last_id
                  );
          $pastedb->query($query);
          file_put_contents('tmp/temp.tdata', $data_ip . "\r\n" . $ip);
        }
} else {
    // Delete the file and clear data_ip
    unlink("tmp/temp.tdata");
    $data_ip = "";

    // New date is created
    $query = "INSERT INTO page_view (date,tpage,tvisit) VALUES ('$date','1','1')";
    $pastedb->query($query);

    // Update the IP
    file_put_contents('tmp/temp.tdata', $data_ip . "\r\n" . $ip);

}

// POST Handler
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// Check if fields are empty
	if (empty($_POST["paste_data"])) {
		$error = $lang['empty_paste'];
		goto OutPut;
		exit;
	}

	// Check if fields are only white space
	if (trim($_POST["paste_data"]) == '') {
		$error = $lang['empty_paste'];
		goto OutPut;
		exit;
	}

	// Set our limits
	if (mb_strlen($_POST["paste_data"], '8bit') >  1024 * 1024 * $pastelimit) {
		$error = $lang['large_paste'];
		goto OutPut;
		exit;
	}

    // Check POST data status
    if (isset($_POST['title']) And isset($_POST['paste_data'])) {
        if ($cap_e == "on" && !isset($_SESSION['username'])) {
            if ($mode == "reCAPTCHA") {
                $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$recaptcha_secretkey."&response=".$_POST['g-recaptcha-response']);
                $response = json_decode($response, true);
                if ( $response["success"] == false ) {
                    // reCAPTCHA Errors
                    switch( $response["error-codes"][0] ) {
                        case "missing-input-response":
                            $error = $lang['missing-input-response'];
                            break;
                        case "missing-input-secret":
                            $error = $lang['missing-input-secret'];
                            break;
                        case "invalid-input-response":
                            $error = $lang['missing-input-response'];
                            break;
                        case "invalid-input-secret":
                            $error = $lang['invalid-input-secret'];
                            break;
                    }
                    goto OutPut;
                }
            } else {
                $scode    = strtolower(htmlentities(Trim($_POST['scode'])));
                $cap_code = strtolower($_SESSION['captcha']['code']);
                if ($cap_code == $scode) {
                } else {
                    $error = $lang['image_wrong']; // Wrong captcha.
                    goto OutPut;
                }
            }
        }

        $p_title    = Trim(htmlspecialchars($_POST['title']));
			if (strlen($p_title)==0) $p_title='Untitled';
        $p_content  = htmlspecialchars($_POST['paste_data']);
        $p_visible  = Trim(htmlspecialchars($_POST['visibility']));
        $p_code     = Trim(htmlspecialchars($_POST['format']));
        $p_expiry   = Trim(htmlspecialchars($_POST['paste_expire_date']));
        $p_password = $_POST['pass'];
        if ($p_password == "" || $p_password == null) {
            $p_password = "NONE";
        } else {
            $p_password = password_hash($p_password, PASSWORD_DEFAULT);
        }
        $p_encrypt = Trim(htmlspecialchars($_POST['encrypted']));

        if ($p_encrypt == "" || $p_encrypt == null) {
            $p_encrypt = "0";
        } else {
            // Encrypt option
            $p_encrypt = "1";
            $p_content = encrypt($p_content);
        }

        if (isset($_SESSION['token'])) {
            $p_member = Trim($_SESSION['username']);
        } else {
            $p_member = "Guest";
        }
        // Set expiry time
        switch ($p_expiry) {
            case '10M':
                $expires = mktime(date("H"), date("i") + "10", date("s"), date("n"), date("j"), date("Y"));
                break;
            case '1H':
                $expires = mktime(date("H") + "1", date("i"), date("s"), date("n"), date("j"), date("Y"));
            case '1D':
                $expires = mktime(date("H"), date("i"), date("s"), date("n"), date("j") + "1", date("Y"));
                break;
            case '1W':
                $expires = mktime(date("H"), date("i"), date("s"), date("n"), date("j") + "7", date("Y"));
                break;
            case '2W':
                $expires = mktime(date("H"), date("i"), date("s"), date("n"), date("j") + "14", date("Y"));
                break;
            case '1M':
                $expires = mktime(date("H"), date("i"), date("s"), date("n") + "1", date("j"), date("Y"));
                break;
            case 'self':
                $expires = "SELF";
                break;
            case 'N':
                $expires = "NULL";
                break;
            default:
                $expires = "NULL";
                break;
        }
        $p_title   = $pastedb->escape($p_title);
        $p_content = $pastedb->escape($p_content);
        $p_date    = date('jS F Y h:i:s A');
        $date      = date('jS F Y');
        $now_time  = mktime(date("H"), date("i"), date("s"), date("n"), date("j"), date("Y"));
        // Edit existing paste or create new?
        if ( isset($_POST['edit'] ) ) {
            $edit_paste_id = $_POST['paste_id'];
            $query = "UPDATE pastes SET title='$p_title',content='$p_content',visible='$p_visible',code='$p_code',expiry='$expires',password='$p_password',encrypt='$p_encrypt',member='$p_member',date='$p_date',ip='$ip' WHERE id = '$edit_paste_id'";
        } else {
            $query = "INSERT INTO pastes (title,content,visible,code,expiry,password,encrypt,member,date,ip,now_time,views,s_date) VALUES
            ('$p_title','$p_content','$p_visible','$p_code','$expires','$p_password','$p_encrypt','$p_member','$p_date','$ip','$now_time','0','$date')";
        }
        $result = $pastedb->query($query);

            $query  = "SELECT @last_id := MAX(id) as last FROM pastes LIMIT 1";
            $result = $pastedb->get_row($query);
            $paste_id = $result->last;
            $success = $paste_id;
            if ($p_visible == '0') {
                addToSitemap($paste_id, $priority, $changefreq, $mod_rewrite);
            }

    } else {
        $error = $lang['error']; // "Something went wrong";
    }

	// Redirect to paste on successful entry, or on successful edit redirect back to edited paste
	if ( isset( $success ) ) {
		if ( $mod_rewrite == '1' ) {
            if ( isset( $_POST['edit'] ) ) {
                $paste_url = "$edit_paste_id";
            } else {
                $paste_url = "$success";
            }
        } else {
            if ( isset( $_POST['edit'] ) ) {
                $paste_url = "paste.php?id=$edit_paste_id";
            } else {
                $paste_url = "paste.php?id=$success";
            }
        }
		header("Location: ".$paste_url."");
	}

}

OutPut:
// Theme
require_once('theme/' . $default_theme . '/header.php');
require_once('theme/' . $default_theme . '/main.php');
require_once('theme/' . $default_theme . '/footer.php');
?>
