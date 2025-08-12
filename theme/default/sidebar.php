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

</style>
<div class="col-lg-12 sidebar-container">
    <?php if (isset($_SESSION['username'])): ?>
        <!-- My Pastes -->
        <div class="col-12">
            <div class="card rounded-3 mb-4">
                <div class="card-header text-light rounded-top">
                    <h6 class="mb-0">Hello <?php echo htmlspecialchars((string) ($_SESSION['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        <small class="ms-2">
                            <a class="text-light text-decoration-none" href="<?php echo htmlspecialchars(
                                $baseurl . ($mod_rewrite ? 'user/' . urlencode((string) ($_SESSION['username'] ?? '')) : 'user.php?user=' . urlencode((string) ($_SESSION['username'] ?? ''))),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>" target="_self"><?php echo htmlspecialchars($lang['mypastes'] ?? 'My Pastes', ENT_QUOTES, 'UTF-8'); ?></a>
                        </small>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php
                        $username = (string) ($_SESSION['username'] ?? '');
                        if ($username === '') {
                            echo '<p class="text-muted">Error: User not logged in.</p>';
                        } else {
                            try {
                                $pastes = getUserRecent($pdo, $username, 10);
                                if (empty($pastes)) {
                                    echo '<p class="text-muted">No pastes yet. Create one!</p>';
                                } else {
                                    foreach ($pastes as $row) {
                                        $title = (string) ($row['title'] ?? 'Untitled');
                                        $p_id = (string) ($row['id'] ?? '');
                                        $p_date = (string) ($row['date'] ?? '');
                                        $p_time = (int) ($row['now_time'] ?? 0);
                                        $p_code = (string) ($row['code'] ?? 'Unknown');
                                        $p_time_ago = conTime($p_time);
                                        $title = truncate($title, 6, 15);
                                        $p_delete_link = $mod_rewrite ? "user.php?del&user=" . urlencode($username) . "&id=" . urlencode($p_id)
                                                                    : "user.php?del&user=" . urlencode($username) . "&id=" . urlencode($p_id);
                                        ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center bg-dark text-light">
                                            <a href="<?php echo htmlspecialchars(
                                                $baseurl . ($mod_rewrite ? $p_id : 'paste.php?id=' . $p_id),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>" title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>" class="text-light fw-medium text-decoration-none">
                                                <?php echo htmlspecialchars(ucfirst($title), ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                            <div class="ms-2">
                                                <a class="btn btn-sm btn-outline-danger me-1 py-0 px-1" href="<?php echo htmlspecialchars($baseurl . $p_delete_link, ENT_QUOTES, 'UTF-8'); ?>" data-paste-id="<?php echo htmlspecialchars($p_id, ENT_QUOTES, 'UTF-8'); ?>" title="Delete <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-light btn-sm popover-clock py-0 px-1" data-bs-container="body" data-bs-toggle="popover" data-bs-placement="left" data-bs-content="Posted: <?php echo htmlspecialchars($p_time_ago, ENT_QUOTES, 'UTF-8'); ?> ago.<br>Syntax: <?php echo htmlspecialchars(strtoupper($p_code), ENT_QUOTES, 'UTF-8'); ?>" title="Paste Details">
                                                    <i class="bi bi-clock"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                }
                            } catch (Exception $e) {
                                echo '<p class="text-danger">Error fetching pastes: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif (!isset($privatesite) || $privatesite !== 'on'): ?>
        <!-- Guest message -->
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body p-4 rounded-1 border border-light">
                    <h6 class="text-blue"><?php echo htmlspecialchars($lang['guestmsgtitle'] ?? 'Guest', ENT_QUOTES, 'UTF-8'); ?></h6>
                    <p class="text-muted mb-0"><?php echo $lang['guestmsgbody'] ?? 'Sign in to manage your pastes.'; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!isset($privatesite) || $privatesite !== 'on'): ?>
        <!-- Recent Public Pastes -->
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header rounded-top">
                    <?php echo htmlspecialchars($lang['recentpastes'] ?? 'Recent Pastes', ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php
                        try {
                            $pastes = getRecent($pdo, 10);
                            if (empty($pastes)) {
                                echo '<p class="text-muted">' . htmlspecialchars($lang['emptypastebin'] ?? 'No pastes found', ENT_QUOTES, 'UTF-8') . '</p>';
                            } else {
                                foreach ($pastes as $row) {
                                    $title = (string) ($row['title'] ?? 'Untitled');
                                    $p_id = (string) ($row['id'] ?? '');
                                    $p_date = (string) ($row['date'] ?? '');
                                    $p_time = (int) ($row['now_time'] ?? 0);
                                    $p_code = (string) ($row['code'] ?? 'Unknown');
                                    $p_time_ago = conTime($p_time);
                                    $title = truncate($title, 6, 15);
                                    ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center text-light">
                                        <a href="<?php echo htmlspecialchars(
                                            $baseurl . ($mod_rewrite ? $p_id : 'paste.php?id=' . $p_id),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>" title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>" class="text-light fw-medium text-decoration-none">
                                            <?php echo htmlspecialchars(ucfirst($title), ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                        <div class="ms-2">
                                            <button type="button" class="btn btn-outline-light btn-sm popover-clock py-0 px-1" data-bs-container="body" data-bs-toggle="popover" data-bs-placement="left" data-bs-content="Posted: <?php echo htmlspecialchars($p_time_ago, ENT_QUOTES, 'UTF-8'); ?> ago.<br>Syntax: <?php echo htmlspecialchars(strtoupper($p_code), ENT_QUOTES, 'UTF-8'); ?>" title="Paste Details">
                                                <i class="bi bi-clock"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                        } catch (Exception $e) {
                            echo '<p class="text-danger">Error fetching recent pastes: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!isset($_SESSION['username']) && (!isset($privatesite) || $privatesite !== 'on')): ?>
        <div class="col-12 text-center mt-3">
            <?php echo $ads_1 ?? ''; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var popoverTriggerList = [].slice.call(document.querySelectorAll('.popover-clock'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl, {
            trigger: 'click',
            container: 'body',
            placement: 'left',
            html: true,
            customClass: 'touch-popover',
            content: popoverTriggerEl.getAttribute('data-bs-content'),
            title: popoverTriggerEl.getAttribute('title')
        });
    });

    document.addEventListener('click', function (e) {
        popoverList.forEach(function (popover) {
            if (!popover._element.contains(e.target) && popover._isShown()) {
                popover.hide();
            }
        });
    });

    popoverTriggerList.forEach(function (el) {
        el.addEventListener('touchstart', function (e) {
            e.preventDefault();
            var popover = bootstrap.Popover.getInstance(el);
            if (popover && popover._isShown()) {
                popover.hide();
            } else if (popover) {
                popover.show();
            }
        }, { passive: false });
    });
});
</script>