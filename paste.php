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

// PHP <5.5 compatibility
require_once('includes/password.php'); 
 
session_start();

// UTF-8
header('Content-Type: text/html; charset=utf-8');

// Required functions
require_once('config.php');
require_once('includes/geshi.php');
require_once('includes/functions.php');

// Path of GeSHi object
$path = 'includes/geshi/';

// Path of Parsedown object
$parsedown_path = 'includes/Parsedown/Parsedown.php';

// GET Paste ID
if (isset($_GET['id'])) {
    $paste_id = Trim(htmlspecialchars($_GET['id']));
} elseif (isset($_POST['id'])) {
    $paste_id = Trim(htmlspecialchars($_POST['id']));
}

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
    $baseurl			= Trim($row['baseurl']);
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
	if ($siteprivate =="on") {
		$privatesite = "on";
    }
}

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

// Logout
if (isset($_GET['logout'])) {
	header('Location: ' . $_SERVER['HTTP_REFERER']);
    unset($_SESSION['token']);
    unset($_SESSION['oauth_uid']);
    unset($_SESSION['username']);
    session_destroy();
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
        
        // IP exists, so update page views
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

$query  = "SELECT * FROM pastes WHERE id='$paste_id'";
$result = mysqli_query($con, $query);
if (mysqli_num_rows($result) > 0) {
    $query  = "SELECT * FROM pastes WHERE id='$paste_id'";
    $result = mysqli_query($con, $query);
    while ($row = mysqli_fetch_array($result)) {
        $p_title    = $row['title'];
        $p_content  = $row['content'];
        $p_visible  = $row['visible'];
        $p_code     = $row['code'];
        $p_expiry   = Trim($row['expiry']);
        $p_password = $row['password'];
        $p_member   = $row['member'];
        $p_date     = $row['date'];
        $p_encrypt  = $row['encrypt'];
        $p_views    = $row['views'];
    }
    
    $p_private_error = '0';
    if ($p_visible == "2") {
        if (isset($_SESSION['username'])) {
            if ($p_member == Trim($_SESSION['username'])) {
            } else {
                $notfound           = $lang['privatepaste']; //" This is a private paste.";
                $p_private_error = '1';
                goto Not_Valid_Paste;
            }
        } else {
            $notfound           = $lang['privatepaste']; //" This is a private paste. If you created this paste, please login to view it.";
            $p_private_error = '1';
            goto Not_Valid_Paste;
        }
    }
    if ($p_expiry == "NULL" || $p_expiry == "SELF") {
    } else {
        $input_time   = $p_expiry;
        $current_time = mktime(date("H"), date("i"), date("s"), date("n"), date("j"), date("Y"));
        if ($input_time < $current_time) {
            $notfound       = $lang['expired'];
            $p_private_error = 1;
            goto Not_Valid_Paste;
        }
    }
    if ($p_encrypt == "" || $p_encrypt == null || $p_encrypt == '0') {
    } else {
        $p_content = decrypt($p_content);
    }
    $op_content = Trim(htmlspecialchars_decode($p_content));
    
    // Download the paste   
    if (isset($_GET['download'])) {
        if ($p_password == "NONE") {
            doDownload($paste_id, $p_title, $op_content, $p_code);
            exit();
        } else {
            if (isset($_GET['password'])) {
                if (password_verify($_GET['password'],$p_password)) {
                    doDownload($paste_id, $p_title, $op_content, $p_code);
                    exit();
                } else {
                    $error = $lang['wrongpassword']; // 'Wrong password';
                }
            } else {
                $error = $lang['pwdprotected']; // 'Password protected paste';
            }
        }
    }
	
    // Raw view   
    if (isset($_GET['raw'])) {
        if ($p_password == "NONE") {
            rawView($paste_id, $p_title, $op_content, $p_code);
            exit();
        } else {
            if (isset($_GET['password'])) {
                if (password_verify($_GET['password'],$p_password)) {
                    rawView($paste_id, $p_title, $op_content, $p_code);
                    exit();
                } else {
                    $error = $lang['wrongpassword']; // 'Wrong password';
                }
            } else {
                $error = $lang['pwdprotected']; // 'Password protected paste';
            }
        }
    } 
    
    // Preprocess
    $highlight   = array();
    $prefix_size = strlen('!highlight!');
    if ($prefix_size) {
        $lines     = explode("\n", $p_content);
        $p_content = "";
        foreach ($lines as $idx => $line) {
            if (substr($line, 0, $prefix_size) == '!highlight!') {
                $highlight[] = $idx + 1;
                $line        = substr($line, $prefix_size);
            }
            $p_content .= $line . "\n";
        }
        $p_content = rtrim($p_content);
    }
    
    // Apply syntax highlight
    $p_content = htmlspecialchars_decode($p_content);
    if ( $p_code == "markdown" ) {
        include( $parsedown_path );
        $Parsedown = new Parsedown();
        $p_content = $Parsedown->text( $p_content );
    } else {
        $geshi     = new GeSHi($p_content, $p_code, $path);
        $geshi->enable_classes();
        $geshi->set_header_type(GESHI_HEADER_DIV);
        $geshi->set_line_style('color: #aaaaaa; width:auto;');
        $geshi->set_code_style('color: #757584;');
        if (count($highlight)) {
            $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
            $geshi->highlight_lines_extra($highlight);
            $geshi->set_highlight_lines_extra_style('color:#399bff;background:rgba(38,92,255,0.14);');
        } else {
            $geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS, 2);
        }
        $p_content = $geshi->parse_code();
        $style     = $geshi->get_stylesheet();
        $ges_style = '<style>' . $style . '</style>';
    }
    
    // Embed view after GeSHI is applied so that $p_code is syntax highlighted as it should be. 
    if (isset($_GET['embed'])) {
        if ( $p_password == "NONE" ) {
            embedView( $paste_id, $p_title, $p_content, $p_code, $title, $baseurl, $ges_style, $lang );
            exit();
        } else {
            if ( isset( $_GET['password'] ) ) {
                if ( password_verify( $_GET['password'], $p_password ) ) {
                    embedView( $paste_id, $p_title, $p_content, $p_code, $title, $p_baseurl, $ges_style, $lang );
                    exit();
                } else {
                    $error = $lang['wrongpassword']; // 'Wrong password';
                }
            } else {
                $error = $lang['pwdprotected']; // 'Password protected paste';
            }
        }
    } 
} else {
	header("HTTP/1.1 404 Not Found");
    $notfound = $lang['notfound']; // "Not found";
}

