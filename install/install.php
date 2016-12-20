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
 
require_once("../config.php");

// PHP <5.5 compatibility
require_once('../includes/password.php');

$admin_user = htmlentities(Trim($_POST['admin_user']));
$admin_pass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
$date = date("j F Y");
$con = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);
// level up, dirty but meh
$x=2;$path = dirname($_SERVER['PHP_SELF']); while(max(0, --$x)) { $levelup = dirname($path); }

if (mysqli_connect_errno()) {
	echo "Failed to connect:" . mysqli_connect_error() . "<br />";
}

// Admin
$sql = "CREATE TABLE admin
(
id INT NOT NULL AUTO_INCREMENT,
PRIMARY KEY(id),
user VARCHAR(250),
pass VARCHAR(250)
)";
	// Execute query

	if (mysqli_query($con, $sql)) {
		echo "admin table created.<br />";
	} else {
		echo "Error creating table: " . mysqli_error($con) . "<br />";
	}

	$query = "INSERT INTO admin (user,pass) VALUES ('$admin_user','$admin_pass')";
	mysqli_query($con, $query);

// Admin history
$sql = "CREATE TABLE admin_history
(
id INT NOT NULL AUTO_INCREMENT,
PRIMARY KEY(id),
last_date VARCHAR(255),
ip VARCHAR(255)
)";
	// Execute query

	if (mysqli_query($con, $sql)) {
		echo "admin_history table created.<br />";
	} else {
		echo "Error creating table: " . mysqli_error($con) . "<br />";
	}

// Site info
$sql = "CREATE TABLE site_info
(
id INT NOT NULL AUTO_INCREMENT,
PRIMARY KEY(id),
title VARCHAR(255),
des mediumtext,
keyword mediumtext,
site_name VARCHAR(255),
email VARCHAR(255),
twit VARCHAR(4000),
face VARCHAR(4000),
gplus VARCHAR(4000),
ga VARCHAR(255),
additional_scripts text, 
baseurl text
)";
	// Execute query

	if (mysqli_query($con, $sql)) {
		echo "site_info table created.<br />";
	} else {
		echo "Error creating table: " . mysqli_error($con) . "<br />";
	}

	$query = "INSERT INTO site_info (title,des,keyword,site_name,email,twit,face,gplus,ga,additional_scripts,baseurl) VALUES ('Paste','Paste can store text, source code or sensitive data for a set period of time.','paste,pastebin.com,pastebin,text,paste,online paste','Paste','','https://twitter.com/','https://www.facebook.com/','https://plus.google.com/','UA-','','" . '//' . $_SERVER['SERVER_NAME'] . $levelup . "')";
	mysqli_query($con, $query);

// Site Permissions
$sql = "CREATE TABLE site_permissions
(
id int(11) NOT NULL AUTO_INCREMENT,
PRIMARY KEY(id),
disableguest varchar(255) DEFAULT NULL,
siteprivate varchar(255) DEFAULT NULL
)
";
	// Execute query

	if (mysqli_query($con, $sql)) {
		echo "site_permissions table created.<br />";
	} else {
		echo "Error creating table: " . mysqli_error($con) . "<br />";
	}

	$query = "INSERT INTO site_permissions (id,disableguest,siteprivate) VALUES (1, 'on', 'on'), (2, 'off', 'off')";

	mysqli_query($con, $query);

// Interface
$sql = "CREATE TABLE interface
(
id INT NOT NULL AUTO_INCREMENT,
PRIMARY KEY(id),
theme text,
lang text
)";
	// Execute query

	if (mysqli_query($con, $sql)) {
		echo "interface table created.<br />";
	} else {
		echo "Error creating table: " . mysqli_error($con) . "<br />";
	}

	$query = "INSERT INTO interface (theme,lang) VALUES ('default','en.php')";
	mysqli_query($con, $query);

// Pastes
$sql = "CREATE TABLE pastes
(
id INT NOT NULL AUTO_INCREMENT,
PRIMARY KEY(id),
title text,
content longtext,
visible text,
code text,
expiry text,
password text,
encrypt text,
member text,
date text,
ip text,
now_time text,
views text,
s_date text
)";
	// Execute query

	if (mysqli_query($con, $sql)) {
		echo "pastes table created.<br />";
	} else {
		echo "Error creating table: " . mysqli_error($con) . "<br />";
	}

