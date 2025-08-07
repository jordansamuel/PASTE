<?php
/*
 * Paste 3 default theme <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
?>

<div class="content">
  <!-- START CONTAINER -->
  <div class="container-padding">
    <!-- Start Row -->
    <div class="row">
      <!-- Start Panel -->
		<div class="col-md-9 col-lg-10">
		  <div class="panel panel-default">
			<?php 
			// Logged in
			if (isset($success)) {
					echo '<div class="paste-alert alert3" style="text-align: center;">
							' . $success . ' <br /> ' . $lang['49'] . '
						</div>'; 
				echo '<meta http-equiv="refresh" content="2;url=./">'; 
			}
			
			// Errors
			elseif (isset($error)) {
				echo '<div class="paste-alert alert5" style="text-align: center;">
						' . $error . '
					</div>'; 
			}
			
			if (isset($old_user)) {
					echo '<div class="paste-alert alert3" style="text-align: center;">
							' . $success . ' <br /> ' . $lang['50'] . '
						</div>'; 
				echo '<meta http-equiv="refresh" content="1;url=./">'; 
			}
			else {
			?>
			
			<div class="panel-title" style="text-align:center;">
			<?php echo $lang['almostthere']; ?>
			</div>			
			<div class="login-form" style="padding-top: 0px;">
			  <form action="oauth.php?newuser" method="post">
				<div class="form-area">
				  <div class="group">
					<input readonly="" type="text" class="form-control" name="autoname" value="<?php echo $username; ?>">
					<i class="fa fa-user"></i>
				  </div>
				  
				  <div class="group">
					<input type="text" class="form-control" name="new_username" placeholder="<?php echo $lang['setuser']; ?>">
					<i class="fa fa-user"></i>
				  </div>

				  <input type="hidden" name="user_change" value="<?php echo md5($date.$ip); ?>" />
				  <button type="submit" name="submit" class="btn btn-default btn-block">Submit</button>
				  <a href="." class="btn btn-primary btn-block"><?php echo $lang['keepuser']; ?></a>	
				</div>
			  </form>
			</div>
		<?php } ?>
	</div>
</div>

<?php require_once('theme/'.$default_theme.'/sidebar.php'); ?>

<?php echo $ads_2; ?>