require_once('theme/' . $default_theme . '/header.php');
if ($p_password == "NONE") {
    
    // No password & diplay the paste
    
    // Set download URL
	if ($mod_rewrite == '1') {
		$p_download = "download/$paste_id";
	} else {
		$p_download = "paste.php?download&id=$paste_id";
	}
	
	// Set raw URL
	if ($mod_rewrite == '1') {
		$p_raw = "raw/$paste_id";
	} else {
		$p_raw = "paste.php?raw&id=$paste_id";
	}

	// Set embed URL
	if ( $mod_rewrite == '1' ) {
		$p_embed = "embed/$paste_id";
	} else {
		$p_embed = "paste.php?embed&id=$paste_id";
	}
    
    // Theme
    require_once('theme/' . $default_theme . '/view.php');
    updateMyView($con, $paste_id);
    if ($p_expiry == "SELF") {
        deleteMyPaste($con, $paste_id);
    }
} else {
    $p_download = "paste.php?download&id=$paste_id&password=" . password_hash(isset($_POST['mypass']), PASSWORD_DEFAULT);
    $p_raw = "paste.php?raw&id=$paste_id&password=" . password_hash(isset($_POST['mypass']), PASSWORD_DEFAULT);
    // Check password
    if (isset($_POST['mypass'])) {
        if (password_verify($_POST['mypass'], $p_password)) {
            // Theme
            require_once('theme/' . $default_theme . '/view.php');
            updateMyView($con, $paste_id);
            if ($p_expiry == "SELF") {
                deleteMyPaste($con, $paste_id);
            }
        } else {
            $error = $lang['wrongpwd']; //"Password is wrong";
            require_once('theme/' . $default_theme . '/errors.php');
        }
    } else {
        // Display errors
        require_once('theme/' . $default_theme . '/errors.php');
    }
}

Not_Valid_Paste:
// Private paste not valid
if ($p_private_error == '1') {
    // Display errors
    require_once('theme/' . $default_theme . '/header.php');
    require_once('theme/' . $default_theme . '/errors.php');
}

// Footer
require_once('theme/' . $default_theme . '/footer.php');
?>
