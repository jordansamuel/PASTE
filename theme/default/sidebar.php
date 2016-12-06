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
 // which protocol are we on
$protocol = ($_SERVER['HTTPS'] == "on")?'https://':'http://';
?>

    <div class="col-md-3 col-lg-2">

<?php
if(isset($_SESSION['token'])) {
?>
	<!-- My Pastes -->
		<div class="panel panel-default">
			<div class="panel-title">
				<h6>Hello <?php echo ($_SESSION['username']);?>
					<small>
						<?php if ( $mod_rewrite == '1' ) {
							echo '<a href="' . $baseurl . '/user/' . $_SESSION['username'] . '" target="_self">' . $lang['mypastes'] . '</a>';
							} else {
							echo '<a href="' . $baseurl . '/user.php?user=' . $_SESSION['username'] . '" target="_self">' . $lang['mypastes'] . '</a>'; }
						?>
					</small>
				</h6>
			</div>
				
			<div class="panel-body">
				<div class="list-widget pagination-content">				
					<?php         
						   $user_username = Trim($_SESSION['username']);     
						   $res = getUserRecent($con,10,$user_username);
						   while($row = mysqli_fetch_array($res)) {
							$title =  Trim($row['title']);
							$p_id =  Trim($row['id']);
							$p_date = Trim($row['date']);
							$p_time = Trim($row['now_time']);
							$nowtime = time();
							$oldtime = $p_time;
							$p_time = conTime($nowtime-$oldtime);
							$title = truncate($title, 6, 15);
					?>
					<p class="no-margin">
					<?php if ($mod_rewrite == '1') {
						echo '<a href="'.$p_id.'" title="' . $title . '">' . ucfirst($title) . '</a>
							  <a class="icon" href="mypastes.php?del&id=' . $p_id . '" title="' . $title . '"><i class="fa fa-trash-o fa-lg" aria-hidden="true"></i></a>'; } else {
						echo '<a href="paste.php?id=' . $p_id . '" title="' . $title . '">' . ucfirst($title) . '</a>
							  <a class="icon" href="mypastes.php?del&id=' . $p_id . '" title="' . $title . '"><i class="fa fa-trash-o fa-lg" aria-hidden="true"></i></a>'; }
					?>
						<button type="button" class="btn-light pull-right" data-container="body" data-toggle="popover" data-placement="left" data-trigger="focus" data-content="<?php echo $p_time;?>" data-original-title="" title="">
						 <i class="fa fa-clock-o fa-lg" aria-hidden="true"></i>
						</button>
					<?php }
					// Display a message if the pastebin is empty
					$query  = "SELECT count(*) as count FROM pastes";
					$result = mysqli_query( $con, $query );
					while ($row = mysqli_fetch_array($result)) {
						$totalpastes = $row['count'];
					}
					
					if ($totalpastes == '0') { echo $lang['emptypastebin']; } ?>
					</p>
				</div>
			</div>
		</div>

<?php } if (isset($_SESSION['username'])) { ?>
	<?php } else { ?>
	<!-- Guest message -->
			<div class="widget guestmsg" style="background:#399bff;">				
			<p class="text"><?php echo $lang['guestmsgtitle'];?></p>
			<p class="text-body"><?php echo $lang['guestmsgbody'];?></p>
			</div>
    <!-- End message -->	
<?php } 
	if ( isset($privatesite) && $privatesite == "on") { // Remove 'recent pastes' if site is private
	} else { ?>
		<!-- Recent Public Pastes -->
		<div class="panel panel-default">
		  <div class="panel-title"><?php echo $lang['recentpastes'];?></div>
			<div class="panel-body">
				<div class="list-widget pagination-content">
					<?php          
							$res = getRecent($con,10);
							while($row = mysqli_fetch_array($res)) {
							$title =  Trim($row['title']);
							$p_id =  Trim($row['id']);
							$p_date = Trim($row['date']);
							$p_time = Trim($row['now_time']);
							$nowtime = time();
							$oldtime = $p_time;
							$p_time = conTime($nowtime-$oldtime);
							$title = truncate($title, 6, 15);
					?>

					<p class="no-margin">
					<?php
					if ($mod_rewrite == '1') {
						echo '<a href="' . $p_id . '" title="' . $title . '">' . ucfirst($title) . '</a>'; } else {
						echo '<a href="paste.php?id=' . $p_id . '" title="' . $title . '">' . ucfirst($title) . '</a>'; }
					?>
						<button type="button" class="btn-light pull-right" data-container="body" data-toggle="popover" data-placement="left" data-trigger="focus" data-content="<?php echo $p_time;?>" data-original-title="" title="">
						 <i class="fa fa-clock-o fa-lg" aria-hidden="true"></i>
						</button>
					<?php }
					// Display a message if the pastebin is empty
					$query  = "SELECT count(*) as count FROM pastes";
					$result = mysqli_query( $con, $query );
					while ($row = mysqli_fetch_array($result)) {
						$totalpastes = $row['count'];
					}
					
					if ($totalpastes == '0') { echo $lang['emptypastebin']; } ?>
					</p>
				</div>
			</div>
		</div>
<?php } ?>
	</div>
	<!-- End Panel -->
	
	<?php if (isset($_SESSION['username'])) { ?>
	<?php } else { ?>
	<div style="text-align:center;">
	<?php echo $ads_1; ?>
	</div>
	<?php } ?>