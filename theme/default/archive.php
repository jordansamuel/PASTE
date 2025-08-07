<?php
/*
 * Paste <https://github.com/boxlabss/PASTE>
 * Demo: https://paste.boxlabs.uk/
 * Licensed under GNU General Public License, version 3 or later.
 */
$protocol = paste_protocol();

// Pagination settings
$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;
?>
<div class="container-xl my-4">
    <div class="row">
        <?php if ($privatesite == "on"): ?>
            <div class="col-lg-10">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-lock" style="font-size: 5rem;"></i>
                        <h1><?php echo htmlspecialchars($lang['siteprivate'] ?? 'This site is private'); ?></h1>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <h1><?php echo htmlspecialchars($lang['archives'] ?? 'Archives'); ?></h1>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="archive" class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th><?php echo htmlspecialchars($lang['pastetitle'] ?? 'Title'); ?></th>
                                        <th><?php echo htmlspecialchars($lang['pastetime'] ?? 'Time'); ?></th>
                                        <th><?php echo htmlspecialchars($lang['pastesyntax'] ?? 'Syntax'); ?></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th><?php echo htmlspecialchars($lang['pastetitle'] ?? 'Title'); ?></th>
                                        <th><?php echo htmlspecialchars($lang['pastetime'] ?? 'Time'); ?></th>
                                        <th><?php echo htmlspecialchars($lang['pastesyntax'] ?? 'Syntax'); ?></th>
                                    </tr>
                                </tfoot>
                                <tbody>
                                    <?php
                                    try {
                                        $res = getRecent($pdo, 100); // Assuming max 100 pastes
                                        if (empty($res)) {
                                            echo '<tr><td colspan="3">' . htmlspecialchars($lang['emptypastebin'] ?? 'No pastes found') . '</td></tr>';
                                        } else {
                                            $totalItems = count($res);
                                            $totalPages = ceil($totalItems / $perPage);
                                            $pagedRes = array_slice($res, $offset, $perPage);
                                            foreach ($pagedRes as $row) {
                                                $title = trim((string) ($row['title'] ?? 'Untitled'));
                                                $p_id = trim((string) ($row['id'] ?? ''));
                                                $p_code = trim((string) ($row['code'] ?? 'text'));
                                                $p_date = trim((string) ($row['date'] ?? ''));
                                                $p_time = (int) ($row['now_time'] ?? 0);
                                                $p_time_ago = conTime(time() - $p_time);
                                                $title = truncate($title, 20, 50);
                                                $url = $mod_rewrite == '1' 
                                                    ? htmlspecialchars( $baseurl . '' . $p_id)
                                                    : htmlspecialchars( $baseurl . 'paste.php?id=' . $p_id);
                                                ?>
                                                <tr>
                                                    <td><a href="<?php echo $url; ?>" title="<?php echo htmlspecialchars($title); ?>"><?php echo ucfirst(htmlspecialchars($title)); ?></a></td>
                                                    <td><?php echo htmlspecialchars($p_time_ago); ?></td>
                                                    <td><?php echo htmlspecialchars(strtoupper($p_code)); ?></td>
                                                </tr>
                                                <?php
                                            }
                                        }
                                    } catch (Exception $e) {
                                        echo '<tr><td colspan="3">Error fetching pastes: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (!empty($res) && $totalPages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center mt-3">
                                    <?php
                                    $range = 2; // Number of pages to show around current page
                                    for ($i = 1; $i <= $totalPages; $i++) {
                                        $isActive = $i === $page ? ' active' : '';
                                        $disabled = $i === $page ? ' disabled' : '';
                                        if ($i === 1 || $i === $totalPages || ($i >= $page - $range && $i <= $page + $range)) {
                                            echo '<li class="page-item' . $isActive . '"><a class="page-link' . $disabled . '" href="?page=' . $i . '">' . $i . '</a></li>';
                                        } elseif ($i === $page - ($range + 1) || $i === $page + ($range + 1)) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }
                                    ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php require_once('theme/' . ($default_theme ?? 'default') . '/sidebar.php'); ?>
        <?php endif; ?>
    </div>
    <?php if ($privatesite != "on"): ?>
        <div class="text-center mb-4">
            <?php echo htmlspecialchars($ads_2 ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once('theme/' . ($default_theme ?? 'default') . '/footer.php'); ?>