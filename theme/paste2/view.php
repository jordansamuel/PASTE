<?php
/*
 * Paste 3 Default theme <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
$protocol = $protocol ?? paste_protocol(); // Ensure $protocol is set
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
                        <span class="badge"><i class="fa fa-code fa-lg" aria-hidden="true"></i> <?php echo htmlspecialchars(strtoupper($p_code ?? 'TEXT')); ?></span>
                        <span class="badge"><i class="fa fa-eye fa-lg" aria-hidden="true"></i> <?php echo htmlspecialchars((string) ($p_views ?? 0)); ?></span>
                        <h6 style="text-align: center;"><?php echo ucfirst(htmlspecialchars($p_title ?? 'Untitled')); ?>
                            <small>
                                <?php 
                                $p_member_display = $p_member ?? 'Guest';
                                if ($p_member_display === 'Guest') {
                                    echo 'Guest';
                                } else {
                                    $user_link = $mod_rewrite ?? false 
                                        ? htmlspecialchars($protocol . $baseurl . '/user/' . $p_member_display) 
                                        : htmlspecialchars($protocol . $baseurl . '/user.php?user=' . $p_member_display);
                                    echo 'By <a href="' . $user_link . '">' . htmlspecialchars($p_member_display) . '</a>';
                                }
                                ?> on <?php echo htmlspecialchars($p_date ?? date('Y-m-d H:i:s')); ?>
                            </small>
                        </h6>
                        <ul class="panel-tools">
                            <?php if (($p_code ?? 'text') !== "markdown") { ?>
                                <li><a class="icon" href="javascript:togglev();"><i class="fa fa-list-ol fa-lg" title="Toggle Line Numbers"></i></a></li>
                            <?php } ?>
                            <li><a class="icon" href="#" onmouseover="selectText('paste');"><i class="fa fa-clipboard fa-lg" title="Select Text"></i></a></li>
                            <li><a class="icon" href="<?php echo htmlspecialchars($p_raw ?? ($protocol . $baseurl . '/raw.php?id=' . ($paste_id ?? ''))); ?>"><i class="fa fa-file-text-o fa-lg" title="View Raw"></i></a></li>
                            <li><a class="icon" href="<?php echo htmlspecialchars($p_download ?? ($protocol . $baseurl . '/download.php?id=' . ($paste_id ?? ''))); ?>"><i class="fa fa-download fa-lg" title="Download Paste"></i></a></li>
                            <li><a class="icon embed-tool"><i class="fa fa-file-code-o fa-lg" title="Embed This Paste"></i></a></li>
                            <li><a class="icon expand-tool"><i class="fa fa-expand fa-lg" title="Full Screen"></i></a></li>
                        </ul>
                    </div>

                    <div class="panel-embed col-xs-3" style="display:none; float:right;">
                        <input type="text" class="form-control" value='<?php echo htmlspecialchars('<script src="' . ($protocol ?? '') . ($baseurl ?? '') . '/' . (($mod_rewrite ?? false) ? 'embed/' : 'paste.php?embed&id=') . ($paste_id ?? '') . '"></script>'); ?>' readonly>
                    </div>
                    <div class="clear" style="clear:both;"></div>

                    <div class="panel-body" style="display: block;">
                        <?php if (isset($error)) {
                            echo '<div class="paste-alert alert6">' . htmlspecialchars($error ?? '') . '</div>'; 
                        } else {
                            echo '<div id="paste">' . $p_content . '</div>';
                        } ?>
                    </div>
                </div>
            </div>
            <!-- End Panel -->
            <?php require_once('theme/' . ($default_theme ?? 'default') . '/sidebar.php'); ?>
            <?php echo $ads_2 ?? ''; ?>
        </div>

        <div class="row">
            <!-- Guests -->
            <?php if (!isset($_SESSION['username'])) { // Site permissions ?>
            <div class="col-md-12 col-lg-12">
                <div class="panel panel-default" style="padding-bottom: 100px;">
                    <div class="panel-title">
                        <?php echo htmlspecialchars($lang['rawpaste'] ?? 'Raw Paste'); ?>
                    </div>
                    <div class="panel-body">
                        <!-- Raw data -->
                        <div class="form-group">
                            <div class="col-md-12">
                                <textarea class="form-control" rows="15" id="code" name="paste_data" onkeydown="return catchTab(this,event)" placeholder="helloworld"><?php echo htmlspecialchars($op_content ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="error-pages">
                        <p><?php echo $lang['registertoedit']; ?></p>
                    </div>
                </div>
            </div>
            <?php } else { ?>

            <!-- Paste Panel -->
            <div class="col-md-12 col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-title">
                        <?php echo htmlspecialchars($lang['modpaste'] ?? 'Modify Paste'); ?>
                    </div>
                    
                    <div class="panel-body">
                        <form class="form-horizontal" name="mainForm" action="index.php" method="POST">
                            <div class="form-group">
                                <!-- Title -->
                                <div class="col-sm-4 col-md-4 col-lg-4" style="padding-bottom:5px;">
                                    <div class="control-group">
                                        <div class="controls">
                                            <div class="input-prepend input-group">
                                                <span class="add-on input-group-addon"><i class="fa fa-font"></i></span>
                                                <input type="text" class="form-control" name="title" placeholder="<?php echo htmlspecialchars($lang['pastetitle'] ?? 'Paste Title'); ?>" value="<?php echo htmlspecialchars(ucfirst($p_title ?? 'Untitled')); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                  
                                <!-- Format -->
                                <div class="col-sm-4 col-md-4 col-lg-4" style="margin-top:-1px; padding-bottom:2px;">
                                    <select class="selectpicker" data-live-search="true" name="format">
                                        <?php 
                                        $geshiformats = $geshiformats ?? [];
                                        $popular_formats = $popular_formats ?? [];
                                        // Show popular GeSHi formats
                                        foreach ($geshiformats as $code => $name) {
                                            if (in_array($code, $popular_formats)) {
                                                $sel = ($p_code ?? 'text') == $code ? 'selected="selected"' : '';
                                                echo '<option ' . $sel . ' value="' . htmlspecialchars($code) . '">' . htmlspecialchars($name) . '</option>';
                                            }
                                        }

                                        echo '<option value="text">-------------------------------------</option>';

                                        // Show all GeSHi formats.
                                        foreach ($geshiformats as $code => $name) {
                                            if (!in_array($code, $popular_formats)) {
                                                $sel = ($p_code ?? 'text') == $code ? 'selected="selected"' : '';
                                                echo '<option ' . $sel . ' value="' . htmlspecialchars($code) . '">' . htmlspecialchars($name) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                    
                                <!-- Buttons -->
                                <div class="col-sm-2 col-md-2 col-lg-2 pull-right" style="margin-top:1px; margin-right:20px">
                                    <a class="btn btn-default" onclick="highlight(document.getElementById('code')); return false;"><i class="fa fa-indent"></i>Highlight</a>
                                </div>
                            </div>

                            <!-- Text area -->
                            <div class="form-group">
                                <div class="col-md-12">
                                    <textarea class="form-control" rows="15" id="code" name="paste_data" onkeydown="return catchTab(this,event)" placeholder="helloworld"><?php echo htmlspecialchars($op_content ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                            </div>

                            <!-- Expiry -->
                            <div class="form-group">
                                <label class="control-label form-label pull-left" style="padding-left: 20px;"><?php echo htmlspecialchars($lang['expiration'] ?? 'Expiration'); ?></label>
                                <div class="col-sm-8">
                                    <select class="selectpicker" style="display: none;" name="paste_expire_date">
                                        <option value="N" selected="selected">Never</option>
                                        <option value="self">View Once</option>
                                        <option value="10M">10 Minutes</option>
                                        <option value="1H">1 Hour</option>
                                        <option value="1D">1 Day</option>
                                        <option value="1W">1 Week</option>
                                        <option value="2W">2 Weeks</option>
                                        <option value="1M">1 Month</option>
                                    </select>
                                </div>
                            </div>
                    
                            <!-- Visibility -->
                            <div class="form-group">
                                <label class="control-label form-label pull-left" style="padding-left: 20px;"><?php echo htmlspecialchars($lang['visibility'] ?? 'Visibility'); ?>&nbsp;&nbsp;</label>
                                <div class="col-sm-8">
                                    <select class="selectpicker" style="display: none;" name="visibility">
                                        <option value="0" <?php echo ($p_visible ?? '0') == "0" ? 'selected="selected"' : ''; ?>>Public</option>
                                        <option value="1" <?php echo ($p_visible ?? '0') == "1" ? 'selected="selected"' : ''; ?>>Unlisted</option>
                                        <?php if (isset($_SESSION['token'])) { ?>
                                        <option value="2" <?php echo ($p_visible ?? '0') == "2" ? 'selected="selected"' : ''; ?>>Private</option>
                                        <?php } else { ?>
                                        <option disabled>Private (Register)</option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                      
                            <!-- Password -->
                            <div class="form-group">
                                <div class="col-md-12 col-lg-3">
                                    <div class="control-group">
                                        <div class="controls">
                                            <div class="input-prepend input-group">
                                                <span class="add-on input-group-addon"><i class="fa fa-lock"></i></span>
                                                <input type="text" class="form-control" name="pass" id="pass" value="" placeholder="<?php echo htmlspecialchars($lang['pwopt'] ?? 'Optional Password'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                              
                            <div class="col-md-12 col-lg-3">
                                <div class="control-group">
                                    <div class="controls">
                                        <div class="input-prepend input-group">
                                            <input type="hidden" name="paste_id" value="<?php echo htmlspecialchars($paste_id ?? ''); ?>" />
                                            <?php // Only the paste owner can edit their own pastes. Everyone else can fork this paste
                                            if (isset($_SESSION['username']) && $_SESSION['username'] == ($p_member ?? 'Guest')) { ?>
                                                <input class="btn btn-default" type="submit" name="edit" id="edit" value="<?php echo htmlspecialchars($lang['editpaste'] ?? 'Edit Paste'); ?>"/>&nbsp;
                                            <?php } ?>
                                            <input class="btn btn-default" type="submit" name="submit" id="submit" value="<?php echo htmlspecialchars($lang['forkpaste'] ?? 'Fork Paste'); ?>"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>   
                    </div>
                </div>
            </div>
            <!-- End Panel -->
            <?php } ?>
        </div>
    </div>
</div>