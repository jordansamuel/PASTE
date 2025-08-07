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
				<h1 class="title is-5"><?php echo $lang['totalpastes'] . ' ' . $total_pastes ?></h1>
				<h1 class="subtitle is-6"><?php echo '<a href="user.php?user=' . $_SESSION['username'] . '" target="_self">' . $lang['mypastes'] . '</a>'; ?></h1>
				<?php
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					if (isset($success)) {
						echo '<div class="paste-alert alert3" style="text-align:center;">
					' . $success . '
					</div>';
					} elseif (isset($error)) {
						echo '<div class="paste-alert alert6" style="text-align:center;">
					' . $error . '
					</div>';
					}
				}
				?>
				<hr>
				<h1 class="title is-5"><?php echo $lang['myprofile']; ?></h1>
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
					<div class="columns">
						<div class="column">
							<div class="field">
								<label class="label">Username</label>
								<div class="control has-icons-left has-icons-right">
									<input disabled="" type="text" class="input" name="username" style="cursor:not-allowed;" placeholder="<?php echo $user_username; ?>">
									<span class="icon is-small is-left">
										<i class="fas fa-user"></i>
									</span>
								</div>
							</div>
							<div class="field">
								<label class="label">Email</label>
								<div class="control has-icons-left has-icons-right">
									<input <?php if ($user_verified == "1") {
												echo 'disabled=""';
											} ?> type="text" class="input" name="email" placeholder="<?php echo $user_email_id; ?>">
									<span class="icon is-small is-left">
										<i class="fas fa-envelope"></i>
									</span>
								</div>
							</div>
							<hr>
							<h1 class="title is-5"><?php echo $lang['chgpwd']; ?></h1>
							<div class="field">
								<label class="label">Current Password</label>
								<div class="control has-icons-left has-icons-right">
									<input type="password" class="input" name="old_password" placeholder="<?php echo $lang['curpwd']; ?>">
									<span class="icon is-small is-left">
										<i class="fas fa-key"></i>
									</span>
								</div>
							</div>
							<div class="field">
								<label class="label">New Password</label>
								<div class="control has-icons-left has-icons-right">
									<input type="password" class="input" name="password" placeholder="<?php echo $lang['newpwd']; ?>">
									<span class="icon is-small is-left">
										<i class="fas fa-key"></i>
									</span>
								</div>
							</div>
							<div class="field">
								<label class="label">Confirm Password</label>
								<div class="control has-icons-left has-icons-right">
									<input type="password" class="input" name="cpassword" placeholder="<?php echo $lang['confpwd']; ?>">
									<span class="icon is-small is-left">
										<i class="fas fa-key"></i>
									</span>
								</div>
							</div>
							<div class="field">
								<button type="submit" name="submit" class="button is-info">Submit</button>
							</div>
						</div>
						<div class="column">
						</div>
						<div class="column">
						</div>
					</div>
				</form>
			</div>
			<?php require_once('theme/' . $default_theme . '/sidebar.php'); ?>
		</div>
	</div>
</main>