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
?>

<main class="bd-main">
	<div class="bd-side-background"></div>
	<div class="bd-main-container container">
		<div class="bd-duo">
			<div class="bd-lead">
				<?php if ($privatesite == "on") { // Site permissions 
				?>
					<h1 class="title is-5"><?php echo $lang['siteprivate']; ?></h1>
				<?php } else { ?>
					<h1 class="title is-4"><?php echo $lang['archives']; ?><h1>
							<table id="archive" class="table is-fullwidth is-hoverable">
								<thead>
									<tr>
										<th><?php echo $lang['pastetitle']; ?></th>
										<th><?php echo $lang['pastetime']; ?></th>
										<th><?php echo $lang['pastesyntax']; ?></th>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<th><?php echo $lang['pastetitle']; ?></th>
										<th><?php echo $lang['pastetime']; ?></th>
										<th><?php echo $lang['pastesyntax']; ?></th>
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
														<td>
															<a href="' . $p_id . '" title="' . $title . '">' . ($title) . '</a>
														</td>    
														<td>
															' . $p_date . '
														</td>
														<td>
															' . strtoupper($p_code) . '
														</td>
													</tr>';
										} else {
											echo ' <tr> 
														<td>
															<a href="paste.php?id=' . $p_id . '" title="' . $title . '">' . ($title) . '</a>
														</td>    
														<td>
															' . $p_date . '
														</td>
														<td>
															' . strtoupper($p_code) . '
														</td>
													</tr>';
										}
									}
									?>
								</tbody>
							</table>
							<?php echo $ads_2; ?>
			</div>
		<?php }
				if ($privatesite == "on") { // Remove sidebar if site is private
				} else {
					require_once('theme/' . $default_theme . '/sidebar.php');
				}
		?>
		</div>
	</div>
</main>