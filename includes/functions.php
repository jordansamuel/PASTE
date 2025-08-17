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
declare(strict_types=1);

// Set default timezone
date_default_timezone_set('UTC');

// Start database connection
try {
    $dsn = "mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbuser, $dbpassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Unable to connect to database: " . $e->getMessage());
}

function str_contains_polyfill(string $haystack, string $needle, bool $ignoreCase = false): bool
{
    if (function_exists('str_contains')) {
        return str_contains($haystack, $needle);
    }
    if ($ignoreCase) {
        $haystack = strtolower($haystack);
        $needle = strtolower($needle);
    }
    return strpos($haystack, $needle) !== false;
}

// Encrypt pastes with AES-256-CBC from our randomly generated $sec_key
function encrypt(string $value, string $sec_key): string
{
    $cipher = "AES-256-CBC";
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $encrypted = openssl_encrypt($value, $cipher, $sec_key, OPENSSL_RAW_DATA, $iv);
    if ($encrypted === false) {
        throw new RuntimeException('Encryption failed.');
    }
    $hmac = hash_hmac('sha256', $encrypted, $sec_key, true);
    return base64_encode($iv . $hmac . $encrypted);
}

function decrypt(string $value, string $sec_key): ?string
{
    $decoded = base64_decode($value, true);
    if ($decoded === false) {
        return null;
    }
    $cipher = "AES-256-CBC";
    $ivlen = openssl_cipher_iv_length($cipher);
    $sha256len = 32;
    if (strlen($decoded) < $ivlen + $sha256len) {
        return null;
    }
    $iv = substr($decoded, 0, $ivlen);
    $hmac = substr($decoded, $ivlen, $sha256len);
    $encrypted = substr($decoded, $ivlen + $sha256len);
    $calculated_hmac = hash_hmac('sha256', $encrypted, $sec_key, true);
    if (!hash_equals($hmac, $calculated_hmac)) {
        return null;
    }
    $decrypted = openssl_decrypt($encrypted, $cipher, $sec_key, OPENSSL_RAW_DATA, $iv);
    return $decrypted !== false ? $decrypted : null;
}

function deleteMyPaste(PDO $pdo, int $paste_id): bool
{
    try {
        $query = "DELETE FROM pastes WHERE id = :paste_id";
        $stmt = $pdo->prepare($query);
        return $stmt->execute(['paste_id' => $paste_id]);
    } catch (PDOException $e) {
        error_log("Failed to delete paste ID {$paste_id}: " . $e->getMessage());
        return false;
    }
}

if (isset($_POST['delete']) && isset($_SESSION['username']) && isset($paste_id)) {
    try {
        // Verify ownership
        $stmt = $pdo->prepare("SELECT member FROM pastes WHERE id = ?");
        $stmt->execute([$paste_id]);
        $paste = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($paste && $paste['member'] === $_SESSION['username']) {
            if (deleteMyPaste($pdo, $paste_id)) {
                header("Location: " . ($mod_rewrite ? $baseurl . "/profile" : $baseurl . "/profile.php"));
                exit;
            } else {
                $error = "Failed to delete paste.";
            }
        } else {
            $error = "You do not have permission to delete this paste.";
        }
    } catch (Exception $e) {
        $error = "Error deleting paste: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

function getRecent(PDO $pdo, int $count = 5, int $offset = 0, string $sortColumn = 'date', string $sortDirection = 'DESC'): array
{
    try {
        $sortColumn = in_array($sortColumn, ['date', 'title', 'code', 'views']) ? $sortColumn : 'date';
        $sortDirection = in_array($sortDirection, ['ASC', 'DESC']) ? $sortDirection : 'DESC';
        $query = "SELECT id, title, content, visible, code, expiry, password, member, date, UNIX_TIMESTAMP(date) AS now_time, encrypt 
                  FROM pastes WHERE visible = '0' AND password = 'NONE' ORDER BY $sortColumn $sortDirection LIMIT :count OFFSET :offset";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':count', $count, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            if ($row['encrypt'] == "1") {
                $row['content'] = decrypt($row['content'], hex2bin(SECRET)) ?? '';
                $row['title'] = decrypt($row['title'], hex2bin(SECRET)) ?? $row['title'];
            }
        }
        unset($row);
        return $rows;
    } catch (PDOException $e) {
        error_log("Failed to fetch recent pastes: " . $e->getMessage());
        return [];
    }
}

