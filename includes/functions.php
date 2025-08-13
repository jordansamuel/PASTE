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
        $sortColumn = in_array($sortColumn, ['date', 'title', 'code', 'views']) ? $sortColumn : 'date';
        $sortDirection = in_array($sortDirection, ['ASC', 'DESC']) ? $sortDirection : 'DESC';
        $query = "SELECT id, title, content, visible, code, expiry, password, member, date, UNIX_TIMESTAMP(date) AS now_time, encrypt, views 
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
        $query = "SELECT id, title, content, visible, code, expiry, password, member, date, UNIX_TIMESTAMP(date) AS now_time, encrypt, views 
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
        $query = "SELECT id, title, content, visible, code, password, member, date, UNIX_TIMESTAMP(date) AS now_time, encrypt, views, expiry 
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
        $ip = $_SERVER['REMOTE_ADDR'];
        $log_file = 'tmp/views_log.txt';
        $current_time = time();
        $unique_view = true;

        // Load existing view log
        $view_log = file_exists($log_file) ? file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        foreach ($view_log as $index => $entry) {
            [$logged_paste_id, $logged_ip, $timestamp] = explode(':', $entry);
            if ($logged_paste_id == $paste_id && $logged_ip == $ip && $current_time - $timestamp < 86400) { // 24 hours
                $unique_view = false;
                break;
            }
            // Clean up old entries (older than 24 hours)
            if ($current_time - $timestamp >= 86400) {
                unset($view_log[$index]);
            }
        }

        if ($unique_view) {
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

            // Log the new unique view
            file_put_contents($log_file, "$paste_id:$ip:$current_time\n", FILE_APPEND | LOCK_EX);
            // Update view_log to remove old entries and add the new one
            file_put_contents($log_file, implode("\n", $view_log) . "\n$paste_id:$ip:$current_time\n");
        }

        return $unique_view;
    } catch (PDOException $e) {
        $pdo->rollBack();
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

function embedView( $paste_id, $p_title, $p_content, $p_code, $title, $baseurl, $ges_style, $lang ) {
    $stats = false;
    if ( $p_content ) {
        $output = "<div class='paste_embed_container'>";
        $output .= "<style>
            .paste_embed_container {
                font-family: monospace;
                font-size: 13px;
                color: #333;
                background: #fff;
                border-radius: 8px;
                overflow: hidden;
                border: 1px solid #ccc;
                margin-bottom: 1em;
                position: relative;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                direction: ltr;
            }
            /* Footer with black gradient */
            .paste_embed_footer {
                font-size: 12px;
                padding: 8px;
                background: linear-gradient(90deg, #000000, #333333);
                color: #ffffff;
                border-top: 1px solid #ccc;
            }
            .paste_embed_footer a {
                color: #ffffff;
                text-decoration: none;
            }
            .paste_embed_footer a:hover {
                text-decoration: underline;
            }
            .paste_embed_code {
                margin: 0;
                padding: 12px;
                max-height: 300px;
                overflow-y: auto;
                overflow-x: auto;
                scroll-behavior: smooth;
                position: relative;
                background: #fafafa;
            }
            /* Fade effect */
            .fade-out {
                position: absolute;
                bottom: 38px;
                left: 0;
                right: 0;
                height: 40px;
                background: linear-gradient(to bottom, rgba(250,250,250,0) 0%, rgba(250,250,250,1) 100%);
                pointer-events: none;
            }
            $ges_style
        </style>";

        // Code content
        $output .= "<div class='paste_embed_code'>" . $p_content . "</div>";
        $output .= "<div class='fade-out'></div>";

        // Footer
        $output .= "<div class='paste_embed_footer'>
            <a href='$baseurl/$paste_id'>$p_title</a> {$lang['embed-hosted-by']}
            <a href='$baseurl'>$title</a> | 
            <a href='$baseurl/raw/$paste_id'>" . strtolower($lang['view-raw']) . "</a>
        </div>";

        $output .= "</div>";

        header( 'Content-type: text/javascript; charset=utf-8;' );
        echo 'document.write(' . json_encode( $output ) . ')';
        $stats = true;
    } else {
        header( 'HTTP/1.1 404 Not Found' );
    }
    return $stats;
}


function getEmbedUrl($paste_id, $mod_rewrite, $baseurl) {
    if ($mod_rewrite) {
        return $baseurl . 'embed/' . $paste_id;
    } else {
        return $baseurl . 'embed.php?id=' . $paste_id;
    }
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
