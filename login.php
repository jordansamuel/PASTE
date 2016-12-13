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

// Required functions
require_once('config.php');
require_once('includes/functions.php');
require_once('mail/mail.php');

// Current Date & User IP
$date    = date('jS F Y');
$ip      = $_SERVER['REMOTE_ADDR'];
$data_ip = file_get_contents('tmp/temp.tdata');

// Mail
$mail_type = "1";

// Check if already logged in
if (isset($_SESSION['token'])) {
   header("Location: ./");
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

$admin_mail = $email;
$admin_name = $site_name;

// Email information

$query  = "SELECT * FROM mail WHERE id='1'";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
	$verification = Trim($row['verification']);
    $smtp_host = Trim($row['smtp_host']);
    $smtp_user = Trim($row['smtp_username']);
    $smtp_pass = Trim($row['smtp_password']);
    $smtp_port = Trim($row['smtp_port']);
    $smtp_protocol  = Trim($row['protocol']);
    $smtp_auth = Trim($row['auth']);
    $smtp_sec  = Trim($row['socket']);
}
$mail_type = $smtp_protocol;

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

// Set theme and language
$query  = "SELECT * FROM interface";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $default_lang  = Trim($row['lang']);
    $default_theme = Trim($row['theme']);
}
require_once("langs/$default_lang");

// Page title
$p_title = $lang['login/register']; //"Login/Register";

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

