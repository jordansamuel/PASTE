<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE> - Bulma theme
 * Theme by wsehl <github.com/wsehl> (January, 2021)
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
<html lang="<?php echo basename($default_lang, ".php"); ?>">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>
		<?php if (isset($p_title)) {
			echo $p_title . ' - ';
		}
		echo $title;
		?>
	</title>
	<meta name="description" content="<?php echo $des; ?>" />
	<meta name="keywords" content="<?php echo $keyword; ?>" />
	<link rel="shortcut icon" href="<?php echo '//' . $baseurl . '/theme/' . $default_theme; ?>/img/favicon.ico">
	<link href="<?php echo '//' . $baseurl . '/theme/' . $default_theme; ?>/css/paste.css" rel="stylesheet" type="text/css" />
	<?php
	if (isset($ges_style)) {
		echo $ges_style;
	}
	if (isset($_SESSION['captcha_mode']) == "recaptcha") {
		echo "<script src='https://www.google.com/recaptcha/api.js'></script>";
	}
	?>
</head>

<body>
	<nav id="navbar" class="bd-navbar navbar is-spaced" style="border-bottom: 1px solid #ebeaeb">
		<div class="container">
			<div class="navbar-brand">
				<a style="font-size: 24px;" href="<?php echo '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\'); ?>" class="navbar-item mx-1"><?php echo $site_name; ?></a>
				<div id="navbarBurger" class="navbar-burger burger" data-target="navMenuDocumentation">
					<span></span><span></span><span></span>
				</div>
			</div>
			<div id="navMenuDocumentation" class="navbar-menu">
				<div class="navbar-end">
					<div class="navbar-item">
						<?php if (isset($_SESSION['token'])) {
							if (isset($privatesite) && $privatesite == "on") { // Hide if site is private
							} else {
								if ($mod_rewrite == '1') {
									echo '<a class="button navbar-item mx-2" href="' . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/archive">
											<span class="icon has-text-info">
												<i class="fa fa-book" aria-hidden="true"></i>
											</span>
											<span>Archive</span>
										</a>';
								} else {
									echo '<a class="button navbar-item mx-2" href="' . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/archive.php">
											<span class="icon has-text-info">
												<i class="fa fa-book" aria-hidden="true"></i>
											</span>
											<span>Archive</span>
										</a>';
								}
							}
							echo '<div class="navbar-item has-dropdown is-hoverable">
										<a class="navbar-link" role="presentation">' . $_SESSION['username'] . '</a>
											<div class="navbar-dropdown">';
							if ($mod_rewrite == '1') {
								echo '<a class="navbar-item" href="' . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/user/' . $_SESSION['username'] . '">Pastes</a>';
								echo '<a class="navbar-item" href="' . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/profile">Settings</a>';
							} else {
								echo '<a class="navbar-item" href="' . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/user.php?user=' . $_SESSION['username'] . '">Pastes</a>';
								echo '<a class="navbar-item" href="' . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/profile.php">Settings</a>';
							}
							echo '<hr class="navbar-divider">
								<a class="navbar-item" href="./?logout">Logout</a>
							  </div>
							</div>';
						?>
						<?php } else { ?>
							<div class="buttons">
								<?php
								if (isset($privatesite) && $privatesite == "on") { // Hide if site is private
								} else {
									if ($mod_rewrite == '1') {
										echo '<a class="button" href="' . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/archive">
											<span class="icon has-text-info">
												<i class="fa fa-book" aria-hidden="true"></i>
											</span>
											<span>Archive</span>	
									</a>';
									} else {
										echo '<a class="button" href="' . '//' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/archive.php">
											<span class="icon has-text-info">
												<i class="fa fa-book" aria-hidden="true"></i>
											</span>
											<span>Archive</span>
									</a>';
									}
								}
								?>
								<a class="button is-info modal-button" data-target="#signin">Sign In</a>
							</div>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</nav>

	<div id="#signin" class="modal modal-fx-fadeInScale">
		<div class="modal-background"></div>
		<div class="modal-content modal-card is-tiny">
			<header class="modal-card-head">
				<nav class="tabs" style="margin-bottom: -1.25rem;flex-grow:1;">
					<div class="container">
						<ul>
							<li class="tab is-active" onclick="openTab(event,'logid')"><a>Login</a></li>
							<li class="tab" onclick="openTab(event,'regid')"><a>Register</a></li>
						</ul>
					</div>
				</nav>
				<button class="modal-button-close delete" aria-label="close"></button>
			</header>
			<div id="logid" class="content-tab">
				<section class="modal-card-body">
					<form method="POST" action="login.php">
						<div class="field">
							<label class="label"><?php echo $lang['username']; ?></label>
							<div class="control has-icons-left has-icons-right">
								<input type="text" class="input" name="username" placeholder="<?php echo $lang['username']; ?>">
								<span class="icon is-small is-left">
									<i class="fas fa-user"></i>
								</span>
							</div>
						</div>
						<div class="field">
							<label class="label"><?php echo $lang['curpwd']; ?></label>
							<div class="control has-icons-left has-icons-right">
								<input type="password" class="input" name="password" placeholder="<?php echo $lang['curpwd']; ?>">
								<span class="icon is-small is-left">
									<i class="fas fa-key"></i>
								</span>
							</div>
						</div>
						<input class="button is-link is-fullwidth my-4" type="submit" name="signin" value="Login" value="<?php echo md5($date . $ip); ?>">
						<div class="checkbox checkbox-primary">
							<input id="rememberme" name="rememberme" type="checkbox" checked="">
							<label for="rememberme">
								<?php echo $lang['rememberme']; ?>
							</label>
						</div>
						<?php if ($enablegoog == "no") {
						} else { ?>
							<a class="button is-fullwidth is-google my-4" href="oauth/google.php?login">
								<span class="icon">
									<i class="fab fa-google"></i>
								</span>
								<span>Sign in with Google</span>
							</a>
						<?php }
						if ($enablefb == "no") {
						} else { ?>
							<a class="button is-fullwidth is-facebook" href="oauth/facebook.php?login">
								<span class="icon">
									<i class="fab fa-facebook"></i>
								</span>
								<span>Sign in with Facebook</span>
							</a>
						<?php } ?>
					</form>
				</section>
				<footer class="modal-card-foot">
					<a href="login.php?forgot">Forgot Password?</a>
				</footer>
			</div>
			<div id="regid" class="content-tab" style="display:none">
				<section class="modal-card-body">
					<form method="POST" action="login.php?register">
						<div class="field">
							<label class="label"><?php echo $lang['username']; ?></label>
							<div class="control has-icons-left has-icons-right">
								<input type="text" class="input" name="username" placeholder="<?php echo $lang['username']; ?>">
								<span class="icon is-small is-left">
									<i class="fas fa-user"></i>
								</span>
							</div>
						</div>
						<div class="field">
							<label class="label"><?php echo $lang['fullname']; ?></label>
							<div class="control has-icons-left has-icons-right">
								<input type="text" class="input" name="full" placeholder="<?php echo $lang['fullname']; ?>">
								<span class="icon is-small is-left">
									<i class="fas fa-user-plus"></i>
								</span>
							</div>
						</div>
						<div class="field">
							<label class="label"><?php echo $lang['email']; ?></label>
							<div class="control has-icons-left has-icons-right">
								<input type="text" class="input" name="email" placeholder="<?php echo $lang['email']; ?>">
								<span class="icon is-small is-left">
									<i class="fas fa-envelope"></i>
								</span>
							</div>
						</div>
						<div class="field">
							<label class="label"><?php echo $lang['newpwd']; ?></label>
							<div class="control has-icons-left has-icons-right">
								<input type="password" class="input" name="password" placeholder="<?php echo $lang['newpwd']; ?>">
								<span class="icon is-small is-left">
									<i class="fas fa-key"></i>
								</span>
							</div>
						</div>
						<input class="button is-link is-fullwidth my-4" type="submit" name="signup" value="Register" value="<?php echo md5($date . $ip); ?>">
						<?php if ($enablegoog == "no") {
						} else { ?>
							<a class="button is-fullwidth is-google my-4" href="oauth/google.php?login">
								<span class="icon">
									<i class="fab fa-google"></i>
								</span>
								<span>Sign up with Google</span>
							</a>
						<?php }
						if ($enablefb == "no") {
						} else { ?>
							<a class="button is-fullwidth is-facebook" href="oauth/facebook.php?login">
								<span class="icon">
									<i class="fab fa-facebook"></i>
								</span>
								<span>Sign up with Facebook</span>
							</a>
						<?php } ?>
					</form>
				</section>
				<footer class="modal-card-foot">
					<a href="login.php?resend">Resend verification email</a>
				</footer>
			</div>
		</div>
	</div>