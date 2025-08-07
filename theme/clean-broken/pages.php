<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE> - Clean theme
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
  <div class="container-padding">
    <!-- Start Row -->
    <div class="row">
      <!-- Start Panel -->
		<div class="col-md-9 col-lg-10">
		  <div class="panel panel-default">
			<div class="panel-title" style="text-align:center;">
				<h6><?php echo $page_title; ?></h6>
			</div>
				<div class="panel-body">
				<?php
				if (isset($stats)) {
					echo $page_content;
				} else {
					echo '<div class="paste-alert alert6"><p>' . $lang['notfound'] . '</p></div>';
				}
				?>
				</div>
			</div>
		</div>
		
<?php require_once('theme/'.$default_theme.'/sidebar.php'); ?>
<?php echo $ads_2; ?> 