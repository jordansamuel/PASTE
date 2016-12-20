<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE>
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
session_start();

if (isset($_SESSION['login'])) {
// Do nothing	
} else {
    header("Location: .");
    exit();
}

if (isset($_GET['logout'])) {
    if (isset($_SESSION['login']))
        unset($_SESSION['login']);
    
    session_destroy();
    header("Location: .");
    exit();
}

$date = date('jS F Y');
$ip   = $_SERVER['REMOTE_ADDR'];
require_once('../config.php');
$con = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);

if (mysqli_connect_errno()) {
    $sql_error = mysqli_connect_error();
    die("Unable connect to database");
}

$query = "SELECT @last_id := MAX(id) FROM admin_history";

$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $last_id = $row['@last_id := MAX(id)'];
}

$query  = "SELECT * FROM admin_history WHERE id=" . Trim($last_id);
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $last_date = $row['last_date'];
    $last_ip   = $row['ip'];
}

if ($last_ip == $ip) {
    if ($last_date == $date) {
        
    } else {
        $query = "INSERT INTO admin_history (last_date,ip) VALUES ('$date','$ip')";
        mysqli_query($con, $query);
    }
} else {
    $query = "INSERT INTO admin_history (last_date,ip) VALUES ('$date','$ip')";
    mysqli_query($con, $query);
}


$query  = "SELECT * FROM site_info";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $title				= Trim($row['title']);
    $des				= Trim($row['des']);
    $baseurl		    = Trim($row['baseurl']);
    $keyword			= Trim($row['keyword']);
    $site_name			= Trim($row['site_name']);
    $email				= Trim($row['email']);
    $twit				= Trim($row['twit']);
    $face				= Trim($row['face']);
    $gplus				= Trim($row['gplus']);
    $ga					= Trim($row['ga']);
    $additional_scripts = Trim($row['additional_scripts']);
}
$query  = "SELECT * FROM captcha WHERE id='1'";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $cap_e					= $row['cap_e'];
    $mode					= $row['mode'];
    $mul					= $row['mul'];
    $allowed				= $row['allowed'];
    $color					= $row['color'];    
    $recaptcha_sitekey		= $row['recaptcha_sitekey'];
    $recaptcha_secretkey	= $row['recaptcha_secretkey'];
}

$query  = "SELECT * FROM site_permissions WHERE id='1'";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $disableguest   = Trim($row['disableguest']);
    $siteprivate	= Trim($row['siteprivate']);
}

