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
          <?php if (isset($notfound)): ?>
            <div class="card-body text-center">
              <i class="bi bi-exclamation-circle" style="font-size: 5rem; color: #f92672;"></i>
              <h1 class="mt-3"><?php echo htmlspecialchars($notfound); ?></h1>
              <div class="mt-4">
                <a href="./" class="btn btn-primary">New Paste</a>
              </div>
            </div>
          <?php else: ?>
            <div class="card-header text-center">
              <h6><?php echo htmlspecialchars($lang['pwdprotected'] ?? 'Password Protected'); ?></h6>
            </div>
            <div class="card-body">
              <?php if (isset($error)): ?>
                <div class="alert alert-danger text-center mb-3">
                  <?php echo htmlspecialchars($error); ?>
                </div>
              <?php endif; ?>
              <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="text-center">
                <div class="form-group mb-3">
                  <input type="hidden" name="id" value="<?php echo htmlspecialchars($paste_id); ?>">
                  <input type="text" class="form-control w-50 mx-auto" name="mypass" placeholder="<?php echo htmlspecialchars($lang['enterpwd'] ?? 'Enter Password'); ?>">
                  <i class="bi bi-unlock" style="position: absolute; right: 25%; top: 50%; transform: translateY(-50%); color: #666;"></i>
                </div>
                <button type="submit" name="submit" class="btn btn-primary">Submit</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php require_once('theme/' . $default_theme . '/sidebar.php'); ?>
    </div>
  </div>
</div>