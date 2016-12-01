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
	$date = date('jS F Y');
	$ip   = $_SERVER['REMOTE_ADDR'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Paste 2.0 - Install</title>
<link href="../admin/css/paste.css" rel="stylesheet">
<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
 <script>
function loadXMLDoc()
{
var xmlhttp;
var sql_host = $('input[name=data_host]').val();
var sql_name = $('input[name=data_name]').val();
var sql_user = $('input[name=data_user]').val();
var sql_pass = $('input[name=data_pass]').val();
var sql_sec = $('input[name=data_sec]').val();
if (window.XMLHttpRequest)
  {// code for IE7+, Firefox, Chrome, Opera, Safari
  xmlhttp=new XMLHttpRequest();
  }
else
  {// code for IE6, IE5
  xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
xmlhttp.onreadystatechange=function()
  {
  if (xmlhttp.readyState==4 && xmlhttp.status==200)
    {
    }
  }
$.post("configure.php", {data_host:sql_host,data_name:sql_name,data_user:sql_user,data_pass:sql_pass,data_sec:sql_sec}, function(results){
if (results == 0) {
     $("#alertfailed").show();
     $("#index_1").show();
     $("#index_2").hide();
}
else
{
     $("#alertfailed").hide();
     $("#alertsuccess").show();
     $("#index_1").hide();
     $("#index_2").show();
}
});
}

function findoc()
{
var xmlhttp;
var user = $('input[name=admin_user]').val();
var pass = $('input[name=admin_pass]').val();
if (window.XMLHttpRequest)
  {// code for IE7+, Firefox, Chrome, Opera, Safari
  xmlhttp=new XMLHttpRequest();
  }
else
  {// code for IE6, IE5
  xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
xmlhttp.onreadystatechange=function()
  {
  if (xmlhttp.readyState==4 && xmlhttp.status==200)
    {
    }
  }
$("#alertfailed").hide();
$("#alertsuccess").hide();
$("#index_1").hide();
$("#index_2").hide();
$("#pre_load").show();
$.post("install.php", {admin_user:user,admin_pass:pass}, function(results){
     $("#index_3").show();
     $("#index_3").append(results);
     $("#pre_load").hide();
});
}
</script>

<style>
 #alertfailed{ display:none; }
 #alertsuccess{ display:none; }
 #index_2{ display:none; }
 #index_3{ display:none; }
 #pre_load{ display:none; }
 </style>     
</head>
<body>

<?php
if ($_SERVER['REQUEST_METHOD'] == POST) {
	// Post Handler
}
?>

<div id="top" class="clearfix">
	<!-- Start App Logo -->
	<div class="applogo">
	  <a href="#" class="logo">Paste 2.0</a>
	</div>
	<!-- End App Logo -->
</div>
<!-- END TOP -->

	<div class="content">
		  <!-- START CONTAINER -->
		<div class="container-widget">
		
			<!-- Start Install -->
			<div class="row" id="index_1">
				<div class="col-md-12">
					<div class="panel panel-widget">
						<div class="panel-body">
							<div class="panel-title">Install Paste</a></div>
							   <div class="paste-alert alert6 alert-dismissable" id="alertfailed">
									<i class="fa fa-ban"></i>
									<button aria-hidden="true" data-dismiss="alert" class="close" type="button">x</button>
									Database connection failed.
							   </div>
							   <div class="paste-alert alert3 alert-dismissable" id="alertsuccess">
									<i class="fa fa-check"></i>
									<button aria-hidden="true" data-dismiss="alert" class="close" type="button">x</button>
									Database connection successful.
							   </div>
							   
								<table class="table table-hover">
									<tbody><tr>
										<th>File</th>
										<th>Status</th>
									</tr>
									
									<tr>
										<td>config.php</td>
									<?php
										$filename = '../config.php';
										
										if (is_writable($filename)) {
											echo '<td><span class="label label-success">Writable</span></td>';
										} else {
											echo '<td><span class="label label-danger">Not Writable</span></td>';
										}
									?>
									</tr>
													
									<tr>
										<td>tmp/temp.tdata</td>
									<?php
										$filename = '../tmp/temp.tdata';
										
										if (is_writable($filename)) {
											echo '<td><span class="label label-success">Writable</span></td>';
										} else {
											echo '<td><span class="label label-danger">Not Writable</span></td>';
										}
									?>
									</tr>
									
									<tr>
										<td>sitemap.xml</td>
									<?php
										$filename = '../sitemap.xml';
										
										if (is_writable($filename)) {
											echo '<td><span class="label label-success">Writable</span></td>';
										} else {
											echo '<td><span class="label label-danger">Not Writable</span></td>';
										}
									?>
									</tr>										
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<div class="col-md-12">
					<div class="panel panel-widget">
						<div class="panel-body">
						 <div class="panel-title">Database Connection</a></div>
							<div class="form-group">
								<label for="data_host">Database Host</label>
								<input type="text" placeholder="localhost" name="data_host" id="data_host" class="span6">
							</div>
							<div class="form-group">
								<label for="data_name">Database Name</label>
								<input type="text" placeholder="name" name="data_name" id="data_name" class="span6">
							</div>
							<div class="form-group">
								<label for="data_user">Database Username</label>
								<input type="text" placeholder="user" name="data_user" id="data_user" class="span6">
							</div>
							<div class="form-group">
								<label for="data_pass">Database Password</label>
								<input type="password" placeholder="password" name="data_pass" id="data_pass" class="span6">
							</div>
							<br /><br />
							<div class="form-group">
								<label for="data_pass">Put this key in a safe place.</label>
								<input style="background-color: #EEEEEE; border-color: #DDDDDD;" readonly="" type="text" value="<?php echo md5(uniqid(rand(), true)); ?>" placeholder="" name="data_sec" id="data_sec" class="span6">
							</div>
							<button class="btn btn btn-primary" onclick="loadXMLDoc()" >Submit</button>
						</div>
					</div>
				</div>
			</div>
			
			<div class="row" id="index_2">
				<div class="col-md-12">
					<div class="panel panel-widget">
						<div class="panel-body">
						 <div class="panel-title">One more step. Create your admin account.</a></div>
							<div class="form-group">
								<label for="admin_user">Username</label>
								<input type="text" placeholder="Enter admin username" name="admin_user" id="admin_user" class="span6">
							</div>
							<div class="form-group">
								<label for="admin_pass">Password</label>
								<input type="password" placeholder="Enter admin password" name="admin_pass" id="admin_pass" class="span6">
							</div>
							   <button class="btn btn btn-primary" onclick="findoc()" >Submit</button>
						</div>
					</div>
				</div>
			</div>
			
			<div class="row" id="pre_load">
				<div class="col-md-12">
					<div class="panel panel-widget">
						<div class="panel-body">
						 <div class="panel-title">Final Step</a></div>
							Installing.
						</div>
					</div>
				</div>
			</div>
			
			<div id="index_3"> </div>
			
		</div>
		<!-- END CONTAINER -->

		<!-- Start Footer -->
		<div class="row footer">
		  <div class="col-md-6 text-left">
		   <a href="https://bitbucket.org/j-samuel/paste/src">Updates</a> &mdash; <a href="https://bitbucket.org/j-samuel/paste/issues?status=new&status=open">Bugs</a>
		  </div>
		  <div class="col-md-6 text-right">
			Powered by <a href="https://bitbucket.org/j-samuel/paste" target="_blank">Paste 2</a>
		  </div> 
		</div>
		<!-- End Footer -->
	</div>
	<!-- End content -->

	<script type="text/javascript" src="../admin/js/jquery.min.js"></script>
	<script type="text/javascript" src="../admin/js/bootstrap.min.js"></script>
  </body>
</html>