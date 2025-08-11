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
    $search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
    $sort = isset($_GET['sort']) && in_array($_GET['sort'], ['date_desc', 'date_asc', 'title_asc', 'title_desc', 'code_asc', 'code_desc']) ? $_GET['sort'] : 'date_desc';
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
    }

    if ($search_query) {
        // Search pastes by title or content, handling encrypted pastes in PHP
        $search_term = '%' . $search_query . '%';
        // Count non-encrypted matching pastes with password = 'NONE'
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pastes WHERE visible = '0' AND password = 'NONE' AND (title LIKE ? OR content LIKE ?)");
        $stmt->execute([$search_term, $search_term]);
        $non_encrypted_count = $stmt->fetchColumn();

        // Fetch all encrypted pastes with password = 'NONE' to check in PHP
        $stmt = $pdo->prepare("SELECT id, title, content, encrypt FROM pastes WHERE visible = '0' AND password = 'NONE' AND encrypt = '1'");
        $stmt->execute();
        $encrypted_pastes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $encrypted_matches = 0;
        $matching_paste_ids = [];

        foreach ($encrypted_pastes as $paste) {
            $decrypted_title = $paste['encrypt'] == '1' ? decrypt($paste['title'], hex2bin(SECRET)) ?? $paste['title'] : $paste['title'];
            $decrypted_content = $paste['encrypt'] == '1' ? decrypt($paste['content'], hex2bin(SECRET)) ?? '' : $paste['content'];
            if (stripos($decrypted_title, $search_query) !== false || stripos($decrypted_content, $search_query) !== false) {
                $encrypted_matches++;
                $matching_paste_ids[] = $paste['id'];
            }
        }

        $totalItems = $non_encrypted_count + $encrypted_matches;

        // Fetch non-encrypted matching pastes with password = 'NONE'
        $stmt = $pdo->prepare("SELECT id, title, code, date, UNIX_TIMESTAMP(date) AS now_time, encrypt, member FROM pastes WHERE visible = '0' AND password = 'NONE' AND (title LIKE ? OR content LIKE ?) ORDER BY $sortColumn $sortDirection LIMIT ? OFFSET ?");
        $stmt->execute([$search_term, $search_term, $perPage, $offset]);
        $pastes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch encrypted matching pastes with password = 'NONE' within pagination range
        if ($matching_paste_ids) {
            $placeholders = implode(',', array_fill(0, count($matching_paste_ids), '?'));
            $stmt = $pdo->prepare("SELECT id, title, code, date, UNIX_TIMESTAMP(date) AS now_time, encrypt, member FROM pastes WHERE visible = '0' AND password = 'NONE' AND id IN ($placeholders) ORDER BY $sortColumn $sortDirection LIMIT ? OFFSET ?");
            $stmt->execute(array_merge($matching_paste_ids, [$perPage, $offset]));
            $encrypted_matching_pastes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $pastes = array_merge($pastes, $encrypted_matching_pastes);
        }

        // Sort pastes by selected column and direction
        usort($pastes, function ($a, $b) use ($sortColumn, $sortDirection) {
            if ($sortColumn === 'date') {
                return $sortDirection === 'ASC' ? $a['now_time'] <=> $b['now_time'] : $b['now_time'] <=> $a['now_time'];
            } elseif ($sortColumn === 'title') {
                return $sortDirection === 'ASC' ? strcmp($a['title'], $b['title']) : strcmp($b['title'], $a['title']);
            } else {
                return $sortDirection === 'ASC' ? strcmp($a['code'], $b['code']) : strcmp($b['code'], $a['code']);
            }
        });

        // Apply pagination to merged results
        $pastes = array_slice($pastes, 0, $perPage);

        // Decrypt titles for display
        foreach ($pastes as &$row) {
            if ($row['encrypt'] == '1') {
                $row['title'] = decrypt($row['title'], hex2bin(SECRET)) ?? $row['title'];
            }
        }
        unset($row);
    } else {
        // Fetch recent pastes with password = 'NONE'
        $stmt = $pdo->query("SELECT COUNT(*) FROM pastes WHERE visible = '0' AND password = 'NONE'");
        $totalItems = (int) $stmt->fetchColumn();
        $pastes = getRecent($pdo, $perPage, $offset, $sortColumn, $sortDirection);
    }

    $totalPages = $totalItems > 0 ? ceil($totalItems / $perPage) : 1;

    // Theme
    require_once('theme/' . $default_theme . '/header.php');
    require_once('theme/' . $default_theme . '/archive.php');
    require_once('theme/' . $default_theme . '/footer.php');
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>