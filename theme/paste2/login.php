<?php
/*
 * Paste 3 Default theme <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
?>

<div class="content">
	<!-- START CONTAINER -->
	<div class="container-padding">
		<!-- Start Row -->
		<div class="row">
			<!-- Start Panel -->
			<div class="col-md-9 col-lg-10">
				<div class="panel panel-default">
					<div class="login-form" style="padding-top: 0px;">
					<?php
					// Logged in
					if (isset($success)) {
							echo '<div class="paste-alert alert3" style="text-align: center;">
									' . $success . '
								</div>';

						// Verification email sent
						if (isset($_GET['register']) && $verification == 'enabled')  {
							echo '<div class="paste-alert alert5" style="text-align: center;">
									' . $lang['versent'] . '
								</div>';
						}

					}

					// Errors
					elseif (isset($error)) {
						echo '<div class="paste-alert alert5" style="text-align: center;">
								' . $error . '
							</div>';
					}

					// Login page
					if (isset($_GET['login'])) {
					?>
						<form action="login.php?login" method="post">
							<div class="form-area">
								<div class="panel-title" style="text-align:center;">
									Login
								</div>

								<div class="group">
									<input type="text" class="form-control" name="username" placeholder="Username">
									<i class="fa fa-user"></i>
								</div>

								<div class="group">
									<input type="password" class="form-control" name="password" placeholder="Password">
									<i class="fa fa-key"></i>
								</div>

								<div class="checkbox checkbox-primary">
									<input id="rememberme" name="rememberme" type="checkbox" checked="">
									<label for="rememberme">
										<?php echo $lang['rememberme']; ?>
									</label>
								</div>

								<input type="hidden" name="signin" value="<?php echo md5($date.$ip); ?>">
								<button type="submit" name="submit" class="btn btn-default btn-block">Sign in</button>
							</div>
						</form>
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

					<?php // Registration page
					} elseif (isset($_GET['register']))  {
					?>
						<form action="login.php?register" method="post">
							<div class="form-area">
								<div class="panel-title" style="text-align:center;">
									Register
								</div>

								<div class="group">
									<input type="text" class="form-control" name="username" placeholder="Username">
									<i class="fa fa-user"></i>
								</div>

								<div class="group">
									<input type="text" class="form-control" name="full" placeholder="Your Name">
									<i class="fa fa-user-plus"></i>
								</div>

								<div class="group">
									<input type="text" class="form-control" name="email" placeholder="Email">
									<i class="fa fa-envelope"></i>
								</div>

								<div class="group">
									<input type="password" class="form-control" name="password" placeholder="Password">
									<i class="fa fa-key"></i>
								</div>

								<input type="hidden" name="signup" value="<?php echo md5($date.$ip); ?>">
								<button type="submit" name="submit" class="btn btn-default btn-block">Register</button>
							</div>
						</form>
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

					<?php // Forgot password
					} elseif (isset($_GET['forgot']))  {
					?>
						<form action="login.php?forgot" method="post">
							<div class="form-area">
								<div class="panel-title" style="text-align:center;">
									Forgot Password
								</div>

								<div class="group">
									<input type="text" class="form-control" name="email" placeholder="Enter your email address">
									<i class="fa fa-envelope"></i>
								</div>

								<input type="hidden" name="forgot" value="<?php echo md5($date.$ip); ?>" />
								<button type="submit" name="submit" class="btn btn-default btn-block">Submit</button>
							</div>
						</form>

					<?php // Resend verification email
					} elseif (isset($_GET['resend']))  {
					?>
						<form action="login.php?resend" method="post">
							<div class="form-area">
								<div class="panel-title" style="text-align:center;">
									Resend verification email
								</div>

								<div class="group">
									<input type="text" class="form-control" name="email" placeholder="Enter your email address">
									<i class="fa fa-envelope"></i>
								</div>

								<input type="hidden" name="resend" value="<?php echo md5($date.$ip); ?>" />
								<button type="submit" name="submit" class="btn btn-default btn-block">Submit</button>
							</div>
						</form>

					<?php } else  {?>
						<div class="panel-title" style="text-align:center;">
							Where to?
						</div>
						<a href="login.php?login">Login</a><br />
						<a href="login.php?register">Register</a> <br />
						<a href="login.php?forgot">Forgot Password</a><br />
						<a href="login.php?resend">Resend verification email</a><br />
					<?php  } ?>
					</div>
				</div>
			</div>

<?php require_once('theme/'.$default_theme.'/sidebar.php'); ?>
<?php echo $ads_2; ?>