if (strpos($banned_ip, $ip) !== false) {
    die($lang['banned']); //"You have been banned from ".$site_name
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
        
        // IP already exists, update page view
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
if (isset($_GET['resend'])) {
    if (isset($_POST['email'])) {
        $email  = htmlentities(trim($_POST['email']));
        $query  = "SELECT * FROM users WHERE email_id='$email'";
        $result = mysqli_query($con, $query);
        if (mysqli_num_rows($result) > 0) {
            // Username found
            while ($row = mysqli_fetch_array($result)) {
                $username     = $row['username'];
                $db_email_id  = $row['email_id'];
                $db_full_name = $row['full_name'];
                $db_platform  = $row['platform'];
                $db_password  = Trim($row['password']);
                $db_verified  = $row['verified'];
                $db_picture   = $row['picture'];
                $db_date      = $row['date'];
                $db_ip        = $row['ip'];
                $db_id        = $row['id'];
            }
            if ($db_verified == '0') {
				$protocol = ($_SERVER['HTTPS'] == "on")?'https://':'http://';
                $verify_url = $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/verify.php?username=$username&code=" . Md5('4et4$55765' . $db_email_id . 'd94ereg');
                $sent_mail  = $email;
                $subject    = $lang['mail_acc_con']; // "$site_name Account Confirmation";
                $body       = "
          Hello $db_full_name, Please verify your account by clicking the link below.<br /><br />
              
          <a href='$verify_url' target='_self'>$verify_url</a>  <br /> <br />
          
          After confirming your account you can log in using your username: <b>$username</b> and the password you used when signing up.
          ";
                
                if ($mail_type == '1') {
                    default_mail($admin_mail, $admin_name, $sent_mail, $subject, $body);
                } else {
                    smtp_mail($smtp_host, $smtp_port, $smtp_auth, $smtp_user, $smtp_pass, $smtp_sec, $admin_mail, $admin_name, $sent_mail, $subject, $body);
                }
                $success = $lang['mail_suc']; // "Verification code successfully sent to your email.";    
                
            } else {
                $error = $lang['email_ver']; //"Email already verified.";    
            }
            
        } else {
            $error = $lang['email_not']; // "Email not found.";
        }
        
    }
}

if (isset($_GET['forgot'])) {
    if (isset($_POST['email'])) {
        $email  = htmlentities(trim($_POST['email']));
        $query  = "SELECT * FROM users WHERE email_id='$email'";
        $result = mysqli_query($con, $query);
        if (mysqli_num_rows($result) > 0) {
            // Username found
            while ($row = mysqli_fetch_array($result)) {
                $username     = $row['username'];
                $db_email_id  = $row['email_id'];
                $db_full_name = $row['full_name'];
                $db_platform  = $row['platform'];
                $db_password  = Trim($row['password']);
                $db_verified  = $row['verified'];
                $db_picture   = $row['picture'];
                $db_date      = $row['date'];
                $db_ip        = $row['ip'];
                $db_id        = $row['id'];
            }
            $new_pass     = uniqid(rand(), true);
            $new_pass_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            
            $query = "UPDATE users SET password='$new_pass_hash' WHERE username='$username'";
            mysqli_query($con, $query);
            if (mysqli_error($con)) {
                $error = "Unable to access database.";
            } else {
                $success   = $lang['pass_change']; //"Password changed successfully and sent to your email address.";
                $sent_mail = $email;
                $subject   = "$site_name Password Reset";
                $body      = "<br />
          Hello, <br /><br />
          
          Your password has been reset: $new_pass  <br /> <br />
          
          You can now login and change your password. <br />
          ";
                if ($mail_type == '1') {
                    default_mail($admin_mail, $admin_name, $sent_mail, $subject, $body);
                } else {
                    smtp_mail($smtp_host, $smtp_port, $smtp_auth, $smtp_user, $smtp_pass, $smtp_sec, $admin_mail, $admin_name, $sent_mail, $subject, $body);
                }
                
            }
            
        } else {
            $error = $lang['email_not']; //"Email not found";  
        }
        
    }
    
}
if ($_SERVER['REQUEST_METHOD'] == POST) {
    // Check if logged in
    if (isset($_SESSION['token'])) {
        header("Location: ./");
    } else {
        // Login process
        if (isset($_POST['signin'])) {
            $username = htmlentities(trim($_POST['username']));
            $password = $_POST['password'];
            if ($username != null && $password != null) {
                
                $query  = "SELECT * FROM users WHERE username='$username'";
                $result = mysqli_query($con, $query);
                if (mysqli_num_rows($result) > 0) {
                    // Username found
                    while ($row = mysqli_fetch_array($result)) {
                        $db_oauth_uid = $row['oauth_uid'];
                        $db_email_id  = $row['email_id'];
                        $db_full_name = $row['full_name'];
                        $db_platform  = $row['platform'];
                        $db_password  = $row['password'];
                        $db_verified  = $row['verified'];
                        $db_picture   = $row['picture'];
                        $db_date      = $row['date'];
                        $db_ip        = $row['ip'];
                        $db_id        = $row['id'];
                    }

                    if (password_verify($password, $db_password)) {
                        if ($db_verified == "1") {
                            // Login successful
                            $_SESSION['token']     = Md5($db_id . $username);
                            $_SESSION['oauth_uid'] = $db_oauth_uid;
                            $_SESSION['username']  = $username;
                            
                            header('Location: ' . $_SERVER['HTTP_REFERER']);
							
                        } elseif ($db_verified == "2") {
                            // User is banned
                            $error = $lang['banned'];
                        } else {
                            // Account not verified
                            $error = $lang['notverified'];
                        }
                    } else {
                        // Password wrong
                        $error = $lang['incorrect'];
                        
                    }
                } else {
                    // Username not found
                    $error = $lang['incorrect'];
                }
            } else {
                $error = $lang['missingfields']; //"All fields must be filled out.";
            }
        }
        
        // Register process
        if (isset($_POST['signup'])) {
            $username  = htmlentities(trim($_POST['username']));
            $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $email     = htmlentities(trim($_POST['email']));
            $full_name = htmlentities(trim($_POST['full']));
            if ($username != null && $password != null && $full_name != null && $email != null) {
                $res = isValidUsername($username);
                if ($res == '1') {
                    $query  = "SELECT * FROM users WHERE username='$username'";
                    $result = mysqli_query($con, $query);
                    if (mysqli_num_rows($result) > 0) {
                        $error = $lang['userexists']; // "Username already taken.";
                    } else {
                        
                        $query  = "SELECT * FROM users WHERE email_id='$email'";
                        $result = mysqli_query($con, $query);
                        if (mysqli_num_rows($result) > 0) {
                            $error = $lang['emailexists']; // "Email already registered.";
                        } else {
								if ($verification == 'disabled') {
									$query = "INSERT INTO users (oauth_uid,username,email_id,full_name,platform,password,verified,picture,date,ip) VALUES ('0','$username','$email','$full_name','Direct','$password','1','NONE','$date','$ip')";
								} else {
									$query = "INSERT INTO users (oauth_uid,username,email_id,full_name,platform,password,verified,picture,date,ip) VALUES ('0','$username','$email','$full_name','Direct','$password','0','NONE','$date','$ip')";
								}
                            mysqli_query($con, $query);
                            if (mysqli_error($con))
                                $error = "Database Error";
                            else {
								if ($verification == 'disabled') {
									$success    = $lang['registered']; // "Your account was successfully registered.";
								} else {
									$success    = $lang['registered']; // "Your account was successfully registered.";
									$protocol = ($_SERVER['HTTPS'] == "on")?'https://':'http://';
									$verify_url = $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/verify.php?username=$username&code=" . Md5('4et4$55765' . $email . 'd94ereg');
									$sent_mail  = $email;
									$subject    = $lang['mail_acc_con']; // "$site_name Account Confirmation";
									$body       = "
			  Hello $full_name, Your $site_name account has been created. Please verify your account by clicking the link below.<br /><br />
				  
			  <a href='$verify_url' target='_self'>$verify_url</a>  <br /> <br />
			  
			  After confirming your account you can log in using your username: <b>$username</b> and the password you used when signing up.
			  ";
									if ($mail_type == '1') {
										default_mail($admin_mail, $admin_name, $sent_mail, $subject, $body);
									} else {
										smtp_mail($smtp_host, $smtp_port, $smtp_auth, $smtp_user, $smtp_pass, $smtp_sec, $admin_mail, $admin_name, $sent_mail, $subject, $body);
									}
								}
                            }
                        }
                        
                    }
                } else {
                    $error = $lang['usrinvalid']; // "Username not valid. Usernames can't contain special characters.";
                }
            } else {
                $error = $lang['missingfields']; // "All fields must be filled out";
            }
        }
    }
}

// Theme
require_once('theme/' . $default_theme . '/header.php');
require_once('theme/' . $default_theme . '/login.php');
require_once('theme/' . $default_theme . '/footer.php');
?>