function getUserRecent(PDO $pdo, string $username, int $count = 5): array
{
    try {
        $query = "SELECT id, title, content, visible, code, expiry, password, member, date, UNIX_TIMESTAMP(date) AS now_time, encrypt 
                  FROM pastes WHERE member = :username ORDER BY id DESC LIMIT :count";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':count', $count, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            if ($row['encrypt'] == "1") {
                $row['content'] = decrypt($row['content'], hex2bin(SECRET)) ?? '';
                $row['title'] = decrypt($row['title'], hex2bin(SECRET)) ?? $row['title'];
            }
        }
        unset($row);
        return $rows;
    } catch (PDOException $e) {
        error_log("Failed to fetch user recent pastes for {$username}: " . $e->getMessage());
        return [];
    }
}

function getUserPastes(PDO $pdo, string $username): array
{
    try {
        $query = "
            SELECT p.id, p.title, p.content, p.visible, p.code, p.password, p.member, p.date, 
                   UNIX_TIMESTAMP(p.date) AS now_time, p.encrypt, p.expiry, 
                   COALESCE(COUNT(pv.id), 0) AS views
            FROM pastes p
            LEFT JOIN paste_views pv ON p.id = pv.paste_id
            WHERE p.member = :username
            GROUP BY p.id, p.title, p.content, p.visible, p.code, p.password, p.member, p.date, p.encrypt, p.expiry
            ORDER BY p.id DESC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            if ($row['encrypt'] == "1") {
                $row['content'] = decrypt($row['content'], hex2bin(SECRET)) ?? '';
                $row['title'] = decrypt($row['title'], hex2bin(SECRET)) ?? $row['title'];
            }
        }
        unset($row);
        return $rows;
    } catch (PDOException $e) {
        error_log("Failed to fetch user pastes for $username: " . $e->getMessage());
        return [];
    }
}

function getTotalPastes(PDO $pdo, string $username): int
{
    try {
        $query = "SELECT COUNT(*) FROM pastes WHERE member = :username";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['username' => $username]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Failed to count pastes for {$username}: " . $e->getMessage());
        return 0;
    }
}

function isValidUsername(string $str): bool
{
    return preg_match('/^[A-Za-z0-9.#\\-$]+$/', $str) === 1;
}

function existingUser(PDO $pdo, string $username): bool
{
    try {
        $query = "SELECT COUNT(*) FROM users WHERE username = :username";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['username' => $username]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Failed to check existing user {$username}: " . $e->getMessage());
        return false;
    }
}

// Function to get paste view count from paste_views
function getPasteViewCount(PDO $pdo, int $paste_id): int
{
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM paste_views WHERE paste_id = :paste_id");
        $stmt->execute(['paste_id' => $paste_id]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Failed to get view count for paste ID {$paste_id}: " . $e->getMessage());
        return 0;
    }
}

