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

$query  = "Select * From sitemap_options WHERE id='1'";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_array($result)) {
    $priority   = $row['priority'];
    $changefreq = $row['changefreq'];
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Sitemap</title>
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
					<li class="col-xs-3 col-sm-2 col-md-1 menu-active">
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
				$priority   = htmlentities(Trim($_POST['priority']));
				$changefreq = htmlentities(Trim($_POST['changefreq']));
				
				$query = "UPDATE sitemap_options SET priority='$priority', changefreq='$changefreq' WHERE id='1'";
				mysqli_query($con, $query);
				
				if (mysqli_errno($con)) {
					echo '<div class="paste-alert alert6">
							' . mysqli_error($con) . '
						</div>';
				} else {
					echo '
					<div class="paste-alert alert3">
						Sitemap saved.
					</div>';
				}
			}
			?> 
	
			<!-- Start Sitemap -->
			<div class="row">
				<div class="col-md-12">
					<div class="panel panel-widget">
						<div class="panel-body">
						 <div class="panel-title">Sitemap</a></div>
						<?php if (isset($msg)) echo $msg; ?>
							<form method="POST" action="sitemap.php">
									<div class="form-group">
										<label for="changefreq">Change Frequency</label>
										<input type="text" placeholder="Enter frequency range" name="changefreq" id="changefreq" value="<?php echo $changefreq; ?>" class="form-control">
									</div>
									<div class="form-group">
										<label for="priority">Priority Level</label>
										<input type="text" placeholder="Enter priority..." id="priority" name="priority" value="<?php echo $priority; ?>" class="form-control">
									</div>
				  
										 <button class="btn btn-default" type="submit">Submit</button>
							</form>
							<br />
							<?php
							if (isset($_GET['re'])) {
								unlink('../sitemap.xml');
								// which protocol are we on
								$protocol = ($_SERVER['HTTPS'] == "on")?'https://':'http://';
								// level up, dirty but meh
								$x=2;$path = dirname($_SERVER['PHP_SELF']); while(max(0, --$x)) { $levelup = dirname($path); }
								$c_date = date('Y-m-d');
								$data   = '<?xml version="1.0" encoding="UTF-8"?>
									<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
							<url>
									<loc>' . $protocol . $_SERVER['SERVER_NAME'] . $levelup . '/</loc>
									<priority>1.0</priority>
									<changefreq>daily</changefreq>
									<lastmod>' . $c_date . '</lastmod>
							</url>
							</urlset>';
								file_put_contents("../sitemap.xml", $data);
								
								$rec_limit = 10;
								$query     = "SELECT count(id) FROM pastes";
								$retval    = mysqli_query($con, $query);
								
								$row       = mysqli_fetch_array($retval);
								$rec_count = Trim($row[0]);
								$offset    = 0;
								// Set the specific query to display in the table
								$sql       = "SELECT * FROM `pastes` WHERE visible='0' LIMIT $offset, $rec_count ";
								$result    = mysqli_query($con, $sql);
								
								// Loop through each record
								while ($row = mysqli_fetch_array($result)) {
									$paste_id  = Trim($row['id']);
									$site_data = file_get_contents("../sitemap.xml");
									$site_data = str_replace("</urlset>", "", $site_data);

									if ($mod_rewrite == "1") {
										$server_name = $protocol . $_SERVER['SERVER_NAME'] . $levelup . "/" . $paste_id;
									} else {
										$server_name = $protocol . $_SERVER['SERVER_NAME'] . $levelup . "/paste.php?id=" . $paste_id;
									}
									$c_date    = date('Y-m-d');
									$c_sitemap = '
							<url>
									<loc>' . $server_name . '</loc>
									<priority>' . $priority . '</priority>
									<changefreq>' . $changefreq . '</changefreq>
									<lastmod>' . $c_date . '</lastmod>
							</url>
							</urlset>';
									$full_map  = $site_data . $c_sitemap;
									file_put_contents("../sitemap.xml", $full_map);
								}
							}
							?>
							
							<?php
							if (isset($_GET['re'])) {
								echo '
									<div class="paste-alert alert3">
										sitemap.xml rebuilt
									</div>';
							}
							?>
							<form method="GET" action="sitemap.php">
								<button class="btn btn-default" name="re" id="re" type="submit">Generate sitemap.xml</button>
							</form>
						</div>
					</div>
				</div>
			</div>
			<!-- End Sitemap -->
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