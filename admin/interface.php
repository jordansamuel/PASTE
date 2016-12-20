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

?>

<!DOCTYPE html>
<html lang="en">
  <head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Interface</title>
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
					<li class="col-xs-3 col-sm-2 col-md-1">
					  <a href="configuration.php"><i class="fa fa-cogs"></i>Configuration</a>
					</li>
					<li class="col-xs-3 col-sm-2 col-md-1 menu-active">
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
			
			<?php
			if ($_SERVER['REQUEST_METHOD'] == 'POST') {
				$d_lang  = Trim($_POST['lang']);
				$d_theme = Trim($_POST['theme']);
				
				$query = "UPDATE interface SET lang='$d_lang', theme='$d_theme' WHERE id='1'";
				mysqli_query($con, $query);
				
				if (mysqli_errno($con)) {
					$msg = '<div class="paste-alert alert6" style="text-align: center;">
				 ' . mysqli_error($con) . '
				 </div>';
					
				} else {
					$msg = '<div class="paste-alert alert3" style="text-align: center;">
					 Settings saved
					 </div>';
				}
			}
			?>

			<!-- Start Interface Settings -->
			<div class="row">
				<div class="col-md-12">
				  <div class="panel panel-widget">
						<div class="panel-body">
							<div class="login-form" style="padding:0;">
								<?php if (isset($msg)) echo $msg; ?>
								<form action="<?php echo $_SERVER['PHP_SELF']; ?>" class="form-area" method="post">
									<div class="form-area">
									  <div class="group">
											<h6>Language</h6>
											   <select class="selectpicker" name="lang">
												<?php
												$dir      = '../langs';
												$files1   = scandir($dir);
												$dircount = count($files1);
												for ($loop = 2; $loop <= $dircount - 1; $loop++) {
													$fname  = explode('.php', $files1[$loop]);
													$fname  = $fname[0];
													$ffname = $files1[$loop];
												if ($ffname == "index.php") {/* we don't want index.php showing */}
													else {
														echo '<option value="' . $ffname . '">' . $fname . '</option>';
													}
												}
												?>
												</select>
									  </div>
									  <div class="group">
											<h6>Theme</h6>
												<select class="selectpicker" name="theme">
												<?php
												// Find the current theme if not set from $_POST
												if ( !isset( $d_theme ) ) {
													$query = "SELECT theme FROM interface WHERE id='1'";
													$result = mysqli_query( $con, $query );
													while ( $row = mysqli_fetch_array( $result ) ) {
														$d_theme = $row['theme'];
													}
												}
												
												$dir    = '../theme';
												$files1 = scandir($dir);

												$dircount = count($files1);
												for ($loop = 2; $loop <= $dircount - 1; $loop++) {
													$fname  = explode('.php', $files1[$loop]);
													$fname  = $fname[0];
													$ffname = $files1[$loop];
													echo $dir . $ffname;
													if (is_dir($dir . '/' . $ffname)) {
														$sel=( $d_theme == $fname )?'selected="selected"':'';
														echo '<option value="' . $ffname . '" '.$sel.'>' . $fname . '</option>';
													}
												}
												?>
												</select>
									  </div>
									  <button type="submit" class="btn btn-default">Save</button>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- End Interface Settings -->
		
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