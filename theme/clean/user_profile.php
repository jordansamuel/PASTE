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
            <div class="panel panel-primary">
                <div class="panel-body">
					<?php echo $lang['hello'] . ', ' . $profile_username . '.<br />' . $lang['profile-message']; ?><br />
					<?php echo $lang['profile-stats'];?><br />
                    <?php echo $lang['totalpastes'] . ' ' . $profile_total_pastes; ?> &mdash;
                    <?php echo $lang['profile-total-pub'] . ' ' . $profile_total_public; ?> &mdash;
                    <?php echo $lang['profile-total-unl'] . ' ' . $profile_total_unlisted; ?> &mdash;
                    <?php echo $lang['profile-total-pri'] . ' ' . $profile_total_private; ?> &mdash;
                    <?php echo $lang['profile-total-views'] . ' ' . $profile_total_paste_views; ?>
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
                        if ( $row['visible'] == 0 ) {
                            echo '<tr> 
                            <td><a href="' . $protocol . $baseurl . '/'.$p_link.'" title="'.$title.'">'.ucfirst($title).'</a></td>    
                            <td>'.$p_date.'</td>
                            <td>'.$p_views.'</td>
                            <td>'.strtoupper($p_code).'</td>
                            </tr>'; 
                        }
                    } else {
                        echo '<tr> 
                        <td><a href="' . $protocol . $baseurl . '/'.$p_link.'" title="'.$title.'">'.ucfirst($title).'</a></td>    
						<td>'.$p_date.'</td>
						<td>'.$p_visible.'</td>
                        <td>'.$p_views.'</td>
						<td>'.strtoupper($p_code).'</td>
						<td><a href="' . $protocol . $baseurl . '/'.$p_delete_link.'" title="'.$title.'"><i class="fa fa-trash-o fa-lg" aria-hidden="true"></i></a></td>    
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