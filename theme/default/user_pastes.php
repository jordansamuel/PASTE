<?php
/*
 * $ID Project: Paste 2.0 - J.Samuel
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in LIC.txt for more details.
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
        <div class="panel-title">
          <h5><?php echo $lang['yourpastes']; ?> <small><?php echo $lang['mypastestitle']; ?></small></h5>
        </div>
        <div class="panel-body table-responsive">
		<?php 
		if (isset($_GET['del'])) {	
			if (isset($success)) {
			// Deleted
			echo '<div class="paste-alert alert3" style="text-align: center;">
					' . $success . '
				</div>'; 
			}
			
			// Errors
			elseif (isset($error)) {
				echo '<div class="paste-alert alert5" style="text-align: center;">
						' . $error . '
					</div>'; 
			}
		}
		?>
            <table id="archive" class="table display">
                <thead>
					<tr>
						<td><?php echo $lang['pastetitle']; ?></td>
						<td><?php echo $lang['pastetime']; ?></td>
						<td ><?php echo $lang['pastesyntax']; ?></td>
						<td ><?php echo $lang['delete']; ?></td>
					</tr>
                </thead>
             
                <tfoot>
					<tr>
						<td><?php echo $lang['pastetitle']; ?></td>
						<td><?php echo $lang['pastetime']; ?></td>
						<td ><?php echo $lang['pastesyntax']; ?></td>
						<td ><?php echo $lang['delete']; ?></td>
					</tr>
                </tfoot>
         
				<tbody>
				<?php

				$res = getUserPastes($con,$user_username);
				while($row = mysqli_fetch_array($res)) {
					$title =  Trim($row['title']);
					$p_id =  Trim($row['id']);
					$p_code =  Trim($row['code']);
					$p_date = Trim($row['date']);
					$p_time = Trim($row['now_time']);
					$nowtime = time();
					$oldtime = $p_time;
					$p_time = conTime($nowtime-$oldtime);
					$title = truncate($title, 20, 50);
					if ($mod_rewrite == '1') {
					   echo '<tr> 
						<td><a href="'.$p_id.'" title="'.$title.'">'.ucfirst($title).'</a></td>    
						<td>'.$p_time.'</td>
						<td>'.strtoupper($p_code).'</td>
						<td><a href="mypastes.php?del&id='.$p_id.'" title="'.$title.'"><i class="fa fa-trash-o fa-lg" aria-hidden="true"></i></a></td>    
						</tr>'; 
						} else {
							  echo '<tr> 
						<td><a href="paste.php?id='.$p_id.'" title="'.$title.'">'.ucfirst($title).'</a></td>    
						<td>'.$p_time.'</td>
						<td>'.strtoupper($p_code).'</td>
						<td><a href="mypastes.php?del&id='.$p_id.'" title="'.$title.'"><i class="fa fa-trash-o fa-lg" aria-hidden="true"></i></a></td>    
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
<?php require_once('theme/'.$default_theme.'/sidebar.php'); ?>
<?php echo $ads_2; ?> 