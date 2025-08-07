<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE> - Default theme
 * License: GNU General Public License v3 or later
 */
?>
<div class="content">
    <div class="container-padding">
        <div class="row">
            <?php if (isset($noguests) && $noguests == "on"): ?>
                <div class="col-md-9 col-lg-10">
                    <div class="panel panel-default" style="padding-bottom: 100px;">
                        <div class="error-pages">
                            <i class="fa fa-users fa-5x" aria-hidden="true"></i>
                            <h1><?php echo htmlspecialchars($lang['guestwelcome'] ?? 'Welcome, Guest!', ENT_QUOTES, 'UTF-8'); ?></h1>
                            <p><?php echo htmlspecialchars($lang['pleaseregister'] ?? 'Please register to create pastes.', ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-md-9 col-lg-10">
                    <?php if (isset($error)): ?>
                        <div class="panel panel-danger">
                            <div class="panel-heading"><?php echo htmlspecialchars($lang['error'] ?? 'Error', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="panel-body">
                                <i class="fa fa-exclamation-circle" aria-hidden="true"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="panel panel-default">
                        <div class="panel-title"><?php echo htmlspecialchars($lang['newpaste'] ?? 'New Paste', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="panel-body">
                            <form class="form-horizontal" name="mainForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="POST">
                                <div class="form-group">
                                    <div class="col-sm-4 col-md-4 col-lg-4" style="padding-bottom:5px;">
                                        <div class="control-group">
                                            <div class="controls">
                                                <div class="input-prepend input-group">
                                                    <span class="add-on input-group-addon"><i class="fa fa-font"></i></span>
                                                    <input type="text" class="form-control" name="title" placeholder="<?php echo htmlspecialchars($lang['pastetitle'] ?? 'Paste Title', ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4 col-md-4 col-lg-4" style="margin-top:-1px; padding-bottom:2px;">
                                        <select class="selectpicker" data-live-search="true" name="format">
                                            <?php
                                            foreach ($geshiformats as $code => $name) {
                                                if (in_array($code, $popular_formats)) {
                                                    $sel = isset($_POST['format']) && $_POST['format'] == $code ? 'selected="selected"' : ($code == "markdown" ? 'selected="selected"' : '');
                                                    echo '<option ' . $sel . ' value="' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</option>';
                                                }
                                            }
                                            echo '<option value="text">-------------------------------------</option>';
                                            foreach ($geshiformats as $code => $name) {
                                                if (!in_array($code, $popular_formats)) {
                                                    $sel = isset($_POST['format']) && $_POST['format'] == $code ? 'selected="selected"' : ($code == "text" ? 'selected="selected"' : '');
                                                    echo '<option ' . $sel . ' value="' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-2 col-md-2 col-lg-2 pull-right" style="margin-top:1px; margin-right:20px">
                                        <a class="btn btn-default" onclick="highlight(document.getElementById('code')); return false;"><i class="fa fa-indent"></i>Highlight</a>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <textarea class="form-control" rows="15" id="code" name="paste_data" onkeydown="return catchTab(this,event)" placeholder="hello world"><?php echo isset($_POST['paste_data']) ? htmlspecialchars($_POST['paste_data'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label form-label pull-left" style="padding-left: 20px;"><?php echo htmlspecialchars($lang['expiration'] ?? 'Expiration', ENT_QUOTES, 'UTF-8'); ?></label>
                                    <div class="col-sm-8">
                                        <?php $post_expire = isset($_POST['paste_expire_date']) ? $_POST['paste_expire_date'] : ''; ?>
                                        <select class="selectpicker" style="display: none;" name="paste_expire_date">
                                            <option value="N" <?php echo $post_expire == "N" ? 'selected="selected"' : ''; ?>>Never</option>
                                            <option value="self" <?php echo $post_expire == "self" ? 'selected="selected"' : ''; ?>>View Once</option>
                                            <option value="10M" <?php echo $post_expire == "10M" ? 'selected="selected"' : ''; ?>>10 Minutes</option>
                                            <option value="1H" <?php echo $post_expire == "1H" ? 'selected="selected"' : ''; ?>>1 Hour</option>
                                            <option value="1D" <?php echo $post_expire == "1D" ? 'selected="selected"' : ''; ?>>1 Day</option>
                                            <option value="1W" <?php echo $post_expire == "1W" ? 'selected="selected"' : ''; ?>>1 Week</option>
                                            <option value="2W" <?php echo $post_expire == "2W" ? 'selected="selected"' : ''; ?>>2 Weeks</option>
                                            <option value="1M" <?php echo $post_expire == "1M" ? 'selected="selected"' : ''; ?>>1 Month</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label form-label pull-left" style="padding-left: 20px;"><?php echo htmlspecialchars($lang['visibility'] ?? 'Visibility', ENT_QUOTES, 'UTF-8'); ?>&nbsp;&nbsp;</label>
                                    <div class="col-sm-8">
                                        <?php $post_visibility = isset($_POST['visibility']) ? $_POST['visibility'] : ''; ?>
                                        <select class="selectpicker" style="display: none;" name="visibility">
                                            <option value="0" <?php echo $post_visibility == "0" ? 'selected="selected"' : ''; ?>>Public</option>
                                            <option value="1" <?php echo $post_visibility == "1" ? 'selected="selected"' : ''; ?>>Unlisted</option>
                                            <?php if (isset($_SESSION['token'])): ?>
                                                <option value="2" <?php echo $post_visibility == "2" ? 'selected="selected"' : ''; ?>>Private</option>
                                            <?php else: ?>
                                                <option disabled>Private (Register)</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-md-12 col-lg-3">
                                        <div class="control-group">
                                            <div class="controls">
                                                <div class="input-prepend input-group">
                                                    <span class="add-on input-group-addon"><i class="fa fa-lock"></i></span>
                                                    <input type="text" class="form-control" name="pass" id="pass" placeholder="<?php echo htmlspecialchars($lang['pwopt'] ?? 'Optional Password', ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo isset($_POST['pass']) ? htmlspecialchars($_POST['pass'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-muted"><small><?php echo htmlspecialchars($lang['encrypt'] ?? 'Encryption', ENT_QUOTES, 'UTF-8'); ?></small></p>
                                </div>
                                <?php if ($cap_e == "on" && !isset($_SESSION['username'])): ?>
                                    <?php if ($_SESSION['captcha_mode'] == "recaptcha"): ?>
                                        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($_SESSION['captcha'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                                    <?php else: ?>
                                        <div class="form-group pull-left captcha">
                                            <div class="col-md-12 col-lg-3">
                                                <div class="control-group">
                                                    <div class="controls">
                                                        <div class="input-prepend input-group">
                                                            <span class="add-on input-group-addon"><?php echo '<img src="' . htmlspecialchars($_SESSION['captcha']['image_src'] ?? '', ENT_QUOTES, 'UTF-8') . '" alt="CAPTCHA" class="imagever">'; ?></span>
                                                            <input style="height: 65px;" type="text" class="form-control" name="scode" value="" placeholder="<?php echo htmlspecialchars($lang['entercode'] ?? 'Enter CAPTCHA code', ENT_QUOTES, 'UTF-8'); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="col-md-12 col-lg-3">
                                    <div class="control-group">
                                        <div class="controls">
                                            <div class="input-prepend input-group">
                                                <input class="btn btn-default" type="submit" name="submit" id="submit" value="Paste"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/sidebar.php'); ?>
        </div>
    </div>
</div>