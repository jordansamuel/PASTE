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
?>

<div class="text-light min-vh-100 py-4">
<!-- START CONTAINER -->
<div class="container-xl">
  <div class="row g-4">
    <!-- Start Main Content (Pastes Table & Recent Pastes) -->
    <div class="col-lg-9 order-lg-1">
      <!-- My Pastes Card -->
      <div class="card text-light border-0 rounded-3 shadow-sm mb-4">
        <div class="card-header bg-secondary border-0 rounded-top-3">
          <h5 class="mb-0"><?php echo htmlspecialchars($profile_username) . htmlspecialchars($lang['user_public_pastes'] ?? ' Public Pastes'); ?>
            <?php if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) { echo '<small class="ms-2 text-muted">' . htmlspecialchars($lang['mypastestitle'] ?? 'My Pastes') . '</small>'; } ?>
          </h5>
          <small class="text-muted"><?php echo htmlspecialchars($lang['membersince'] ?? 'Member since') . ' ' . htmlspecialchars($profile_join_date); ?></small>
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
          <div class="table-responsive">
            <table id="archive" class="table table-striped table-hover align-middle">
              <thead>
                <tr>
                  <th><?php echo htmlspecialchars($lang['pastetitle'] ?? 'Paste Title'); ?></th>
                  <th><?php echo htmlspecialchars($lang['pastetime'] ?? 'Time'); ?></th>
                  <?php if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) { echo '<th>' . htmlspecialchars($lang['visibility'] ?? 'Visibility') . '</th>'; } ?>
                  <th><?php echo htmlspecialchars($lang['pasteviews'] ?? 'Views'); ?></th>
                  <th><?php echo htmlspecialchars($lang['pastesyntax'] ?? 'Syntax'); ?></th>
                  <?php if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) { echo '<th style="min-width: 60px;">' . htmlspecialchars($lang['delete'] ?? 'Delete') . '</th>'; } ?>
                </tr>
              </thead>
              <tbody>
                <?php
                $res = getUserPastes($pdo, $profile_username);
                if (empty($res)) {
                  $colspan = isset($_SESSION['username']) && $_SESSION['username'] == $profile_username ? 6 : 4;
                  echo '<tr><td colspan="' . $colspan . '" class="text-center text-muted">' . htmlspecialchars($lang['emptypastebin'] ?? 'No pastes found') . '</td></tr>';
                } else {
                  foreach ($res as $row) {
                    $title = trim($row['title'] ?? 'Untitled');
                    $p_id = trim($row['id'] ?? '');
                    $p_code = trim($row['code'] ?? 'text');
                    $p_date = trim($row['date'] ?? '');
                    $p_views = (int) ($row['views'] ?? 0);
                    $p_visible = trim($row['visible'] ?? '0');
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
                          <td><span class="badge bg-primary">' . htmlspecialchars(strtoupper($p_code)) . '</span></td>
                        </tr>'; 
                      }
                    } else {
                      echo '<tr> 
                        <td><a href="' . htmlspecialchars($baseurl . $p_link) . '" title="' . htmlspecialchars($title) . '" class="text-light fw-medium">' . ucfirst(htmlspecialchars($title)) . '</a></td>    
                        <td>' . htmlspecialchars($p_date) . '</td>
                        <td>' . htmlspecialchars($p_visible) . '</td>
                        <td>' . htmlspecialchars($p_views) . '</td>
                        <td><span class="badge bg-primary">' . htmlspecialchars(strtoupper($p_code)) . '</span></td>
                        <td class="text-center"><a href="' . htmlspecialchars($baseurl . $p_delete_link) . '" class="btn btn-sm btn-outline-danger" title="Delete ' . htmlspecialchars($title) . '"><i class="bi bi-trash" aria-hidden="true"></i></a></td>    
                      </tr>';                   
                    }
                  }
                }
                ?>
              </tbody>
              <tfoot>
                <tr>
                  <th><?php echo htmlspecialchars($lang['pastetitle'] ?? 'Paste Title'); ?></th>
                  <th><?php echo htmlspecialchars($lang['pastetime'] ?? 'Time'); ?></th>
                  <?php if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) { echo '<th>' . htmlspecialchars($lang['visibility'] ?? 'Visibility') . '</th>'; } ?>
                  <th><?php echo htmlspecialchars($lang['pasteviews'] ?? 'Views'); ?></th>
                  <th><?php echo htmlspecialchars($lang['pastesyntax'] ?? 'Syntax'); ?></th>
                  <?php if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) { echo '<th>' . htmlspecialchars($lang['delete'] ?? 'Delete') . '</th>'; } ?>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
      <!-- Recent Pastes Card -->
      <div class="card text-light border-0 rounded-3 shadow-sm">
        <div class="card-header bg-secondary border-0 rounded-top-3">
          <h5 class="mb-0"><?php echo htmlspecialchars($lang['recentpastes'] ?? 'Recent Pastes'); ?></h5>
        </div>
        <div class="card-body p-4">
          <div class="table-responsive">
            <table id="recent-pastes" class="table table-striped table-hover align-middle">
              <thead>
                <tr>
                  <th><?php echo htmlspecialchars($lang['pastetitle'] ?? 'Paste Title'); ?></th>
                  <th><?php echo htmlspecialchars($lang['pastetime'] ?? 'Time'); ?></th>
                  <th><?php echo htmlspecialchars($lang['pastesyntax'] ?? 'Syntax'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php
                try {
                  $pastes = getRecent($pdo, 10);
                  if (empty($pastes)) {
                    echo '<tr><td colspan="3" class="text-center text-muted">' . htmlspecialchars($lang['emptypastebin'] ?? 'No pastes found') . '</td></tr>';
                  } else {
                    foreach ($pastes as $row) {
                      $title = (string) ($row['title'] ?? 'Untitled');
                      $p_id = (string) ($row['id'] ?? '');
                      $p_date = (string) ($row['date'] ?? '');
                      $p_time = (int) ($row['now_time'] ?? 0);
                      $p_code = (string) ($row['code'] ?? 'Unknown');
                      $p_time_ago = conTime($p_time);
                      $p_link = ($mod_rewrite == '1') ? "$p_id" : "paste.php?id=$p_id";
                      $title = truncate($title, 20, 50);
                      echo '<tr> 
                        <td><a href="' . htmlspecialchars($baseurl . $p_link) . '" title="' . htmlspecialchars($title) . '" class="text-light fw-medium">' . ucfirst(htmlspecialchars($title)) . '</a></td>    
                        <td>' . htmlspecialchars($p_date) . '</td>
                        <td><span class="badge bg-primary">' . htmlspecialchars(strtoupper($p_code)) . '</span></td>
                      </tr>'; 
                    }
                  }
                } catch (Exception $e) {
                  echo '<tr><td colspan="3" class="text-center text-danger">' . htmlspecialchars('Error fetching recent pastes: ' . $e->getMessage()) . '</td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <!-- Start Sidebar (Welcome & Stats) -->
    <div class="col-lg-3 order-lg-2">
      <?php if (isset($_SESSION['username']) && $_SESSION['username'] == $profile_username) { ?>
      <div class="card bg-secondary text-light mb-4 border-0 rounded-3 position-relative welcome-card">
        <div class="card-body p-3">
          <h6 class="d-flex align-items-center gap-2 mb-3 text-light">
            <i class="bi bi-person-circle fs-5"></i>
            <?php echo htmlspecialchars($lang['hello'] ?? 'Hello') . ', ' . htmlspecialchars($profile_username); ?>
          </h6>
          <p class="mb-3 small"><?php echo htmlspecialchars($lang['profile-message'] ?? 'Manage your pastes here.'); ?></p>
          <ul class="list-group list-group-flush">
            <li class="list-group-item bg-dark text-light d-flex align-items-center justify-content-between gap-2 py-2">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-file-code fs-5 text-primary"></i>
                <small class="text-muted text-uppercase"><?php echo htmlspecialchars($lang['totalpastes'] ?? 'Total Pastes'); ?></small>
              </div>
              <div class="fw-bold"><?php echo htmlspecialchars($profile_total_pastes); ?></div>
            </li>
            <li class="list-group-item bg-dark text-light d-flex align-items-center justify-content-between gap-2 py-2">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-globe fs-5 text-primary"></i>
                <small class="text-muted text-uppercase"><?php echo htmlspecialchars($lang['profile-total-pub'] ?? 'Public'); ?></small>
              </div>
              <div class="fw-bold"><?php echo htmlspecialchars($profile_total_public); ?></div>
            </li>
            <li class="list-group-item bg-dark text-light d-flex align-items-center justify-content-between gap-2 py-2">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-eye-slash fs-5 text-primary"></i>
                <small class="text-muted text-uppercase"><?php echo htmlspecialchars($lang['profile-total-unl'] ?? 'Unlisted'); ?></small>
              </div>
              <div class="fw-bold"><?php echo htmlspecialchars($profile_total_unlisted); ?></div>
            </li>
            <li class="list-group-item bg-dark text-light d-flex align-items-center justify-content-between gap-2 py-2">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-lock fs-5 text-primary"></i>
                <small class="text-muted text-uppercase"><?php echo htmlspecialchars($lang['profile-total-pri'] ?? 'Private'); ?></small>
              </div>
              <div class="fw-bold"><?php echo htmlspecialchars($profile_total_private); ?></div>
            </li>
            <li class="list-group-item bg-dark text-light d-flex align-items-center justify-content-between gap-2 py-2">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-bar-chart fs-5 text-primary"></i>
                <small class="text-muted text-uppercase"><?php echo htmlspecialchars($lang['profile-total-views'] ?? 'Total Views'); ?></small>
              </div>
              <div class="fw-bold"><?php echo htmlspecialchars($profile_total_paste_views); ?></div>
            </li>
          </ul>
        </div>
      </div>
      <?php } ?>
     <?php echo $ads_2 ?? ''; ?>
    </div>
    <!-- End Sidebar -->
  </div>
</div>
</div>