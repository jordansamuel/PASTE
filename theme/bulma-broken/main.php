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
				<!-- Guests -->
				<?php if (isset($noguests) && $noguests == "on") { // Site permissions 
				?>
					<section class="hero is-medium">
						<div class="hero-body">
							<div class="container">
								<h1 class="title"><?php echo $lang['guestwelcome']; ?></h1>
								<p class="subtitle"><?php echo $lang['pleaseregister']; ?></p>
							</div>
						</div>
					</section>
				<?php } else { ?>
					<!-- Paste Panel -->
					<?php if ($_SERVER['REQUEST_METHOD'] == 'POST') {
						if (isset($error)) { ?>
							<!-- Error Panel -->
							<i class="fa fa-exclamation-circle" aria-hidden=" true"></i> <?php echo $error; ?>
					<?php }
					}
					?>
					<h1 class="subtitle is-4">
						<?php echo $lang['newpaste']; ?>
					</h1>
					<form name="mainForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
						<nav class="level">
							<div class="level-left">
								<!-- Title -->
								<div class="level-item is-pulled-left" style="margin-right: 5px;">
									<p class="control has-icons-left">
										<input type="text" class="input" name="title" placeholder="<?php echo $lang['pastetitle']; ?>" value="<?php echo (isset($_POST['title'])) ? $_POST['title'] : ''; ?>">
										<span class="icon is-small is-left">
											<i class="fa fa-font"></i></a>
										</span>
									</p>
								</div>
								<!-- Format -->
								<div class="level-item is-pulled-left mx-1">
									<div class="select">
										<select data-live-search="true" name="format">
											<?php // Show popular GeSHi formats
											foreach ($geshiformats as $code => $name) {
												if (in_array($code, $popular_formats)) {
													if (isset($_POST['format'])) {
														$sel = ($_POST['format'] == $code) ? 'selected="selected"' : ''; // Pre-populate if we come here on an error
													} else {
														$sel = ($code == "markdown") ? 'selected="selected"' : '';
													}
													echo '<option ' . $sel . ' value="' . $code . '">' . $name . '</option>';
												}
											}
											echo '<option value="text">__________________</option>';
											// Show all GeSHi formats.
											foreach ($geshiformats as $code => $name) {
												if (!in_array($code, $popular_formats)) {
													if (isset($_POST['format'])) {
														$sel = ($_POST['format'] == $code) ? 'selected="selected"' : ''; // Pre-populate if we come here on an error
													} else {
														$sel = ($code == "text") ? 'selected="selected"' : '';
													}
													echo '<option ' . $sel . ' value="' . $code . '">' . $name . '</option>';
												}
											}
											?>
										</select>
									</div>
								</div>
								<div class="level-item is-pulled-left mx-1">
									<a class="button" onclick="highlight(document.getElementById('code')); return false;"><i class="fas fa-indent"></i>&nbspHighlight</a>
								</div>
								<div class="level-item is-pulled-left mx-1">
									<input class="button is-info" type="submit" name="submit" id="submit" value="Paste" />
								</div>
							</div>
						</nav>
						<!-- Text area -->
						<textarea class="textarea" rows="15" id="code" name="paste_data" onkeydown="return catchTab(this,event)" placeholder="hello world"><?php echo (isset($_POST['paste_data'])) ? $_POST['paste_data'] : ''; ?></textarea>
						<br>
						<div class="columns">
							<div class="column is-5">
								<nav class="level">
									<div class="level-left">
										<div class="level-item is-pulled-left mr-1">
											<div class="field">
												<div class="subtitle has-text-weight-semibold " style="margin-left: 2px; margin-bottom: 0.6rem; font-size: 1rem;"><?php echo $lang['expiration']; ?></div>
												<div class="control">
													<div class="select">
														<?php
														$post_expire = "";
														if ($_POST) {
															if (isset($_POST['paste_expire_date'])) {
																$post_expire = $_POST['paste_expire_date'];
															}
														}
														?>
														<select name="paste_expire_date">
															<option value="N" <?php echo ($post_expire == "N") ? 'selected="selected"' : ''; ?>>Never</option>
															<option value="self" <?php echo ($post_expire == "self") ? 'selected="selected"' : ''; ?>>View Once</option>
															<option value="10M" <?php echo ($post_expire == "10M") ? 'selected="selected"' : ''; ?>>10 Minutes</option>
															<option value="1H" <?php echo ($post_expire == "1H") ? 'selected="selected"' : ''; ?>>1 Hour</option>
															<option value="1D" <?php echo ($post_expire == "1D") ? 'selected="selected"' : ''; ?>>1 Day</option>
															<option value="1W" <?php echo ($post_expire == "1W") ? 'selected="selected"' : ''; ?>>1 Week</option>
															<option value="2W" <?php echo ($post_expire == "2W") ? 'selected="selected"' : ''; ?>>2 Weeks</option>
															<option value="1M" <?php echo ($post_expire == "1M") ? 'selected="selected"' : ''; ?>>1 Month</option>
														</select>
													</div>
												</div>
											</div>
										</div>
										<div class="level-item is-pulled-left mx-1">
											<div class="field">
												<div class="subtitle has-text-weight-semibold " style="margin-left: 2px; margin-bottom: 0.6rem; font-size: 1rem;"><?php echo $lang['visibility']; ?>&nbsp;&nbsp;</div>
												<div class="control">
													<div class="select">
														<?php
														$post_visibility = "";
														if ($_POST) {
															if (isset($_POST['visibility'])) {
																$post_visibility = $_POST['visibility'];
															}
														}
														?>
														<select name="visibility">
															<option value="0" <?php echo ($post_visibility == "0") ? 'selected="selected"' : ''; ?>>Public</option>
															<option value="1" <?php echo ($post_visibility == "1") ? 'selected="selected"' : ''; ?>>Unlisted</option>
															<?php if (isset($_SESSION['token'])) { ?>
																<option value="2" <?php echo ($post_visibility == "2") ? 'selected="selected"' : ''; ?>>Private</option>
															<?php } else { ?>
																<option disabled>Private</option>
															<?php } ?>
														</select>
													</div>
												</div>
											</div>
										</div>
									</div>
								</nav>
								<nav>
									<div class="level-left">
										<!-- Password -->
										<div class="columns">
											<div class="column">
												<input type="text" class="input" name="pass" id="pass" placeholder="<?php echo $lang['pwopt']; ?>" value="<?php echo (isset($_POST['pass'])) ? $_POST['pass'] : ''; ?>">
											</div>
										</div>
									</div>
								</nav>
								<br>
								<nav>
									<div class="level-left">
										<!-- Encrypted -->
										<div class="field">
											<?php
											$encrypted_checked = "";
											if ($_POST) {
												// We came here from an error, carry the checkbox setting forward
												if (isset($_POST['encrypted'])) {
													$encrypted_checked = "checked";
												}
											} else {
												// Fresh paste. Default to encrypted on
												$encrypted_checked = "checked";
											}
											?>
											<input class="is-checkradio is-info has-background-color" id="encrypt" name="encrypted" type="checkbox" <?php echo $encrypted_checked; ?>>
											<label for="encrypt">
												<?php echo $lang['encrypt']; ?>
											</label>
										</div>
									</div>
								</nav>
							</div>
							<div class="column is-3">
								<!-- $text_ads -->
								<?php if (isset($_SESSION['username'])) { ?>
								<?php } else { ?>
									<?php echo $text_ads; ?>
								<?php } ?>
							</div>
							<div class="column is-4">
								<!-- ReCaptcha & Captcha -->
								<?php if ($cap_e == "on" && !isset($_SESSION['username'])) {
									if ($_SESSION['captcha_mode'] == "recaptcha") {
								?>
										<div class="g-recaptcha" style="float: right; right: 0;" data-sitekey="<?php echo $_SESSION['captcha']; ?>"></div>
										<br />
									<?php
									} else {
									?>
										<!-- Captcha -->
										<div class="is-one-quarter">
											<div class="notification">
												<span class="tags are-large"><?php echo '<img src="' . $_SESSION['captcha']['image_src'] . '" alt="CAPTCHA" class="imagever">';   ?></span>
												<input type="text" class="input" name="scode" value="" placeholder="<?php echo $lang['entercode']; ?>">
												<p class="is-size-6	has-text-grey-light has-text-left mt-2">and press "Enter"</p>
											</div>
										</div>
								<?php }
								} ?>
							</div>
						</div>
					</form>
				<?php } ?>
			</div>
			<?php require_once('theme/' . $default_theme . '/sidebar.php'); ?>
		</div>
	</div>
</main>