// Users
$sql = "CREATE TABLE users
(
id INT NOT NULL AUTO_INCREMENT,
PRIMARY KEY(id),
oauth_uid text,
username text,
email_id text,
full_name text,
platform text,
password text,
verified text,
picture text,
date text,
ip text
)";
	// Execute query

	if (mysqli_query($con, $sql)) {
		echo "users table created.<br />";
	} else {
		echo "Error creating table: " . mysqli_error($con) . "<br />";
	}


// Bans
$sql = "CREATE TABLE ban_user
(
id INT NOT NULL AUTO_INCREMENT,
PRIMARY KEY(id),
ip VARCHAR(255),
last_date VARCHAR(255)
)";
	// Execute query

	if (mysqli_query($con, $sql)) {
		echo "ban_user table created.<br />";
	} else {
		echo "Error creating table: " . mysqli_error($con) . "<br />";
	}

	mysqli_query($con, $query);

// Mail
$sql = "CREATE TABLE mail
(
id INT NOT NULL AUTO_INCREMENT,
PRIMARY KEY(id),
verification text,
smtp_host text,
smtp_username text,
smtp_password text,
smtp_port text,
protocol text,
auth text,
socket text
)";
	// Execute query

	if (mysqli_query($con, $sql)) {
		echo "mail table created.<br />";
	} else {
		echo "Error creating table: " . mysqli_error($con) . "<br />";
	}

	$query = "INSERT INTO mail (verification,smtp_host,smtp_username,smtp_password,smtp_port,protocol,auth,socket) VALUES ('enabled','','','','','1','true','ssl')";
	mysqli_query($con, $query);

// Pages
$sql = "CREATE TABLE pages
(
id INT NOT NULL AUTO_INCREMENT,
PRIMARY KEY(id),
last_date VARCHAR(255),
page_name VARCHAR(255),
page_title mediumtext,
page_content longtext
)";
	// Execute query

	if (mysqli_query($con, $sql)) {
		echo "pages table created.<br />";
	} else {
		echo "Error creating table: " . mysqli_error($con) . "<br />";
	}

	mysqli_query($con, $query);

// Page views
$sql = "CREATE TABLE page_view
(
id INT NOT NULL AUTO_INCREMENT,
PRIMARY KEY(id),
date VARCHAR(255),
tpage VARCHAR(255),
tvisit VARCHAR(255)
)";
	// Execute query

	if (mysqli_query($con, $sql)) {
		echo "page_view table created.<br />";
	} else {
		echo "Error creating table: " . mysqli_error($con) . "<br />";
	}

// Ads
$sql = "CREATE TABLE ads
(
id INT NOT NULL AUTO_INCREMENT,
PRIMARY KEY(id),
text_ads text,
ads_1 text,
ads_2 text
)";
	// Execute query

	if (mysqli_query($con, $sql)) {
		echo "Ad related tables created. <br />";
	} else {
		echo "Error creating table: " . mysqli_error($con) . "<br />";
	}

	$query = "INSERT INTO ads (text_ads,ads_1,ads_2) VALUES ('','','')";
	mysqli_query($con, $query);

// Sitemap options
$sql = "CREATE TABLE sitemap_options
(
id INT NOT NULL AUTO_INCREMENT,
PRIMARY KEY(id),
priority VARCHAR(255),
changefreq VARCHAR(255)
)";
	// Execute query

	if (mysqli_query($con, $sql)) {
		echo "sitemap_options table created.<br />";
	} else {
		echo "Error creating table: " . mysqli_error($con) . "<br />";
	}

	$query = "INSERT INTO sitemap_options (id,priority,changefreq) VALUES ('1','0.9','daily')";
	mysqli_query($con, $query);

// Captcha
$sql = "CREATE TABLE captcha
(
id INT NOT NULL AUTO_INCREMENT,
PRIMARY KEY(id),
cap_e VARCHAR(255),
mode VARCHAR(255),
mul VARCHAR(255),
allowed text,
color mediumtext,
recaptcha_sitekey text,
recaptcha_secretkey text
)";
	// Execute query

	if (mysqli_query($con, $sql)) {
		echo "captcha table created.<br />";
	} else {
		echo "Error creating table: " . mysqli_error($con) . "<br />";
	}

	$query = "INSERT INTO captcha (cap_e,mode,mul,allowed,color,recaptcha_sitekey,recaptcha_secretkey) VALUES ('off','Normal','off','ABCDEFGHIJKLMNOPQRSTUVYXYZabcdefghijklmnopqrstuvwxyz0123456789','#000000','','')";
	mysqli_query($con, $query);
?>

If you received no errors above, you can assume everything went OK. You can now remove the /install directory and proceed to the <a href="../" class="btn btn-default">main site</a> or your <a href="../admin" class="btn btn-default">dashboard</a>
