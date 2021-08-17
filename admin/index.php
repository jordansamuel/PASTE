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
require_once('../includes/password.php'); 

session_start();

require_once ('../config.php');

$con = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);
if (mysqli_connect_errno()) {
	$sql_error = mysqli_connect_error();
	die("Unable connect to database");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$username = htmlentities(trim($_POST['username']));

	$query = 'SELECT user, pass FROM admin WHERE user = ? LIMIT 1';
	$stmt = mysqli_prepare($con, $query);

	mysqli_stmt_bind_param($stmt, 's', $username);

	mysqli_stmt_execute($stmt);

	mysqli_stmt_bind_result($stmt, $user, $pass);

	mysqli_stmt_fetch($stmt);

	mysqli_stmt_free_result($stmt);

	if ($user && password_verify($_POST['password'], $pass)) {
		header("Location: dashboard.php");
		$_SESSION['login'] = true;

		return;
	}

	$msg = '<div class="paste-alert alert6" style="text-align:center;">
				Wrong User/Password
			</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Paste - Login</title>
  <link href="css/paste.css" rel="stylesheet">
  <style type="text/css">
    body{background: #F5F5F5;}
  </style>
  </head>
<body>
    <div class="login-form">
	<?php
	if (isset($msg)) {
		echo $msg;
	}
	?>
      <form action="." method="post">
        <div class="top">
          <h1>Paste</h1>
        </div>
        <div class="form-area">
          <div class="group">
            <input type="text" class="form-control" id="username" name="username" placeholder="Username" value="">
            <i class="fa fa-user"></i>
          </div>
          <div class="group">
            <input type="password" class="form-control" id="password" name="password" placeholder="Password" value="">
            <i class="fa fa-key"></i>
          </div>
		  <!-- Not yet implemented
			<div class="checkbox checkbox-primary">
					<input id="rememberme" type="checkbox" checked="">
					<label for="rememberme"> Remember Me</label>
			</div>
		  -->
          <button type="submit" class="btn btn-default btn-block">LOGIN</button>
        </div>
      </form>
    </div>
</body>
</html>