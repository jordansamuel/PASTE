<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE> - Default theme
 * Licensed under the GNU General Public License, version 3 or later.
 */
$protocol = $protocol ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://');
?>
<?php require_once('theme/' . ($default_theme ?? 'default') . '/header.php'); ?>
<style>
/* Custom styles for dark theme and spacing */
.g-recaptcha {
    margin-bottom: 1.5rem; /* Add spacing below reCAPTCHA */
}
.paste-button {
    margin-top: 1.5rem; /* Add spacing above submit button */
}
.card {
    background-color: #1a1a1a; /* Dark background for card */
    color: #ffffff; /* Light text for dark theme */
}
.form-control, .form-select, .input-group-text {
    background-color: #2a2a2a; /* Dark input background */
    color: #ffffff; /* Light text */
    border-color: #444444; /* Darker border */
}
.form-control::placeholder {
    color: #aaaaaa; /* Lighter placeholder text */
}
.btn-primary {
    background-color: #007bff; /* Primary button color */
    border-color: #007bff;
}
.btn-primary:hover {
    background-color: #0056b3;
    border-color: #0056b3;
}
.alert-warning {
    background-color: #4a3c1c; /* Darker warning alert */
    color: #ffffff;
    border-color: #664d1e;
}
<?php if (isset($privatesite) && $privatesite === "on"): ?>
/* Ensure proper spacing when sidebar is below main content */
.sidebar-below {
    margin-top: 1.5rem; /* Add spacing between main content and sidebar */
}
<?php endif; ?>
</style>
<div class="container-xl my-4">
    <div class="row">
        <?php if (isset($privatesite) && $privatesite === "on"): ?>
            <!-- Private site: Main content full width, sidebar below -->
            <div class="col-lg-12">
                <?php if (!isset($_SESSION['username'])): ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <?php echo htmlspecialchars($lang['login_required'] ?? 'You must be logged in to create a paste.', ENT_QUOTES, 'UTF-8'); ?>
                                <a href="<?php echo htmlspecialchars($baseurl . '/login.php', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary mt-2">Login</a>
                            </div>
                        </div>
                    </div>
                <?php elseif (isset($disableguest) && $disableguest === "on" && !isset($_SESSION['username'])): ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <?php echo htmlspecialchars($lang['login_required'] ?? 'You must be logged in to create a paste.', ENT_QUOTES, 'UTF-8'); ?>
                                <a href="<?php echo htmlspecialchars($baseurl . '/login.php', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary mt-2">Login</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h1><?php echo htmlspecialchars($lang['newpaste'] ?? 'New Paste'); ?></h1>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-warning"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                            <form class="form-horizontal" name="mainForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="POST">
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
                                            $geshiformats = $geshiformats ?? [];
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
                                            <option value="N" <?php echo ($paste_expire_date ?? 'N') == "N" ? 'selected' : ''; ?>>Never</option>
                                            <option value="self" <?php echo ($paste_expire_date ?? 'N') == "self" ? 'selected' : ''; ?>>View Once</option>
                                            <option value="10M" <?php echo ($paste_expire_date ?? 'N') == "10M" ? 'selected' : ''; ?>>10 Minutes</option>
                                            <option value="1H" <?php echo ($paste_expire_date ?? 'N') == "1H" ? 'selected' : ''; ?>>1 Hour</option>
                                            <option value="1D" <?php echo ($paste_expire_date ?? 'N') == "1D" ? 'selected' : ''; ?>>1 Day</option>
                                            <option value="1W" <?php echo ($paste_expire_date ?? 'N') == "1W" ? 'selected' : ''; ?>>1 Week</option>
                                            <option value="2W" <?php echo ($paste_expire_date ?? 'N') == "2W" ? 'selected' : ''; ?>>2 Weeks</option>
                                            <option value="1M" <?php echo ($paste_expire_date ?? 'N') == "1M" ? 'selected' : ''; ?>>1 Month</option>
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
                                    <?php if ($_SESSION['captcha_mode'] == "recaptcha"): ?>
                                        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($_SESSION['captcha'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-theme="dark"></div>
                                    <?php else: ?>
                                        <div class="row mb-3">
                                            <?php echo '<img src="' . htmlspecialchars($_SESSION['captcha']['image_src'] ?? '', ENT_QUOTES, 'UTF-8') . '" alt="CAPTCHA" class="imagever">'; ?>
                                            <input style="height: 65px;" type="text" class="form-control" name="scode" value="" placeholder="<?php echo htmlspecialchars($lang['entercode'] ?? 'Enter CAPTCHA code', ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="row mb-3">
                                    <div class="d-grid gap-2">
                                        <input class="btn btn-primary paste-button" type="submit" name="submit" id="submit" value="<?php echo htmlspecialchars($lang['createpaste'] ?? 'Paste'); ?>"/>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="sidebar-below<?php echo (isset($privatesite) && $privatesite === 'on') ? ' sidebar-below' : ''; ?>">
                <?php require_once('theme/' . ($default_theme ?? 'default') . '/sidebar.php'); ?>
            </div>
        <?php else: ?>
            <!-- Non-private site: Main content and sidebar side by side -->
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
                                <a href="<?php echo htmlspecialchars($baseurl . '/login.php', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary mt-2">Login</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h1><?php echo htmlspecialchars($lang['newpaste'] ?? 'New Paste'); ?></h1>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-warning"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                            <form class="form-horizontal" name="mainForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="POST">
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
                                            $geshiformats = $geshiformats ?? [];
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
                                            <option value="N" <?php echo ($paste_expire_date ?? 'N') == "N" ? 'selected' : ''; ?>>Never</option>
                                            <option value="self" <?php echo ($paste_expire_date ?? 'N') == "self" ? 'selected' : ''; ?>>View Once</option>
                                            <option value="10M" <?php echo ($paste_expire_date ?? 'N') == "10M" ? 'selected' : ''; ?>>10 Minutes</option>
                                            <option value="1H" <?php echo ($paste_expire_date ?? 'N') == "1H" ? 'selected' : ''; ?>>1 Hour</option>
                                            <option value="1D" <?php echo ($paste_expire_date ?? 'N') == "1D" ? 'selected' : ''; ?>>1 Day</option>
                                            <option value="1W" <?php echo ($paste_expire_date ?? 'N') == "1W" ? 'selected' : ''; ?>>1 Week</option>
                                            <option value="2W" <?php echo ($paste_expire_date ?? 'N') == "2W" ? 'selected' : ''; ?>>2 Weeks</option>
                                            <option value="1M" <?php echo ($paste_expire_date ?? 'N') == "1M" ? 'selected' : ''; ?>>1 Month</option>
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
                                    <?php if ($_SESSION['captcha_mode'] == "recaptcha"): ?>
                                        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($_SESSION['captcha'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-theme="dark"></div>
                                    <?php else: ?>
                                        <div class="row mb-3">
                                            <?php echo '<img src="' . htmlspecialchars($_SESSION['captcha']['image_src'] ?? '', ENT_QUOTES, 'UTF-8') . '" alt="CAPTCHA" class="imagever">'; ?>
                                            <input style="height: 65px;" type="text" class="form-control" name="scode" value="" placeholder="<?php echo htmlspecialchars($lang['entercode'] ?? 'Enter CAPTCHA code', ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="row mb-3">
                                    <div class="d-grid gap-2">
                                        <input class="btn btn-primary paste-button" type="submit" name="submit" id="submit" value="<?php echo htmlspecialchars($lang['createpaste'] ?? 'Paste'); ?>"/>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-2 mt-4 mt-lg-0">
                <?php require_once('theme/' . ($default_theme ?? 'default') . '/sidebar.php'); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('paste.php DOMContentLoaded fired');
    const toggleLineNumbersBtn = document.querySelector('.toggle-line-numbers');
    const toggleFullscreenBtn = document.querySelector('.toggle-fullscreen');
    const copyClipboardBtn = document.querySelector('.copy-clipboard');
    const embedToolBtn = document.querySelector('.embed-tool');
    const highlightLineBtn = document.querySelector('.highlight-line');

    if (toggleLineNumbersBtn) {
        toggleLineNumbersBtn.addEventListener('click', function(e) {
            console.log('Toggle Line Numbers button clicked');
            e.preventDefault();
            window.togglev();
        });
    }
    if (toggleFullscreenBtn) {
        toggleFullscreenBtn.addEventListener('click', function(e) {
            console.log('Toggle Fullscreen button clicked');
            e.preventDefault();
            window.toggleFullScreen();
        });
    }
    if (copyClipboardBtn) {
        copyClipboardBtn.addEventListener('click', function(e) {
            console.log('Copy to Clipboard button clicked');
            e.preventDefault();
            window.copyToClipboard();
        });
    }
    if (embedToolBtn) {
        embedToolBtn.addEventListener('click', function(e) {
            console.log('Embed Tool button clicked');
            e.preventDefault();
            window.showEmbedCode();
        });
    }
    if (highlightLineBtn) {
        highlightLineBtn.addEventListener('click', function(e) {
            console.log('Highlight Line button clicked');
            e.preventDefault();
            window.highlightLine(e);
        });
    }
});
</script>
<?php require_once('theme/' . ($default_theme ?? 'default') . '/footer.php'); ?>