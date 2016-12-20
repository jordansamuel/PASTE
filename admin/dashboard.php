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

$today_users_count  = 0;
$today_pastes_count = 0;

$date = date('jS F Y');
$ip   = $_SERVER['REMOTE_ADDR'];
require_once('../config.php');
require_once('../includes/functions.php');
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

$query  = "SELECT * FROM page_view";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $total_page  = isset($total_page) + Trim($row['tpage']);
    $total_visit = isset($total_visit) + Trim($row['tvisit']);
}

$query = "SELECT @last_id := MAX(id) FROM page_view";

$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $page_last_id = $row['@last_id := MAX(id)'];
}

$query  = "SELECT * FROM page_view WHERE id=" . Trim($page_last_id);
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $today_page  = $row['tpage'];
    $today_visit = $row['tvisit'];
}

$query  = "SELECT * FROM site_info";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $admin_email = Trim($row['email']);
}

$c_date = date('jS F Y');
$query  = "SELECT * FROM users where date='$c_date'";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $today_users_count = $today_users_count + 1;
}

$query  = "SELECT * FROM pastes where s_date='$c_date'";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $today_pastes_count = $today_pastes_count + 1;
}
for ($loop = 0; $loop <= 6; $loop++) {
    $myid   = $page_last_id - $loop;
    $query  = "SELECT * FROM page_view WHERE id='$myid'";
    $result = mysqli_query($con, $query);
    
    while ($row = mysqli_fetch_array($result)) {
        $sdate = $row['date'];
        $sdate = str_replace(date('Y'), '', $sdate);
        $sdate = str_replace('January', 'Jan', $sdate);
        $sdate = str_replace('February', 'Feb', $sdate);
        $sdate = str_replace('March', 'Mar', $sdate);
        $sdate = str_replace('April', 'Apr', $sdate);
        $sdate = str_replace('August', 'Aug', $sdate);
        $sdate = str_replace('September', 'Sep', $sdate);
        $sdate = str_replace('October', 'Oct', $sdate);
        $sdate = str_replace('November', 'Nov', $sdate);
        $sdate = str_replace('December', 'Dec', $sdate);
        
        $ldate[$loop]  = $sdate;
        $tpage[$loop]  = $row['tpage'];
        $tvisit[$loop] = $row['tvisit'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Dashboard</title>
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
					<li class="col-xs-3 col-sm-2 col-md-1 menu-active">
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
			
			<!-- Start Stats -->
			<div class="row">
				<div class="col-md-12">
				  <ul class="panel topstats clearfix">
					<li class="col-xs-6 col-lg-3">
					  <span class="title"><i class="fa fa-eye"></i> Views</span>
					  <h3><?php echo $today_page; ?></h3>
					  <span class="diff">Today</span>
					</li>
					<li class="col-xs-6 col-lg-3">
					  <span class="title"><i class="fa fa-clipboard"></i> Pastes</span>
					  <h3><?php echo $today_pastes_count; ?></h3>
					  <span class="diff">Today</span>
					</li>
					<li class="col-xs-6 col-lg-3">
					  <span class="title"><i class="fa fa-users"></i> Users</span>
					  <h3><?php echo $today_users_count; ?></h3>
					  <span class="diff">Today</span>
					</li>
					<li class="col-xs-6 col-lg-3">
					  <span class="title"><i class="fa fa-users"></i> Unique Views</span>
					  <h3><?php echo $today_visit; ?></h3>
					  <span class="diff">Today</span>
					</li>
				  </ul>
				</div>
			</div>
			<!-- End Stats -->
		  
			<div class="row">
				<!-- Start Recent -->
				<div class="col-md-12 col-lg-6">
				  <div class="panel panel-widget">
					<div class="panel-title">
					  Recent Pastes
					</div>

					<div class="panel-body table-responsive">

					  <table class="table table-hover">
						<thead>
						  <tr>
							<td>ID</td>
							<td>Username</td>
							<td>Date</td>
							<td>IP</td>
							<td>Views</td>
						  </tr>
						</thead>
						<tbody>
						<?php
						$res = getRecent($con, 7);
						while ($row = mysqli_fetch_array($res)) {
							$title    = Trim($row['title']);
							$p_id     = Trim($row['id']);
							$p_date   = Trim($row['s_date']);
							$p_ip     = Trim($row['ip']);
							$p_member = Trim($row['member']);
							$p_view   = Trim($row['views']);
							$p_time   = Trim($row['now_time']);
							$nowtime  = time();
							$oldtime  = $p_time;
							$p_time   = conTime($nowtime - $oldtime);
							$title    = truncate($title, 5, 30);
							echo "
										  <tr>
											<td>$p_id</td>
											<td>$p_member</td>
											<td>$p_date</td>
											<td><span class='label label-default'>$p_ip</span></td>
											<td>$p_view</td>
										  </tr> ";
						}
						?>
						</tbody>
					  </table>

					</div>
				  </div>
				</div>
				<!-- End Recent -->

				<!-- Start Recent Users -->
				<div class="col-md-12 col-lg-6">
				  <div class="panel panel-widget">
					<div class="panel-title">
					  Recent Users
					</div>

					<div class="panel-body table-responsive">

					  <table class="table table-hover">
						<thead>
						  <tr>
							<td>ID</td>
							<td>Username</td>
							<td>Date</td>
							<td>IP</td>
						  </tr>
						</thead>
						<tbody>
						<?php
						$query = "SELECT @last_id := MAX(id) FROM users";
						$result = mysqli_query($con, $query);

						while ($row = mysqli_fetch_array($result)) {
							$last_id = $row['@last_id := MAX(id)'];
						}

						for ($uloop = 0; $uloop <= 6; $uloop++) {
							$r_my_id = $last_id - $uloop;
							$query   = "SELECT * FROM users WHERE id='$r_my_id'";
							$result  = mysqli_query($con, $query);
							
							while ($row = mysqli_fetch_array($result)) {
								$u_date   = $row['date'];
								$ip       = $row['ip'];
								$username = $row['username'];
							}
							echo "
										  <tr>
											<td>$r_my_id</td>
											<td>$username</td>
											<td>$u_date</td>
											<td><span class='label label-default'>$ip</span></td>
										  </tr> ";
						}

						?>
						</tbody>
					  </table>

					</div>
				  </div>
				</div>
			<!-- End Recent Users -->
			</div>
			
			<div class="row">
				<!-- Start Admin History -->
				<div class="col-md-12 col-lg-6">
				  <div class="panel panel-widget">
					<div class="panel-title">
					  Admin History
					</div>

					<div class="panel-body table-responsive">

					  <table class="table table-hover">
						<thead>
						  <tr>
							<td>ID</td>
							<td>Last Login Date</td>
							<td>IP</td>
						  </tr>
						</thead>
						<tbody>
						<?php
						$query = "SELECT @last_id := MAX(id) admin_history";
						$result = mysqli_query($con, $query);

						for ($cloop = 0; $cloop <= 6; $cloop++) {
							$c_my_id = $last_id - $cloop;
							$query   = "SELECT * FROM admin_history WHERE id='$c_my_id'";
							$result  = mysqli_query($con, $query);
							
							while ($row = mysqli_fetch_array($result)) {
								$last_date = $row['last_date'];
								$ip        = $row['ip'];
							}
							echo "
										  <tr>
											<td>$c_my_id</td>
											<td>$last_date</td>
											<td><span class='label label-default'>$ip</span></td>
										  </tr> ";
						}

						?>
						</tbody>
					  </table>

					</div>
				  </div>
				</div>
				<!-- End Admin History -->
	  
				<div class="col-md-12 col-lg-6">
				  <div class="panel panel-widget">
					<div class="panel-title">
					</div>
					<p style="height: auto;">
					<?php
					$latestversion = file_get_contents('https://raw.githubusercontent.com/jordansamuel/PASTE/releases/version');
					echo "Latest version: " . $latestversion . "&mdash; Installed version: " . $currentversion;
					if ($currentversion == $latestversion) { echo '<br />You have the latest version'; } else { echo '<br />Your Paste installation is outdated. Get the latest version from <a href="https://sourceforge.net/projects/phpaste/files/latest/download">SourceForge</a>'; }
					?>
					
					</p>
				  </div>
				</div>
			</div>
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