function pageViewTrack(PDO $pdo, string $ip): void {
    $date = date('Y-m-d');
    try {
        $stmt = $pdo->prepare("SELECT id, tpage, tvisit FROM page_view WHERE date = ?");
        $stmt->execute([$date]);
        $row = $stmt->fetch();

        if ($row) {
            $page_view_id = $row['id'];
            $tpage = (int)$row['tpage'] + 1;
            $tvisit = (int)$row['tvisit'];

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM visitor_ips WHERE ip = ? AND visit_date = ?");
            $stmt->execute([$ip, $date]);
            if ($stmt->fetchColumn() == 0) {
                $tvisit += 1;
                $stmt = $pdo->prepare("INSERT INTO visitor_ips (ip, visit_date) VALUES (?, ?)");
                $stmt->execute([$ip, $date]);
            }

            $stmt = $pdo->prepare("UPDATE page_view SET tpage = ?, tvisit = ? WHERE id = ?");
            $stmt->execute([$tpage, $tvisit, $page_view_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO page_view (date, tpage, tvisit) VALUES (?, ?, ?)");
            $stmt->execute([$date, 1, 1]);

            $stmt = $pdo->prepare("INSERT INTO visitor_ips (ip, visit_date) VALUES (?, ?)");
            $stmt->execute([$ip, $date]);
        }
    } catch (PDOException $e) {
        error_log("Page view tracking error: " . $e->getMessage());
    }
}

function updateMyView(PDO $pdo, int $paste_id): bool
{
    try {
        $ip = $_SERVER['REMOTE_ADDR'];
        $view_date = date('Y-m-d');

        // Check if this IP has viewed the paste today
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM paste_views WHERE paste_id = :paste_id AND ip = :ip AND view_date = :view_date");
        $stmt->execute(['paste_id' => $paste_id, 'ip' => $ip, 'view_date' => $view_date]);
        $has_viewed = $stmt->fetchColumn() > 0;

        if (!$has_viewed) {
            // Log the unique view in paste_views table
            $stmt = $pdo->prepare("INSERT INTO paste_views (paste_id, ip, view_date) VALUES (:paste_id, :ip, :view_date)");
            $stmt->execute(['paste_id' => $paste_id, 'ip' => $ip, 'view_date' => $view_date]);
            return true;
        }

        return false; // Not a unique view
    } catch (PDOException $e) {
        error_log("Failed to update view count for paste ID {$paste_id}: " . $e->getMessage());
        return false;
    }
}

function conTime(int $timestamp): string
{
    if ($timestamp <= 0) {
        return '0 seconds';
    }
    $now = time();
    $diff = $now - $timestamp;
    if ($diff < 0) {
        return 'In the future';
    }
    $periods = [
        'year' => 31536000,
        'month' => 2592000,
        'day' => 86400,
        'hour' => 3600,
        'minute' => 60,
        'second' => 1
    ];
    $result = '';
    foreach ($periods as $name => $duration) {
        $value = floor($diff / $duration);
        if ($value >= 1) {
            $result .= "$value $name" . ($value > 1 ? 's' : '') . ' ';
            $diff -= $value * $duration;
        }
    }
    return trim($result) ?: 'just now';
}

function getRelativeTime(int $seconds): string
{
    if ($seconds <= 0) {
        return '0 seconds';
    }
    $now = new DateTime('@0');
    $then = new DateTime("@$seconds");
    $diff = $now->diff($then);
    $ret = '';
    foreach ([
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second'
    ] as $time => $timename) {
        if ($diff->$time !== 0) {
            $ret .= $diff->$time . ' ' . $timename;
            if (abs($diff->$time) !== 1) {
                $ret .= 's';
            }
            $ret .= ' ';
        }
    }
    return trim($ret);
}

function formatRealTime(string $dateStr): string
{
    // Convert database date (Y-m-d H:i:s) to a formatted date with time
    if (empty($dateStr)) {
        return 'Invalid date';
    }
    try {
        $date = new DateTime($dateStr, new DateTimeZone('UTC'));
        return $date->format('jS F Y H:i'); // e.g., "11th August 2025 23:43"
    } catch (Exception $e) {
        return 'Invalid date';
    }
}

function truncate(string $input, int $maxWords, int $maxChars): string
{
    $words = preg_split('/\s+/', trim($input), $maxWords + 1, PREG_SPLIT_NO_EMPTY);
    $words = array_slice($words, 0, $maxWords);
    $result = '';
    $chars = 0;
    foreach ($words as $word) {
        $chars += strlen($word) + 1;
        if ($chars > $maxChars) {
            break;
        }
        $result .= $word . ' ';
    }
    $result = rtrim($result);
    return $result === $input ? $result : $result . '[...]';
}

function doDownload(int $paste_id, string $p_title, string $p_content, string $p_code): bool
{
    if (!$p_code || !$p_content) {
        header('HTTP/1.1 404 Not Found');
        return false;
    }
    $ext = match ($p_code) {
        'bash' => 'sh',
        'actionscript', 'html4strict' => 'html',
        'javascript' => 'js',
        'perl' => 'pl',
        'csharp' => 'cs',
        'ruby' => 'rb',
        'python' => 'py',
        'sql' => 'sql',
        'php' => 'php',
        'c' => 'c',
        'cpp' => 'cpp',
        'css' => 'css',
        'xml' => 'xml',
        default => 'txt',
    };
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . htmlspecialchars($p_title, ENT_QUOTES, 'UTF-8') . '.' . $ext . '"');
    echo $p_content;
    return true;
}

function rawView(int $paste_id, string $p_title, string $p_content, string $p_code): bool
{
    if (!$paste_id || !$p_code || !$p_content) {
        header('HTTP/1.1 404 Not Found');
        error_log("Debug: rawView - Invalid input: paste_id=$paste_id, p_code=$p_code, p_content length=" . strlen($p_content));
        return false;
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo $p_content;
    return true;
}

function embedView(int $paste_id, string $p_title, string $p_content, string $p_code, string $title, string $baseurl, string $ges_style, array $lang): bool
{
    if (!$paste_id || !$p_content) {
        header('HTTP/1.1 404 Not Found');
        return false;
    }
    $output = "<div class='paste_embed_container'>";
    $output .= "<style>
        .paste_embed_container {
            font-size: 12px;
            color: #333;
            text-align: left;
            margin-bottom: 1em;
            border: 1px solid #ddd;
            background-color: #f7f7f7;
            border-radius: 3px;
        }
        .paste_embed_container a {
            font-weight: bold;
            color: #666;
            text-decoration: none;
            border: 0;
        }
        .paste_embed_container ol {
            color: white;
            background-color: #f7f7f7;
            border-right: 1px solid #ccc;
            margin: 0;
        }
        .paste_embed_footer {
            font-size: 14px;
            padding: 10px;
            overflow: hidden;
            color: #767676;
            background-color: #f7f7f7;
            border-radius: 0 0 2px 2px;
            border-top: 1px solid #ccc;
        }
        .de1, .de2 {
            -moz-user-select: text;
            -webkit-user-select: text;
            -ms-user-select: text;
            user-select: text;
            padding: 0 8px;
            color: #000;
            border-left: 1px solid #ddd;
            background: #ffffff;
            line-height: 20px;
        }
    </style>";
    $output .= $ges_style;
    $output .= $p_content;
    $output .= "<div class='paste_embed_footer'>";
    $output .= "<a href='$baseurl/$paste_id'>" . htmlspecialchars($p_title, ENT_QUOTES, 'UTF-8') . "</a> " . htmlspecialchars($lang['embed-hosted-by'], ENT_QUOTES, 'UTF-8') . " <a href='$baseurl'>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</a> | <a href='$baseurl/raw/$paste_id'>" . htmlspecialchars(strtolower($lang['view-raw']), ENT_QUOTES, 'UTF-8') . "</a>";
    $output .= "</div>";
    $output .= "</div>";
    header('Content-Type: text/javascript; charset=utf-8');
    echo 'document.write(' . json_encode($output) . ')';
    return true;
}

function getEmbedUrl($paste_id, $mod_rewrite, $baseurl) {
    if ($mod_rewrite) {
        return $baseurl . 'embed/' . $paste_id;
    } else {
        return $baseurl . 'paste.php?embed&id=' . $paste_id;
    }
}

function addToSitemap(PDO $pdo, int $paste_id, string $priority, string $changefreq, bool $mod_rewrite): bool
{
    try {
        $c_date = date('Y-m-d H:i:s');
        $server_name = $mod_rewrite
            ? $baseurl . "/" . $paste_id
            : $baseurl . "/paste.php?id=" . $paste_id;
        $site_data = file_exists('sitemap.xml') ? file_get_contents('sitemap.xml') : '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $site_data = rtrim($site_data, "</urlset>");
        $c_sitemap = "\t<url>\n\t\t<loc>" . htmlspecialchars($server_name, ENT_QUOTES, 'UTF-8') . "</loc>\n\t\t<priority>$priority</priority>\n\t\t<changefreq>$changefreq</changefreq>\n\t\t<lastmod>$c_date</lastmod>\n\t</url>\n</urlset>";
        $full_map = $site_data . $c_sitemap;
        return file_put_contents('sitemap.xml', $full_map) !== false;
    } catch (Exception $e) {
        error_log("Failed to update sitemap for paste ID {$paste_id}: " . $e->getMessage());
        return false;
    }
}

function is_banned(PDO $pdo, string $ip): bool
{
    try {
        $query = "SELECT COUNT(*) FROM ban_user WHERE ip = :ip";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['ip' => $ip]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Failed to check ban status for IP {$ip}: " . $e->getMessage());
        return false;
    }
}

// Get a single page by its slug-like name (pages.page_name), only if active.
function getPageByName(PDO $pdo, string $page_name): ?array
{
    try {
        $stmt = $pdo->prepare("
            SELECT id, last_date, page_name, page_title, page_content, location, nav_parent, sort_order, is_active
            FROM pages
            WHERE page_name = :name AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute(['name' => $page_name]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        error_log("getPageByName failed for {$page_name}: " . $e->getMessage());
        return null;
    }
}

/**
 * Build a page URL that respects mod_rewrite.
 * With mod_rewrite:  {$baseurl}page/{page_name}
 * Without:          {$baseurl}page.php?p={page_name}
 */
function getPageUrl(string $page_name): string
{
    global $baseurl, $mod_rewrite;

    $safe = rawurlencode($page_name);
    if (!empty($mod_rewrite) && $mod_rewrite === "1") {
        return rtrim($baseurl, '/') . '/page/' . $safe;
    }
    return rtrim($baseurl, '/') . '/pages.php?p=' . $safe;
}

/**
 * Fetch pages for a given location (header|footer).
 * Returns a hierarchical array: each item has keys: id, name, title, url, children[]
 */
function getNavLinks(PDO $pdo, string $location): array
{
    $location = in_array($location, ['header', 'footer'], true) ? $location : 'header';

    try {
        // Get all active pages that match this location or are marked for both
        $stmt = $pdo->prepare("
            SELECT id, page_name, page_title, nav_parent, sort_order
            FROM pages
            WHERE is_active = 1
              AND (location = :loc OR location = 'both')
            ORDER BY sort_order ASC, page_title ASC
        ");
        $stmt->execute(['loc' => $location]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Index by id, pre-fill structure
        $items = [];
        foreach ($rows as $r) {
            $items[(int)$r['id']] = [
                'id'       => (int)$r['id'],
                'name'     => (string)$r['page_name'],
                'title'    => (string)$r['page_title'],
                'parent'   => $r['nav_parent'] !== null ? (int)$r['nav_parent'] : null,
                'order'    => (int)$r['sort_order'],
                'url'      => getPageUrl((string)$r['page_name']),
                'children' => [],
            ];
        }

        // Build tree
        $tree = [];
        foreach ($items as $id => &$node) {
            if ($node['parent'] !== null && isset($items[$node['parent']])) {
                $items[$node['parent']]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        unset($node); // break reference

        // Ensure children are sorted (by sort_order then title)
        $sortFn = static function (&$list) use (&$sortFn) {
            usort($list, static function ($a, $b) {
                return ($a['order'] <=> $b['order']) ?: strcasecmp($a['title'], $b['title']);
            });
            foreach ($list as &$i) {
                if (!empty($i['children'])) {
                    $sortFn($i['children']);
                }
            }
            unset($i);
        };
        $sortFn($tree);

        return $tree;
    } catch (PDOException $e) {
        error_log("getNavLinks failed for {$location}: " . $e->getMessage());
        return [];
    }
}


// Simple HTML renderer for nav links.
function renderNavListSimple(array $links, string $separator = ''): string
{
    // Render a flat inline list if separator provided, else nested <ul>
    if ($separator !== '') {
        $flat = [];
        $stack = $links;
        while ($stack) {
            $node = array_shift($stack);
            $flat[] = '<a href="' . htmlspecialchars($node['url'], ENT_QUOTES, 'UTF-8') . '">' .
                      htmlspecialchars($node['title'], ENT_QUOTES, 'UTF-8') . '</a>';
            foreach ($node['children'] as $child) {
                $stack[] = $child;
            }
        }
        return implode($separator, $flat);
    }

    $render = static function (array $nodes) use (&$render): string {
        $html = "<ul>";
        foreach ($nodes as $n) {
            $html .= '<li><a href="' . htmlspecialchars($n['url'], ENT_QUOTES, 'UTF-8') . '">' .
                     htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8') . '</a>';
            if (!empty($n['children'])) {
                $html .= $render($n['children']);
            }
            $html .= '</li>';
        }
        $html .= "</ul>";
        return $html;
    };
    return $render($links);
}

// Fetch only the content of a page by name if active (helper for page.php).
function getPageContentByName(PDO $pdo, string $page_name): ?array
{
    try {
        $stmt = $pdo->prepare("
            SELECT page_title, page_content, last_date
            FROM pages
            WHERE page_name = :name AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute(['name' => $page_name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (PDOException $e) {
        error_log("getPageContentByName failed for {$page_name}: " . $e->getMessage());
        return null;
    }
}

/**
 * Bootstrap 5 nav renderer (supports one dropdown level).
 * Returns <li> items ready to live inside <ul class="navbar-nav">.
 */
function renderBootstrapNav(array $links): string
{
    $html = '';
    foreach ($links as $item) {
        $title = htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8');
        $url   = htmlspecialchars($item['url'],   ENT_QUOTES, 'UTF-8');

        if (!empty($item['children'])) {
            $id = 'dd_' . $item['id'];
            $html .= '<li class="nav-item dropdown">';
            $html .= '<a class="nav-link dropdown-toggle" href="#" id="'. $id .'" role="button" data-bs-toggle="dropdown" aria-expanded="false">'. $title .'</a>';
            $html .= '<ul class="dropdown-menu" aria-labelledby="'. $id .'">';
            foreach ($item['children'] as $child) {
                $ctitle = htmlspecialchars($child['title'], ENT_QUOTES, 'UTF-8');
                $curl   = htmlspecialchars($child['url'],   ENT_QUOTES, 'UTF-8');
                $html  .= '<li><a class="dropdown-item" href="'. $curl .'">'. $ctitle .'</a></li>';
            }
            $html .= '</ul></li>';
        } else {
            $html .= '<li class="nav-item"><a class="nav-link" href="'. $url .'">'. $title .'</a></li>';
        }
    }
    return $html;
}

?>
