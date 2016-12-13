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
     $("#install").show();
     $("#configure").hide();
}
else
{
     $("#install").hide();
     $("#configure").show();
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
$("#install").hide();
$("#configure").hide();
$("#pre_load").show();
$.post("install.php", {admin_user:user,admin_pass:pass}, function(results){
     $("#logpanel").show();
     $("#log").append(results);
     $("#pre_load").hide();
});
}
</script>

<style>
 #alertfailed{ display:none; }
 #configure{ display:none; }
 #logpanel{ display:none; }
 #pre_load{ display:none; }
 </style>     
</head>
<body>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// Post Handler
}
?>

<div id="top" class="clearfix">
	<!-- Start App Logo -->
	<div class="applogo">
	  <a href="#" class="logo">Paste</a>
	</div>
	<!-- End App Logo -->
</div>
<!-- END TOP -->

<div class="content">
	<!-- START CONTAINER -->
	<div class="container-padding">
		<!-- Start Row -->
		<div class="row">
			<!-- INSTALL PANEL -->
			<div id="install">
				<div class="col-md-4">
					<div class="panel panel-default">
						<div class="panel-body">
								<div class="panel-title">Pre-installation checks</a></div>
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

				<!-- Database Information -->
				<div class="col-md-8">
					<div class="panel panel-default">
						<div class="panel-title">Database Information</a></div>
						<div class="form-horizontal">
							<div class="paste-alert alert6 alert-dismissable" style="text-align: center;" id="alertfailed">
								<button aria-hidden="true" data-dismiss="alert" class="close" type="button">x</button>
								Database connection failed.
							</div>
							<div class="form-group">
								<label class="col-sm-2 control-label form-label" for="data_host">Hostname</label>
								<div class="col-sm-10">
									<input type="text" class="form-control" placeholder="localhost" value="localhost" name="data_host" id="data_host">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-2 control-label form-label" for="data_name">Database Name</label>
								<div class="col-sm-10">
									<input type="text" class="form-control" placeholder="Example: Paste" name="data_name" id="data_name">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-2 control-label form-label" for="data_user">Username</label>
								<div class="col-sm-10">
									<input type="text" class="form-control" name="data_user" id="data_user">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-2 control-label form-label" for="data_pass">Password</label>
								<div class="col-sm-10">
									<input type="password" class="form-control" name="data_pass" id="data_pass">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-2 control-label form-label" for="data_pass">Put this key in a safe place.</label>
								<div class="col-sm-10">
									<input type="text" class="form-control" style="background-color: #EEEEEE; border-color: #DDDDDD;" readonly="" value="<?php echo md5(uniqid(rand(), true)); ?>" placeholder="" name="data_sec" id="data_sec" class="span6">
								</div>
							</div>
							<br />
							<button class="btn btn-default btn-block" onclick="loadXMLDoc()" >Install</button>
						</div>
					</div>
				</div>
			</div>
			<!-- END INSTALL PANEL -->

			<!-- CONFIGURATION PANEL -->
			<div id="configure">
				<div class="col-md-12">
					<div class="panel panel-default">
						<div class="panel-title">One more step. Configure your Paste installation.</a></div>
						<div class="form-horizontal">
							&mdash;&gt; Admin Account
							<div class="form-group">
								<label class="col-sm-2 control-label form-label" for="admin_user">Username</label>
								<div class="col-sm-10">
									<input type="text" placeholder="Enter admin username" name="admin_user" id="admin_user">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-2 control-label form-label" for="admin_pass">Password</label>
								<div class="col-sm-10">
									<input type="password" placeholder="Enter admin password" name="admin_pass" id="admin_pass">
								</div>
							</div>
							   <button class="btn btn-default" onclick="findoc()" >Submit</button>
						</div>
					</div>
				</div>
			</div>
			<!-- END CONFIGURATION PANEL -->

			<div id="pre_load">
				<div class="col-md-12">
					<div class="panel panel-widget">
						<div class="panel-body">
						 <div class="panel-title">Installing database schema for Paste. Please wait a moment.</a></div>
						</div>
					</div>
				</div>
			</div>

			<!-- Display SQL errors/queries  -->
			<div class="col-md-12" id="logpanel">
				<div class="panel panel-widget">
					<div class="panel-body">
						<div id="log">
						</div>
					</div>
				</div>
			</div>
			<!--//-->

		</div>
		<!-- END ROW -->
	</div>
	<!-- END CONTAINER -->

	<!-- Start Footer -->
	<div class="row footer">
	  <div class="col-md-6 text-left">
	   <a href="https://github.com/jordansamuel/PASTE">Updates</a> &mdash; <a href="https://github.com/jordansamuel/PASTE/issues">Bugs</a>
	  </div>
	  <div class="col-md-6 text-right">
		Powered by <a href="https://phpaste.sourceforge.io/" target="_blank">Paste 2</a>
	  </div> 
	</div>
	<!-- End Footer -->
</div>
<!-- End content -->

	<script type="text/javascript" src="../admin/js/jquery.min.js"></script>
	<script type="text/javascript" src="../admin/js/bootstrap.min.js"></script>
  </body>
</html>