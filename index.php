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
require_once('includes/captcha.php');
require_once('includes/functions.php');

// PHP <5.5 compatibility
require_once('includes/password.php');

// UTF-8
header('Content-Type: text/html; charset=utf-8');

// Database Connection
$con = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);
if (mysqli_connect_errno()) {
    die("Unable to connect to database");
}

// Get site info
$query  = "SELECT * FROM site_info";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $title				= Trim($row['title']);
    $des				= Trim($row['des']);
    $baseurl    		= Trim($row['baseurl']);
    $keyword			= Trim($row['keyword']);
    $site_name			= Trim($row['site_name']);
    $email				= Trim($row['email']);
    $twit				= Trim($row['twit']);
    $face				= Trim($row['face']);
    $gplus				= Trim($row['gplus']);
    $ga					= Trim($row['ga']);
    $additional_scripts	= Trim($row['additional_scripts']);
}

// Set theme and language
$query  = "SELECT * FROM interface";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $default_lang  = Trim($row['lang']);
    $default_theme = Trim($row['theme']);
}
require_once("langs/$default_lang");

// Current date & user IP
$date    = date('jS F Y');
$ip      = $_SERVER['REMOTE_ADDR'];
$data_ip = file_get_contents('tmp/temp.tdata');

// Ads
$query  = "SELECT * FROM ads WHERE id='1'";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $text_ads = Trim($row['text_ads']);
    $ads_1    = Trim($row['ads_1']);
    $ads_2    = Trim($row['ads_2']);
}

// Sitemap
$query  = "Select * From sitemap_options WHERE id='1'";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $priority   = $row['priority'];
    $changefreq = $row['changefreq'];
}

// Captcha
$query  = "SELECT * FROM captcha where id='1'";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $color   = Trim($row['color']);
    $mode    = Trim($row['mode']);
    $mul     = Trim($row['mul']);
    $allowed = Trim($row['allowed']);
    $cap_e   = Trim($row['cap_e']);
    $recaptcha_sitekey   = Trim($row['recaptcha_sitekey']);
    $recaptcha_secretkey   = Trim($row['recaptcha_secretkey']);
}
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
$query  = "SELECT * FROM ban_user";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $banned_ip = isset($banned_ip) . "::" . $row['ip'];
}
if ( isset( $banned_ip) ) {
    if (strpos($banned_ip, $ip) !== false) {
        die($lang['banned']); // "You have been banned from ".$site_name;
    }
}

// Site permissions
$query  = "SELECT * FROM site_permissions where id='1'";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $disableguest   = Trim($row['disableguest']);
	$siteprivate	= Trim($row['siteprivate']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
} else {
    if ($disableguest == "on") {
        $noguests = "on";
	}
	if ($siteprivate =="on") {
		$privatesite = "on";
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
$query = "SELECT @last_id := MAX(id) FROM page_view";

$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $last_id = $row['@last_id := MAX(id)'];
}

$query  = "SELECT * FROM page_view WHERE id=" . Trim($last_id);
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $last_date = $row['date'];
}

if ($last_date == $date) {
    if (str_contains($data_ip, $ip)) {
        $query  = "SELECT * FROM page_view WHERE id=" . Trim($last_id);
        $result = mysqli_query($con, $query);
        
        while ($row = mysqli_fetch_array($result)) {
            $last_tpage = Trim($row['tpage']);
        }
        $last_tpage = $last_tpage + 1;
        
        // IP already exists, Update view count
        $query = "UPDATE page_view SET tpage=$last_tpage WHERE id=" . Trim($last_id);
        mysqli_query($con, $query);
    } else {
        $query  = "SELECT * FROM page_view WHERE id=" . Trim($last_id);
        $result = mysqli_query($con, $query);
        
        while ($row = mysqli_fetch_array($result)) {
            $last_tpage  = Trim($row['tpage']);
            $last_tvisit = Trim($row['tvisit']);
        }
        $last_tpage  = $last_tpage + 1;
        $last_tvisit = $last_tvisit + 1;
        
        // Update both tpage and tvisit.
        $query = "UPDATE page_view SET tpage=$last_tpage,tvisit=$last_tvisit WHERE id=" . Trim($last_id);
        mysqli_query($con, $query);
        file_put_contents('tmp/temp.tdata', $data_ip . "\r\n" . $ip);
    }
} else {
    // Delete the file and clear data_ip
    unlink("tmp/temp.tdata");
    $data_ip = "";
    
    // New date is created
    $query = "INSERT INTO page_view (date,tpage,tvisit) VALUES ('$date','1','1')";
    mysqli_query($con, $query);
    
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
        $p_title   = mysqli_real_escape_string($con, $p_title);
        $p_content = mysqli_real_escape_string($con, $p_content);
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
        $result = mysqli_query($con, $query);
        if (mysqli_error($con)) {
            $error = $lang['paste_db_error']; // "Unable to post the paste on database";
        } else {
            $query  = "SELECT @last_id := MAX(id) FROM pastes";
            $result = mysqli_query($con, $query);
            while ($row = mysqli_fetch_array($result)) {
                $paste_id = $row['@last_id := MAX(id)'];
            }
            $success = $paste_id;
            if ($p_visible == '0') {
                addToSitemap($paste_id, $priority, $changefreq, $mod_rewrite);
            }
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
            if ( $_POST['edit'] ) {
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