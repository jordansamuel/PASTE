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

require_once('facebook/facebook.php');
require_once('../config.php');

// Current Date & User IP
$date = date('jS F Y');
$ip   = $_SERVER['REMOTE_ADDR'];

// Database Connection
$con = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);
if (mysqli_connect_errno()) {
    die("Unable connect to database");
}

$facebook = new Facebook(array(
    'appId' => FB_APP_ID,
    'secret' => FB_APP_SECRET
));

$user = $facebook->getUser();

if ($user) {
    try {
        // Proceed knowing you have a logged in user who's authenticated.
        $user_profile = $facebook->api('/me');
    }
    catch (FacebookApiException $e) {
        
        $user = null;
    }
    
    if (!empty($user_profile)) {
        # User info ok? Let's print it (Here we will be adding the login and registering routines)
        
        $client_name  = $user_profile['name'];
        $client_id    = $user_profile['id'];
        $client_email = $user_profile['email'];
        $client_pic   = $user_profile['picture'];
        $client_plat  = 'Facebook';
        
        
        if (!empty($user_profile)) {
            $query = mysqli_query($con, "SELECT * FROM users WHERE oauth_uid='$client_id'");
            if (mysqli_num_rows($query) > 0) {
                $query  = "SELECT * FROM users WHERE oauth_uid='$client_id'";
                $result = mysqli_query($con, $query);
                while ($row = mysqli_fetch_array($result)) {
                    $user_username = $row['username'];
                    $db_verified   = $row['verified'];
                }
                if ($db_verified == "2") {
                    die("Your account has been banned.");
                } else {
                    
                    $_SESSION['username']  = $user_username;
                    $_SESSION['token']     = Md5($db_id . $username);
                    $_SESSION['oauth_uid'] = $client_id;
                    $_SESSION['pic']       = $client_pic;
                    
                    $old_user = 1;
                    header("Location: .");
                    exit();
                }
            } else {
                $new_user = 1;
                #user not present.
                $query    = "SELECT @last_id := MAX(id) FROM users";
                $result   = mysqli_query($con, $query);
                while ($row = mysqli_fetch_array($result)) {
                    $last_id = $row['@last_id := MAX(id)'];
                }
                if ($last_id == "" || $last_id == null) {
                    $username = "User1";
                } else {
                    $last_id  = $last_id + 1;
                    $username = "User$last_id";
                }
                $_SESSION['username']  = $username;
                $_SESSION['oauth_uid'] = $client_id;
                $_SESSION['token']     = Md5($db_id . $username);
                $query                 = "INSERT INTO users (oauth_uid,username,email_id,full_name,platform,password,verified,picture,date,ip) VALUES ('$client_id','$username','$client_email','$client_name','$client_plat','$password','1','$client_pic','$date','$ip')";
                mysqli_query($con, $query);
                header("Location: oauth.php?new_user");
                exit();
            }
            
        }
    } else {
        # For testing purposes, if there was an error, let's kill the script
        die("There was an error.");
    }
} else {
    if (isset($_GET['login'])) {
        # There's no active session, let's generate one
        $login_url = $facebook->getLoginUrl(array(
            'scope' => 'email'
        ));
        header("Location: " . $login_url);
        exit();
    }
}
?>
