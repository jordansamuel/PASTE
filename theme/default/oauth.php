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
          <?php 
          // Success message for returning users
          if (isset($success) && isset($old_user)) {
            echo '<div class="alert alert-success text-center">' . htmlspecialchars($success) . ' <br /> ' . htmlspecialchars($lang['50'] ?? 'Logged in successfully.') . '</div>'; 
            echo '<meta http-equiv="refresh" content="2;url=./">';
          }
          // Success message for new users
          elseif (isset($success)) {
            echo '<div class="alert alert-success text-center">' . htmlspecialchars($success) . ' <br /> ' . htmlspecialchars($lang['49'] ?? 'Account created successfully.') . '</div>'; 
          }
          // Error message
          elseif (isset($error)) {
            echo '<div class="alert alert-danger text-center">' . htmlspecialchars($error) . '</div>'; 
          }
          
          // Username customization form for new OAuth users
          if (isset($_GET['new_user']) && !isset($old_user)) {
          ?>
            <div class="card-header text-center">
              <?php echo htmlspecialchars($lang['almostthere'] ?? 'Almost There'); ?>
            </div>          
            <div class="card-body mt-0">
              <form action="oauth.php?newuser" method="post" class="text-center">
                <div class="form-group mb-3 position-relative">
                  <input readonly type="text" class="form-control w-50 mx-auto" name="autoname" value="<?php echo htmlspecialchars($username ?? ''); ?>">
                  <i class="bi bi-person" style="position: absolute; right: 25%; top: 50%; transform: translateY(-50%); color: #666;"></i>
                </div>
                <div class="form-group mb-3 position-relative">
                  <input type="text" class="form-control w-50 mx-auto" name="new_username" placeholder="<?php echo htmlspecialchars($lang['setuser'] ?? 'Set Username'); ?>">
                  <i class="bi bi-person" style="position: absolute; right: 25%; top: 50%; transform: translateY(-50%); color: #666;"></i>
                </div>
                <input type="hidden" name="user_change" value="1">
                <button type="submit" name="submit" class="btn btn-primary w-50 mx-auto">Submit</button>
                <a href="." class="btn btn-outline-primary w-50 mx-auto mt-2"><?php echo htmlspecialchars($lang['keepuser'] ?? 'Keep Username'); ?></a>  
              </form>
            </div>
          <?php } else { ?>
            <!-- Redirect for returning users or after username submission -->
            <meta http-equiv="refresh" content="2;url=./">
          <?php } ?>
        </div>
      </div>
      
      <?php require_once('theme/' . $default_theme . '/sidebar.php'); ?>
      <?php echo htmlspecialchars($ads_2 ?? '', ENT_QUOTES, 'UTF-8'); ?>
    </div>
  </div>
</div>