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

<main class="bd-main">
	<div class="bd-side-background"></div>
	<div class="bd-main-container container">
		<div class="bd-duo">
			<div class="bd-lead">
				<?php
				// Logged in
				if (isset($success)) {
					echo '<p class="help is-success subtitle is-6">' . $success . '</p>';
					// Verification email sent
					if (isset($_GET['register']) && $verification == 'enabled') {
						echo '<p class="help is-success subtitle is-6">' . $lang['versent'] . '</p>';
					}
				}
				// Errors
				elseif (isset($error)) {
					echo '<p class="help is-danger subtitle is-6">' . $error . '</p>';
				}
				// Login page
				if (isset($_GET['login'])) {
				?>
					<form action="login.php?login" method="post">
						<div class="columns">
							<div class="column">
								<h1 class="title is-4">Login</h1>
								<div class="field">
									<label class="label">Username</label>
									<div class="control has-icons-left has-icons-right">
										<input type="text" class="input" name="username" placeholder="Username">
										<span class="icon is-small is-left">
											<i class="fas fa-user"></i>
										</span>
									</div>
								</div>
								<div class="field">
									<label class="label">Password</label>
									<div class="control has-icons-left has-icons-right">
										<input type="password" class="input" name="password" placeholder="Password">
										<span class="icon is-small is-left">
											<i class="fas fa-key"></i>
										</span>
									</div>
								</div>
								<div class="field">
									<div class="b-checkbox is-info is-inline">
										<input class="is-checkradio is-info" id="rememberme" name="rememberme" type="checkbox" checked="">
										<label for="rememberme">
											<?php echo $lang['rememberme']; ?>
										</label>
									</div>
								</div>
								<div class="field">
									<input class="button is-info is-fullwidth" type="submit" name="signin" value="Login" value="<?php echo md5($date . $ip); ?>">
								</div>
								<hr>
								<!-- Oauth -->
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
								<!-- // -->
							</div>
							<div class="column">
							</div>
							<div class="column">
								<?php if (isset($_SESSION['username'])) { ?>
								<?php } else { ?>
									<?php echo $ads_2; ?>
								<?php } ?>
							</div>
						</div>
					</form>
				<?php // Registration page
				} elseif (isset($_GET['register'])) {
				?>
					<form action="login.php?register" method="post">
						<div class="columns">
							<div class="column">
								<h1 class="title is-4">Register</h1>
								<div class="field">
									<label class="label">Username</label>
									<div class="control has-icons-left has-icons-right">
										<input type="text" class="input" name="username" placeholder="Username">
										<span class="icon is-small is-left">
											<i class="fas fa-user"></i>
										</span>
									</div>
								</div>
								<div class="field">
									<label class="label">Your name</label>
									<div class="control has-icons-left has-icons-right">
										<input type="text" class="input" name="full" placeholder="Your Name">
										<span class="icon is-small is-left">
											<i class="fas fa-user-plus"></i>
										</span>
									</div>
								</div>
								<div class="field">
									<label class="label">Email</label>
									<div class="control has-icons-left has-icons-right">
										<input type="text" class="input" name="email" placeholder="Email">
										<span class="icon is-small is-left">
											<i class="fas fa-envelope"></i>
										</span>
									</div>
								</div>
								<div class="field mb-4">
									<label class="label">Password</label>
									<div class="control has-icons-left has-icons-right">
										<input type="password" class="input" name="password" placeholder="Password">
										<span class="icon is-small is-left">
											<i class="fas fa-key"></i>
										</span>
									</div>
								</div>
								<div class="field">
									<input class="button is-info is-fullwidth" type="submit" name="signup" value="Register" value="<?php echo md5($date . $ip); ?>">
								</div>
								<hr>
								<!-- Oauth -->
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
								<!-- // -->
							</div>
							<div class="column">
							</div>
							<div class="column">
								<?php if (isset($_SESSION['username'])) { ?>
								<?php } else { ?>
									<?php echo $ads_2; ?>
								<?php } ?>
							</div>
						</div>
					</form>
				<?php // Forgot password
				} elseif (isset($_GET['forgot'])) {
				?>
					<form action="login.php?forgot" method="post">
						<div class="columns">
							<div class="column">
								<h1 class="title is-4">Forgot Password</h1>
								<div class="field">
									<label class="label">Email</label>
									<div class="control has-icons-left has-icons-right">
										<input type="text" class="input" name="email" placeholder="Enter your email address">
										<span class="icon is-small is-left">
											<i class="fas fa-envelope"></i>
										</span>
									</div>
								</div>
								<div class="field">
									<input class="button is-fullwidth is-info" type="submit" name="forgot" value="Submit" value="<?php echo md5($date . $ip); ?>" />
								</div>
							</div>
							<div class="column">
							</div>
							<div class="column">
								<?php if (isset($_SESSION['username'])) { ?>
								<?php } else { ?>
									<?php echo $ads_2; ?>
								<?php } ?>
							</div>
						</div>
					</form>
				<?php // Resend verification email
				} elseif (isset($_GET['resend'])) {
				?>
					<form action="login.php?resend" method="post">
						<div class="columns">
							<div class="column">
								<h1 class="title is-4">Resend verification email</h1>
								<div class="field">
									<label class="label">Email</label>
									<div class="control has-icons-left has-icons-right">
										<input type="text" class="input" name="email" placeholder="Enter your email address">
										<span class="icon is-small is-left">
											<i class="fas fa-envelope"></i>
										</span>
									</div>
								</div>
								<div class="field">
									<input class="button is-fullwidth is-info" type="submit" value="Submit" name="resend" value="<?php echo md5($date . $ip); ?>" />
								</div>
							</div>
							<div class="column">
							</div>
							<div class="column">
								<?php if (isset($_SESSION['username'])) { ?>
								<?php } else { ?>
									<?php echo $ads_2; ?>
								<?php } ?>
							</div>
						</div>
					</form>
				<?php } else { ?>
					<div class="columns">
						<div class="column">
							<h1 class="title is-4">Where to?</h1>
							<a href="login.php?login">Login</a><br />
							<a href="login.php?register">Register</a> <br />
							<a href="login.php?forgot">Forgot Password</a><br />
							<a href="login.php?resend">Resend verification email</a><br />
						</div>
						<div class="column">
						</div>
						<div class="column">
							<?php if (isset($_SESSION['username'])) { ?>
							<?php } else { ?>
								<?php echo $ads_2; ?>
							<?php } ?>
						</div>
					</div>
				<?php  } ?>
			</div>
			<?php require_once('theme/' . $default_theme . '/sidebar.php'); ?>
		</div>
	</div>
</main>