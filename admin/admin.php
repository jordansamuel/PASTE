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

// PHP <5.5 compatibility
require_once('../includes/password.php'); 

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


$query  = "SELECT * FROM admin";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $adminid  = Trim($row['user']);
    $password = Trim($row['pass']);
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Admin Settings</title>
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
					<li class="col-xs-3 col-sm-2 col-md-1">
					  <a href="interface.php"><i class="fa fa-eye"></i>Interface</a>
					</li>
					<li class="col-xs-3 col-sm-2 col-md-1 menu-active">
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
				$adminid  = htmlentities(Trim($_POST['adminid']));
				$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
				
				$query = "UPDATE admin SET user='$adminid', pass='$password' WHERE id='1'";
				mysqli_query($con, $query);
				
				if (mysqli_errno($con)) {
					$msg = '<div class="paste-alert alert6" style="text-align: center;">
				 ' . mysqli_error($con) . '
				 </div>';
					
				} else {
					$msg = '<div class="paste-alert alert3" style="text-align: center;">
					 Account details updated.
					 </div>';
				}
			}
			?>
			
			<!-- Start Admin Settings -->
			<div class="row">
				<div class="col-md-12">
				  <div class="panel panel-widget">
						<div class="panel-body">
							<div role="tabpanel">
								<!-- Nav tabs -->
								<ul class="nav nav-tabs nav-line" role="tablist" style="text-align: center;">
									<li role="presentation" class="active"><a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">Settings</a></li>
									<li role="presentation"><a href="#logs" aria-controls="logs" role="tab" data-toggle="tab">Login History</a></li>
								</ul>

								<!-- Tab panes -->
								<div class="tab-content">
									<div role="tabpanel" class="tab-pane active" id="settings">
										<div class="login-form" style="padding:0;">
											<?php if (isset($msg)) echo $msg; ?>
											<form action="<?php echo $_SERVER['PHP_SELF']; ?>" class="form-area" method="POST">
												<div class="form-area">
												  <div class="group">
													<input type="text" id="adminid" name="adminid" class="form-control" placeholder="Username" value="<?php echo $adminid; ?>">
													<i class="fa fa-user"></i>
												  </div>
												  <div class="group">
													<input type="password" id="password" name="password" class="form-control" placeholder="Password">
													<i class="fa fa-key"></i>
												  </div>
												  <button type="submit" class="btn btn-default btn-block">Save</button>
												</div>
											</form>
										</div>
									</div>

									<div role="tabpanel" class="tab-pane" id="logs">
										<table class="table">
											<tbody>
												<tr>
													<th>Login date</th>
													<th>IP</th>
												</tr>
												<?php
												$rec_limit = 10;
												$query     = "SELECT count(id) FROM admin_history";
												$retval    = mysqli_query($con, $query);

												$row       = mysqli_fetch_array($retval);
												$rec_count = Trim($row[0]);

												$sql      = "SELECT * FROM admin_history ORDER BY `id` DESC LIMIT $rec_limit";
												$result   = mysqli_query($con, $sql);

												// Loop through each record
												while ($row = mysqli_fetch_array($result)) {
													// Populate and display result data in each row
													echo '<tr>';
													echo '<td>' . $row['last_date'] . '</td>';
													echo '<td>' . $row['ip'] . '</td>';
												}
												echo '</tr>';
												?>
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>              			
					</div>
				</div>
			</div>
			<!-- End Admin Settings -->
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
  </body>
</html>