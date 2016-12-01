<?php
/*
 * $ID Project: Paste 2.0 - J.Samuel
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in LIC.txt for more details.
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
					<div class="panel-title">
					  <span class="badge"><i class="fa fa-code fa-lg" aria-hidden="true"></i> <?php echo strtoupper($p_code); ?></span>
					  <span class="badge"><i class="fa fa-eye fa-lg" aria-hidden="true"></i> <?php echo $p_views; ?></span>
					  <h6 style="text-align: center;"><?php echo ucfirst($p_title); ?> <small><?php echo 'By ' . $p_member . ' on ' . $p_date ;?></small></h6>
					  <ul class="panel-tools">
						<li><a class="icon" href="."><i class="fa fa-plus-square fa-lg"></i></a></li>
						<li><a class="icon" href="<?php echo $p_raw; ?>"><i class="fa fa-file-text-o fa-lg"></i></a></li>
						<li><a class="icon" href="<?php echo $p_download; ?>"><i class="fa fa-download fa-lg"></i></a></li>
						<?php if ( $p_code != "markdown" ) {
							?>
						    <li><a class="icon" href="javascript:togglev();"><i class="fa fa-list-ol fa-lg"></i></a></li>
							<?php
							}
						?>
						<li><a class="icon" href="#" onmouseover="selectText('paste');"><i class="fa fa-clipboard fa-lg"></i></a></li>
						<!-- <li><a class="icon search-tool"><i class="fa fa-search fa-lg"></i></a></li> -->
						<li><a class="icon expand-tool"><i class="fa fa-expand fa-lg"></i></a></li>
					  </ul>
					</div>

					<div class="panel-search" style="display: none;">
					  <form>
						<input type="text" class="form-control" placeholder="Search this paste">
						<i class="fa fa-search icon"></i>
					  </form>
					</div>

					<div class="panel-body" style="display: block;">
					<?php if (isset($error)) {
					echo '<div class="paste-alert alert6">' . $error . '</div>'; 
					} else {
					echo '<div id="paste">' . $p_content . '</div>';
					?>
					</div>
					<?php } ?>
				</div>
			</div>
			<!-- End Panel -->
			<?php require_once('theme/'.$default_theme.'/sidebar.php'); ?>
			<?php echo $ads_2; ?>
		</div>

		<div class="row">
			<!-- Guests -->
			<?php if (!isset($_SESSION['username'])) { // Site permissions ?>
			<div class="col-md-12 col-lg-12">
				<div class="panel panel-default" style="padding-bottom: 100px;">
					<div class="panel-title">
						<?php echo $lang['rawpaste']; ?>
					</div>
					<div class="panel-body">
						<!-- Raw data -->
						<div class="form-group">
							<div class="col-md-12">
							  <textarea class="form-control" rows="15" id="code" name="paste_data" onkeydown="return catchTab(this,event)" placeholder="helloworld"><?php echo $op_content; ?></textarea>
							</div>
						</div>
					</div>
					<div class="error-pages">
						<p><?php echo $lang['registertoedit']; ?></p>
					</div>
				</div>
			</div>
			<?php } else { ?>

			<!-- Paste Panel -->
			<div class="col-md-12 col-lg-12">
				<div class="panel panel-default">
					<div class="panel-title">
						<?php echo $lang['modpaste']; ?>
					</div>
					
					<div class="panel-body">
						<form class="form-horizontal" name="mainForm" action="index.php" method="POST">
							<div class="form-group">
								<!-- Title -->
								<div class="col-sm-4 col-md-4 col-lg-4" style="padding-bottom:5px;">
									<div class="control-group">
									  <div class="controls">
									   <div class="input-prepend input-group">
										 <span class="add-on input-group-addon"><i class="fa fa-font"></i></span>
											<input type="text" class="form-control" name="title" placeholder="<?php echo $lang['pastetitle']; ?>" value="<?php echo ucfirst($p_title); ?>">
									   </div>
									  </div>
									</div>
								</div>
								  
								<!-- Format -->
								<div class="col-sm-4 col-md-4 col-lg-4" style="margin-top:-1px; padding-bottom:2px;">
									<select class="selectpicker" data-live-search="true" name="format">
										<?php // Show popular GeSHi formats
											foreach ($geshiformats as $code=>$name)
											{
												if (in_array($code, $popular_formats))
												{
												$sel=($p_code == $code)?'selected="selected"':' ';
												echo '<option ' . $sel . ' value="' . $code . '">' . $name . '</option>';
												}
											}

											echo '<option value="text">-------------------------------------</option>';

											// Show all GeSHi formats.
											foreach ($geshiformats as $code=>$name) {
                                                if ( !in_array( $code, $popular_formats ) ) {
                                                    $sel=($p_code == $code)?'selected="selected"':'';
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
									  <textarea class="form-control" rows="15" id="code" name="paste_data" onkeydown="return catchTab(this,event)" placeholder="helloworld"><?php echo $op_content; ?></textarea>
									</div>
								</div>

								<!-- Expiry -->
								<div class="form-group">
								  <label class="control-label form-label pull-left" style="padding-left: 20px;"><?php echo $lang['expiration']; ?></label>
									<div class="col-sm-8">
										<select class="selectpicker" style="display: none;" name="paste_expire_date">
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
						
								<!-- Visibility -->
								<div class="form-group">
								  <label class="control-label form-label pull-left" style="padding-left: 20px;"><?php echo $lang['visibility']; ?>&nbsp;&nbsp;</label>
									<div class="col-sm-8">
										<select class="selectpicker" style="display: none;" name="visibility">
											<option value="0" selected="selected">Public</option>
											<option value="1">Unlisted</option>
											<?php if (isset($_SESSION['token'])) {?>
											<option value="2">Private</option>
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
												<input type="text" class="form-control" name="pass" id="pass" value="" placeholder="<?php echo $lang['pwopt']; ?>">
										   </div>
										  </div>
										</div>
									</div>
								</div>
					
								<!-- Encrypt -->
								<div class="col-md-6">
									<div class="checkbox checkbox-primary">
										<input id="encrypt" name="encrypted" type="checkbox" checked="">
										<label for="encrypt">
											<?php echo $lang['encrypt']; ?>
										</label>
									</div>
								</div><br /><br />
					  
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
		</div>
	<?php } ?>