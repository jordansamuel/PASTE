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
  <div class="container-xl my-4">
    <!-- Start Row -->
    <div class="row">
      <!-- Start Card -->
      <div class="col-lg-10">
        <div class="card">
          <div class="card-header">
            <?php echo htmlspecialchars($lang['totalpastes'] ?? 'Total Pastes') . ' ' . htmlspecialchars($total_pastes ?? 0) . ' <a class="btn btn-outline-light float-end" href="' . htmlspecialchars($baseurl . ($mod_rewrite ? 'user/' . urlencode($_SESSION['username'] ?? '') : 'user.php?user=' . urlencode($_SESSION['username'] ?? ''))) . '" target="_self">' . htmlspecialchars($lang['mypastes'] ?? 'My Pastes') . '</a>'; ?>
          </div>
          
          <div class="card-body">
            <?php 
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {	
              if (isset($success)) {
                echo '<div class="alert alert-success text-center">' . htmlspecialchars($success) . '</div>'; 
              } elseif (isset($error)) {
                echo '<div class="alert alert-danger text-center">' . htmlspecialchars($error) . '</div>'; 
              }
            }
            ?>
            <div class="card-header text-center">
              <?php echo htmlspecialchars($lang['myprofile'] ?? 'My Profile'); ?>
            </div>
            <form action="<?php echo htmlspecialchars($baseurl . 'profile.php'); ?>" method="post" class="text-center mt-3">
              <div class="form-group mb-3">
                <input disabled="" type="text" class="form-control w-50 mx-auto" name="username" style="cursor:not-allowed;" placeholder="<?php echo htmlspecialchars($user_username ?? ''); ?>">
                <i class="bi bi-person" style="position: absolute; right: 25%; top: 50%; transform: translateY(-50%); color: #666;"></i>
              </div>
              
              <div class="form-group mb-3">
                <input <?php if ($user_verified == "1") { echo 'disabled=""'; } ?> type="text" class="form-control w-50 mx-auto" name="email" placeholder="<?php echo htmlspecialchars($user_email_id ?? ''); ?>">
                <i class="bi bi-envelope" style="position: absolute; right: 25%; top: 50%; transform: translateY(-50%); color: #666;"></i>
              </div>

              <h5 class="text-center"><?php echo htmlspecialchars($lang['chgpwd'] ?? 'Change Password'); ?></h5>
              
              <div class="form-group mb-3">
                <input type="password" class="form-control w-50 mx-auto" name="old_password" placeholder="<?php echo htmlspecialchars($lang['curpwd'] ?? 'Current Password'); ?>">
                <i class="bi bi-key" style="position: absolute; right: 25%; top: 50%; transform: translateY(-50%); color: #666;"></i>
              </div>
              
              <div class="form-group mb-3">
                <input type="password" class="form-control w-50 mx-auto" name="password" placeholder="<?php echo htmlspecialchars($lang['newpwd'] ?? 'New Password'); ?>">
                <i class="bi bi-pencil" style="position: absolute; right: 25%; top: 50%; transform: translateY(-50%); color: #666;"></i>
              </div>

              <div class="form-group mb-3">
                <input type="password" class="form-control w-50 mx-auto" name="cpassword" placeholder="<?php echo htmlspecialchars($lang['confpwd'] ?? 'Confirm Password'); ?>">
                <i class="bi bi-check" style="position: absolute; right: 25%; top: 50%; transform: translateY(-50%); color: #666;"></i>
              </div>
              <button type="submit" name="submit" class="btn btn-primary w-50 mx-auto">Submit</button>
            </form>
          </div>
        </div>
      </div>
      <?php require_once('theme/' . $default_theme . '/sidebar.php'); ?>
    </div>
  </div>
</div>