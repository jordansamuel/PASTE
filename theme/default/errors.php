<?php
/*
 * Paste $v3.1 2025/08/16 https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 *
 * https://phpaste.sourceforge.io/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in LICENCE for more details.
 *
 * ----------------------------------------------------------------------
 * Expected context variables
 * $error (string)            - optional human message
 * $notfound (string)         - optional message for 404/expired/private
 * $require_password (bool)   - when TRUE, show the password form
 * $paste_id (int)            - used by the password form
 * $baseurl (string), $default_theme (string), $lang (array)
 * $privatesite (optional: "on"), $mod_rewrite (string "1"/"0")
 */
$show_pw = isset($require_password) && $require_password === true;

// Build rewrite-aware action for the password form
$pw_action_url = '';
if ($show_pw && isset($paste_id) && $paste_id !== '') {
    if (isset($mod_rewrite) && $mod_rewrite == '1') {
        $pw_action_url = rtrim((string)$baseurl, '/') . '/' . rawurlencode((string)$paste_id);
    } else {
        $pw_action_url = rtrim((string)$baseurl, '/') . '/paste.php?id=' . rawurlencode((string)$paste_id);
    }
}

// Resolve message to show
$generic_msg = $notfound ?? ($error ?? ($error_msg ?? ($lang['error'] ?? 'An error occurred.')));
?>
<style>
<?php if (isset($privatesite) && $privatesite === "on"): ?>
.sidebar-below { margin-top: 1.5rem; }
<?php endif; ?>
</style>

<div class="content">
  <div class="container-xl my-4">
    <div class="row">

      <?php if (isset($privatesite) && $privatesite === "on"): ?>

        <div class="col-lg-12">
          <div class="card">
            <?php if (!$show_pw): ?>
              <div class="card-body text-center">
                <i class="bi bi-exclamation-circle" style="font-size: 5rem; color: #0d6efd;"></i>
                <h1 class="mt-3"><?php echo htmlspecialchars($generic_msg, ENT_QUOTES, 'UTF-8'); ?></h1>
                <div class="mt-4">
                  <a href="<?php echo htmlspecialchars($baseurl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
                    <?php echo htmlspecialchars($lang['newpaste'] ?? 'New Paste', ENT_QUOTES, 'UTF-8'); ?>
                  </a>
                </div>
              </div>
            <?php else: ?>
              <div class="card-header text-center">
                <h6><?php echo htmlspecialchars($lang['pwdprotected'] ?? 'Password Protected', ENT_QUOTES, 'UTF-8'); ?></h6>
              </div>
              <?php if (!empty($error)): ?>
                <div class="alert alert-dark text-center mb-0 rounded-0">
                  <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
              <?php endif; ?>
              <div class="card-body">
                <form action="<?php echo htmlspecialchars($pw_action_url ?: $_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post" class="text-center">
                  <input type="hidden" name="id" value="<?php echo isset($paste_id) ? htmlspecialchars((string)$paste_id, ENT_QUOTES, 'UTF-8') : ''; ?>">
                  <div class="input-group w-50 mx-auto mb-3">
                    <span class="input-group-text"><i class="bi bi-unlock"></i></span>
                    <input type="text" class="form-control" name="mypass" placeholder="<?php echo htmlspecialchars($lang['enterpwd'] ?? 'Enter Password', ENT_QUOTES, 'UTF-8'); ?>">
                  </div>
                  <button type="submit" name="submit" class="btn btn-primary">
                    <?php echo htmlspecialchars($lang['submit'] ?? 'Submit', ENT_QUOTES, 'UTF-8'); ?>
                  </button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="sidebar-below">
          <?php
          $__sidebar = __DIR__ . '/sidebar.php';
          if (is_file($__sidebar)) { include $__sidebar; }
          ?>
        </div>

      <?php else: ?>

        <div class="col-lg-12">
          <div class="card">
            <?php if (!$show_pw): ?>
              <div class="card-body text-center">
                <i class="bi bi-exclamation-circle" style="font-size: 5rem; color: #0d6efd;"></i>
                <p class="mt-3"><?php echo htmlspecialchars($generic_msg, ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="mt-4">
                  <a href="<?php echo htmlspecialchars($baseurl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
                    <?php echo htmlspecialchars($lang['newpaste'] ?? 'New Paste', ENT_QUOTES, 'UTF-8'); ?>
                  </a>
                </div>
              </div>
            <?php else: ?>
              <div class="card-header text-center">
                <h6><?php echo htmlspecialchars($lang['pwdprotected'] ?? 'Password Protected', ENT_QUOTES, 'UTF-8'); ?></h6>
              </div>
              <?php if (!empty($error)): ?>
                <div class="card-body text-center">
                  <div class="alert alert-dark text-center mb-0 rounded-0">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                  </div>
                </div>
              <?php endif; ?>
              <div class="card-body">
                <form action="<?php echo htmlspecialchars($pw_action_url ?: $_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post" class="text-center">
                  <input type="hidden" name="id" value="<?php echo isset($paste_id) ? htmlspecialchars((string)$paste_id, ENT_QUOTES, 'UTF-8') : ''; ?>">
                  <div class="input-group w-50 mx-auto mb-3">
                    <span class="input-group-text"><i class="bi bi-unlock"></i></span>
                    <input type="text" class="form-control" name="mypass" placeholder="<?php echo htmlspecialchars($lang['enterpwd'] ?? 'Enter Password', ENT_QUOTES, 'UTF-8'); ?>">
                  </div>
                  <button type="submit" name="submit" class="btn btn-primary">
                    <?php echo htmlspecialchars($lang['submit'] ?? 'Submit', ENT_QUOTES, 'UTF-8'); ?>
                  </button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        </div>

      <?php endif; ?>

    </div>
  </div>
</div>