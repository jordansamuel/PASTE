<?php
/*
 * Paste 3 default theme <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
$protocol = paste_protocol();
?>

<div class="content">
<!-- START CONTAINER -->
<div class="container-padding">
  <!-- Start Row -->
  <div class="row">
    <!-- Start Panel -->
<?php if ($privatesite == "on") { // Site permissions ?>
    <div class="col-md-12">
        <div class="panel panel-default" style="padding-bottom: 100px;">
            <div class="error-pages">
                <i class="fa fa-lock fa-5x" aria-hidden="true"></i>
                <h1><?php echo htmlspecialchars($lang['siteprivate']); ?></h1>
            </div>
        </div>
    </div>
    
<?php } else { ?>
    
    <div class="col-md-9 col-lg-10">
      <div class="panel panel-default">
        <div class="panel-title">
          <?php echo htmlspecialchars($lang['archives']); ?>
        </div>
        <div class="panel-body table-responsive">
        
            <table id="archive" class="table display">
                <thead>
                    <tr>
                        <th><?php echo htmlspecialchars($lang['pastetitle']); ?></th>
                        <th><?php echo htmlspecialchars($lang['pastetime']); ?></th>
                        <th><?php echo htmlspecialchars($lang['pastesyntax']); ?></th>
                    </tr>
                </thead>
             
                <tfoot>
                    <tr>
                        <th><?php echo htmlspecialchars($lang['pastetitle']); ?></th>
                        <th><?php echo htmlspecialchars($lang['pastetime']); ?></th>
                        <th><?php echo htmlspecialchars($lang['pastesyntax']); ?></th>
                    </tr>
                </tfoot>
         
                <tbody>
                <?php
                $res = getRecent($pdo, 100);
                foreach ($res as $row) {
                    $title = trim($row['title']);
                    $p_id = trim($row['id']);
                    $p_code = trim($row['code']);
                    $p_date = trim($row['date']);
                    $p_time = trim($row['now_time']);
                    $nowtime = time();
                    $oldtime = $p_time;
                    $p_time = conTime($nowtime - $oldtime);
                    $title = truncate($title, 20, 50);
                    if ($mod_rewrite == '1') {
                        echo '<tr> 
                            <td><a href="' . htmlspecialchars($p_id) . '" title="' . htmlspecialchars($title) . '">' . ucfirst(htmlspecialchars($title)) . '</a></td>    
                            <td>' . htmlspecialchars($p_time) . '</td>
                            <td>' . htmlspecialchars(strtoupper($p_code)) . '</td>
                        </tr>';
                    } else {
                        echo '<tr> 
                            <td><a href="paste.php?id=' . htmlspecialchars($p_id) . '" title="' . htmlspecialchars($title) . '">' . ucfirst(htmlspecialchars($title)) . '</a></td>    
                            <td>' . htmlspecialchars($p_time) . '</td>
                            <td>' . htmlspecialchars(strtoupper($p_code)) . '</td>
                        </tr>';
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
      </div>
    </div>
    <!-- End Panel -->
<?php } if ($privatesite == "on") { // Remove sidebar if site is private
    } else {
        require_once('theme/' . $default_theme . '/sidebar.php');
        echo $ads_2;
    }
?>
</div>
</div>
</div>