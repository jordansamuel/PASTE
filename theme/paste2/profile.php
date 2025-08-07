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
			<div class="panel-title">
				<?php echo $lang['totalpastes'] . ' ' . $total_pastes . ' <a class="btn btn-light pull-right" href="user.php?user=' . $_SESSION['username'] . '" target="_self">' . $lang['mypastes'] . '</a>' ;?>
			</div>
			
				<div class="login-form" style="padding-top: 0px;">
				<?php 
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {	
				if (isset($success)) {
					echo '<div class="paste-alert alert3" style="text-align:center;">
					'.$success.'
					</div>'; 
					} elseif (isset($error)) {
						echo '<div class="paste-alert alert6" style="text-align:center;">
					'.$error.'
					</div>'; 
					}
				}
				?>
			<div class="panel-title" style="text-align:center;">
				<?php echo $lang['myprofile']; ?>
			</div>
			  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
					<div class="form-area">
					  <div class="group">
						<input disabled="" type="text" class="form-control"  name="username" style="cursor:not-allowed;" placeholder="<?php echo $user_username; ?>">
						<i class="fa fa-user"></i>
					  </div>
					  
					  <div class="group">
						<input <?php if ($user_verified == "1") { echo 'disabled=""'; } ?> type="text" class="form-control" name="email" placeholder="<?php echo $user_email_id; ?>">
						<i class="fa fa-envelope-o"></i>
					  </div>

					  <h5><?php echo $lang['chgpwd']; ?></h5>
					  
					  <div class="group">
						<input type="password" class="form-control" name="old_password" placeholder="<?php echo $lang['curpwd']; ?>">
						<i class="fa fa-key"></i>
					  </div>
					  
					  <div class="group">
						<input type="password" class="form-control" name="password" placeholder="<?php echo $lang['newpwd']; ?>">
						<i class="fa fa-pencil"></i>
					  </div>

					  <div class="group">
						<input type="password" class="form-control" name="cpassword" placeholder="<?php echo $lang['confpwd']; ?>">
						<i class="fa fa-check"></i>
					  </div>
					  <button type="submit" name="submit" class="btn btn-default btn-block">Submit</button>
					</div>
				  </form>
				</div>
			</div>
		</div>
<?php require_once('theme/'.$default_theme.'/sidebar.php'); ?>