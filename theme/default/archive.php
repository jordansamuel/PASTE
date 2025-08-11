<?php
/*
 * Paste <https://github.com/boxlabss/PASTE>
 * Demo: https://paste.boxlabs.uk/
 * Licensed under GNU General Public License, version 3 or later.
 */
$protocol = paste_protocol();
?>
<div class="container-xl my-4">
    <div class="row">
        <?php if ($privatesite == "on"): ?>
            <div class="col-12">
                <div class="card text-center shadow-sm border-0" style="background-color: #212529; color: #fff;">
                    <div class="card-body py-5">
                        <i class="bi bi-lock" style="font-size: 5rem; color: #dc3545;"></i>
                        <h1 class="mt-3"><?php echo htmlspecialchars($lang['siteprivate'] ?? 'This site is private', ENT_QUOTES, 'UTF-8'); ?></h1>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-12">
                <div class="card shadow-sm border-0" style="background-color: #212529;">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center flex-wrap py-3">
                        <h1 class="h4 mb-0">
                            <?php echo htmlspecialchars($lang['archives'] ?? 'Archives', ENT_QUOTES, 'UTF-8'); ?>
                            <?php if ($search_query): ?> - <?php echo htmlspecialchars($lang['search_results_for'] ?? 'Search Results for', ENT_QUOTES, 'UTF-8'); ?> "<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>
                        </h1>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <form class="d-flex align-items-center" action="<?php echo htmlspecialchars($baseurl . ($mod_rewrite == '1' ? 'archive' : 'archive.php'), ENT_QUOTES, 'UTF-8'); ?>" method="get">
                                <input type="text" name="q" class="form-control me-2" style="background-color: #2a2a2a; border-color: #444; color: #fff;" placeholder="<?php echo htmlspecialchars($lang['search'] ?? 'Search pastes...', ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($search_query ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                            </form>
                            <form class="d-flex align-items-center" action="<?php echo htmlspecialchars($baseurl . ($mod_rewrite == '1' ? 'archive' : 'archive.php'), ENT_QUOTES, 'UTF-8'); ?>" method="get">
                                <?php if ($search_query): ?>
                                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php endif; ?>
                                <select name="sort" class="form-select me-2" style="width: auto; background-color: #2a2a2a; border-color: #444; color: #fff;">
                                    <option value="date_desc" <?php echo ($sort == 'date_desc') ? 'selected' : ''; ?>><?php echo htmlspecialchars($lang['sort_date_desc'] ?? 'Date (Newest)', ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="date_asc" <?php echo ($sort == 'date_asc') ? 'selected' : ''; ?>><?php echo htmlspecialchars($lang['sort_date_asc'] ?? 'Date (Oldest)', ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="title_asc" <?php echo ($sort == 'title_asc') ? 'selected' : ''; ?>><?php echo htmlspecialchars($lang['sort_title_asc'] ?? 'Title (A-Z)', ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="title_desc" <?php echo ($sort == 'title_desc') ? 'selected' : ''; ?>><?php echo htmlspecialchars($lang['sort_title_desc'] ?? 'Title (Z-A)', ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="code_asc" <?php echo ($sort == 'code_asc') ? 'selected' : ''; ?>><?php echo htmlspecialchars($lang['sort_code_asc'] ?? 'Syntax (A-Z)', ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="code_desc" <?php echo ($sort == 'code_desc') ? 'selected' : ''; ?>><?php echo htmlspecialchars($lang['sort_code_desc'] ?? 'Syntax (Z-A)', ENT_QUOTES, 'UTF-8'); ?></option>
                                </select>
                                <button type="submit" class="btn btn-outline-light"><?php echo htmlspecialchars($lang['sort'] ?? 'Sort', ENT_QUOTES, 'UTF-8'); ?></button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body" style="background-color: #2a2a2a; color: #fff;">
                        <?php if ($search_query && empty($pastes)): ?>
                            <div class="alert alert-info"><?php echo htmlspecialchars($lang['no_results'] ?? 'No results found for your search.', ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table id="archive" class="table table-hover table-bordered" style="background-color: #2a2a2a; color: #fff;">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col"><?php echo htmlspecialchars($lang['pastetitle'] ?? 'Title', ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th scope="col"><?php echo htmlspecialchars($lang['pastetime'] ?? 'Time', ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th scope="col"><?php echo htmlspecialchars($lang['pastesyntax'] ?? 'Syntax', ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th scope="col"><?php echo htmlspecialchars($lang['pastemember'] ?? 'Posted By', ENT_QUOTES, 'UTF-8'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        if (empty($pastes)) {
                                            echo '<tr><td colspan="4" class="text-center">' . htmlspecialchars($lang['emptypastebin'] ?? 'No pastes found', ENT_QUOTES, 'UTF-8') . '</td></tr>';
                                        } else {
                                            foreach ($pastes as $row) {
                                                $title = trim((string) ($row['title'] ?? 'Untitled'));
                                                $p_id = trim((string) ($row['id'] ?? ''));
                                                $p_code = trim((string) ($row['code'] ?? 'text'));
                                                $p_date = trim((string) ($row['date'] ?? ''));
                                                $p_time = (int) ($row['now_time'] ?? 0);
                                                $p_member = trim((string) ($row['member'] ?? 'Guest'));
                                                $p_time_ago = conTime(time() - $p_time);
                                                $title = truncate($title, 20, 50);
                                                $url = $mod_rewrite == '1' 
                                                    ? htmlspecialchars($baseurl . '' . $p_id, ENT_QUOTES, 'UTF-8')
                                                    : htmlspecialchars($baseurl . 'paste.php?id=' . $p_id, ENT_QUOTES, 'UTF-8');
                                                ?>
                                                <tr>
                                                    <td><a href="<?php echo $url; ?>" title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>" class="text-primary"><?php echo ucfirst(htmlspecialchars($title, ENT_QUOTES, 'UTF-8')); ?></a></td>
                                                    <td><?php echo htmlspecialchars($p_time_ago, ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><span class="badge bg-primary"><?php echo htmlspecialchars(strtoupper($p_code), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                    <td><?php echo htmlspecialchars($p_member, ENT_QUOTES, 'UTF-8'); ?></td>
                                                </tr>
                                                <?php
                                            }
                                        }
                                    } catch (Exception $e) {
                                        echo '<tr><td colspan="4" class="text-center text-danger">Error fetching pastes: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($totalItems > 0 && $totalPages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php
                                    $range = 2;
                                    $prevPage = $page > 1 ? $page - 1 : 1;
                                    $queryParamsPrev = http_build_query(array_merge($_GET, ['page' => $prevPage]));
                                    $disabledPrev = $page == 1 ? ' disabled' : '';
                                    echo '<li class="page-item' . $disabledPrev . '"><a class="page-link btn btn-primary btn-sm" href="?' . $queryParamsPrev . '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';

                                    for ($i = 1; $i <= $totalPages; $i++) {
                                        $isActive = $i === $page ? ' active' : '';
                                        $queryParams = http_build_query(array_merge($_GET, ['page' => $i]));
                                        if ($i === 1 || $i === $totalPages || ($i >= $page - $range && $i <= $page + $range)) {
                                            echo '<li class="page-item' . $isActive . '"><a class="page-link" href="?' . $queryParams . '">' . $i . '</a></li>';
                                        } elseif ($i === $page - ($range + 1) || $i === $page + ($range + 1)) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }

                                    $nextPage = $page < $totalPages ? $page + 1 : $totalPages;
                                    $queryParamsNext = http_build_query(array_merge($_GET, ['page' => $nextPage]));
                                    $disabledNext = $page == $totalPages ? ' disabled' : '';
                                    echo '<li class="page-item' . $disabledNext . '"><a class="page-link" href="?' . $queryParamsNext . '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
                                    ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($privatesite != "on"): ?>
        <div class="text-center my-4">
            <?php echo htmlspecialchars($ads_2 ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
</div>
<style>
    .table-hover tbody tr:hover { background-color: #343a40; }
    .pagination .page-link { background-color: #2a2a2a; border-color: #444; color: #fff; }
    .pagination .page-link:hover { background-color: #0d6efd; }
    .pagination .active .page-link { background-color: #0d6efd; border-color: #0d6efd; }
    .badge.bg-primary { background-color: #0d6efd; }
</style>
<?php require_once('theme/' . ($default_theme ?? 'default') . '/footer.php'); ?>