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

error_reporting(1);

// required functions
require_once ('../config.php');
require_once ('../includes/functions.php');
require_once 'Google/Client.php';

// Current Date & User IP
$date = date('jS F Y');
$ip = $_SERVER['REMOTE_ADDR'];


// Database Connection
$con = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);
if (mysqli_connect_errno())
{
    die("Unable connect to database");
}

$client = new Google_Client();
$client->setScopes(array(
    "https://www.googleapis.com/auth/userinfo.profile",
    "https://www.googleapis.com/auth/userinfo.email"
));


if (isset($_GET['code'])) {
  $client->authenticate($_GET['code']);
  $_SESSION['access_token'] = $client->getAccessToken();
  $redirect = 'http://' . $_SERVER['HTTP_HOST'];
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
}

if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
  $client->setAccessToken($_SESSION['access_token']);
  $access_token = json_decode($_SESSION['access_token'], 1);
  $access_token = $access_token['access_token'];
  $resp = file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo?access_token='.$access_token);
  $user = json_decode($resp, 1);  
  $client_email = filter_var($user['email'], FILTER_SANITIZE_EMAIL);
  $client_name = filter_var($user['name'], FILTER_SANITIZE_STRING);
  $client_id = filter_var($user['id']);
  $client_plat = "Google";
  $client_pic = $user['picture'];
  $content = $user;
  $token = $client->getAccessToken();
} else {
  $authUrl = $client->createAuthUrl();
}
if ($client->getAccessToken() && isset($_GET['url'])) {

  $_SESSION['access_token'] = $client->getAccessToken();
}

if (isset($client_email))
{
$query = mysqli_query($con,"SELECT * FROM users WHERE oauth_uid='$client_id'");
if(mysqli_num_rows($query) > 0){
$query =  "SELECT * FROM users WHERE oauth_uid='$client_id'";
  $result = mysqli_query($con,$query);
  while($row = mysqli_fetch_array($result)) {
  $user_username  = $row['username'];
  $db_verified  = $row['verified'];
  }
    if ($db_verified == "2")
  {
    die("Your account has been suspended.");
  }
  else
  {    
  $_SESSION['username'] = $user_username;
  $_SESSION['token'] = $token;
  $_SESSION['oauth_uid'] = $client_id;
  $_SESSION['pic'] = $client_pic;
  
  $old_user =1;
  header("Location: ../");
  exit();
  }

} else {
   $new_user= 1;
  #user not present.
  $query =  "SELECT @last_id := MAX(id) FROM users";
  $result = mysqli_query($con,$query);
  while($row = mysqli_fetch_array($result)) {
  $last_id =  $row['@last_id := MAX(id)'];
  }
  if ($last_id== "" || $last_id==null)
  {
      $username = "User1";
  }
  else
  {
      $last_id = $last_id+1;  
      $username = "User$last_id";
  }
  $_SESSION['username'] = $username;
  $_SESSION['token'] = $token;
  $_SESSION['oauth_uid'] = $client_id;
  $_SESSION['pic'] = $client_pic;
  $query = "INSERT INTO users (oauth_uid,username,email_id,full_name,platform,password,verified,picture,date,ip) VALUES ('$client_id','$username','$client_email','$client_name','$client_plat','$password','1','$client_pic','$date','$ip')"; 
  mysqli_query($con,$query); 
  header("Location: ../oauth.php?new_user");
  exit();
}

}
else
{
if(isset($_GET['login']))
{
header("Location: $authUrl");
exit();
}
}
?>