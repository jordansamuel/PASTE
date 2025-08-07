<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE> - Default theme
 * Licensed under the GNU General Public License, version 3 or later.
 */
declare(strict_types=1);

// Protocol detection (assumes paste_protocol() is defined)
$protocol = paste_protocol();
?>

<div class="col-md-3 col-lg-2">
<?php if (isset($_SESSION['token'])): ?>
    <!-- My Pastes -->
    <div class="panel panel-default">
        <div class="panel-title">
            <h6>Hello <?php echo htmlspecialchars((string) ($_SESSION['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                <small>
                    <a href="<?php echo htmlspecialchars(
                        $protocol . $baseurl . ($mod_rewrite ? '/user/' . urlencode((string) ($_SESSION['username'] ?? '')) : '/user.php?user=' . urlencode((string) ($_SESSION['username'] ?? ''))),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>" target="_self"><?php echo htmlspecialchars($lang['mypastes'], ENT_QUOTES, 'UTF-8'); ?></a>
                </small>
            </h6>
        </div>
        <div class="panel-body">
            <div class="list-widget pagination-content">
                <?php
                $username = (string) ($_SESSION['username'] ?? '');
                if ($username === '') {
                    echo '<p>Error: User not logged in.</p>';
                } else {
                    $pastes = getUserRecent($pdo, $username, 10);
                    foreach ($pastes as $row) {
                        $title = (string) ($row['title'] ?? '');
                        $p_id = (string) ($row['id'] ?? '');
                        $p_date = (string) ($row['date'] ?? '');
                        $p_time = (int) ($row['now_time'] ?? 0);
                        $p_time_ago = conTime(time() - $p_time);
                        $title = truncate($title, 6, 15);
                        $p_delete_link = $mod_rewrite ? "user.php?del&user=" . urlencode($username) . "&id=" . urlencode($p_id)
                                                     : "user.php?del&user=" . urlencode($username) . "&id=" . urlencode($p_id);
                ?>
                <p class="no-margin">
                    <a href="<?php echo htmlspecialchars(
                        $protocol . $baseurl . ($mod_rewrite ? '/' . $p_id : '/paste.php?id=' . $p_id),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>" title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars(ucfirst($title), ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <a class="icon" href="<?php echo htmlspecialchars($protocol . $baseurl . '/' . $p_delete_link, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa fa-trash-o fa-lg" aria-hidden="true"></i>
                    </a>
                    <button type="button" class="btn-light pull-right" data-container="body" data-toggle="popover" data-placement="left" data-trigger="focus" data-content="<?php echo htmlspecialchars($p_time_ago, ENT_QUOTES, 'UTF-8'); ?>" title="">
                        <i class="fa fa-clock-o fa-lg" aria-hidden="true"></i>
                    </button>
                </p>
                <?php
                    }
                    // Check if pastebin is empty
                    $stmt = $pdo->query("SELECT COUNT(*) FROM pastes");
                    if ($stmt->fetchColumn() == 0) {
                        echo htmlspecialchars($lang['emptypastebin'], ENT_QUOTES, 'UTF-8');
                    }
                }
                ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Guest message -->
    <div class="widget guestmsg" style="background:#399bff;">
        <p class="text"><?php echo htmlspecialchars($lang['guestmsgtitle'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="text-body"><?php echo $lang['guestmsgbody']; ?></p>
    </div>
<?php endif; ?>

<?php if (!isset($privatesite) || $privatesite !== 'on'): ?>
    <!-- Recent Public Pastes -->
    <div class="panel panel-default">
        <div class="panel-title"><?php echo htmlspecialchars($lang['recentpastes'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="panel-body">
            <div class="list-widget pagination-content">
                <?php
                $pastes = getRecent($pdo, 10);
                foreach ($pastes as $row) {
                    $title = (string) ($row['title'] ?? '');
                    $p_id = (string) ($row['id'] ?? '');
                    $p_date = (string) ($row['date'] ?? '');
                    $p_time = (int) ($row['now_time'] ?? 0);
                    $p_time_ago = conTime(time() - $p_time);
                    $title = truncate($title, 6, 15);
                ?>
                <p class="no-margin">
                    <a href="<?php echo htmlspecialchars(
                        $protocol . $baseurl . ($mod_rewrite ? '/' . $p_id : '/paste.php?id=' . $p_id),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>" title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars(ucfirst($title), ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <button type="button" class="btn-light pull-right" data-container="body" data-toggle="popover" data-placement="left" data-trigger="focus" data-content="<?php echo htmlspecialchars($p_time_ago, ENT_QUOTES, 'UTF-8'); ?>" title="">
                        <i class="fa fa-clock-o fa-lg" aria-hidden="true"></i>
                    </button>
                </p>
                <?php
                }
                // Check if pastebin is empty
                $stmt = $pdo->query("SELECT COUNT(*) FROM pastes");
                if ($stmt->fetchColumn() == 0) {
                    echo htmlspecialchars($lang['emptypastebin'], ENT_QUOTES, 'UTF-8');
                }
                ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!isset($_SESSION['username'])): ?>
    <div style="text-align:center;">
        <?php echo $ads_1; ?>
    </div>
<?php endif; ?>
</div>