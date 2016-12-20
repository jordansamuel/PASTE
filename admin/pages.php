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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST{'editme'})) {
        $edit_me_id   = htmlentities(Trim($_POST['editme']));
        $page_name    = Trim($_POST['page_name']);
        $page_title   = Trim($_POST['page_title']);
        $page_content = $_POST['data'];
        
        $query = "UPDATE pages SET last_date='$date', page_name='$page_name', page_title='$page_title', page_content='$page_content' WHERE id='$edit_me_id'";
        mysqli_query($con, $query);
    } else {
        $page_name    = Trim($_POST['page_name']);
        $page_title   = Trim($_POST['page_title']);
        $page_content = $_POST['data'];
        
        $query = "INSERT INTO pages (last_date,page_name,page_title,page_content) VALUES ('$date','$page_name','$page_title','$page_content')";
        mysqli_query($con, $query);
    }
    $page_name    = "";
    $page_title   = "";
    $page_content = "";
}

if (isset($_GET{'edit'})) {
    
    $page_id = trim($_GET['edit']);
    $sql     = "SELECT * FROM pages where id='$page_id'";
    $result  = mysqli_query($con, $sql);
    
    //we loop through each records
    while ($row = mysqli_fetch_array($result)) {
        //populate and display results data in each row
        $page_name    = $row['page_name'];
        $page_title   = $row['page_title'];
        $page_content = $row['page_content'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Pages</title>
	<link rel="shortcut icon" href="favicon.ico">
    <link href="css/paste.css" rel="stylesheet" type="text/css" />
	<link href="css/bootstrap3-wysihtml5.min.css" rel="stylesheet" type="text/css" />
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
					<li class="col-xs-3 col-sm-2 col-md-1 menu-active">
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
    
			<!-- Start Pages -->
			<div class="row">
				<div class="col-md-12">
					<div class="panel panel-widget">
						<div class="panel-body">
						 <div class="panel-title">Add a Page</a></div>
							<form action="<?php echo $_SERVER['PHP_SELF']; ?>" class="form-horizontal" method="post">
								<div class="control-group">
									<label for="page_name">Page name (No spaces, e.g. terms_of_service)</label> <input class="span6" id=
									"page_name" name="page_name" placeholder="Enter page name"
									type="text" value="<?php echo isset($page_name); ?>">
								</div>
								<div class="control-group">
									<label for="page_title">Page title</label> <input class=
									"span6" id="page_title" name="page_title" placeholder=
									"Enter page title" type="text" value=
									"<?php echo isset($page_title); ?>">
								</div>
								<br />
								<?php
								if (isset($_GET{'edit'})) {
									echo '<input type="hidden" value=' . $_GET{'edit'} . 'id="editme" name="editme" />';
								}
								?>
								<div class='control-group'>
									<textarea class="span6" cols="80" id="editor1" name="data" rows="10"><?php echo isset($page_content); ?></textarea><br>
								</div>
								<button class="btn btn-default btn-sm">Save</button>
							</form>
						</div>
					</div>
				</div>
			</div>
				
			<div class="row">
				<div class="col-md-12">
					<div class="panel panel-widget">
						<div class="panel-body">
						 <div class="panel-title">Pages</div>
						  <table class="table table-striped">
						   <tbody>
							<tr>
								<th>Date Added</th>
								<th>Page Name</th>
								<th>Page Title</th>
								<th>View</th>
								<th>Edit</th>
								<th>Delete</th>

								<?php
								if (isset($_GET{'delete'})) {
									$delete = htmlentities(Trim($_GET['delete']));
									$query  = "DELETE FROM pages WHERE id=$delete";
									$result = mysqli_query($con, $query);
									
									if (mysqli_errno($con)) {
										echo '<div class="paste-alert alert6">
												 ' . mysqli_error($con) . '
											  </div>';
									} else {
										echo '
										<div class="paste-alert alert3">
											Page deleted.
										</div>';
									}
								}
								$rec_limit = 20;
								$query     = "SELECT count(id) FROM pages";
								$retval    = mysqli_query($con, $query);

								$row       = mysqli_fetch_array($retval);
								$rec_count = Trim($row[0]);



								if (isset($_GET{'page'})) { // Get the current page
									$page   = $_GET{'page'} + 1;
									$offset = $rec_limit * $page;
								} else {
									// Show first set of results
									$page   = 0;
									$offset = 0;
								}
								$left_rec = $rec_count - ($page * $rec_limit);
								// Set the specific query to display in the table
								$sql      = "SELECT * FROM pages ORDER BY `id` DESC LIMIT $offset, $rec_limit";
								$result   = mysqli_query($con, $sql);
								$no       = 1;
								// Loop through each records
								while ($row = mysqli_fetch_array($result)) {
									// Populate and display results data in each row
									echo '<tr>';
									echo '<td>' . $row['last_date'] . '</td>';
									echo '<td>' . $row['page_name'] . '</td>';
									echo '<td>' . $row['page_title'] . '</td>';
									$myid = $row['id'];
									echo '<td>' . "<a class='btn btn-success btn-sm' href=../page/" . $row['page_name'] . "> View </a>" . '</td>';
									echo '<td>' . "<a class='btn btn-default btn-sm' href=" . $_PHP_SELF . "?edit=" . $myid . "> Edit </a>" . '</td>';
									echo '<td>' . "<a class='btn btn-danger btn-sm' href=" . $_PHP_SELF . "?delete=" . $myid . "> Delete </a>" . '</td>';
									$no++;
								}
								echo '</tr>';
								echo '</tbody>';
								echo '</table>';
								// Display the pagination
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
			<!-- End Pages -->
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
	
	<!-- CK Editor -->
	<script src="js/plugins/ckeditor/ckeditor.js" type="text/javascript"></script>

	<!-- Bootstrap WYSIHTML5 -->
	<script src="js/bootstrap3-wysihtml5.all.min.js" type="text/javascript"></script>

	<script type="text/javascript">
				$(function() {
					CKEDITOR.replace('editor1');
				});
	</script>
  </body>
</html>
<?php mysqli_close($con); ?>