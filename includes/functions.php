<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in GPL.txt for more details.
 */

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle, $ignoreCase = false)
    {
        if ($ignoreCase) {
            $haystack = strtolower($haystack);
            $needle   = strtolower($needle);
        }
        $needlePos = strpos($haystack, $needle);
        return ($needlePos === false ? false : ($needlePos + 1));
    }
}

function encrypt($value)
{
    global $sec_key;

    $ivlen = openssl_cipher_iv_length($cipher = "AES-256-CBC");
    $iv    = openssl_random_pseudo_bytes($ivlen);

    $encrypted = openssl_encrypt($value, $cipher, $sec_key, OPENSSL_RAW_DATA, $iv);

    $hmac = hash_hmac('sha256', $encrypted, $sec_key, true);

    return base64_encode($iv . $hmac . $encrypted);
}

function decrypt($value)
{
    global $sec_key;

    $decoded = base64_decode($value);

    $ivlen = openssl_cipher_iv_length($cipher = "AES-256-CBC");
    $iv    = substr($decoded, 0, $ivlen);
    $hmac  = substr($decoded, $ivlen, $sha256len = 32);

    $encrypted = substr($decoded, $ivlen + $sha256len);

    $decrypted = openssl_decrypt($encrypted, $cipher, $sec_key, OPENSSL_RAW_DATA, $iv);

    if (hash_equals($hmac, hash_hmac('sha256', $encrypted, $sec_key, true))) {
        return $decrypted;
    }

    return '';
}

function deleteMyPaste($con, $paste_id)
{
    $query  = "DELETE FROM pastes where id='$paste_id'";
    $result = mysqli_query($con, $query);
}

function getRecent($con, $count = 5)
{
    $limit  = $count ? "limit $count" : "";
    $query  = "SELECT *
FROM pastes where visible='0'
ORDER BY id DESC
LIMIT 0 , $count";
    $result = mysqli_query($con, $query);
    return $result;
}

function getUserRecent($con, $username, $count = 5)
{
    $limit  = $count ? "limit $count" : "";
    $query  = "SELECT *
FROM pastes where member='$username'
ORDER BY id DESC
LIMIT 0 , $count";
    $result = mysqli_query($con, $query);
    return $result;
}

function getUserPastes($con, $username)
{
    $query  = "SELECT * FROM pastes where member='$username' ORDER by id DESC";
    $result = mysqli_query($con, $query);
    return $result;
}

function getTotalPastes($con, $username)
{
    $query  = "SELECT * FROM pastes WHERE member='$username'";
    $result = mysqli_query($con, $query);
    $count  = 0;
    while ($row = mysqli_fetch_array($result)) {
        $count = $count + 1;
    }
    return $count;
}

function isValidUsername($str)
{
    return !preg_match('/[^A-Za-z0-9.#\\-$]/', $str);
}

function existingUser($con, $username)
{
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($con, $query);
    $num_rows = mysqli_num_rows($result);
    if ($num_rows == 0) {
        // No records. User doesn't exist.
        return false;
    } else {
        return true;
    }
}

function updateMyView($con, $paste_id)
{
    $query  = "SELECT * FROM pastes WHERE id=" . Trim($paste_id);
    $result = mysqli_query($con, $query);

    while ($row = mysqli_fetch_array($result)) {
        $p_view = Trim($row['views']);
    }
    $p_view = $p_view + 1;
    $query  = "UPDATE pastes SET views='$p_view' where id='$paste_id'";
    $result = mysqli_query($con, $query);
}

/**
 * Convert seconds to a human readable string.
 *
 * @source https://stackoverflow.com/a/40777273/6158792 Written by Hans Henrik Bergan.
 * @param int $seconds
 *
 * @return string
 */
function conTime($seconds)
{
    if (! \is_int($seconds)) {
        throw new \InvalidArgumentException('Argument 1 passed to secondsToHumanReadable() must be of the type int, ' . \gettype($seconds) . ' given');
    }

    $now = new \DateTime('@0');
    $then = new \DateTime("@$seconds");
    $ret = '';

    if ($seconds === 0)
        return '0 seconds';

    $diff = $now->diff($then);

    foreach (array(
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second'
    ) as $time => $timename) {
        if ($diff->$time !== 0) {
            $ret .= $diff->$time . ' ' . $timename;
            if ($diff->$time !== 1 && $diff->$time !== -1) {
                $ret .= 's';
            }
            $ret .= ' ';
        }
    }

    return substr($ret, 0, -1);
}

function truncate($input, $maxWords, $maxChars)
{
    $words = preg_split('/\s+/', $input);
    $words = array_slice($words, 0, $maxWords);
    $words = array_reverse($words);

    $chars     = 0;
    $truncated = array();

    while (count($words) > 0) {
        $fragment = trim(array_pop($words));
        $chars += strlen($fragment);

        if ($chars > $maxChars)
            break;

        $truncated[] = $fragment;
    }

    $result = implode(' ', $truncated);

    return $result . ($input == $result ? '' : '[...]');
}

