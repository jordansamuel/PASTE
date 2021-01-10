<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE> - Bulma theme
 * Theme by wsehl <github.com/wsehl> (January, 2021)
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

<aside class="bd-side">
	<?php
	if (isset($_SESSION['token'])) {
	?>
		<!-- My Pastes -->
		<nav id="categories" class="bd-categories">
			<div class="bd-category ">
				<h1 class="title is-4">Hello, <?php echo ($_SESSION['username']); ?></h1>
				<h2 class="subtitle is-6">
					<?php if ($mod_rewrite == '1') {
						echo '<a href="' . $protocol . $baseurl . '/user/' . $_SESSION['username'] . '" target="_self">' . $lang['mypastes'] . '</a>';
					} else {
						echo '<a href="' . $protocol . $baseurl . '/user.php?user=' . $_SESSION['username'] . '" target="_self">' . $lang['mypastes'] . '</a>';
					}
					?>
				</h2>
				<?php
				$user_username = Trim($_SESSION['username']);
				$res = getUserRecent($con, 7, $user_username);
				while ($row = mysqli_fetch_array($res)) {
					$title =  Trim($row['title']);
					$p_id =  Trim($row['id']);
					$p_date = Trim($row['date']);
					$p_time = Trim($row['now_time']);
					$nowtime = time();
					$oldtime = $p_time;
					$p_time = conTime($nowtime - $oldtime);
					$title = truncate($title, 6, 18);
					$p_delete_link = ($mod_rewrite == '1') ? "user.php?del&user=$user_username&id=$p_id" : "user.php?del&user=$user_username&id=$p_id";
				?>
					<?php
					if ($mod_rewrite == '1') {
						echo '<header class="bd-category-header my-2"><a href="' . $protocol . $baseurl . '/' . $p_id . '" title="' . $title . '">' . $title . '</a>
							  	<a class="icon is-pulled-right" href="' . $protocol . $baseurl . '/' . $p_delete_link . '" title="' . $title . '">
							  		<i class="far fa-trash-alt has-text-grey" aria-hidden="true"></i>
								</a>' . '
								<a class="icon is-pulled-right has-tooltip-arrow has-tooltip-left-mobile has-tooltip-bottom-desktop has-tooltip-left-until-widescreen" data-tooltip="' . $p_time . '">
									<i class="far fa-clock has-text-grey" aria-hidden="true"></i>
								</a>' . '
							</header>';
					} else {
						echo '<header class="bd-category-header my-2"><a href="' . $protocol . $baseurl . '/paste.php?id=' . $p_id . '" title="' . $title . '">' . $title . '</a>
							  	<a class="icon is-pulled-right" href="' . $protocol . $baseurl . '/' . $p_delete_link . '" title="' . $title . '">
							  		<i class="far fa-trash-alt has-text-grey" aria-hidden="true"></i>
								</a>' . '
								<a class="icon is-pulled-right has-tooltip-arrow has-tooltip-left-mobile has-tooltip-bottom-desktop has-tooltip-left-until-widescreen" data-tooltip="' . $p_time . '">
									<i class="far fa-clock has-text-grey" aria-hidden="true"></i>
								</a>' . '
							</header>';
					}
					?>
				<?php }
				// Display a message if the pastebin is empty
				$query  = "SELECT count(*) as count FROM pastes";
				$result = mysqli_query($con, $query);
				while ($row = mysqli_fetch_array($result)) {
					$totalpastes = $row['count'];
				}
				if ($totalpastes == '0') {
					echo $lang['emptypastebin'];
				} ?>
			</div>
		</nav>
		<hr>
	<?php }
	if (isset($_SESSION['username'])) { ?>
	<?php } else { ?>
		<!-- Guest message -->
		<nav id="categories" class="bd-categories is-hidden">
			<div class="bd-category ">
				<p class="text-body"><?php echo $lang['guestmsgbody']; ?></p>
			</div>
			<hr>
		</nav>
	<?php }
	if (isset($privatesite) && $privatesite == "on") {
	} else { ?>
		<!-- Recent Public Pastes -->
		<nav id="categories" class="bd-categories">
			<div class="bd-category ">
				<h1 class="subtitle is-4"><?php echo $lang['recentpastes']; ?></h1>
				<?php if (!isset($user_username)) {
					$res = getRecent($con, 16); //16 Pastes if you logged of
					while ($row = mysqli_fetch_array($res)) {
						$title =  Trim($row['title']);
						$p_id =  Trim($row['id']);
						$p_date = Trim($row['date']);
						$p_time = Trim($row['now_time']);
						$nowtime = time();
						$oldtime = $p_time;
						$p_time = conTime($nowtime - $oldtime);
						$title = truncate($title, 6, 18);
						$p_member = Trim($row['member']);
				?>
						<?php
						if ($mod_rewrite == '1') {
							echo '<header class="bd-category-header my-1">
									<a href="' . $protocol . $baseurl . '/' . $p_id . '" title="' . $title . '">' . $title . '</a>
									<a class="icon is-pulled-right has-tooltip-arrow has-tooltip-left-mobile has-tooltip-bottom-desktop has-tooltip-left-until-widescreen" data-tooltip="' . $p_time . '">
										<i class="far fa-clock has-text-grey" aria-hidden="true"></i>
									</a>
									<p class="subtitle is-7">' . 'by ' . '
										<i>' . $p_member . '</i>' . '
									</p>' .
								'</header>';
						} else {
							echo '<header class="bd-category-header my-1">
									<a href="' . $protocol . $baseurl . '/paste.php?id=' . $p_id . '" title="' . $title . '">' . $title . '</a>
									<a class="icon is-pulled-right has-tooltip-arrow has-tooltip-left-mobile has-tooltip-bottom-desktop has-tooltip-left-until-widescreen" data-tooltip="' . $p_time . '">
										<i class="far fa-clock has-text-grey" aria-hidden="true"></i>
									</a>
									<p class="subtitle is-7">' . 'by ' . '
										<b>' . $p_member . '</b>' . '
									</p>' .
								'</header>';
						}
						?>
					<?php }
				} else {
					$res = getRecent($con, 12); //12 Pastes if you logged in
					while ($row = mysqli_fetch_array($res)) {
						$title =  Trim($row['title']);
						$p_id =  Trim($row['id']);
						$p_date = Trim($row['date']);
						$p_time = Trim($row['now_time']);
						$nowtime = time();
						$oldtime = $p_time;
						$p_time = conTime($nowtime - $oldtime);
						$title = truncate($title, 6, 18);
						$p_member = Trim($row['member']);
					?>
						<?php
						if ($mod_rewrite == '1') {
							echo '<header class="bd-category-header my-1">
									<a href="' . $protocol . $baseurl . '/' . $p_id . '" title="' . $title . '">' . $title . '</a>
									<a class="icon is-pulled-right has-tooltip-arrow has-tooltip-left-mobile has-tooltip-bottom-desktop has-tooltip-left-until-widescreen" data-tooltip="' . $p_time . '">
										<i class="far fa-clock has-text-grey" aria-hidden="true"></i>
									</a>
									<p class="subtitle is-7">' . 'by ' . '
										<i>' . $p_member . '</i>' . '
									</p>' .
								'</header>';
						} else {
							echo '<header class="bd-category-header my-1">
									<a href="' . $protocol . $baseurl . '/paste.php?id=' . $p_id . '" title="' . $title . '">' . $title . '</a>
									<a class="icon is-pulled-right has-tooltip-arrow has-tooltip-left-mobile has-tooltip-bottom-desktop has-tooltip-left-until-widescreen" data-tooltip="' . $p_time . '">
										<i class="far fa-clock has-text-grey" aria-hidden="true"></i>
									</a>
									<p class="subtitle is-7">' . 'by ' . '
										<b>' . $p_member . '</b>' . '
									</p>' .
								'</header>';
						}
						?>
				<?php }
				}
				// Display a message if the pastebin is empty
				$query  = "SELECT count(*) as count FROM pastes";
				$result = mysqli_query($con, $query);
				while ($row = mysqli_fetch_array($result)) {
					$totalpastes = $row['count'];
				}
				if ($totalpastes == '0') {
					echo $lang['emptypastebin'];
				} ?>
			</div>
			<?php if (isset($_SESSION['username'])) { ?>
			<?php } else { ?>
				<?php echo $ads_1; ?>
			<?php } ?>
		</nav>
	<?php } ?>
</aside>