<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
?>

<div class="content">
<!-- START CONTAINER -->
<div class="container-xl my-5">
  <!-- Start Row -->
  <div class="row">
    <!-- Start Card -->
    <div class="col-lg-12">
      <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header">
          <h5 class="mb-0"><?php echo htmlspecialchars($profile_username) . htmlspecialchars($lang['user_public_pastes'] ?? 'Public Pastes'); ?>
            <?php if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) { echo '<small class="ms-2">' . htmlspecialchars($lang['mypastestitle'] ?? 'My Pastes') . '</small>'; } ?>
          </h5>
          <small class="text-light"><?php echo htmlspecialchars($lang['membersince'] ?? 'Member since') . ' ' . htmlspecialchars($profile_join_date); ?></small>
        </div>
        <div class="card-body p-4">
            <?php 
            if (isset($_GET['del'])) {	
                if (isset($success)) {
                    echo '<div class="alert alert-success text-center rounded-3">' . htmlspecialchars($success) . '</div>'; 
                } elseif (isset($error)) {
                    echo '<div class="alert alert-danger text-center rounded-3">' . htmlspecialchars($error) . '</div>'; 
                }
            }
            ?>
            
            <?php 
            if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) {
            ?>
            <div class="card bg-secondary text-light mb-4 border-0 rounded-3">
                <div class="card-body">
                    <h6 class="mb-3"><?php echo htmlspecialchars($lang['hello'] ?? 'Hello') . ', ' . htmlspecialchars($profile_username) . '. ' . htmlspecialchars($lang['profile-message'] ?? 'Manage your pastes here.'); ?></h6>
                    <p class="mb-0"><?php echo htmlspecialchars($lang['profile-stats'] ?? 'Stats:'); ?>
                    <?php echo htmlspecialchars($lang['totalpastes'] ?? 'Total Pastes') . ': <strong>' . htmlspecialchars($profile_total_pastes) . '</strong>'; ?> &mdash;
                    <?php echo htmlspecialchars($lang['profile-total-pub'] ?? 'Public') . ': <strong>' . htmlspecialchars($profile_total_public) . '</strong>'; ?> &mdash;
                    <?php echo htmlspecialchars($lang['profile-total-unl'] ?? 'Unlisted') . ': <strong>' . htmlspecialchars($profile_total_unlisted) . '</strong>'; ?> &mdash;
                    <?php echo htmlspecialchars($lang['profile-total-pri'] ?? 'Private') . ': <strong>' . htmlspecialchars($profile_total_private) . '</strong>'; ?> &mdash;
                    <?php echo htmlspecialchars($lang['profile-total-views'] ?? 'Total Views') . ': <strong>' . htmlspecialchars($profile_total_paste_views) . '</strong>'; ?></p>
                </div>
            </div>
            <?php
            }
            ?>
            
            <div class="table-responsive">
                <table id="archive" class="table table-striped table-hover align-middle text-light">
                    <thead class="table-dark">
                        <tr>
                            <th><?php echo htmlspecialchars($lang['pastetitle'] ?? 'Paste Title'); ?></th>
                            <th><?php echo htmlspecialchars($lang['pastetime'] ?? 'Time'); ?></th>
                            <?php if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) { echo '<th>' . htmlspecialchars($lang['visibility'] ?? 'Visibility') . '</th>'; } ?>
                            <th><?php echo htmlspecialchars($lang['pasteviews'] ?? 'Views'); ?></th>
                            <th><?php echo htmlspecialchars($lang['pastesyntax'] ?? 'Syntax'); ?></th>
                            <?php if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) { echo '<th style="min-width: 60px;">' . htmlspecialchars($lang['delete'] ?? 'Delete') . '</th>'; } ?>
                        </tr>
                    </thead>
                    <tfoot class="table-dark">
                        <tr>
                            <th><?php echo htmlspecialchars($lang['pastetitle'] ?? 'Paste Title'); ?></th>
                            <th><?php echo htmlspecialchars($lang['pastetime'] ?? 'Time'); ?></th>
                            <?php if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) { echo '<th>' . htmlspecialchars($lang['visibility'] ?? 'Visibility') . '</th>'; } ?>
                            <th><?php echo htmlspecialchars($lang['pasteviews'] ?? 'Views'); ?></th>
                            <th><?php echo htmlspecialchars($lang['pastesyntax'] ?? 'Syntax'); ?></th>
                            <?php if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) { echo '<th>' . htmlspecialchars($lang['delete'] ?? 'Delete') . '</th>'; } ?>
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
                                $p_visible = $lang['public'] ?? 'Public';
                                break;
                            case '1':
                                $p_visible = $lang['unlisted'] ?? 'Unlisted';
                                break;
                            case '2':
                                $p_visible = $lang['private'] ?? 'Private';
                                break;
                        }
                        $p_link = ($mod_rewrite == '1') ? "$p_id" : "paste.php?id=$p_id";
                        $p_delete_link = ($mod_rewrite == '1') ? "user.php?del&user=$profile_username&id=$p_id" : "user.php?del&user=$profile_username&id=$p_id";
                        $title = truncate($title, 20, 50);
                        
                        // Guests only see public pastes
                        if (!isset($_SESSION['token']) || (isset($_SESSION['username']) && $_SESSION['username'] != $profile_username)) {
                            if ($row['visible'] == '0') {
                                echo '<tr> 
                                    <td><a href="' . htmlspecialchars($baseurl . $p_link) . '" title="' . htmlspecialchars($title) . '" class="text-light fw-medium">' . ucfirst(htmlspecialchars($title)) . '</a></td>    
                                    <td>' . htmlspecialchars($p_date) . '</td>
                                    <td>' . htmlspecialchars($p_views) . '</td>
                                    <td><span class="badge bg-secondary">' . htmlspecialchars(strtoupper($p_code)) . '</span></td>
                                </tr>'; 
                            }
                        } else {
                            echo '<tr> 
                                <td><a href="' . htmlspecialchars($baseurl . $p_link) . '" title="' . htmlspecialchars($title) . '" class="text-light fw-medium">' . ucfirst(htmlspecialchars($title)) . '</a></td>    
                                <td>' . htmlspecialchars($p_date) . '</td>
                                <td>' . htmlspecialchars($p_visible) . '</td>
                                <td>' . htmlspecialchars($p_views) . '</td>
                                <td><span class="badge bg-secondary">' . htmlspecialchars(strtoupper($p_code)) . '</span></td>
                                <td class="text-center"><a href="' . htmlspecialchars($baseurl . $p_delete_link) . '" class="btn btn-sm btn-outline-danger" title="Delete ' . htmlspecialchars($title) . '"><i class="bi bi-trash" aria-hidden="true"></i></a></td>    
                            </tr>';                   
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
      </div>
    </div>
    <!-- End Card -->
    <?php require_once('theme/' . $default_theme . '/sidebar.php'); ?>
    <?php echo $ads_2; ?> 
  </div>
</div>
</div>