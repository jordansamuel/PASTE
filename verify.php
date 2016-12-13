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

require_once('config.php');

// Database Connection
$con = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);
if (mysqli_connect_errno()) {
    die("Unable connect to database");
}
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

$username = htmlentities(trim($_GET['username']));
$code     = htmlentities(trim($_GET['code']));

$query  = "SELECT * FROM users WHERE username='$username'";
$result = mysqli_query($con, $query);
if (mysqli_num_rows($result) > 0) {
    // Username found
    while ($row = mysqli_fetch_array($result)) {
        $db_oauth_uid = $row['oauth_uid'];
        $db_email_id  = Trim($row['email_id']);
        $db_full_name = $row['full_name'];
        $db_platform  = $row['platform'];
        $db_password  = Trim($row['password']);
        $db_verified  = $row['verified'];
        $db_picture   = $row['picture'];
        $db_date      = $row['date'];
        $db_ip        = $row['ip'];
        $db_id        = $row['id'];
    }
    $ver_code = Md5('4et4$55765' . $db_email_id . 'd94ereg');
    if ($db_verified == '1') {
        die("Account already verified.");
    }
    if ($ver_code == $code) {
        $query = "UPDATE users SET verified='1' WHERE username='$username'";
        mysqli_query($con, $query);
        if (mysqli_error($con)) {
            $error = "Something went wrong.";
        } else {
            header("Location: login.php?login");
            exit();
        }
    } else {
        echo $ver_code;
        die("Verification code is wrong.");
    }
} else {
    die("Username not found.");
}
?>