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
					echo '<p class="help is-success subtitle is-6">' . $success . ' <br /> ' . $lang['49'] . '</p>';
					echo '<meta http-equiv="refresh" content="2;url=./">';
				}
				// Errors
				elseif (isset($error)) {
					echo '<p class="help is-danger subtitle is-6">' . $error . '</p>';
				}
				if (isset($old_user)) {
					echo '<p class="help is-success subtitle is-6">' . $success . ' <br /> ' . $lang['50'] . '</p>';
					echo '<meta http-equiv="refresh" content="1;url=./">';
				} else {
				?>
					<h1 class="title is-4"><?php echo $lang['almostthere']; ?><h1>
							<form action="oauth.php?newuser" method="post">
								<div class="columns">
									<div class="column">
										<div class="field">
											<label class="label">Possible Username</label>
											<div class="control">
												<input readonly="" type="text" class="input" name="autoname" value="<?php echo $username; ?>" disabled>
											</div>
										</div>
										<div class="field">
											<label class="label">Username</label>
											<div class="control">
												<input type="text" class="input" name="new_username" placeholder="<?php echo $lang['setuser']; ?>">
											</div>
										</div>
										<div class="field is-grouped">
											<div class="control">
												<input class="button is-info" type="submit" name="user_change" value="Submit" value="<?php echo md5($date . $ip); ?>">
											</div>
											<div class="control">
												<a href="." class="button"><?php echo $lang['keepuser']; ?></a>
											</div>
										</div>
									</div>
									<div class="column">
									</div>
									<div class="column">
										<?php echo $ads_2; ?>
									</div>
								</div>
							</form>
						<?php } ?>
			</div>
			<?php require_once('theme/' . $default_theme . '/sidebar.php'); ?>
		</div>
	</div>
</main>