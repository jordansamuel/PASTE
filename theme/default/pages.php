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

<div class="content">
  <!-- START CONTAINER -->
  <div class="container-xl my-4">
    <!-- Start Row -->
    <div class="row">
      <!-- Start Card -->
      <div class="col-lg-12">
        <div class="card">
          <div class="card-header text-center">
            <h6><?php echo htmlspecialchars($page_title ?? ''); ?></h6>
          </div>
          <div class="card-body">
            <?php
            if (isset($stats)) {
              echo htmlspecialchars_decode($page_content ?? '');
            } else {
              echo '<div class="alert alert-dark text-center"><p>' . htmlspecialchars($lang['notfound'] ?? '404 Not Found') . '</p></div>';
            }
            ?>
          </div>
        </div>
      </div>
      
      <?php require_once('theme/' . $default_theme . '/sidebar.php'); ?>
      <?php echo htmlspecialchars($ads_2 ?? '', ENT_QUOTES, 'UTF-8'); ?>
    </div>
  </div>
</div>