$query  = "SELECT * FROM mail WHERE id='1'";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
	$verification	= Trim($row['verification']);
    $smtp_host		= Trim($row['smtp_host']);
    $smtp_username	= Trim($row['smtp_username']);
    $smtp_password	= Trim($row['smtp_password']);
    $smtp_port		= Trim($row['smtp_port']);
    $protocol		= Trim($row['protocol']);
    $auth			= Trim($row['auth']);
    $socket			= Trim($row['socket']);
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Configuration</title>
	<link rel="shortcut icon" href="favicon.ico">
    <link href="css/paste.css" rel="stylesheet" type="text/css" />
  </head>
  <body>
  
	<div id="top" class="clearfix">
		<!-- Start App Logo -->
		<div class="applogo">
		  <a href="../" class="logo">Paste</a>
		</div>
		<!-- End App Logo -->

		<!-- Start Top Right -->
		<ul class="top-right">
			<li class="dropdown link">
				<a href="#" data-toggle="dropdown" class="dropdown-toggle profilebox"><b>Admin</b><span class="caret"></span></a>
				<ul class="dropdown-menu dropdown-menu-list dropdown-menu-right">
				  <li><a href="admin.php">Settings</a></li>
				  <li><a href="?logout">Logout</a></li>
				</ul>
			</li>
		</ul>
		<!-- End Top Right -->
	</div>
	<!-- END TOP -->	

	<div class="content">
		  <!-- START CONTAINER -->
		<div class="container-widget">
			<!-- Start Menu -->
			<div class="row">
				<div class="col-md-12">
				  <ul class="panel quick-menu clearfix">
					<li class="col-xs-3 col-sm-2 col-md-1">
					  <a href="dashboard.php"><i class="fa fa-home"></i>Dashboard</a>
					</li>
					<li class="col-xs-3 col-sm-2 col-md-1 menu-active">
					  <a href="configuration.php"><i class="fa fa-cogs"></i>Configuration</a>
					</li>
					<li class="col-xs-3 col-sm-2 col-md-1">
					  <a href="interface.php"><i class="fa fa-eye"></i>Interface</a>
					</li>
					<li class="col-xs-3 col-sm-2 col-md-1">
					  <a href="admin.php"><i class="fa fa-user"></i>Admin Account</a>
					</li>
					<li class="col-xs-3 col-sm-2 col-md-1">
					  <a href="pastes.php"><i class="fa fa-clipboard"></i>Pastes</a>
					</li>
					<li class="col-xs-3 col-sm-2 col-md-1">
					  <a href="users.php"><i class="fa fa-users"></i>Users</a>
					</li>
					<li class="col-xs-3 col-sm-2 col-md-1">
					  <a href="ipbans.php"><i class="fa fa-ban"></i>IP Bans</a>
					</li>
					<li class="col-xs-3 col-sm-2 col-md-1">
					  <a href="stats.php"><i class="fa fa-line-chart"></i>Statistics</a>
					</li>
					<li class="col-xs-3 col-sm-2 col-md-1">
					  <a href="ads.php"><i class="fa fa-gbp"></i>Ads</a>
					</li>
					<li class="col-xs-3 col-sm-2 col-md-1">
					  <a href="pages.php"><i class="fa fa-file"></i>Pages</a>
					</li>
					<li class="col-xs-3 col-sm-2 col-md-1">
					  <a href="sitemap.php"><i class="fa fa-map-signs"></i>Sitemap</a>
					</li>
					<li class="col-xs-3 col-sm-2 col-md-1">
					  <a href="tasks.php"><i class="fa fa-tasks"></i>Tasks</a>
					</li>
				  </ul>
				</div>
			</div>
			<!-- End Menu -->
    
			<!-- Start Configuration Panel -->
			<div class="row">
				<div class="col-md-12">
				  <div class="panel panel-widget">
						<div class="panel-body">
						<?php
						if ($_SERVER['REQUEST_METHOD'] == 'POST') {
							if (isset($_POST['manage'])) {
								$site_name	= mysqli_real_escape_string( $con, Trim($_POST['site_name']) );
								$title		= mysqli_real_escape_string( $con, Trim($_POST['title']) );
								$baseurl	= mysqli_real_escape_string( $con, Trim($_POST['baseurl']) );
								$des		= mysqli_real_escape_string( $con, Trim($_POST['des']) );
								$keyword	= htmlentities(Trim($_POST['keyword']));
								$email		= mysqli_real_escape_string( $con, Trim($_POST['email']) );
								$twit		= htmlentities(Trim($_POST['twit']));
								$face		= htmlentities(Trim($_POST['face']));
								$gplus		= htmlentities(Trim($_POST['gplus']));
								$ga			= htmlentities(Trim($_POST['ga']));
								$additional_scripts        = mysqli_real_escape_string( $con, $_POST['additional_scripts'] );
                                
								$query = "UPDATE site_info SET title='$title', des='$des', baseurl='$baseurl', keyword='$keyword', site_name='$site_name', email='$email', twit='$twit', face='$face', gplus='$gplus', ga='$ga', additional_scripts='$additional_scripts' WHERE id='1'";
								mysqli_query($con, $query);
								
								if (mysqli_errno($con)) {
									$msg = '<div class="paste-alert alert6" style="text-align: center;">
											' . mysqli_error($con) . '
											</div>';
								} else {
									$msg = '<div class="paste-alert alert3" style="text-align: center;">
											Configuration saved
											</div>';
								}
							}
							if (isset($_POST['cap'])) {
								$cap_e   = Trim($_POST['cap_e']);
								$mode    = Trim($_POST['mode']);
								$mul     = Trim($_POST['mul']);
								$allowed = Trim($_POST['allowed']);
								$color   = Trim($_POST['color']);
                                $recaptcha_sitekey = Trim($_POST['recaptcha_sitekey']);
								$recaptcha_secretkey   = Trim($_POST['recaptcha_secretkey']);
								
								$query = "UPDATE captcha SET cap_e='$cap_e', mode='$mode', mul='$mul', allowed='$allowed', color='$color', recaptcha_sitekey='$recaptcha_sitekey', recaptcha_secretkey='$recaptcha_secretkey' WHERE id='1'";
								mysqli_query($con, $query);
								
								if (mysqli_errno($con)) {
									$msg = '<div class="paste-alert alert6" style="text-align: center;">
											' . mysqli_error($con) . '
											</div>';
								} else {
									$msg = '<div class="paste-alert alert3" style="text-align: center;">
									Captcha settings saved
									</div>';
								}
							}
							
							if (isset($_POST['permissions'])) {
								$disableguest = Trim($_POST['disableguest']);
								$siteprivate  = Trim($_POST['siteprivate']);
								
								$query = "UPDATE site_permissions SET disableguest='$disableguest', siteprivate='$siteprivate' WHERE id='1'";
								mysqli_query($con, $query);
								
								if (mysqli_errno($con)) {
									$msg = '<div class="paste-alert alert6" style="text-align: center;">
											' . mysqli_error($con) . '
											</div>';
								} else {
									$msg = '<div class="paste-alert alert3" style="text-align: center;">
									Site permissions saved.
									</div>';
								}
							}

						}

						if (isset($_POST['smtp_code'])) {
							$verification	= Trim($_POST['verification']);
							$smtp_host		= Trim($_POST['smtp_host']);
							$smtp_port		= Trim($_POST['smtp_port']);
							$smtp_username	= Trim($_POST['smtp_user']);
							$smtp_password	= Trim($_POST['smtp_pass']);
							$socket			= Trim($_POST['socket']);
							$auth			= Trim($_POST['auth']);
							$protocol		= Trim($_POST['protocol']);
							
							$query = "UPDATE mail SET verification='$verification', smtp_host='$smtp_host', smtp_port='$smtp_port', smtp_username='$smtp_username', smtp_password='$smtp_password', socket='$socket', protocol='$protocol', auth='$auth' WHERE id='1'";
							mysqli_query($con, $query);
							
							if (mysqli_errno($con)) {
								$msg = '<div class="paste-alert alert6" style="text-align: center;">
										' . mysqli_error($con) . '
										</div>';
								
							} else {
									$msg = '
							<div class="paste-alert alert3" style="text-align: center;">
							Mail settings updated
							</div>';
							}
						}
						if (isset($msg)) echo $msg;
						?>
						
							<div role="tabpanel">
							  <!-- Nav tabs -->
							  <ul class="nav nav-tabs nav-line" role="tablist" style="text-align: center;">
								<li role="presentation" class="active"><a href="#siteinfo" aria-controls="siteinfo" role="tab" data-toggle="tab">Site Info</a></li>
								<li role="presentation"><a href="#permissions" aria-controls="permissions" role="tab" data-toggle="tab">Permissions</a></li>
								<li role="presentation"><a href="#captcha" aria-controls="captcha" role="tab" data-toggle="tab">Captcha Settings</a></li>
								<li role="presentation"><a href="#mail" aria-controls="mail" role="tab" data-toggle="tab">Mail Settings</a></li>
							  </ul>

							  <!-- Tab panes -->		
								<div class="tab-content">
									<div role="tabpanel" class="tab-pane active" id="siteinfo">
										<form class="form-horizontal" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
										
											<div class="form-group">
											  <label class="col-sm-2 control-label form-label">Site Name</label>
											  <div class="col-sm-10">
												<input type="text" class="form-control" name="site_name" placeholder="The name of your site" value="<?php echo (isset($_POST['site_name']))?$_POST['site_name']:$site_name; // Prevent special characters on $_POST ?>">
											  </div>
											</div>

											<div class="form-group">
											  <label class="col-sm-2 control-label form-label">Site Title</label>
											  <div class="col-sm-10">
												<input type="text" class="form-control" name="title" placeholder="Site title tag" value="<?php echo (isset($_POST['title']))?$_POST['title']:$title; // Prevent special characters on $_POST ?>">
											  </div>
											</div>

											<div class="form-group">
											  <label class="col-sm-2 control-label form-label">Domain name</label>
												<div class="col-sm-1" style="padding:5px;">
												<span class="badge">
												<?php if ($_SERVER['HTTPS'] == "on") {
													echo "https://";
												} else {
													echo "http://";
												}?>
												</span>
												</div>
												<div class="col-sm-5">
												<input type="text" class="form-control" name="baseurl" placeholder="eg: pastethis.in (no trailing slash)" value="<?php echo (isset($_POST['baseurl']))?$_POST['baseurl']:$baseurl; // Prevent special characters on $_POST ?>">
											  </div>
											</div>

											<div class="form-group">
											  <label class="col-sm-2 control-label form-label">Site Description</label>
											  <div class="col-sm-10">
												<input type="text" class="form-control" name="des" placeholder="Site description" value="<?php echo (isset($_POST['des']))?$_POST['des']:$des; // Prevent special characters on $_POST ?>">
											  </div>
											</div>

											<div class="form-group">
											  <label class="col-sm-2 control-label form-label">Site Keywords</label>
											  <div class="col-sm-10">
												<input type="text" class="form-control" name="keyword" placeholder="Keywords (separated by a comma)" value="<?php echo $keyword; ?>">
											  </div>
											</div>
											
											<div class="form-group">
											  <label class="col-sm-2 control-label form-label">Google Analytics</label>
											  <div class="col-sm-10">
												<input type="text" class="form-control" name="ga" placeholder="Google Analytics ID" value="<?php echo $ga; ?>">
											  </div>
											</div>

											<div class="form-group">
											  <label class="col-sm-2 control-label form-label">Admin Email</label>
											  <div class="col-sm-10">
												<input type="text" class="form-control" name="email" placeholder="Email" value="<?php echo (isset($_POST['email']))?$_POST['email']:$email; // Prevent special characters on $_POST ?>">
											  </div>
											</div>
											
											<div class="form-group">
											  <label class="col-sm-2 control-label form-label">Facebook URL</label>
											  <div class="col-sm-10">
												<input type="text" class="form-control" name="face" placeholder="Facebook URL" value="<?php echo $face; ?>">
											  </div>
											</div>
											
											<div class="form-group">
											  <label class="col-sm-2 control-label form-label">Twitter URL</label>
											  <div class="col-sm-10">
												<input type="text" class="form-control" name="twit" placeholder="Twitter URL" value="<?php echo $twit; ?>">
											  </div>
											</div>
											
											<div class="form-group">
											  <label class="col-sm-2 control-label form-label">Google+ URL</label>
											  <div class="col-sm-10">
												<input type="text" class="form-control" name="gplus" placeholder="Google+ URL" value="<?php echo $gplus; ?>">
											  </div>
											</div>
											
											<div class="form-group">
											  <label class="col-sm-2 control-label form-label">Additional Site Scripts</label>
											  <div class="col-sm-10">
												<textarea class="form-control" id="additional_scripts" name="additional_scripts" rows="8"><?php echo (isset($_POST['additional_scripts']))?$_POST['additional_scripts']:$additional_scripts; // Prevent special characters on $_POST ?></textarea>
											  </div>
											</div>
											
											<input type="hidden" name="manage" value="manage" />

											<div class="form-group">
											  <div class="col-sm-offset-2 col-sm-10">
												<button type="submit" class="btn btn-default">Save</button>
											  </div>
											</div>
										</form>
									</div>
									
									<!-- Permissions -->
									
									<div role="tabpanel" class="tab-pane" id="permissions">
										<form class="form-horizontal" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
										
											<div class="checkbox checkbox-primary">
												<input <?php if ($disableguest == "on") echo 'checked="true"'; ?> type="checkbox" name="disableguest" id="disableguest">
												<label for="disableguest">
													Only allow registered users to paste
												</label>
											</div>
											
											<div class="checkbox checkbox-primary">
												<input <?php if ($siteprivate == "on") echo 'checked="true"'; ?> type="checkbox" name="siteprivate" id="siteprivate">
												<label for="siteprivate">
													Make site private (no Recent Pastes or Archives)
												</label>
											</div>
											
											<br />
																						
											<input type="hidden" name="permissions" value="permissions" />

											<div class="form-group">
											  <div class="col-sm-offset-2 col-sm-10">
												<button type="submit" class="btn btn-default">Save</button>
											  </div>
											</div>
										</form>
									</div>
								
									<!-- Captcha pane -->
									
									<div role="tabpanel" class="tab-pane" id="captcha">
										<form class="form-horizontal" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">

											<div class="checkbox checkbox-primary">
												<input <?php if ( $cap_e == "on" ) echo 'checked="true"'; ?> type="checkbox" name="cap_e" id="cap_e">
												<label for="cap_e">Enable Captcha</label>
											</div>
                                            <br />

                                            <div class="form-group row">
                                                <label for="mode" class="col-sm-1 col-form-label">Captcha Type</label>
                                                <select class="selectpicker" name="mode">
                                                    <?php
                                                    if ( $mode == "reCAPTCHA" ) {
                                                        echo '<option selected="">reCAPTCHA</option>';
                                                    } else {
                                                        echo '<option>reCAPTCHA</option>';
                                                    }
                                                    if ( $mode == "Easy" ) {
                                                        echo '<option selected="">Easy</option>';
                                                    } else {
                                                        echo '<option>Easy</option>';
                                                    }
                                                    if ( $mode == "Normal" ) {
                                                        echo '<option selected="">Normal</option>';
                                                    } else {
                                                        echo '<option>Normal</option>';
                                                    }
                                                    if ( $mode == "Tough" ) {
                                                        echo '<option selected="">Tough</option>';
                                                    } else {
                                                        echo '<option>Tough</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            
											<hr />
											<div class="panel-title">
                                                Internal Captcha Settings:
                                            </div>
											<div class="checkbox checkbox-primary">
												<input <?php if ( $mul == "on" ) echo 'checked="true"'; ?> type="checkbox" name="mul" id="mul">
												<label for="mul">Enable multiple backgrounds</label>
											</div>
                                            <br />
                                            <div class="form-group row">
												<label for="allowed" class="col-sm-1 col-form-label">Captcha Characters</label>
                                                <div class="col-sm-10">
                                                    <input type="text" id="allowed" name="allowed" placeholder="Allowed Characters" value="<?php echo $allowed; ?>">
                                                </div>
											</div>
                                            
                                            <div class="form-group row">
												<label for="color" class="col-sm-1 col-form-label">Captcha Text Colour</label>
                                                <div class="col-sm-10">
                                                    <input type="text" id="color" name="color" placeholder="Captcha Text Colour" value="<?php echo $color; ?>">
                                                </div>
											</div>
                                            
                                            <hr />
                                            <div class="panel-title">
                                                reCAPTCHA Settings:
                                            </div>
											<div class="form-group row">
												<label for="recaptcha_sitekey" class="col-sm-1 col-form-label">Site Key</label>
                                                <div class="col-sm-10">
                                                    <input type="text" id="recaptcha_sitekey" name="recaptcha_sitekey" placeholder="Site Key" value="<?php echo $recaptcha_sitekey; ?>">
                                                </div>
											</div>
											
                                            <div class="form-group row">
												<label for="recaptcha_secretkey" class="col-sm-1 col-form-label">Secret Key</label>
                                                <div class="col-sm-10">
                                                    <input type="text" id="recaptcha_secretkey" name="recaptcha_secretkey" placeholder="Site Key" value="<?php echo $recaptcha_secretkey; ?>">
                                                </div>
											</div>
                                          
											<input type="hidden" name="cap" value="cap" />
											
											<div class="form-group">
											  <div class="col-sm-offset-2 col-sm-10">
												<button type="submit" class="btn btn-default">Save</button>
											  </div>
											</div>
										</form>
									</div>
								
									<!-- Mail Settings -->
								
									<div role="tabpanel" class="tab-pane" id="mail">
										<form class="form-horizontal" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
										
											<div class="form-group">
												<div class="panel-title">
														  Registration Settings
												</div>
												<label class="col-sm-2 control-label form-label">Email Verification</label>
													<select class="selectpicker" name="verification">
													<?php
													if ($verification == 'enabled') {
														echo '<option selected value="enabled">Enabled</option>';
														echo '<option value="disabled">Disabled</option>';
													} else {
														echo '<option value="enabled">Enabled</option>';
														echo '<option selected value="disabled">Disabled</option>';
													}
													?>
													</select> 
											</div>
											
											<div class="form-group">
												<div class="panel-title">
														  Mail Settings
												</div>
												<label class="col-sm-2 control-label form-label">Mail Protocol</label>
													<select class="selectpicker" name="protocol">
													<?php
													if ($protocol == '1') {
														echo '<option selected value="1">PHP Mail</option>';
														echo '<option value="2">SMTP</option>';
													} else {
														echo '<option value="1">PHP Mail</option>';
														echo '<option selected value="2">SMTP</option>';
													}
													?>
													</select>  
											</div>
											
											<div class="form-group">
												<label class="col-sm-2 control-label form-label">SMTP Auth</label>
													<select class="selectpicker" name="auth"> 
														<?php
														if ($auth == 'true') {
															echo '<option selected value="true">True</option>
																  <option value="false">False</option>';
														} else {
															echo '<option value="true">True</option>
															<option selected value="false">False</option>';
														}
														?>
													</select>
											</div>
											
											<div class="form-group">											
												<label class="col-sm-2 control-label form-label">SMTP Protocol</label>
													<select class="selectpicker" name="socket"> 
														<?php
															if ($socket == 'tls') {
															echo '   
														   <option selected value="tls">TLS</option>
														   <option value="ssl">SSL</option>';
															} else {
															echo '   
														   <option value="tls">TLS</option>
														   <option selected value="ssl">SSL</option>';
															}
														?>
													</select>
											</div>

											<div class="form-group">
											  <label class="col-sm-2 control-label form-label">SMTP Host</label>
											  <div class="col-sm-10">
												<input type="text" class="form-control" placeholder="eg smtp.gmail.com" name="smtp_host" value="<?php echo $smtp_host; ?>">
											  </div>
											</div>
											
											<div class="form-group">
											  <label class="col-sm-2 control-label form-label">SMTP Port</label>
											  <div class="col-sm-10">
												<input type="text" class="form-control" name="smtp_port" placeholder="eg 465 for SSL or 587 for TLS" value="<?php echo $smtp_port; ?>">
											  </div>
											</div>

											<div class="form-group">
											  <label class="col-sm-2 control-label form-label">SMTP User</label>
											  <div class="col-sm-10">
												<input type="text" class="form-control" name="smtp_user" placeholder="eg user@gmail.com" value="<?php echo $smtp_username; ?>">
											  </div>
											</div>
											
											<div class="form-group">
											  <label class="col-sm-2 control-label form-label">SMTP Password</label>
											  <div class="col-sm-10">
												<input type="text" class="form-control" name="smtp_pass" placeholder="eg gmail password" value="<?php echo $smtp_password; ?>">
											  </div>
											</div>
											
											<input type="hidden" name="smtp_code" value="smtp">
											
											<div class="form-group">
											  <div class="col-sm-offset-2 col-sm-10">
												<button type="submit" class="btn btn-default">Save</button>
											  </div>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- End Configuration Panel -->
		</div>
		<!-- END CONTAINER -->

		<!-- Start Footer -->
		<div class="row footer">
		  <div class="col-md-6 text-left">
		   <a href="https://github.com/jordansamuel/PASTE" target="_blank">Updates</a> &mdash; <a href="https://github.com/jordansamuel/PASTE/issues" target="_blank">Bugs</a>
		  </div>
		  <div class="col-md-6 text-right">
			Powered by <a href="https://phpaste.sourceforge.io" target="_blank">Paste</a>
		  </div> 
		</div>
		<!-- End Footer -->
	</div>
	<!-- End content -->

	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/bootstrap-select.js"></script>
  </body>
</html>
