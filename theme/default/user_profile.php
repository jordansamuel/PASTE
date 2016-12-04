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
          <h5><?php echo $profile_username . $lang['user_public_pastes']; ?> <?php if ( isset( $_SESSION ) && $_SESSION['username'] == $profile_username ) { echo "<small>". $lang['mypastestitle'] . "</small>"; } ?></h5>
          <small><?php echo $lang['membersince'] . $profile_join_date; ?></small>
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
            
            <?php 
            if ( $_SESSION['username'] == $profile_username ) {
            ?>
            <div class="panel panel-default">
                <div class="panel-body">
                    Hey <?php echo $profile_username; ?>! This is your profile page where you can manage your pastes! If you are logged in, you can see all of your public, private and unlisted pastes. You can also delete your pastes from this page. 
                    <br><br>
                    Guests to this page will only see the list of your public pastes.
                    <br><br>
                    Some quick stats (shown only to you):<br>
                    Total pastes: <?php echo $profile_total_pastes; ?><br>
                    Total public pastes: <?php echo $profile_total_public; ?><br>
                    Total unlisted pastes: <?php echo $profile_total_unlisted; ?><br>
                    Total private pastes: <?php echo $profile_total_private; ?><br>
                    Total views of all your pastes: <?php echo $profile_total_paste_views; ?><br>
                </div>
            </div>
            <?php
            }
            ?>
            
            <table id="archive" class="table display">
                <thead>
					<tr>
						<td><?php echo $lang['pastetitle']; ?></td>
						<td><?php echo $lang['pastetime']; ?></td>
                        <?php if ( isset( $_SESSION ) && $_SESSION['username'] == $profile_username ) { echo "<td>". $lang['visibility'] . "</td>"; } ?>
						<td><?php echo $lang['pasteviews']; ?></td>
						<td><?php echo $lang['pastesyntax']; ?></td>
                        <?php if ( isset( $_SESSION ) && $_SESSION['username'] == $profile_username ) { echo "<td>". $lang['delete'] . "</td>"; } ?>
					</tr>
                </thead>
             
                <tfoot>
					<tr>
						<td><?php echo $lang['pastetitle']; ?></td>
						<td><?php echo $lang['pastetime']; ?></td>
                        <?php if ( isset( $_SESSION ) && $_SESSION['username'] == $profile_username ) { echo "<td>". $lang['visibility'] . "</td>"; } ?>
						<td><?php echo $lang['pasteviews']; ?></td>
						<td><?php echo $lang['pastesyntax']; ?></td>
                        <?php if ( isset( $_SESSION ) && $_SESSION['username'] == $profile_username ) { echo "<td>". $lang['delete'] . "</td>"; } ?>
					</tr>
                </tfoot>
         
				<tbody>
				<?php

				$res = getUserPastes( $con, $profile_username );
				while( $row = mysqli_fetch_array( $res ) ) {
					$title =  Trim( $row['title'] );
					$p_id =  Trim( $row['id'] );
					$p_code =  Trim( $row['code'] );
					$p_date = Trim( $row['date'] );
                    $p_views = Trim( $row['views'] );
                    $p_visible = Trim( $row['visible'] );
                    switch( $p_visible ) {
                        case 0:
                            $p_visible = $lang['public'];
                            break;
                        case 1:
                            $p_visible = $lang['unlisted'];
                            break;
                        case 2:
                            $p_visible = $lang['private'];
                            break;
                    }
                    $p_link = ( $mod_rewrite == '1' )?"$p_id":"paste.php?id=$p_id";
                    $p_delete_link = ( $mod_rewrite == '1' )?"user.php?del&user=$profile_username&id=$p_id":"user.php?del&user=$profile_username&id=$p_id";
					$title = truncate( $title, 20, 50 );
                    
                    // Guests only see public pastes
                    if ( !isset( $_SESSION['token'] ) || $_SESSION['username'] != $profile_username ) {
                        if ( $p_visible == $lang['public'] ) {
                            echo '<tr> 
                            <td><a href="'.$p_link.'" title="'.$title.'">'.ucfirst($title).'</a></td>    
                            <td>'.$p_date.'</td>
                            <td>'.$p_views.'</td>
                            <td>'.strtoupper($p_code).'</td>
                            </tr>'; 
                        }
                    } else {
                        echo '<tr> 
                        <td><a href="'.$p_link.'" title="'.$title.'">'.ucfirst($title).'</a></td>    
						<td>'.$p_date.'</td>
						<td>'.$p_visible.'</td>
                        <td>'.$p_views.'</td>
						<td>'.strtoupper($p_code).'</td>
						<td><a href="'.$p_delete_link.'" title="'.$title.'"><i class="fa fa-trash-o fa-lg" aria-hidden="true"></i></a></td>    
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