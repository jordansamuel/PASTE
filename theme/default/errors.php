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
<style>
<?php if (isset($privatesite) && $privatesite === "on"): ?>
/* Ensure proper spacing when sidebar is below main content */
.sidebar-below {
    margin-top: 1.5rem; /* Add spacing between main content and sidebar */
}
<?php endif; ?>
</style>
<div class="content">
  <!-- START CONTAINER -->
  <div class="container-xl my-4">
    <!-- Start Row -->
    <div class="row">
      <?php if (isset($privatesite) && $privatesite === "on"): ?>
        <!-- Private site: Main content full width, sidebar below -->
        <div class="col-lg-12">
          <?php if (!isset($_SESSION['username'])): ?>
            <div class="card">
              <div class="card-body">
                <div class="alert alert-warning text-center">
                  <?php echo htmlspecialchars($lang['login_required'] ?? 'You must be logged in to view this paste.', ENT_QUOTES, 'UTF-8'); ?>
                  <a href="<?php echo htmlspecialchars($baseurl . '/login.php', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary mt-2">Login</a>
                </div>
              </div>
            </div>
          <?php else: ?>
            <div class="card">
              <?php if (isset($notfound)): ?>
                <div class="card-body text-center">
                  <i class="bi bi-exclamation-circle" style="font-size: 5rem; color: #0d6efd;"></i>
                  <h1 class="mt-3"><?php echo htmlspecialchars($notfound, ENT_QUOTES, 'UTF-8'); ?></h1>
                  <div class="mt-4">
                    <a href="./" class="btn btn-primary">New Paste</a>
                  </div>
                </div>
              <?php else: ?>
                <div class="card-header text-center">
                  <h6><?php echo htmlspecialchars($lang['pwdprotected'] ?? 'Password Protected', ENT_QUOTES, 'UTF-8'); ?></h6>
                </div>
                <div class="card-body">
                  <?php if (isset($error)): ?>
                    <div class="alert alert-danger text-center mb-3">
                      <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                  <?php endif; ?>
                  <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post" class="text-center">
                    <div class="form-group mb-3">
                      <input type="hidden" name="id" value="<?php echo htmlspecialchars($paste_id, ENT_QUOTES, 'UTF-8'); ?>">
                      <div class="input-group w-50 mx-auto">
                        <span class="input-group-text"><i class="bi bi-unlock"></i></span>
                        <input type="text" class="form-control" name="mypass" placeholder="<?php echo htmlspecialchars($lang['enterpwd'] ?? 'Enter Password', ENT_QUOTES, 'UTF-8'); ?>">
                      </div>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">Submit</button>
                  </form>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="sidebar-below<?php echo (isset($privatesite) && $privatesite === 'on') ? ' sidebar-below' : ''; ?>">
          <?php require_once('theme/' . ($default_theme ?? 'default') . '/sidebar.php'); ?>
        </div>
      <?php else: ?>
        <!-- Non-private site: Main content and sidebar side by side -->
        <div class="col-lg-10">
          <div class="card">
            <?php if (isset($notfound)): ?>
              <div class="card-body text-center">
                <i class="bi bi-exclamation-circle" style="font-size: 5rem; color: #0d6efd;"></i>
                <h1 class="mt-3"><?php echo htmlspecialchars($notfound, ENT_QUOTES, 'UTF-8'); ?></h1>
                <div class="mt-4">
                  <a href="./" class="btn btn-primary">New Paste</a>
                </div>
              </div>
            <?php else: ?>
              <div class="card-header text-center">
                <h6><?php echo htmlspecialchars($lang['pwdprotected'] ?? 'Password Protected', ENT_QUOTES, 'UTF-8'); ?></h6>
              </div>
              <div class="card-body">
                <?php if (isset($error)): ?>
                  <div class="alert alert-danger text-center mb-3">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                  </div>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post" class="text-center">
                  <div class="form-group mb-3">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($paste_id, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="input-group w-50 mx-auto">
                      <span class="input-group-text"><i class="bi bi-unlock"></i></span>
                      <input type="text" class="form-control" name="mypass" placeholder="<?php echo htmlspecialchars($lang['enterpwd'] ?? 'Enter Password', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                  </div>
                  <button type="submit" name="submit" class="btn btn-primary">Submit</button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-2 mt-4 mt-lg-0">
          <?php require_once('theme/' . ($default_theme ?? 'default') . '/sidebar.php'); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>