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

// Get IP from form or URL
if ( $_SERVER['REQUEST_METHOD'] == 'POST' || isset( $_GET['banip'] ) ) {
    if ( isset( $_POST['banip'] ) ) {
        $ban_ip = htmlentities( Trim( $_POST['ban_ip'] ) );
    } elseif ( isset( $_GET['banip'] ) ) {
        $ban_ip = htmlentities( Trim( $_GET['banip'] ) );
    }
    // Check if IP is blank or already banned.
    if ( trim($ban_ip) == '' ) {
         $msg = '<div class="paste-alert alert6" style="text-align: center;">Please enter an IP to ban.</div>';
    } else {
        $query  = "SELECT * FROM ban_user where ip='$ban_ip'";
        $result = mysqli_query( $con, $query );
        $num_rows = mysqli_num_rows( $result );
        if ( $num_rows >= 1 ) {
            $msg = '<div class="paste-alert alert1" style="text-align: center;">' . $ban_ip . ' already banned</div>';
        } else {
            // Valid IP which is not banned. Add to database
            $query  = "INSERT INTO ban_user (last_date,ip) VALUES ('$date','$ban_ip')";
            mysqli_query( $con, $query );
            if ( mysqli_errno( $con ) ) {
                $msg = '<div class="paste-alert alert6" style="text-align: center;">' . mysqli_error($con) . '</div>';
            } else {
                $msg = '<div class="paste-alert alert3" style="text-align: center;">' . $ban_ip . ' added to the banlist</div>';
            }
        }
    }
}

if (isset($_GET{'delete'})) {
	$delete = htmlentities(Trim($_GET['delete']));
	$query  = "DELETE FROM ban_user WHERE id=$delete";
	$result = mysqli_query($con, $query);
	
	if (mysqli_errno($con)) {
		$msg = '<div class="paste-alert alert6" style="text-align: center;">
				' . mysqli_error($con) . '
				</div>';
	} else {
			$msg = '
	<div class="paste-alert alert3" style="text-align: center;">
	IP removed from the banlist
	</div>';
	}
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - IP Bans</title>
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
					<li class="col-xs-3 col-sm-2 col-md-1 menu-active">
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
    
			<!-- Start IP bans -->
			<div class="row">
				<div class="col-md-12">
					<div class="panel panel-widget">
						<div class="panel-body">
						 <div class="panel-title">Ban an IP</a></div>
						<?php if (isset($msg)) echo $msg; ?>
							<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
								<div class="form-group">
									<input type="text" class="form-control" name="ban_ip" placeholder="Enter an IP address">
								 <input type="hidden" name="banip" value="banip" />
								</div>
								<button type="submit" class="btn btn-default">Add</button>
							</form>
						</div>
					</div>
				</div>
			</div>
				
			<div class="row">
				<div class="col-md-12">
					<div class="panel panel-widget">
						<div class="panel-body">
						 <div class="panel-title">Banlist</div>
						  <table class="table table-striped">
						   <tbody>
							<tr>
								<th>Date Added</th>
								<th>IP</th>
								<th>Delete</th>
							</tr>
										
							<?php
							$rec_limit = 20;
							$query     = "SELECT count(id) FROM ban_user";
							$retval    = mysqli_query($con, $query);

							$row       = mysqli_fetch_array($retval);
							$rec_count = Trim($row[0]);



							if (isset($_GET{'page'})) { // Current page
								$page   = $_GET{'page'} + 1;
								$offset = $rec_limit * $page;
							} else {
								// Show first set of results
								$page   = 0;
								$offset = 0;
							}
							$left_rec = $rec_count - ($page * $rec_limit);
							// Set the specific query to display in the table
							$sql      = "SELECT * FROM ban_user ORDER BY `id` DESC LIMIT $offset, $rec_limit";
							$result   = mysqli_query($con, $sql);
							$no       = 1;
							// Loop through each records
							while ($row = mysqli_fetch_array($result)) {
								// Populate and display result data in each row
								echo '<tr>';
								echo '<td>' . $row['last_date'] . '</td>';
								echo '<td>' . $row['ip'] . '</td>';
								$myid = $row['id'];
								echo '<td>' . "<a class='btn btn-danger btn-sm' href=" . $_PHP_SELF . "?delete=" . $myid . "> Delete </a>" . '</td>';
								$no++;
							}
							echo '</tr>';
							echo '</tbody>';
							echo '</table>';
							// Display pagination
							echo '<ul class="pager">';
							if ($left_rec < $rec_limit) {
								$last = $page - 2;
								if ($last < 0) {
									
								} else {
									echo @"<li><a href=\"$_PHP_SELF?page=$last\">Previous</a></li>";
								}
							} else if ($page == 0) {
								echo @"<li><a href=\"$_PHP_SELF?page=$page\">Next</a></li>";
							} else if ($page > 0) {
								$last = $page - 2;
								echo @"<li><a href=\"$_PHP_SELF?page=$last\">Previous</a></li> ";
								echo @"<li><a href=\"$_PHP_SELF?page=$page\">Next</a></li>";
							}
							echo '</ul>';
							?>

						</div>              			
					</div>
				</div>
			</div>
			<!-- End IP bans -->
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