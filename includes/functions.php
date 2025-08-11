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
                header("Location: " . ($mod_rewrite ? $protocol . $baseurl . "/archive" : $protocol . $baseurl . "/archive.php"));
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
        $sortColumn = in_array($sortColumn, ['date', 'title', 'code']) ? $sortColumn : 'date';
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
        $query = "SELECT id, title, content, visible, code, expiry, password, member, date, now_time, encrypt 
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
        $query = "SELECT id, title, content, visible, code, password, member, date, now_time, encrypt, views, expiry 
                  FROM pastes WHERE member = :username ORDER BY id DESC";
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

function updateMyView(PDO $pdo, int $paste_id): bool
{
    try {
        $pdo->beginTransaction();
        $query = "SELECT views FROM pastes WHERE id = :paste_id FOR UPDATE";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['paste_id' => $paste_id]);
        $p_view = (int) ($stmt->fetchColumn() ?? 0);
        $p_view++;
        $query = "UPDATE pastes SET views = :views WHERE id = :paste_id";
        $stmt = $pdo->prepare($query);
        $success = $stmt->execute(['views' => $p_view, 'paste_id' => $paste_id]);
        $pdo->commit();
        return $success;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Failed to update view count for paste ID {$paste_id}: " . $e->getMessage());
        return false;
    }
}

function conTime(int $seconds): string
{
    if ($seconds === 0) {
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
    if (!$p_code) {
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
    if (!$p_code) {
        header('HTTP/1.1 404 Not Found');
        return false;
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo $p_content;
    return true;
}

function embedView(int $paste_id, string $p_title, string $p_content, string $p_code, string $title, string $baseurl, string $ges_style, array $lang): bool
{
    if (!$p_content) {
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

function paste_protocol(): string
{
    return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
}

function addToSitemap(PDO $pdo, int $paste_id, string $priority, string $changefreq, bool $mod_rewrite): bool
{
    try {
        $c_date = date('Y-m-d');
        $protocol = paste_protocol();
        $server_name = $mod_rewrite
            ? $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/" . $paste_id
            : $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/paste.php?id=" . $paste_id;
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
?>