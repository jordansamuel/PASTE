<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE> - Clean theme
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
?>

<div class="content">
  <!-- START CONTAINER -->
  <div class="container-padding">
    <!-- Start Row -->
    <div class="row">
      <!-- Start Panel -->
		<div class="col-md-9 col-lg-10">
			<div class="panel panel-default" style="padding-bottom: 100px;">
			<?php if (isset($notfound)) { ?>
				<div class="error-pages">
					<i class="fa fa-minus-circle fa-5x" aria-hidden="true"></i>
					<h1><?php echo $notfound; ?></h1>

					<div class="bottom-links">
					  <a href="./" class="btn btn-default">New Paste</a>
					</div>
				</div>
				<?php } else { ?>
				<div class="panel-title" style="text-align:center;">
					<h6><?php echo $lang['pwdprotected']; ?></h6>
				</div>
				<div class="login-form" style="padding-top: 0px;">
				<?php if (isset($error)) { ?>
						<div class="paste-alert alert6" style="text-align:center;">
							<?php echo $error;?>
						</div>
				<?php } ?>
					  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
						<div class="form-area">
						  <div class="group">
							<input type="hidden" name="id" value="<?php echo $paste_id; ?>">
							<input type="text" class="form-control" name="mypass" placeholder="<?php echo $lang['enterpwd']; ?>">
							<i class="fa fa-unlock" aria-hidden="true"></i>
						  </div>
						  <button type="submit" name="submit" class="btn btn-default btn-block">Submit</button>
						</div>
					  </form>
				</div>
			<?php } ?>
			</div>
		</div>
<?php require_once('theme/'.$default_theme.'/sidebar.php'); ?>