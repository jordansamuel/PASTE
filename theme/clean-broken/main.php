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

<div class="content">
	<!-- START CONTAINER -->
	<div class="container-padding">
		<!-- Start Row -->
		<div class="row">

			<!-- Guests -->
			<?php if ( isset($noguests) && $noguests == "on" ) { // Site permissions ?>
			<div class="col-md-9 col-lg-10">
				<div class="panel panel-default" style="padding-bottom: 100px;">
					<div class="error-pages">
						<i class="fa fa-users fa-5x" aria-hidden="true"></i>
						<h1><?php echo $lang['guestwelcome']; ?></h1>
						<p><?php echo $lang['pleaseregister']; ?></p>
					</div>
				</div>
			</div>
			<?php } else { ?>

			<!-- Paste Panel -->
			<div class="col-md-9 col-lg-10">
			<?php if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					if (isset($error)) { ?>
						<!-- Error Panel -->
							<div class="panel panel-primary">
								<div class="panel-body">
								<i class="fa fa-exclamation-circle"" aria-hidden="true"></i> <?php echo $error; ?>
								</div>
							</div>
				<?php } 
				}
			?>
				<div class="panel panel-default">
					<div class="panel-title">
						<?php echo $lang['newpaste']; ?>
					</div>
					
					<div class="panel-body">
						<form class="form-horizontal" name="mainForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
							<div class="form-group">
								<!-- Title -->
								<div class="col-sm-4 col-md-4 col-lg-4" style="padding-bottom:5px;">
									<div class="control-group">
									  <div class="controls">
									   <div class="input-prepend input-group">
										 <span class="add-on input-group-addon"><i class="fa fa-font"></i></span>
											<input type="text" class="form-control" name="title" placeholder="<?php echo $lang['pastetitle']; ?>" value="<?php echo ( isset( $_POST['title'] ) )?$_POST['title']:''; // Pre-populate if we come here on an error" ?>">
									   </div>
									  </div>
									</div>
								</div>
								  
								<!-- Format -->
								<div class="col-sm-4 col-md-4 col-lg-4" style="margin-top:-1px; padding-bottom:2px;">
									<select class="selectpicker" data-live-search="true" name="format">
										<?php // Show popular GeSHi formats
											foreach ($geshiformats as $code=>$name) {
												if (in_array($code, $popular_formats)) {
                                                    if ( isset( $_POST['format'] ) ) {
                                                        $sel = ($_POST['format'] == $code)?'selected="selected"':''; // Pre-populate if we come here on an error
                                                    } else {
                                                        $sel = ($code == "text")?'selected="selected"':'';
                                                    }
                                                    echo '<option ' . $sel . ' value="' . $code . '">' . $name . '</option>';
												}
											}

											echo '<option value="text">-------------------------------------</option>';

											// Show all GeSHi formats.
                                            foreach ($geshiformats as $code=>$name) {
                                                if ( !in_array( $code, $popular_formats ) ) {
                                                    if ( isset( $_POST['format'] ) ) {
                                                        $sel = ($_POST['format'] == $code)?'selected="selected"':''; // Pre-populate if we come here on an error
                                                    } else {
                                                        $sel = ($code == "text")?'selected="selected"':'';
                                                    }
                                                    echo '<option ' . $sel . ' value="' . $code . '">' . $name . '</option>';
                                                }
											}
										?>
									</select>
								</div>
									
								<!-- Buttons -->
								<div class="col-sm-2 col-md-2 col-lg-2 pull-right" style="margin-top:1px; margin-right:20px">
									<a class="btn btn-default" onclick="highlight(document.getElementById('code')); return false;"><i class="fa fa-indent"></i>Highlight</a>
								</div>
							</div>

								<!-- Text area -->
								<div class="form-group">
									<div class="col-md-12">
									  <textarea class="form-control" rows="15" id="code" name="paste_data" onkeydown="return catchTab(this,event)" placeholder="hello world"><?php echo ( isset( $_POST['paste_data'] ) )?$_POST['paste_data']:''; // Pre-populate if we come here on an error" ?></textarea>
									</div>
								</div>

								<!-- Expiry -->
								<div class="form-group">
								  <label class="control-label form-label pull-left" style="padding-left: 20px;"><?php echo $lang['expiration']; ?></label>
									<div class="col-sm-8">
                                        <?php 
                                        $post_expire = "";
                                        if ( $_POST ) {
                                            if ( isset( $_POST['paste_expire_date'] ) ) {
                                                $post_expire = $_POST['paste_expire_date'];
                                            }
                                        }
                                        ?>                                    
										<select class="selectpicker" style="display: none;" name="paste_expire_date">
											<option value="N" <?php echo ($post_expire == "N")?'selected="selected"':''; ?>>Never</option>
											<option value="self" <?php echo ($post_expire == "self")?'selected="selected"':''; ?>>View Once</option>
											<option value="10M" <?php echo ($post_expire == "10M")?'selected="selected"':''; ?>>10 Minutes</option>
											<option value="1H" <?php echo ($post_expire == "1H")?'selected="selected"':''; ?>>1 Hour</option>
											<option value="1D" <?php echo ($post_expire == "1D")?'selected="selected"':''; ?>>1 Day</option>
											<option value="1W" <?php echo ($post_expire == "1W")?'selected="selected"':''; ?>>1 Week</option>
											<option value="2W" <?php echo ($post_expire == "2W")?'selected="selected"':''; ?>>2 Weeks</option>
											<option value="1M" <?php echo ($post_expire == "1M")?'selected="selected"':''; ?>>1 Month</option>
										</select>
									</div>
								</div>
						
								<!-- Visibility -->
								<div class="form-group">
								  <label class="control-label form-label pull-left" style="padding-left: 20px;"><?php echo $lang['visibility']; ?>&nbsp;&nbsp;</label>
									<div class="col-sm-8">
                                        <?php 
                                        $post_visibility = "";
                                        if ( $_POST ) {
                                            if ( isset( $_POST['visibility'] ) ) {
                                                $post_visibility = $_POST['visibility'];
                                            }
                                        }
                                        ?>
										<select class="selectpicker" style="display: none;" name="visibility">
											<option value="0" <?php echo ($post_visibility == "0")?'selected="selected"':''; ?>>Public</option>
											<option value="1" <?php echo ($post_visibility == "1")?'selected="selected"':''; ?>>Unlisted</option>
											<?php if (isset($_SESSION['token'])) {?>
											<option value="2" <?php echo ($post_visibility == "2")?'selected="selected"':''; ?>>Private</option>
											<?php } else { ?>
											<option disabled >Private (Register)</option>
											<?php } ?>
										</select>
									</div>
								</div>
						  
								<!-- Password -->
								<div class="form-group">
									<div class="col-md-12 col-lg-3">
										<div class="control-group">
										  <div class="controls">
										   <div class="input-prepend input-group">
											 <span class="add-on input-group-addon"><i class="fa fa-lock"></i></span>
												<input type="text" class="form-control" name="pass" id="pass" placeholder="<?php echo $lang['pwopt']; ?>" value="<?php echo ( isset($_POST['pass'] ) )?$_POST['pass']:''; // Pre-populate if we come here on an error" ?>">
										   </div>
										  </div>
										</div>
									</div>
								</div>
					
								<!-- Encrypt -->
								<div class="col-md-6">
									<div class="checkbox checkbox-primary">
                                        <?php 
                                        $encrypted_checked = "";
                                        if ( $_POST ) {
                                            // We came here from an error, carry the checkbox setting forward
                                            if ( isset( $_POST['encrypted'] ) ) { 
                                                $encrypted_checked = "checked";
                                            }
                                        } else {
                                            // Fresh paste. Default to encrypted on
                                            $encrypted_checked = "checked";
                                        }
                                        ?>
                                            
										<input id="encrypt" name="encrypted" type="checkbox" <?php echo $encrypted_checked; ?>>
										<label for="encrypt">
											<?php echo $lang['encrypt']; ?>
										</label>
									</div>
								</div><br /><br />

						  <?php if ($cap_e == "on" && !isset($_SESSION['username'])) { 
                            if ($_SESSION['captcha_mode'] == "recaptcha") {
                                ?>
                                <div class="g-recaptcha" data-sitekey="<?php echo $_SESSION['captcha']; ?>"></div>
                                <br />
                                <?php
                            } else {
                            ?>
								<!-- Captcha -->
								<div class="form-group pull-left captcha">
									<div class="col-md-12 col-lg-3">
										<div class="control-group">
										  <div class="controls">
										   <div class="input-prepend input-group">
											 <span class="add-on input-group-addon"><?php echo '<img src="' . $_SESSION['captcha']['image_src'] . '" alt="CAPTCHA" class="imagever">';   ?></span>
												<input style="height: 65px;" type="text" class="form-control" name="scode" value="" placeholder="<?php echo $lang['entercode']; ?>">
										   </div>
										  </div>
										</div>
									</div>
								</div>
						  <?php }
                          } ?>
					  
							<div class="col-md-12 col-lg-3">
								<div class="control-group">
									<div class="controls">
										<div class="input-prepend input-group">
											<input class="btn btn-default" type="submit" name="submit" id="submit" value="Paste"/>
										</div>
									</div>
								</div>
							</div>
						</form>   
					</div>
				</div>
			</div>
			<!-- End Panel -->
<?php } ?>
	
<?php require_once('theme/'.$default_theme.'/sidebar.php'); ?>
</div>