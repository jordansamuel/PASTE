<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE> - Default theme
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in GPL.txt for more details.
 */
?>

<div class="content">
  <!-- START CONTAINER -->
  <div class="container-xl my-4">
    <!-- Start Row -->
    <div class="row">
      <!-- Start Card -->
      <div class="col-lg-10">
        <div class="card">
          <div class="card-header text-center">
            <h6><?php echo htmlspecialchars($page_title ?? ''); ?></h6>
          </div>
          <div class="card-body">
            <?php
            if (isset($stats)) {
              echo htmlspecialchars_decode($page_content ?? '');
            } else {
              echo '<div class="alert alert-danger text-center"><p>' . htmlspecialchars($lang['notfound'] ?? 'Not found') . '</p></div>';
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