function doDownload($paste_id, $p_title, $p_content, $p_code)
{
    $stats = false;
    if ($p_code) {
        // Figure out extensions.
        $ext = "txt";
        switch ($p_code) {
            case 'bash':
                $ext = 'sh';
                break;
            case 'actionscript':
                $ext = 'html';
                break;
            case 'html4strict':
                $ext = 'html';
                break;
            case 'javascript':
                $ext = 'js';
                break;
            case 'perl':
                $ext = 'pl';
                break;
            case 'csharp':
                $ext = 'cs';
                break;
            case 'ruby':
                $ext = 'rb';
                break;
            case 'python':
                $ext = 'py';
                break;
            case 'sql':
                $ext = 'sql';
                break;
            case 'php':
                $ext = 'php';
                break;
            case 'c':
                $ext = 'c';
                break;
            case 'cpp':
                $ext = 'cpp';
                break;
            case 'css':
                $ext = 'css';
                break;
            case 'xml':
                $ext = 'xml';
                break;
            default:
                $ext = 'txt';
                break;
        }

        // Download
        header('Content-type: text/plain');
        header('Content-Disposition: attachment; filename="' . $p_title . '.' . $ext . '"');
        echo $p_content;
        $stats = true;
    } else {
        // 404
        header('HTTP/1.1 404 Not Found');
    }
    return $stats;
}

function rawView($paste_id, $p_title, $p_content, $p_code)
{
    $stats = false;
    if ($p_code) {
        // Raw
        header('Content-type: text/plain');
        echo $p_content;
        $stats = true;
    } else {
        // 404
        header('HTTP/1.1 404 Not Found');
    }
    return $stats;
}

function embedView($paste_id, $p_title, $p_content, $p_code, $title, $baseurl, $ges_style, $lang)
{
    $stats = false;
    if ($p_content) {
        // Build the output
        $output = "<div class='paste_embed_container'>";
        $output .= "<style>"; // Add our own styles
        $output .= "
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
                font-size:14px;
                padding: 10px;
                overflow: hidden;
                color: #767676;
                background-color: #f7f7f7;
                border-radius: 0 0 2px 2px;
                border-top: 1px solid #ccc;
            }
            .de1, .de2 {
                -moz-user-select: text;
                -khtml-user-select: text;
                -webkit-user-select: text;
                -ms-user-select: text;
                user-select: text;
                padding: 0 8px;
                color: #000;
                border-left: 1px solid #ddd;
                background: #ffffff;
                line-height:20px;
            }";
        $output .= "</style>";
        $output .= "$ges_style"; // Dynamic GeSHI Style
        $output .= $p_content; // Paste content
        $output .= "<div class='paste_embed_footer'>";
        $output .= "<a href='$baseurl/$paste_id'>$p_title</a> " . $lang['embed-hosted-by'] . " <a href='$baseurl'>$title</a> | <a href='$baseurl/raw/$paste_id'>" . strtolower($lang['view-raw']) . "</a>";
        $output .= "</div>";
        $output .= "</div>";

        // Display embed content using json_encode since that escapes
        // characters well enough to satisfy javascript. http://stackoverflow.com/a/169035
        header('Content-type: text/javascript; charset=utf-8;');
        echo 'document.write(' . json_encode($output) . ')';
        $stats = true;
    } else {
        // 404
        header('HTTP/1.1 404 Not Found');
    }
    return $stats;
}

function paste_protocol()
{

    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ? 'https://' : 'http://';

    return $protocol;
}

function addToSitemap($paste_id, $priority, $changefreq, $mod_rewrite)
{
    $c_date    = date('Y-m-d');
    $site_data = file_get_contents("sitemap.xml");
    $site_data = str_replace("</urlset>", "", $site_data);
    // which protocol are we on
    $protocol = paste_protocol();

    if ($mod_rewrite == "1") {
        $server_name = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/" . $paste_id;
    } else {
        $server_name = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/paste.php?id=" . $paste_id;
    }

    $c_sitemap =
        '	<url>
		<loc>' . $server_name . '</loc>
		<priority>' . $priority . '</priority>
		<changefreq>' . $changefreq . '</changefreq>
		<lastmod>' . $c_date . '</lastmod>
	</url>
</urlset>';

    $full_map  = $site_data . $c_sitemap;
    file_put_contents("sitemap.xml", $full_map);
}

function is_banned($con, $ip)
{
    $query  = "SELECT id FROM ban_user WHERE ip=?";
    if ($stmt = mysqli_prepare($con, $query)) {
        mysqli_stmt_bind_param($stmt, "s", $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_close($stmt);
            return true;
        } else {
            mysqli_stmt_close($stmt);
            return false;
        }
    }
}
