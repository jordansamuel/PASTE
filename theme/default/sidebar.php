<?php
/*
 * Paste $v3.1 2025/08/16 https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 *
 * https://phpaste.sourceforge.io/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in LICENCE for more details.
 */
?>
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

                <!-- // ajax message; "paste deleted" -->
                <div id="sidebar-msg" class="px-3 py-2" style="display:none;"></div>

                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="sidebar-paste-list">
                        <?php
                        $username = (string) ($_SESSION['username'] ?? '');
                        if ($username === '') {
                            echo '<p class="p-4 text-muted">Error: User not logged in.</p>';
                        } else {
                            try {
                                $pastes = getUserRecent($pdo, $username, 10);
                                if (empty($pastes)) {
                                    echo '<p class="p-4 text-muted">No pastes yet. Create one!</p>';
                                } else {
                                    foreach ($pastes as $row) {
                                        $title = (string) ($row['title'] ?? 'Untitled');
                                        $p_id  = (string) ($row['id'] ?? '');
                                        $p_time= (int) ($row['now_time'] ?? 0);
                                        $p_code= (string) ($row['code'] ?? 'Unknown');
                                        $p_time_ago = conTime($p_time);
                                        $title = truncate($title, 6, 15);
                                        // controller delete link (same for rewrite)
                                        $p_delete_link = "user.php?del&user=" . urlencode($username) . "&id=" . urlencode($p_id);
                                        ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center bg-dark text-light"
                                             id="paste-item-<?php echo htmlspecialchars($p_id, ENT_QUOTES, 'UTF-8'); ?>">
                                            <a href="<?php echo htmlspecialchars(
                                                $baseurl . ($mod_rewrite ? $p_id : 'paste.php?id=' . $p_id),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>" title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>" class="text-light fw-medium text-decoration-none">
                                                <?php echo htmlspecialchars(ucfirst($title), ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                            <div class="ms-2">
                                                <a class="btn btn-sm btn-outline-danger me-1 py-0 px-1 js-del"
                                                   href="<?php echo htmlspecialchars($baseurl . $p_delete_link, ENT_QUOTES, 'UTF-8'); ?>"
                                                   data-paste-id="<?php echo htmlspecialchars($p_id, ENT_QUOTES, 'UTF-8'); ?>"
                                                   title="Delete <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-light btn-sm popover-clock py-0 px-1"
                                                        data-bs-container="body" data-bs-toggle="popover" data-bs-placement="left"
                                                        data-bs-content="Posted: <?php echo htmlspecialchars($p_time_ago, ENT_QUOTES, 'UTF-8'); ?> ago.<br>Syntax: <?php echo htmlspecialchars(strtoupper($p_code), ENT_QUOTES, 'UTF-8'); ?>"
                                                        title="Paste Details">
                                                    <i class="bi bi-clock"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                }
                            } catch (Exception $e) {
                                echo '<p class="p-4 text-danger">Error fetching pastes: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
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
                <div class="card-body rounded-1 border border-light">
                    <h6><?php echo htmlspecialchars($lang['guestmsgtitle'] ?? 'Guest', ENT_QUOTES, 'UTF-8'); ?></h6>
                    <p class="p-1 text-muted"><?php echo $lang['guestmsgbody'] ?? 'Sign in to manage your pastes.'; ?></p>
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
                                echo '<p class="p-4 text-muted">' . htmlspecialchars($lang['emptypastebin'] ?? 'No pastes found', ENT_QUOTES, 'UTF-8') . '</p>';
                            } else {
                                foreach ($pastes as $row) {
                                    $title = (string) ($row['title'] ?? 'Untitled');
                                    $p_id  = (string) ($row['id'] ?? '');
                                    $p_time= (int) ($row['now_time'] ?? 0);
                                    $p_code= (string) ($row['code'] ?? 'Unknown');
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
                                            <button type="button" class="btn btn-outline-light btn-sm popover-clock py-0 px-1"
                                                    data-bs-container="body" data-bs-toggle="popover" data-bs-placement="left"
                                                    data-bs-content="Posted: <?php echo htmlspecialchars($p_time_ago, ENT_QUOTES, 'UTF-8'); ?> ago.<br>Syntax: <?php echo htmlspecialchars(strtoupper($p_code), ENT_QUOTES, 'UTF-8'); ?>"
                                                    title="Paste Details">
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
// // popovers
document.addEventListener('DOMContentLoaded', function() {
    var popoverTriggerList = [].slice.call(document.querySelectorAll('.popover-clock'));
    var popoverList = popoverTriggerList.map(function (el) {
        return new bootstrap.Popover(el, {
            trigger: 'click',
            container: 'body',
            placement: 'left',
            html: true,
            customClass: 'touch-popover',
            content: el.getAttribute('data-bs-content'),
            title: el.getAttribute('title')
        });
    });
    document.addEventListener('click', function (e) {
        popoverList.forEach(function (p) {
            if (!p._element.contains(e.target) && p._isShown && p._isShown()) p.hide();
        });
    });
    popoverTriggerList.forEach(function (el) {
        el.addEventListener('touchstart', function (e) {
            e.preventDefault();
            var p = bootstrap.Popover.getInstance(el);
            if (p && p._isShown && p._isShown()) p.hide(); else if (p) p.show();
        }, { passive: false });
    });
});

// // ajax delete
(function(){
    function msg(type, text) {
        var box = document.getElementById('sidebar-msg');
        if (!box) return;
        box.style.display = 'block';
        box.className = 'px-3 py-2 ' + (type === 'ok' ? 'text-bg-success' : 'text-bg-danger');
        box.textContent = text;
        setTimeout(function(){ box.style.display = 'none'; }, 2500);
    }
    function findItem(el) {
        while (el && el !== document) {
            if (el.classList && el.classList.contains('list-group-item')) return el;
            el = el.parentNode;
        }
        return null;
    }
    document.addEventListener('click', function(e){
        var t = e.target;
        while (t && t !== document && !(t.tagName === 'A' && t.classList.contains('js-del'))) t = t.parentNode;
        if (!t || t === document) return;
        e.preventDefault();

        var href = t.getAttribute('href');
        var pid  = t.getAttribute('data-paste-id') || '';
        if (!href || !pid) { window.location.href = href; return; }

        if (!confirm('Delete this paste? This cannot be undone.')) return;

        var row = findItem(t);
        var old = t.innerHTML;
        t.classList.add('disabled');
        t.setAttribute('aria-disabled', 'true');
        t.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        if (row) row.style.opacity = '0.5';

        var fd = new FormData();
        fd.set('ajax', '1');

        fetch(href, { method: 'POST', credentials: 'same-origin', body: fd })
            .then(function(res){ return res.json().catch(function(){ return null; }); })
            .then(function(data){
                if (data && typeof data.success !== 'undefined') {
                    if (data.success) {
                        if (row) row.remove();
                        msg('ok', data.message || 'Paste deleted.');
                        return;
                    } else {
                        if (row) row.style.opacity = '';
                        t.innerHTML = old;
                        t.classList.remove('disabled');
                        t.removeAttribute('aria-disabled');
                        msg('err', data.message || 'Delete failed.');
                        return;
                    }
                }
                // // non-json: fall back to navigation
                window.location.href = href;
            })
            .catch(function(){
                window.location.href = href;
            });
    });
})();
</script>
