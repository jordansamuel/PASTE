<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE> - Clean theme
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
?>

<!DOCTYPE html>
<html lang="<?php echo basename($default_lang, ".php");?>">
  <head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php if(isset($p_title)) { echo $p_title.' - ';}echo $title; ?></title>
    <meta name="description" content="<?php echo $des; ?>" />
    <meta name="keywords" content="<?php echo $keyword; ?>" />
	<link rel="shortcut icon" href="<?php echo '//' . $baseurl . '/theme/' . $default_theme; ?>/img/favicon.ico">
    <link href="<?php echo '//' . $baseurl . '/theme/' . $default_theme; ?>/css/paste.css" rel="stylesheet" type="text/css" />
<?php
if (isset($ges_style))
{
    echo $ges_style;
}
if (isset($_SESSION['captcha_mode']) == "recaptcha") {
    echo "<script src='https://www.google.com/recaptcha/api.js'></script>";
}
?>

  </head>

<body>
  <div id="top" class="clearfix">
    <!-- Start App Logo -->
    <div class="applogo">
      <a href="<?php echo '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');?>" class="logo"><?php echo $site_name;?></a>
    </div>
    <!-- End App Logo -->

	<!-- Not yet implemented
    <form class="searchform">
      <input type="text" class="searchbox" id="searchbox" placeholder="Search">
      <span class="searchbutton"><i class="fa fa-search"></i></span>
    </form>
	//-->
	
    <!-- Start Top Menu -->
    <ul class="topmenu">
	<?php
	if ( isset($privatesite) && $privatesite == "on") { // Hide if site is private
		} else {
			if ($mod_rewrite == '1') {
			echo '<li><a href="' . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/archive">Archive</a></li>';
			} else {
			echo '<li><a href="' . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/archive.php">Archive</a></li>';
			}
		}
	?>
    </ul>
    <!-- End Top Menu -->

    <!-- Start Top Right -->
    <ul class="top-right">

    <li class="dropdown link">
		<?php if(isset($_SESSION['token'])) {
			echo '<a href="#" data-toggle="dropdown" class="dropdown-toggle profilebox"><b>' . $_SESSION['username'] . '</b><span class="caret"></span></a>';
		} else {
			echo '<a href="#" data-toggle="dropdown" class="dropdown-toggle profilebox"><b>Guest</b><span class="caret"></span></a>';
		}
		?>
        <ul class="dropdown-menu dropdown-menu-list dropdown-menu-right">
		<?php if(isset($_SESSION['token'])) {
				echo '<li role="presentation" class="dropdown-header">My Account</li>';
				  if ($mod_rewrite == '1') {
					echo '<li><a href="' . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/user/' . $_SESSION['username'] . '"><i class="fa falist fa-clipboard"></i> Pastes</a></li>';
					echo '<li><a href="' . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/profile"><i class="fa falist fa-user"></i> Settings</a></li>';
					} else {
						echo '<li><a href="' . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/user.php?user=' . $_SESSION['username'] . '"><i class="fa falist fa-clipboard"></i> Pastes</a></li>';
						echo '<li><a href="' . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/profile.php"><i class="fa falist fa-user"></i> Settings</a></li>';
					}
		?>
          <li class="divider"></li>
          <li><a href="./?logout"><i class="fa falist fa-sign-out"></i> Logout</a></li>
		<?php } else { ?>
          <li><a data-target="#signin" data-toggle="modal" href="#">Login</a></li>
          <li><a data-target="#signup" data-toggle="modal" href="#">Register</a></li>
		  <?php } ?>
		</ul>
    </li>

    </ul>
    <!-- End Top Right -->

  </div>
  <!-- END TOP -->	

    
  <!-- Sign in -->
	<div class="modal fade" id="signin" tabindex="-1" role="dialog" aria-hidden="true">
	  <div class="modal-dialog modal-sm">
		<div class="modal-content">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title">Login</h4>
		  </div>
		  <div class="modal-body">
			<form method="POST" action="login.php?login">
				  <div class="input-group">
				    <div class="input-group-addon"><i class="fa fa-user"></i></div>
					<input type="text" name="username" class="form-control" placeholder="Username">
				  </div><br />
				  <div class="input-group">
                    <div class="input-group-addon"><i class="fa fa-key"></i></div>
					<input type="password" name="password" class="form-control" placeholder="Password">
				  </div>
					<div class="checkbox checkbox-primary">
						<input id="rememberme" name="rememberme" type="checkbox" checked="">
						<label for="rememberme">
							<?php echo $lang['rememberme']; ?>
						</label>
					</div>
				  <button type="submit" class="btn btn-default btn-block">Login</button>
				  <a class="btn btn-light btn-block" href="login.php?forgot">Forgot Password?</a>
				  <input type="hidden" name="signin" value="<?php echo md5($date.$ip); ?>" />
			</form>
			<br />
			<!-- Oauth -->
		<?php if ($enablefb == "no") { } else { ?>
			<a href="oauth/facebook.php?login" class="btn btn-primary btn-block">
				<i class="fa fa-facebook"></i>Sign in with Facebook
			</a>
		<?php } 
			if ($enablegoog == "no") { } else { ?>
			<a href="oauth/google.php?login" class="btn btn-danger btn-block">
				<i class="fa fa-google"></i>Sign in with Google
			</a>
		<?php } ?>
			<!-- // -->
			  <!--
			  <div class="footer-links row">
			  </div>
			  -->			
		  </div>
		  <div class="modal-footer">
			<a style="float:left;" href="login.php?register">Register</a> <a href="login.php?resend" >Resend verification email</a>
		  </div>
		</div>
	  </div>
	</div>



