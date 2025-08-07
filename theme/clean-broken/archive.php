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
<?php if ($privatesite == "on") { // Site permissions ?>
	<div class="col-md-12">
		<div class="panel panel-default" style="padding-bottom: 100px;">
			<div class="error-pages">
				<i class="fa fa-lock fa-5x" aria-hidden="true"></i>
				<h1><?php echo $lang['siteprivate']; ?></h1>
			</div>
		</div>
	</div>
	
<?php } else { ?>
	
    <div class="col-md-9 col-lg-10">
      <div class="panel panel-default">
        <div class="panel-title">
          <?php echo $lang['archives'];?>
        </div>
        <div class="panel-body table-responsive">
		
            <table id="archive" class="table display">
                <thead>
                    <tr>
                        <th><?php echo $lang['pastetitle'];?></th>
                        <th><?php echo $lang['pastetime'];?></th>
                        <th><?php echo $lang['pastesyntax'];?></th>
                    </tr>
                </thead>
             
                <tfoot>
                    <tr>
                        <th><?php echo $lang['pastetitle'];?></th>
                        <th><?php echo $lang['pastetime'];?></th>
                        <th><?php echo $lang['pastesyntax'];?></th>
                    </tr>
                </tfoot>
         
				<tbody>
				<?php

				$res = getRecent($con, 100);
				while ($row = mysqli_fetch_array($res)) {
					$title   = Trim($row['title']);
					$p_id    = Trim($row['id']);
					$p_code  = Trim($row['code']);
					$p_date  = Trim($row['date']);
					$p_time  = Trim($row['now_time']);
					$nowtime = time();
					$oldtime = $p_time;
					$p_time  = conTime($nowtime - $oldtime);
					$title = truncate($title, 20, 50);
					if ($mod_rewrite == '1') {
						echo ' <tr> 
					<td><a href="' . $p_id . '" title="' . $title . '">' . ucfirst($title) . '</a></td>    
					<td>' . $p_time . '</td>
					<td>' . strtoupper($p_code) . '</td></tr>';
					} else {
						echo ' <tr> 
					<td><a href="paste.php?id=' . $p_id . '" title="' . $title . '">' . ucfirst($title) . '</a></td>    
					<td>' . $p_time . '</td>
					<td>' . strtoupper($p_code) . '</td></tr>';
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
		require_once('theme/'.$default_theme.'/sidebar.php');
		echo $ads_2;
	}
?>