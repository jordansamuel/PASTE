<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
declare(strict_types=1);
?>

<div class="content">
  <!-- START CONTAINER -->
  <div class="container-xl my-5">
    <!-- Start Row -->
    <div class="row">
      <!-- Start Card -->
      <div class="col-lg-12">
        <div class="card">
          <div class="card-header bg-dark text-light rounded-top d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?php echo htmlspecialchars($lang['myprofile'] ?? 'My Profile', ENT_QUOTES, 'UTF-8'); ?></h5>
            <a class="btn btn-outline-light btn-sm" href="<?php echo htmlspecialchars(
                $baseurl . ($mod_rewrite ? 'user/' . urlencode($_SESSION['username'] ?? '') : 'user.php?user=' . urlencode($_SESSION['username'] ?? '')),
                ENT_QUOTES,
                'UTF-8'
            ); ?>" target="_self"><?php echo htmlspecialchars($lang['mypastes'] ?? 'My Pastes', ENT_QUOTES, 'UTF-8'); ?></a>
          </div>
          
          <div class="card-body p-4">
            <?php 
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {	
              if (isset($success)) {
                echo '<div class="alert alert-success text-center rounded-3">' . htmlspecialchars($success, ENT_QUOTES, 'UTF-8') . '</div>'; 
              } elseif (isset($error)) {
                echo '<div class="alert alert-danger text-center rounded-3">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>'; 
              }
            }
            ?>
            <form action="<?php echo htmlspecialchars($baseurl . 'profile.php', ENT_QUOTES, 'UTF-8'); ?>" method="post" class="mt-4">
              <div class="row justify-content-center">
                <div class="col-md-6">
                  <div class="mb-4 position-relative">
                    <label class="form-label text-light"><?php echo htmlspecialchars($lang['username'] ?? 'Username', ENT_QUOTES, 'UTF-8'); ?></label>
                    <input disabled type="text" class="form-control bg-dark text-light pe-5" name="username" placeholder="<?php echo htmlspecialchars($user_username ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-person position-absolute" style="right: 12px; top: 50%; transform: translateY(-50%); color: #6c757d; font-size: 1.1rem;"></i>
                  </div>
                  
                  <div class="mb-4 position-relative">
                    <label class="form-label text-light"><?php echo htmlspecialchars($lang['email'] ?? 'Email', ENT_QUOTES, 'UTF-8'); ?></label>
                    <input <?php if ($user_verified == "1") { echo 'disabled'; } ?> type="text" class="form-control bg-dark text-light pe-5" name="email" placeholder="<?php echo htmlspecialchars($user_email_id ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-envelope position-absolute" style="right: 12px; top: 50%; transform: translateY(-50%); color: #6c757d; font-size: 1.1rem;"></i>
                  </div>

                  <h5 class="text-center mb-4 text-light"><?php echo htmlspecialchars($lang['chgpwd'] ?? 'Change Password', ENT_QUOTES, 'UTF-8'); ?></h5>
                  
                  <div class="mb-4 position-relative">
                    <label class="form-label text-light"><?php echo htmlspecialchars($lang['curpwd'] ?? 'Current Password', ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="password" class="form-control bg-dark text-light pe-5" name="old_password" placeholder="<?php echo htmlspecialchars($lang['curpwd'] ?? 'Current Password', ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-key position-absolute password-toggle" style="right: 12px; top: 50%; transform: translateY(-50%); color: #6c757d; font-size: 1.1rem; cursor: pointer;"></i>
                  </div>
                  
                  <div class="mb-4 position-relative">
                    <label class="form-label text-light"><?php echo htmlspecialchars($lang['newpwd'] ?? 'New Password', ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="password" class="form-control bg-dark text-light pe-5" name="password" placeholder="<?php echo htmlspecialchars($lang['newpwd'] ?? 'New Password', ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-pencil position-absolute password-toggle" style="right: 12px; top: 50%; transform: translateY(-50%); color: #6c757d; font-size: 1.1rem; cursor: pointer;"></i>
                  </div>

                  <div class="mb-4 position-relative">
                    <label class="form-label text-light"><?php echo htmlspecialchars($lang['confpwd'] ?? 'Confirm Password', ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="password" class="form-control bg-dark text-light pe-5" name="cpassword" placeholder="<?php echo htmlspecialchars($lang['confpwd'] ?? 'Confirm Password', ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-check position-absolute password-toggle" style="right: 12px; top: 50%; transform: translateY(-50%); color: #6c757d; font-size: 1.1rem; cursor: pointer;"></i>
                  </div>
                  <button type="submit" name="submit" class="btn btn-outline-light w-100 rounded-3">Submit</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php require_once('theme/' . $default_theme . '/sidebar.php'); ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Optional: Toggle password visibility
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.remove('bi-key', 'bi-pencil', 'bi-check');
                this.classList.add('bi-eye');
            } else {
                input.type = 'password';
                this.classList.remove('bi-eye');
                this.classList.add(
                    this.parentElement.querySelector('input[name="old_password"]') ? 'bi-key' :
                    this.parentElement.querySelector('input[name="password"]') ? 'bi-pencil' :
                    'bi-check'
                );
            }
        });
    });
});
</script>