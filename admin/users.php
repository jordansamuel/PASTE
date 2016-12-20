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
    <title>Paste - Users</title>
	<link rel="shortcut icon" href="favicon.ico">
    <link href="css/paste.css" rel="stylesheet" type="text/css" />
	<link href="css/datatables.min.css" rel="stylesheet" type="text/css" />
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
					<li class="col-xs-3 col-sm-2 col-md-1">
					  <a href="admin.php"><i class="fa fa-user"></i>Admin Account</a>
					</li>
					<li class="col-xs-3 col-sm-2 col-md-1">
					  <a href="pastes.php"><i class="fa fa-clipboard"></i>Pastes</a>
					</li>
					<li class="col-xs-3 col-sm-2 col-md-1 menu-active">
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
			if (isset($_GET['delete'])) {
				$user_id = htmlentities(Trim($_GET['delete']));
				$query   = "DELETE FROM users WHERE id=$user_id";
				$result  = mysqli_query($con, $query);
				if (mysqli_errno($con)) {
					$msg = '<div class="paste-alert alert6" style="text-align: center;">
				 ' . mysqli_error($con) . '
				 </div>';
				} else {
					$msg = '<div class="paste-alert alert3" style="text-align: center;">
					 User deleted
					 </div>';
				}
			}

			if (isset($_GET['ban'])) {
				$ban_id = htmlentities(Trim($_GET['ban']));
				$query  = "UPDATE users SET verified='2' WHERE id='$ban_id'";
				$result = mysqli_query($con, $query);
				if (mysqli_errno($con)) {
					$msg = '<div class="paste-alert alert6" style="text-align: center;">
				 ' . mysqli_error($con) . '
				 </div>';
				} else {
					$msg = '<div class="paste-alert alert3" style="text-align: center;">
					 User banned
					 </div>';
				}
			}

			if (isset($_GET['unban'])) {
				$ban_id = htmlentities(Trim($_GET['unban']));
				$query  = "UPDATE users SET verified='1' WHERE id='$ban_id'";
				$result = mysqli_query($con, $query);
				if (mysqli_errno($con)) {
					$msg = '<div class="paste-alert alert6" style="text-align: center;">
				 ' . mysqli_error($con) . '
				 </div>';
				} else {
					$msg = '<div class="paste-alert alert3" style="text-align: center;">
					 User unbanned
					 </div>';
				}
			}
			?>
			
			<!-- Start Users -->
			<div class="row">
				<div class="col-md-12">
					<div class="panel panel-widget">
						<?php
						if (isset($_GET['details'])) {
							$detail_id = htmlentities(Trim($_GET['details']));
							$query     = "SELECT * FROM users WHERE id='$detail_id'";
							$result    = mysqli_query($con, $query);
							while ($row = mysqli_fetch_array($result)) {
								$user_oauth_uid = $row['oauth_uid'];
								$user_username  = $row['username'];
								$user_email_id  = $row['email_id'];
								$user_full_name = $row['full_name'];
								$user_platform  = Trim($row['platform']);
								$user_verified  = $row['verified'];
								$user_date      = $row['date'];
								$user_ip        = $row['ip'];
							}
							if ($user_oauth_uid == '0') {
								$user_oauth_uid = "None";
							}
							if ($user_verified == '0') {
								$user_verified = "Unverified";
							} elseif ($user_verified == '1') {
								$user_verified = "Verified";
							} elseif ($user_verified == '2') {
								$user_verified = "Banned";
							}
						?>
						<div class="panel-body">
							<div class="panel-title">
							<?php echo $user_username . ' Details'; ?>
							</div>

						  <table class="table table-striped table-bordered">
							<tbody>
							  <tr>
								<td>  Username </td>
								<td> <?php echo $user_username; ?> </td>   
							  </tr>
							  
							  <tr>
								<td> Email ID </td>
								<td> <?php echo $user_email_id;?> </td>
							  </tr>
							  
							  <tr>
								<td> Platform </td>
								 <td> <?php echo $user_platform; ?> </td>
							  </tr>
							  
							  <tr>
								<td> OAUTH ID </td>
								<td> <?php echo $user_oauth_uid; ?> </td>
							  </tr>
										
							  <tr>
								<td> Status </td>
								 <td> <?php echo $user_verified; ?> </td>
							  </tr>
							  
							  <tr>
								<td> User IP </td>
								 <td> <?php echo $user_ip; ?> </td>
							  </tr>
							  
							  <tr>
								<td> Date Registered </td>
								 <td> <?php echo $user_date; ?> </td>
							  </tr>
							  
							  <tr>
								<td> Full Name </td>
								 <td> <?php echo $user_full_name; ?> </td>
							  </tr>
							</tbody>
						  </table>
						</div>
						<?php } else { ?>
						
						<div class="panel-body">
							<div class="panel-title">
							Manage Users
							</div>

							<?php if (isset($msg)) echo $msg; ?>
							
							<table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="usersTable">
								<thead>
									<tr>
										  <th>Username</th>
										  <th>Email ID</th>
										  <th>Date Registered</th>
										  <th>Platform</th>
										  <th>OAUTH ID</th>
										  <th>Ban User</th>
										  <th>Profile</th>
										  <th>Delete</th>
									</tr>
								</thead>         
								<tbody>
															
								</tbody>
							</table>
						</div>
						<?php } ?>
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
	<script type="text/javascript" language="javascript" src="js/jquery.dataTables.js"></script>
	<script type="text/javascript" language="javascript" class="init">
		$(document).ready(function() {
			$('#usersTable').dataTable( {
				"processing": true,
				"serverSide": true,
				"ajax": "ajax_users.php"
			} );
		} );
		</script>
	<script type="text/javascript" src="js/bootstrap.min.js"></script>
  </body>
</html>