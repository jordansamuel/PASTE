<?php
/*
 * Paste 3 Default theme <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
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
    <div class="col-md-9 col-lg-10">
      <div class="panel panel-default">
        <div class="panel-title">
          <h5><?php echo htmlspecialchars($profile_username) . $lang['user_public_pastes']; ?> <?php if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) { echo "<small>" . htmlspecialchars($lang['mypastestitle']) . "</small>"; } ?></h5>
          <small><?php echo htmlspecialchars($lang['membersince'] . $profile_join_date); ?></small>
        </div>
        <div class="panel-body table-responsive">
            <?php 
            if (isset($_GET['del'])) {	
                if (isset($success)) {
                    // Deleted
                    echo '<div class="paste-alert alert3" style="text-align: center;">' . htmlspecialchars($success) . '</div>'; 
                } elseif (isset($error)) {
                    // Errors
                    echo '<div class="paste-alert alert5" style="text-align: center;">' . htmlspecialchars($error) . '</div>'; 
                }
            }
            ?>
            
            <?php 
            if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) {
            ?>
            <div class="panel panel-primary">
                <div class="panel-body">
                    <?php echo htmlspecialchars($lang['hello'] . ', ' . $profile_username . '.  ' . $lang['profile-message']); ?>
                    <?php echo htmlspecialchars($lang['profile-stats']); ?>
                    <?php echo htmlspecialchars($lang['totalpastes'] . ' ' . $profile_total_pastes); ?> &mdash;
                    <?php echo htmlspecialchars($lang['profile-total-pub'] . ' ' . $profile_total_public); ?> &mdash;
                    <?php echo htmlspecialchars($lang['profile-total-unl'] . ' ' . $profile_total_unlisted); ?> &mdash;
                    <?php echo htmlspecialchars($lang['profile-total-pri'] . ' ' . $profile_total_private); ?> &mdash;
                    <?php echo htmlspecialchars($lang['profile-total-views'] . ' ' . $profile_total_paste_views); ?>
                </div>
            </div>
            <?php
            }
            ?>
            
            <table id="archive" class="table display">
                <thead>
                    <tr>
                        <td><?php echo htmlspecialchars($lang['pastetitle']); ?></td>
                        <td><?php echo htmlspecialchars($lang['pastetime']); ?></td>
                        <?php if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) { echo "<td>" . htmlspecialchars($lang['visibility']) . "</td>"; } ?>
                        <td><?php echo htmlspecialchars($lang['pasteviews']); ?></td>
                        <td><?php echo htmlspecialchars($lang['pastesyntax']); ?></td>
                        <?php if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) { echo "<td>" . htmlspecialchars($lang['delete']) . "</td>"; } ?>
                    </tr>
                </thead>
             
                <tfoot>
                    <tr>
                        <td><?php echo htmlspecialchars($lang['pastetitle']); ?></td>
                        <td><?php echo htmlspecialchars($lang['pastetime']); ?></td>
                        <?php if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) { echo "<td>" . htmlspecialchars($lang['visibility']) . "</td>"; } ?>
                        <td><?php echo htmlspecialchars($lang['pasteviews']); ?></td>
                        <td><?php echo htmlspecialchars($lang['pastesyntax']); ?></td>
                        <?php if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) { echo "<td>" . htmlspecialchars($lang['delete']) . "</td>"; } ?>
                    </tr>
                </tfoot>
         
                <tbody>
                <?php
                $res = getUserPastes($pdo, $profile_username);
                foreach ($res as $row) {
                    $title = trim($row['title']);
                    $p_id = trim($row['id']);
                    $p_code = trim($row['code']);
                    $p_date = trim($row['date']);
                    $p_views = trim($row['views']);
                    $p_visible = trim($row['visible']);
                    switch ($p_visible) {
                        case '0':
                            $p_visible = $lang['public'];
                            break;
                        case '1':
                            $p_visible = $lang['unlisted'];
                            break;
                        case '2':
                            $p_visible = $lang['private'];
                            break;
                    }
                    $p_link = ($mod_rewrite == '1') ? "$p_id" : "paste.php?id=$p_id";
                    $p_delete_link = ($mod_rewrite == '1') ? "user.php?del&user=$profile_username&id=$p_id" : "user.php?del&user=$profile_username&id=$p_id";
                    $title = truncate($title, 20, 50);
                    
                    // Guests only see public pastes
                    if (!isset($_SESSION['token']) || (isset($_SESSION['username']) && $_SESSION['username'] != $profile_username)) {
                        if ($row['visible'] == '0') {
                            echo '<tr> 
                                <td><a href="' . $protocol . $baseurl . '/' . htmlspecialchars($p_link) . '" title="' . htmlspecialchars($title) . '">' . ucfirst(htmlspecialchars($title)) . '</a></td>    
                                <td>' . htmlspecialchars($p_date) . '</td>
                                <td>' . htmlspecialchars($p_views) . '</td>
                                <td>' . htmlspecialchars(strtoupper($p_code)) . '</td>
                            </tr>'; 
                        }
                    } else {
                        echo '<tr> 
                            <td><a href="' . $protocol . $baseurl . '/' . htmlspecialchars($p_link) . '" title="' . htmlspecialchars($title) . '">' . ucfirst(htmlspecialchars($title)) . '</a></td>    
                            <td>' . htmlspecialchars($p_date) . '</td>
                            <td>' . htmlspecialchars($p_visible) . '</td>
                            <td>' . htmlspecialchars($p_views) . '</td>
                            <td>' . htmlspecialchars(strtoupper($p_code)) . '</td>
                            <td><a href="' . $protocol . $baseurl . '/' . htmlspecialchars($p_delete_link) . '" title="' . htmlspecialchars($title) . '"><i class="fa fa-trash-o fa-lg" aria-hidden="true"></i></a></td>    
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
<?php require_once('theme/' . $default_theme . '/sidebar.php'); ?>
<?php echo $ads_2; ?> 
</div>
</div>
</div>