<!-- Sign up -->
	<div class="modal fade" id="signup" tabindex="-1" role="dialog" aria-hidden="true">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title">Register</h4>
		  </div>
		  <div class="modal-body">
			  <form method="POST" action="login.php?register">
				<div class="form-area">
				  <div class="input-group">
                     <div class="input-group-addon"><i class="fa fa-user"></i></div>
					<input type="text" name="username" class="form-control" placeholder="Username">
				  </div>
				  <br />
				  <div class="input-group">
                    <div class="input-group-addon"><i class="fa fa-user-plus"></i></div>
					<input type="text" name="full" class="form-control" placeholder="Your Name">
				  </div>
				  <br />
				  <div class="input-group">
                    <div class="input-group-addon"><i class="fa fa-envelope"></i></div>
					<input type="text" name="email" class="form-control" placeholder="Email">
				  </div>
				  <br />
				  <div class="input-group">
                    <div class="input-group-addon"><i class="fa fa-key"></i></div>
					<input type="password" name="password" class="form-control" placeholder="Password">
				  </div>
				  <br />
				  <button type="submit" class="btn btn-default btn-block">Register</button>
				</div>
					 <input type="hidden" name="signup" value="<?php echo md5($date.$ip); ?>" />
			  </form>
			<br />
			<!-- Oauth -->
		<?php if ($enablefb == "no") { } else { ?>
			<a href="oauth/facebook.php?login" class="btn btn-primary btn-block">
				<i class="fa fa-facebook"></i>Register with Facebook
			</a>
		<?php } 
			if ($enablegoog == "no") { } else { ?>
			<a href="oauth/google.php?login" class="btn btn-danger btn-block">
				<i class="fa fa-google"></i>Register with Google
			</a>
		<?php } ?>
			<!-- // -->			  
			  <!--
			  <div class="footer-links row">
			  </div>
			  -->
		  </div>
		  <div class="modal-footer">
			<a style="float:left;" href="login.php?login">Already have an account?</a> <a href="login.php?resend" >Resend verification email</a>
		  </div>
		</div>
	  </div>
	</div>
