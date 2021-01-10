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
				<?php if (isset($notfound)) { ?>
					<h1 class="subtitle is-4"><?php echo $notfound; ?></h1>
					<a href="./" class="btn btn-default">New Paste</a>
				<?php } else { ?>
					<h1 class="title is-5"><?php echo $lang['pwdprotected']; ?><h1>
							<?php if (isset($error)) { ?>
								<p class="help is-danger subtitle is-6"><?php echo $error; ?></p>
							<?php } ?>
							<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
								<div class="field has-addons">
									<div class="control">
										<input type="hidden" name="id" value="<?php echo $paste_id; ?>">
										<input type="password" class="input" name="mypass" placeholder="<?php echo $lang['enterpwd']; ?>">
									</div>
								</div>
								<button type="submit" name="submit" class="button is-info">Submit</button>
							</form>
						<?php } ?>
			</div>
			<?php require_once('theme/' . $default_theme . '/sidebar.php'); ?>
		</div>
	</div>
</main>