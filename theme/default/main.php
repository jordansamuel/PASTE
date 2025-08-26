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
 */
$captcha_mode = $_SESSION['captcha_mode'] ?? 'none'; // 'recaptcha' (v2 checkbox), 'recaptcha_v3', 'internal', 'none'
$main_sitekey = $_SESSION['captcha']       ?? '';     // sitekey for this main form (set in index during GET)
?>

<div class="container-xl my-4">
  <div class="row">
    <?php if (isset($privatesite) && $privatesite === "on"): ?>
      <div class="col-lg-12">
        <?php if (!isset($_SESSION['username'])): ?>
          <div class="card">
            <div class="card-body">
              <div class="alert alert-warning">
                <?php echo htmlspecialchars($lang['login_required'] ?? 'You must be logged in to create a paste.', ENT_QUOTES, 'UTF-8'); ?>
                <a href="<?php echo htmlspecialchars($baseurl . 'login.php', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary mt-2">Login</a>
              </div>
            </div>
          </div>
        <?php else: ?>
          <!-- Paste form (private site, logged-in user) -->
          <div class="card">
            <div class="card-header">
              <h1><?php echo htmlspecialchars($lang['newpaste'] ?? 'New Paste'); ?></h1>
            </div>
            <div class="card-body">
              <?php if (!empty($flash_error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($flash_error, ENT_QUOTES, 'UTF-8'); ?></div>
              <?php elseif (isset($_GET['error'])): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
              <?php elseif (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8'); ?></div>
              <?php elseif (isset($error)): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
              <?php endif; ?>

              <form class="form-horizontal" name="mainForm" id="mainForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="POST">
                <div class="row mb-3 g-3">
                  <div class="col-sm-4">
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-fonts"></i></span>
                      <input type="text" class="form-control" name="title" placeholder="<?php echo htmlspecialchars($lang['pastetitle'] ?? 'Paste Title'); ?>">
                    </div>
                  </div>
                  <div class="col-sm-4">
                    <select class="form-select" name="format" id="format">
                      <option value="markdown" <?php echo ($format ?? 'markdown') == 'markdown' ? 'selected' : ''; ?>>Markdown</option>
                      <?php
                      $geshiformats    = $geshiformats ?? [];
                      $popular_formats = $popular_formats ?? [];
                      foreach ($geshiformats as $code => $name) {
                        if ($code !== 'markdown' && in_array($code, $popular_formats)) {
                          $sel = ($format ?? 'markdown') == $code ? 'selected' : '';
                          echo '<option ' . $sel . ' value="' . htmlspecialchars($code) . '">' . htmlspecialchars($name) . '</option>';
                        }
                      }
                      echo '<option value="text">-------------------------------------</option>';
                      foreach ($geshiformats as $code => $name) {
                        if ($code !== 'markdown' && !in_array($code, $popular_formats)) {
                          $sel = ($format ?? 'markdown') == $code ? 'selected' : '';
                          echo '<option ' . $sel . ' value="' . htmlspecialchars($code) . '">' . htmlspecialchars($name) . '</option>';
                        }
                      }
                      ?>
                    </select>
                  </div>
                  <div class="col-sm-2 ms-auto">
                    <a class="btn btn-secondary highlight-line" href="#" title="Highlight selected lines"><i class="bi bi-text-indent-left"></i> Highlight</a>
                  </div>
                </div>

                <div class="mb-3">
                  <textarea class="form-control" rows="15" id="edit-code" name="paste_data" placeholder="hello world"><?php echo htmlspecialchars($paste_data ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label"><?php echo htmlspecialchars($lang['expiration'] ?? 'Expiration'); ?></label>
                  <div class="col-sm-10">
                    <select class="form-select" name="paste_expire_date">
                      <option value="N"   <?php echo ($paste_expire_date ?? 'N') == "N"   ? 'selected' : ''; ?>>Never</option>
                      <option value="self"<?php echo ($paste_expire_date ?? 'N') == "self"? 'selected' : ''; ?>>View Once</option>
                      <option value="10M" <?php echo ($paste_expire_date ?? 'N') == "10M" ? 'selected' : ''; ?>>10 Minutes</option>
                      <option value="1H"  <?php echo ($paste_expire_date ?? 'N') == "1H"  ? 'selected' : ''; ?>>1 Hour</option>
                      <option value="1D"  <?php echo ($paste_expire_date ?? 'N') == "1D"  ? 'selected' : ''; ?>>1 Day</option>
                      <option value="1W"  <?php echo ($paste_expire_date ?? 'N') == "1W"  ? 'selected' : ''; ?>>1 Week</option>
                      <option value="2W"  <?php echo ($paste_expire_date ?? 'N') == "2W"  ? 'selected' : ''; ?>>2 Weeks</option>
                      <option value="1M"  <?php echo ($paste_expire_date ?? 'N') == "1M"  ? 'selected' : ''; ?>>1 Month</option>
                    </select>
                  </div>
                </div>

                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label"><?php echo htmlspecialchars($lang['visibility'] ?? 'Visibility'); ?></label>
                  <div class="col-sm-10">
                    <select class="form-select" name="visibility">
                      <option value="0" <?php echo ($visibility ?? '0') == "0" ? 'selected' : ''; ?>>Public</option>
                      <option value="1" <?php echo ($visibility ?? '0') == "1" ? 'selected' : ''; ?>>Unlisted</option>
                      <option value="2" <?php echo ($visibility ?? '0') == "2" ? 'selected' : ''; ?>>Private</option>
                    </select>
                  </div>
                </div>

                <div class="mb-3">
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="text" class="form-control" name="pass" id="pass" placeholder="<?php echo htmlspecialchars($lang['pwopt'] ?? 'Optional Password'); ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <p class="text-muted"><small><?php echo htmlspecialchars($lang['encrypt'] ?? 'Encryption', ENT_QUOTES, 'UTF-8'); ?></small></p>
                </div>

                <?php if ($cap_e == "on" && !isset($_SESSION['username']) && (!isset($disableguest) || $disableguest !== "on")): ?>
                  <?php if ($captcha_mode === "recaptcha"): ?>
                    <!-- reCAPTCHA v2 checkbox -->
                    <div class="g-recaptcha mb-3"
                         data-sitekey="<?php echo htmlspecialchars($main_sitekey, ENT_QUOTES, 'UTF-8'); ?>"
                         data-callback="onRecaptchaSuccess"></div>
                    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                  <?php elseif ($captcha_mode === "recaptcha_v3"): ?>
                    <!-- v3: hidden field only; token populated by footer -->
                    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                  <?php else: ?>
                    <!-- Internal CAPTCHA -->
                    <div class="row mb-3">
                      <?php echo '<img src="' . htmlspecialchars($_SESSION['captcha']['image_src'] ?? '', ENT_QUOTES, 'UTF-8') . '" alt="CAPTCHA" class="imagever">'; ?>
                      <input style="height: 65px;" type="text" class="form-control" name="scode" value="" placeholder="<?php echo htmlspecialchars($lang['entercode'] ?? 'Enter CAPTCHA code', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                  <?php endif; ?>
                <?php endif; ?>

                <div class="row mb-3">
                  <div class="d-grid gap-2">
                    <!-- Keep the name="submit" if your PHP expects it; JS should use HTMLFormElement.prototype.submit -->
                    <input class="btn btn-primary paste-button" type="submit" id="submit" data-recaptcha-action="create_paste" value="<?php echo htmlspecialchars($lang['createpaste'] ?? 'Paste'); ?>">
                  </div>
                </div>
              </form>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div class="sidebar-below<?php echo (isset($privatesite) && $privatesite === 'on') ? ' sidebar-below' : ''; ?>">
        <?php
        $__sidebar = __DIR__ . '/sidebar.php';
        if (is_file($__sidebar)) { include $__sidebar; }
        ?>
      </div>

    <?php else: ?>
      <!-- Non-private site: Main content + sidebar -->
      <div class="col-lg-10">
        <?php if (!isset($_SESSION['username']) && (!isset($privatesite) || $privatesite != "on")): ?>
          <div class="card guest-welcome text-center">
            <div class="btn-group" role="group" aria-label="Login or Register">
              <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#signin">Login</a>
              <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#signup">Register</a>
            </div>
          </div>
        <?php endif; ?>

        <?php if (!isset($_SESSION['username']) && (isset($disableguest) && $disableguest === "on")): ?>
          <div class="card">
            <div class="card-body">
              <div class="alert alert-warning">
                <?php echo htmlspecialchars($lang['login_required'] ?? 'You must be logged in to create a paste.', ENT_QUOTES, 'UTF-8'); ?>
                <a href="<?php echo htmlspecialchars($baseurl . 'login.php', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary mt-2">Login</a>
              </div>
            </div>
          </div>
        <?php else: ?>
          <!-- Paste form (public site) -->
          <div class="card">
            <div class="card-header">
              <h1><?php echo htmlspecialchars($lang['newpaste'] ?? 'New Paste'); ?></h1>
            </div>
            <div class="card-body">
              <?php if (!empty($flash_error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($flash_error, ENT_QUOTES, 'UTF-8'); ?></div>
              <?php elseif (isset($_GET['error'])): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
              <?php elseif (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8'); ?></div>
              <?php elseif (isset($error)): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
              <?php endif; ?>

              <form class="form-horizontal" name="mainForm" id="mainForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="POST">
                <div class="row mb-3 g-3">
                  <div class="col-sm-4">
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-fonts"></i></span>
                      <input type="text" class="form-control" name="title" placeholder="<?php echo htmlspecialchars($lang['pastetitle'] ?? 'Paste Title'); ?>">
                    </div>
                  </div>
                  <div class="col-sm-4">
                    <select class="form-select" name="format" id="format">
                      <option value="markdown" <?php echo ($format ?? 'markdown') == 'markdown' ? 'selected' : ''; ?>>Markdown</option>
                      <?php
                      $geshiformats    = $geshiformats ?? [];
                      $popular_formats = $popular_formats ?? [];
                      foreach ($geshiformats as $code => $name) {
                        if ($code !== 'markdown' && in_array($code, $popular_formats)) {
                          $sel = ($format ?? 'markdown') == $code ? 'selected' : '';
                          echo '<option ' . $sel . ' value="' . htmlspecialchars($code) . '">' . htmlspecialchars($name) . '</option>';
                        }
                      }
                      echo '<option value="text">-------------------------------------</option>';
                      foreach ($geshiformats as $code => $name) {
                        if ($code !== 'markdown' && !in_array($code, $popular_formats)) {
                          $sel = ($format ?? 'markdown') == $code ? 'selected' : '';
                          echo '<option ' . $sel . ' value="' . htmlspecialchars($code) . '">' . htmlspecialchars($name) . '</option>';
                        }
                      }
                      ?>
                    </select>
                  </div>
                  <div class="col-sm-2 ms-auto">
                    <a class="btn btn-secondary highlight-line" href="#" title="Highlight selected lines"><i class="bi bi-text-indent-left"></i> Highlight</a>
                  </div>
                </div>

                <div class="mb-3">
                  <textarea class="form-control" rows="15" id="edit-code" name="paste_data" placeholder="hello world"><?php echo htmlspecialchars($paste_data ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label"><?php echo htmlspecialchars($lang['expiration'] ?? 'Expiration'); ?></label>
                  <div class="col-sm-10">
                    <select class="form-select" name="paste_expire_date">
                      <option value="N"   <?php echo ($paste_expire_date ?? 'N') == "N"   ? 'selected' : ''; ?>>Never</option>
                      <option value="self"<?php echo ($paste_expire_date ?? 'N') == "self"? 'selected' : ''; ?>>View Once</option>
                      <option value="10M" <?php echo ($paste_expire_date ?? 'N') == "10M" ? 'selected' : ''; ?>>10 Minutes</option>
                      <option value="1H"  <?php echo ($paste_expire_date ?? 'N') == "1H"  ? 'selected' : ''; ?>>1 Hour</option>
                      <option value="1D"  <?php echo ($paste_expire_date ?? 'N') == "1D"  ? 'selected' : ''; ?>>1 Day</option>
                      <option value="1W"  <?php echo ($paste_expire_date ?? 'N') == "1W"  ? 'selected' : ''; ?>>1 Week</option>
                      <option value="2W"  <?php echo ($paste_expire_date ?? 'N') == "2W"  ? 'selected' : ''; ?>>2 Weeks</option>
                      <option value="1M"  <?php echo ($paste_expire_date ?? 'N') == "1M"  ? 'selected' : ''; ?>>1 Month</option>
                    </select>
                  </div>
                </div>

                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label"><?php echo htmlspecialchars($lang['visibility'] ?? 'Visibility'); ?></label>
                  <div class="col-sm-10">
                    <select class="form-select" name="visibility">
                      <option value="0" <?php echo ($visibility ?? '0') == "0" ? 'selected' : ''; ?>>Public</option>
                      <option value="1" <?php echo ($visibility ?? '0') == "1" ? 'selected' : ''; ?>>Unlisted</option>
                      <option value="2" <?php echo ($visibility ?? '0') == "2" ? 'selected' : ''; ?>>Private</option>
                    </select>
                  </div>
                </div>

                <div class="mb-3">
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="text" class="form-control" name="pass" id="pass" placeholder="<?php echo htmlspecialchars($lang['pwopt'] ?? 'Optional Password'); ?>">
                  </div>
                </div>

                <div class="row mb-3">
                  <p class="text-muted"><small><?php echo htmlspecialchars($lang['encrypt'] ?? 'Encryption', ENT_QUOTES, 'UTF-8'); ?></small></p>
                </div>

                <?php if ($cap_e == "on" && !isset($_SESSION['username']) && (!isset($disableguest) || $disableguest !== "on")): ?>
                  <?php if ($captcha_mode === "recaptcha"): ?>
                    <!-- reCAPTCHA v2 checkbox -->
                    <div class="g-recaptcha mb-3"
                         data-sitekey="<?php echo htmlspecialchars($main_sitekey, ENT_QUOTES, 'UTF-8'); ?>"
                         data-callback="onRecaptchaSuccess"></div>
                    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                  <?php elseif ($captcha_mode === "recaptcha_v3"): ?>
                    <!-- v3: hidden field only -->
                    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                  <?php else: ?>
                    <!-- Internal CAPTCHA -->
                    <div class="row mb-3">
                      <?php echo '<img src="' . htmlspecialchars($_SESSION['captcha']['image_src'] ?? '', ENT_QUOTES, 'UTF-8') . '" alt="CAPTCHA" class="imagever">'; ?>
                      <input style="height: 65px;" type="text" class="form-control" name="scode" value="" placeholder="<?php echo htmlspecialchars($lang['entercode'] ?? 'Enter CAPTCHA code', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                  <?php endif; ?>
                <?php endif; ?>

                <div class="row mb-3">
                  <div class="d-grid gap-2">
                    <input class="btn btn-primary paste-button" type="submit" id="submit" data-recaptcha-action="create_paste" value="<?php echo htmlspecialchars($lang['createpaste'] ?? 'Paste'); ?>">
                  </div>
                </div>
              </form>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div class="col-lg-2 mt-4 mt-lg-0">
        <?php
        $__sidebar = __DIR__ . '/sidebar.php';
        if (is_file($__sidebar)) { include $__sidebar; }
        ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Safe, idempotent v2 callback (no grecaptcha usage here) -->
<script>
(function() {
  function setToken(id, token) {
    var el = document.getElementById(id);
    if (el) { el.value = token; }
  }
  window.onRecaptchaSuccess = window.onRecaptchaSuccess || function(token) {
    setToken('g-recaptcha-response', token);
  };
})();
</script>
