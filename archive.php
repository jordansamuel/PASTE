<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
session_start();

require_once('config.php');
require_once('includes/functions.php');

// Disable non-GET requests
if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    http_response_code(405);
    exit('405 Method Not Allowed.');
}

$date = date('Y-m-d H:i:s'); // Use DATETIME format for database
$ip = $_SERVER['REMOTE_ADDR'];
$data_ip = file_get_contents('tmp/temp.tdata');

// Database Connection (PDO from config.php)
global $pdo;

try {
    // Get site info
    $stmt = $pdo->query("SELECT * FROM site_info WHERE id = '1'");
    $row = $stmt->fetch();
    $title = trim($row['title']);
    $des = trim($row['des']);
    $baseurl = trim($row['baseurl']);
    $keyword = trim($row['keyword']);
    $site_name = trim($row['site_name']);
    $email = trim($row['email']);
    $twit = trim($row['twit']);
    $face = trim($row['face']);
    $gplus = trim($row['gplus']);
    $ga = trim($row['ga']);
    $additional_scripts = trim($row['additional_scripts']);

    // Set theme and language
    $stmt = $pdo->query("SELECT * FROM interface WHERE id = '1'");
    $row = $stmt->fetch();
    $default_lang = trim($row['lang']);
    $default_theme = trim($row['theme']);
    require_once("langs/$default_lang");

    $p_title = $lang['archive'];

    // Check if IP is banned
    if (is_banned($pdo, $ip)) die($lang['banned']);

    // Site permissions
    $stmt = $pdo->query("SELECT * FROM site_permissions WHERE id = 1 LIMIT 1");
    $row = $stmt->fetch();
    $siteprivate = trim($row['siteprivate']);
    $privatesite = ($siteprivate === '0' || $siteprivate === 0) ? '0' : '1';

    // Logout
    if (isset($_GET['logout'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        unset($_SESSION['token']);
        unset($_SESSION['oauth_uid']);
        unset($_SESSION['username']);
        session_destroy();
    }

    // Page views
    $stmt = $pdo->query("SELECT MAX(id) AS last_id FROM page_view");
    $row = $stmt->fetch();
    $last_id = $row['last_id'];

    if ($last_id) {
        $stmt = $pdo->prepare("SELECT * FROM page_view WHERE id = ?");
        $stmt->execute([$last_id]);
        $row = $stmt->fetch();
        $last_date = $row['date'];

        if ($last_date == $date) {
            if (str_contains_polyfill($data_ip, $ip)) {
                $stmt = $pdo->prepare("SELECT tpage FROM page_view WHERE id = ?");
                $stmt->execute([$last_id]);
                $last_tpage = trim($stmt->fetchColumn()) + 1;
                $stmt = $pdo->prepare("UPDATE page_view SET tpage = ? WHERE id = ?");
                $stmt->execute([$last_tpage, $last_id]);
            } else {
                $stmt = $pdo->prepare("SELECT tpage, tvisit FROM page_view WHERE id = ?");
                $stmt->execute([$last_id]);
                $row = $stmt->fetch();
                $last_tpage = trim($row['tpage']) + 1;
                $last_tvisit = trim($row['tvisit']) + 1;
                $stmt = $pdo->prepare("UPDATE page_view SET tpage = ?, tvisit = ? WHERE id = ?");
                $stmt->execute([$last_tpage, $last_tvisit, $last_id]);
                file_put_contents('tmp/temp.tdata', $data_ip . "\r\n" . $ip);
            }
        } else {
            unlink("tmp/temp.tdata");
            $data_ip = "";
            $stmt = $pdo->prepare("INSERT INTO page_view (date, tpage, tvisit) VALUES (?, '1', '1')");
            $stmt->execute([$date]);
            file_put_contents('tmp/temp.tdata', $data_ip . "\r\n" . $ip);
        }
    } else {
        unlink("tmp/temp.tdata");
        $data_ip = "";
        $stmt = $pdo->prepare("INSERT INTO page_view (date, tpage, tvisit) VALUES (?, '1', '1')");
        $stmt->execute([$date]);
        file_put_contents('tmp/temp.tdata', $data_ip . "\r\n" . $ip);
    }

    // Ads
    $stmt = $pdo->query("SELECT * FROM ads WHERE id = '1'");
    $row = $stmt->fetch();
    $text_ads = trim($row['text_ads']);
    $ads_1 = trim($row['ads_1']);
    $ads_2 = trim($row['ads_2']);

    // Search, pagination, and sorting
    $search_query = isset($_GET['q']) && !empty($_GET['q']) ? trim($_GET['q']) : '';
    $sort = isset($_GET['sort']) && in_array($_GET['sort'], ['date_desc', 'date_asc', 'title_asc', 'title_desc', 'code_asc', 'code_desc', 'views_desc', 'views_asc']) ? $_GET['sort'] : 'date_desc';
    $perPage = 10;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $perPage;

    // Determine sort column and direction
    $sortColumn = 'date';
    $sortDirection = 'DESC';
    switch ($sort) {
        case 'date_asc':
            $sortDirection = 'ASC';
            break;
        case 'title_asc':
            $sortColumn = 'title';
            $sortDirection = 'ASC';
            break;
        case 'title_desc':
            $sortColumn = 'title';
            $sortDirection = 'DESC';
            break;
        case 'code_asc':
            $sortColumn = 'code';
            $sortDirection = 'ASC';
            break;
        case 'code_desc':
            $sortColumn = 'code';
            $sortDirection = 'DESC';
            break;
        case 'views_desc':
            $sortColumn = 'views';
            $sortDirection = 'DESC';
            break;
        case 'views_asc':
            $sortColumn = 'views';
            $sortDirection = 'ASC';
            break;
    }

    // Initialize variables
    $pastes = [];
    $totalItems = 0;
    $totalPages = 1;
    $error = '';

    if ($search_query && strlen($search_query) >= 3) { // Only proceed with search if query is valid
        // Search all pastes (encrypted or not) with a single query
        $search_term = '%' . $search_query . '%';
        $stmt = $pdo->prepare("SELECT id, title, code, date, UNIX_TIMESTAMP(date) AS now_time, encrypt, member, views FROM pastes WHERE visible = '0' AND password = 'NONE' AND (title LIKE ? OR content LIKE ?) ORDER BY $sortColumn $sortDirection LIMIT ? OFFSET ?");
        $stmt->execute([$search_term, $search_term, $perPage, $offset]);
        $pastes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Count total matching pastes for pagination
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pastes WHERE visible = '0' AND password = 'NONE' AND (title LIKE ? OR content LIKE ?)");
        $stmt->execute([$search_term, $search_term]);
        $totalItems = $stmt->fetchColumn();
        $totalPages = $totalItems > 0 ? ceil($totalItems / $perPage) : 1;

        // Decrypt titles and format time
        foreach ($pastes as &$row) {
            if ($row['encrypt'] == '1') {
                $row['title'] = decrypt($row['title'], hex2bin(SECRET)) ?? $row['title'];
            }
            $row['time_display'] = formatRealTime($row['date']);
            $row['url'] = $mod_rewrite == '1' ? $baseurl . $row['id'] : $baseurl . 'paste.php?id=' . $row['id'];
            $row['title'] = truncate($row['title'], 20, 50);
        }
        unset($row);
    } elseif (isset($_GET['q']) && (empty($search_query) || strlen($search_query) < 3)) {
        // Set error for empty or too short search query
        $error = "Please use a keyword.";
    }

    // Pagination
    $prev_page_query = http_build_query(array_merge($_GET, ['page' => $page > 1 ? $page - 1 : 1]));
    $next_page_query = http_build_query(array_merge($_GET, ['page' => $page < $totalPages ? $page + 1 : $totalPages]));
    $page_queries = [];
    for ($i = 1; $i <= $totalPages; $i++) {
        $page_queries[$i] = http_build_query(array_merge($_GET, ['page' => $i]));
    }

    // Set archives title
    $archives_title = htmlspecialchars($lang['archives'] ?? 'Archives');
    if ($search_query && !empty($search_query)) {
        $archives_title .= ' - ' . htmlspecialchars($lang['search_results_for'] ?? 'Search Results for') . ' "' . htmlspecialchars($search_query) . '"';
    }

    // Theme
    require_once('theme/' . $default_theme . '/header.php');
    require_once('theme/' . $default_theme . '/archive.php');
    require_once('theme/' . $default_theme . '/footer.php');
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>