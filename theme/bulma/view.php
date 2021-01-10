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
$protocol = paste_protocol();
?>

<main class="bd-main">
	<div class="bd-side-background"></div>
	<div class="bd-main-container container">
		<div class="bd-duo">
			<div class="bd-lead">
				<div class="content panel">
					<div class="columns is-multiline">
						<div class="column is-4">
							<span class="tag is-normal"><i class="fa fa-code fa-lg" aria-hidden="true"></i>&nbsp;&nbsp;<?php echo strtoupper($p_code); ?></span>
							<span class="tag is-normal"><i class="fa fa-eye fa-lg" aria-hidden="true"></i>&nbsp;&nbsp;<?php echo $p_views; ?></span>
						</div>
						<div class="column is-4 has-text-centered">
							<h1 class="title is-6" style="margin-bottom:0;"><?php echo ($p_title); ?></h1>
							<small class="title is-6 has-text-weight-normal has-text-grey">
								<?php if ($p_member == 'Guest') {
									echo 'Guest';
								} else {
									if ($mod_rewrite == '1') {
										echo 'By <a href="' . $protocol . $baseurl . '/user/' . $p_member . '">' . $p_member . '</a>';
									} else {
										echo 'By <a href="' . $protocol . $baseurl . '/user.php?user=' . $p_member . '">' . $p_member . '</a>';
									}
								}
								?>
								on <?php echo $p_date; ?>
							</small>
						</div>
						<div class="column is-4 has-text-right">
							<div class="">
								<div class="panel-tools">
									<?php if ($p_code != "markdown") { ?>
										<a class="icon tool-icon" href="javascript:togglev();"><i class="fas fa-list-ol fa-lg has-text-grey" title="Toggle Line Numbers"></i></a>
									<?php } ?>
									<a class="icon tool-icon" href="#" onmouseover="selectText('paste');"><i class="far fa-clipboard fa-lg has-text-grey" title="Select Text"></i></a>
									<a class="icon tool-icon" href="<?php echo $p_raw; ?>"><i class="far fa-file-alt fa-lg has-text-grey" title="View Raw"></i></a>
									<a class="icon tool-icon" href="<?php echo $p_download; ?>"><i class="fas fa-file-download fa-lg has-text-grey" title="Download Paste"></i></a>
									<a class="icon tool-icon embed-tool "><i class="far fa-file-code fa-lg has-text-grey" title="Embed This Paste"></i></a>
									<a class="icon tool-icon expand-tool"><i class="fas fa-expand-alt has-text-grey" title="Full Screen"></i></a>
									<div class="panel-embed my-5" style="display:none;">
										<input type="text" class="input has-background-white-ter has-text-grey" value='<?php echo '<script src="' . $protocol . $baseurl . '/';
																														if ($mod_rewrite == '1') {
																															echo 'embed/';
																														} else {
																															echo 'paste.php?embed&id=';
																														}
																														echo $paste_id . '"></script>'; ?>' readonly>
									</div>
								</div>
							</div>
						</div>
					</div>
					<br>
					<?php if (isset($error)) {
						echo '<p class="help is-danger subtitle is-6">' . $error . '</p>';
					} else {
						echo '
						<div id="paste" style="line-height:1!important;">' . $p_content . '</div>';
					} ?>
				</div>
				<!-- Guests -->
				<?php if (!isset($_SESSION['username'])) { ?>
					<hr>
					<h1 class="title is-6 "><?php echo $lang['rawpaste']; ?><h1>
							<!-- Raw  -->
							<textarea style="line-height: 1.2;" class="textarea mx-1" rows="13" id="code" name="paste_data" onkeydown="return catchTab(this,event)" placeholder="helloworld"><?php echo $op_content; ?></textarea>
							<hr>
							<p><?php echo $lang['registertoedit']; ?></p>
						<?php } else { ?>
							<!-- Paste Panel -->
							<hr>
							<h1 class="title is-6 mx-1"><?php echo $lang['modpaste']; ?><h1>
									<form class="form-horizontal" name="mainForm" action="index.php" method="POST">
										<nav class="level">
											<div class="level-left">
												<!-- Title -->
												<div class="level-item is-pulled-left mx-1">
													<p class="control has-icons-left">
														<input type="text" class="input" name="title" placeholder="<?php echo $lang['pastetitle']; ?>" value="<?php echo ($p_title); ?>">
														<span class="icon is-small is-left">
															<i class="fa fa-font"></i></a>
														</span>
													</p>
												</div>
												<!-- Format -->
												<div class="level-item is-pulled-left mx-1">
													<div class="select">
														<div class="select">
															<select data-live-search="true" name="format">
																<?php // Show popular GeSHi formats
																foreach ($geshiformats as $code => $name) {
																	if (in_array($code, $popular_formats)) {
																		$sel = ($p_code == $code) ? 'selected="selected"' : ' ';
																		echo '<option ' . $sel . ' value="' . $code . '">' . $name . '</option>';
																	}
																}

																echo '<option value="text">__________________</option>';

																// Show all GeSHi formats.
																foreach ($geshiformats as $code => $name) {
																	if (!in_array($code, $popular_formats)) {
																		$sel = ($p_code == $code) ? 'selected="selected"' : '';
																		echo '<option ' . $sel . ' value="' . $code . '">' . $name . '</option>';
																	}
																}
																?>
															</select>
														</div>
													</div>
												</div>
												<div class="level-item is-pulled-left mx-1">
													<input class="button is-info" type="hidden" name="paste_id" value="<?php echo $paste_id; ?>" />
												</div>
												<div class="level-item is-pulled-left mx-1">
													<a class="button" onclick="highlight(document.getElementById('code')); return false;"><i class="fa fa-indent"></i>&nbspHighlight</a>
												</div>
												<div class="level-item is-pulled-left mx-1">
													<?php
													if ($_SESSION['username'] == $p_member || $_SESSION['username'] == 'admin') {
													?>
														<input class="button is-info" type="submit" name="edit" id="edit" value="<?php echo $lang['editpaste']; ?>" />
													<?php
													} ?>
												</div>
												<div class="level-item is-pulled-left mx-1">
													<input class="button is-info" type="submit" name="submit" id="submit" value="<?php echo $lang['forkpaste']; ?>" />
												</div>
											</div>
										</nav>
										<!-- Text area -->
										<textarea style="line-height: 1.2;" class="textarea mx-1" rows="13" id="code" name="paste_data" onkeydown="return catchTab(this,event)" placeholder="helloworld"><?php echo $op_content; ?></textarea>
										<br>
										<nav class="level">
											<div class="level-left">
												<div class="level-item is-pulled-left mr-1">
													<div class="field">
														<div class="subtitle has-text-weight-semibold " style="margin-left: 2px; margin-bottom: 0.6rem; font-size: 1rem;"><?php echo $lang['expiration']; ?></div>
														<div class="control">
															<!-- Expiry -->
															<div class="select">
																<select name="paste_expire_date">
																	<?php// if (isset($_SESSION['token'])) {?>
																	<option value="N" selected="selected">Never</option>
																	<option value="self">View Once</option>
																	<option value="10M">10 Minutes</option>
																	<option value="1H">1 Hour</option>
																	<option value="1D">1 Day</option>
																	<option value="1W">1 Week</option>
																	<option value="2W">2 Weeks</option>
																	<option value="1M">1 Month</option>
																	<?php// } else { ?>
																	<!--
																		<option value="1D" selected="selected">1 Day</option>
																		<option value="self">View Once</option>
																		<option value="10M">10 Minutes</option>
																		<option disabled >1 Week (Register)</option>
																		<option disabled >2 Weeks (Register)</option>
																		<option disabled >1 Month (Register)</option>
																		<option disabled >Never (Register)</option>
																		-->
																	<?php// } ?>
																</select>
															</div>
														</div>
													</div>
												</div>
												<div class="level-item is-pulled-left mx-1">
													<div class="field">
														<div class="subtitle has-text-weight-semibold " style="margin-left: 2px; margin-bottom: 0.6rem; font-size: 1rem;"><?php echo $lang['visibility']; ?>&nbsp;&nbsp;</div>
														<div class="control">
															<!-- Visibility -->
															<div class="select">
																<select name="visibility">
																	<option value="0" <?php echo ($p_visible == "0") ? 'selected="selected"' : ''; ?>>Public</option>
																	<option value="1" <?php echo ($p_visible == "1") ? 'selected="selected"' : ''; ?>>Unlisted</option>
																	<?php if (isset($_SESSION['token'])) { ?>
																		<option value="2" <?php echo ($p_visible == "2") ? 'selected="selected"' : ''; ?>>Private</option>
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
														<input type="text" class="input" name="pass" id="pass" value="" placeholder="<?php echo $lang['pwopt']; ?>">
													</div>
												</div>
											</div>
										</nav>
										<br>
										<nav>
											<div class="level-left">
												<!-- Encrypted -->
												<div class="b-checkbox is-info is-inline">
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
													<input class="is-checkradio is-info" id="encrypt" name="encrypted" type="checkbox" <?php echo $encrypted_checked; ?>>
													<label for="encrypt">
														<?php echo $lang['encrypt']; ?>
													</label>
												</div>
										</nav>
										<?php echo $ads_2; ?>
									</form>
								<?php } ?>
			</div>
			<?php require_once('theme/' . $default_theme . '/sidebar.php'); ?>
		</div>
	</div>
</main>