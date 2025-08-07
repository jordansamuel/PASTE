<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
declare(strict_types=1);
?>
<!-- Sidebar -->
<div class="col-lg-2 mt-4 mt-lg-0">
    <?php if (isset($_SESSION['username'])): ?>
        <!-- My Pastes -->
        <div class="card mt-3">
            <div class="card-header">
                <h6>Hello <?php echo htmlspecialchars((string) ($_SESSION['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    <small>
                        <a href="<?php echo htmlspecialchars(
                            $baseurl . ($mod_rewrite ? 'user/' . urlencode((string) ($_SESSION['username'] ?? '')) : 'user.php?user=' . urlencode((string) ($_SESSION['username'] ?? ''))),
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>" target="_self"><?php echo htmlspecialchars($lang['mypastes'] ?? 'My Pastes', ENT_QUOTES, 'UTF-8'); ?></a>
                    </small>
                </h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php
                    $username = (string) ($_SESSION['username'] ?? '');
                    if ($username === '') {
                        echo '<p>Error: User not logged in.</p>';
                    } else {
                        try {
                            $pastes = getUserRecent($pdo, $username, 10);
                            if (empty($pastes)) {
                                echo '<p>No pastes yet. Create one!</p>';
                            } else {
                                foreach ($pastes as $row) {
                                    $title = (string) ($row['title'] ?? 'Untitled');
                                    $p_id = (string) ($row['id'] ?? '');
                                    $p_date = (string) ($row['date'] ?? '');
                                    $p_time = (int) ($row['now_time'] ?? 0);
                                    $p_time_ago = conTime(time() - $p_time);
                                    $title = truncate($title, 6, 15);
                                    $p_delete_link = $mod_rewrite ? "user.php?del&user=" . urlencode($username) . "&id=" . urlencode($p_id)
                                                                : "user.php?del&user=" . urlencode($username) . "&id=" . urlencode($p_id);
                                    ?>
                                    <p class="mb-0">
                                        <a href="<?php echo htmlspecialchars(
                                            $baseurl . ($mod_rewrite ? $p_id : 'paste.php?id=' . $p_id),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>" title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($title), ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                        <a class="delete-paste icon" href="<?php echo htmlspecialchars($baseurl . $p_delete_link, ENT_QUOTES, 'UTF-8'); ?>" data-paste-id="<?php echo htmlspecialchars($p_id, ENT_QUOTES, 'UTF-8'); ?>" title="Delete <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </a>
                                        <button type="button" class="btn btn-dark btn-sm float-end" data-bs-container="body" data-bs-toggle="popover" data-bs-placement="left" data-bs-trigger="hover focus" data-bs-content="<?php echo htmlspecialchars($p_time_ago, ENT_QUOTES, 'UTF-8'); ?>" title="Posted">
                                            <i class="bi bi-clock"></i>
                                        </button>
                                    </p>
                                    <?php
                                }
                            }
                        } catch (Exception $e) {
                            echo '<p>Error fetching pastes: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Guest message -->
        <div class="card mt-3" style="background:#399bff;">
            <div class="card-body">
                <p class="text"><?php echo htmlspecialchars($lang['guestmsgtitle'] ?? 'Guest', ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="text-body"><?php echo $lang['guestmsgbody'] ?? 'Sign in to manage your pastes.'; ?></p>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!isset($privatesite) || $privatesite !== 'on'): ?>
        <!-- Recent Public Pastes -->
        <div class="card mt-3">
            <div class="card-header"><?php echo htmlspecialchars($lang['recentpastes'] ?? 'Recent Pastes', ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php
                    try {
                        $pastes = getRecent($pdo, 10);
                        if (empty($pastes)) {
                            echo htmlspecialchars($lang['emptypastebin'] ?? 'No pastes found', ENT_QUOTES, 'UTF-8');
                        } else {
                            foreach ($pastes as $row) {
                                $title = (string) ($row['title'] ?? 'Untitled');
                                $p_id = (string) ($row['id'] ?? '');
                                $p_date = (string) ($row['date'] ?? '');
                                $p_time = (int) ($row['now_time'] ?? 0);
                                $p_time_ago = conTime(time() - $p_time);
                                $title = truncate($title, 6, 15);
                                ?>
                                <p class="mb-0">
                                    <a href="<?php echo htmlspecialchars(
                                        $baseurl . ($mod_rewrite ? $p_id : 'paste.php?id=' . $p_id),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>" title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars(ucfirst($title), ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                    <button type="button" class="btn btn-dark btn-sm float-end" data-bs-container="body" data-bs-toggle="popover" data-bs-placement="left" data-bs-trigger="hover focus" data-bs-content="<?php echo htmlspecialchars($p_time_ago, ENT_QUOTES, 'UTF-8'); ?>" title="Posted <?php echo htmlspecialchars($p_time_ago, ENT_QUOTES, 'UTF-8'); ?> ago">
                                        <i class="bi bi-clock"></i>
                                    </button>
                                </p>
                                <?php
                            }
                        }
                    } catch (Exception $e) {
                        echo '<p>Error fetching recent pastes: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!isset($_SESSION['username'])): ?>
        <div class="text-center">
            <?php echo $ads_1 ?? ''; ?>
        </div>
    <?php endif; ?>
</div>