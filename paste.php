<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
require_once('includes/password.php');
session_start();

require_once('config.php');
require_once('includes/geshi.php');
require_once('includes/functions.php');

$path = 'includes/geshi/';
$parsedown_path = 'includes/Parsedown/Parsedown.php';
$ges_style = '';

// Initialize variables
$p_password = ''; // Default to empty string to avoid undefined warning

$paste_id = isset($_GET['id']) && !empty($_GET['id']) ? (int) trim(htmlspecialchars($_GET['id'])) : (isset($_POST['id']) && !empty($_POST['id']) ? (int) trim(htmlspecialchars($_POST['id'])) : null);
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'No referrer';
error_log("Debug: paste.php - \$_GET: " . print_r($_GET, true) . ", \$paste_id: " . ($paste_id ?? 'null') . ", Referrer: $referrer");

try {
    $stmt = $pdo->query("SELECT * FROM site_info WHERE id = '1'");
    $row = $stmt->fetch();
    $title = trim($row['title'] ?? '');
    $des = trim($row['des'] ?? '');
    $baseurl = rtrim(trim($row['baseurl'] ?? ''), '/') . '/'; // Normalize baseurl
    $keyword = trim($row['keyword'] ?? '');
    $site_name = trim($row['site_name'] ?? '');
    $email = trim($row['email'] ?? '');
    $twit = trim($row['twit'] ?? '');
    $face = trim($row['face'] ?? '');
    $gplus = trim($row['gplus'] ?? '');
    $ga = trim($row['ga'] ?? '');
    $additional_scripts = trim($row['additional_scripts'] ?? '');

    $stmt = $pdo->query("SELECT * FROM interface WHERE id = '1'");
    $row = $stmt->fetch();
    $default_lang = trim($row['lang'] ?? 'en.php');
    $default_theme = trim($row['theme'] ?? 'default');
    require_once("langs/$default_lang");

    $ip = $_SERVER['REMOTE_ADDR'];
    if (is_banned($pdo, $ip)) {
        die(htmlspecialchars($lang['banned'] ?? 'You are banned from this site.', ENT_QUOTES, 'UTF-8'));
    }

    $stmt = $pdo->query("SELECT * FROM site_permissions WHERE id = '1'");
    $row = $stmt->fetch();
    $disableguest = trim($row['disableguest'] ?? 'off');
    $siteprivate = trim($row['siteprivate'] ?? 'off');

    if ($_SERVER['REQUEST_METHOD'] != 'POST' && $siteprivate == "on") {
        $privatesite = "on";
    }

	$date = date('Y-m-d H:i:s');
    $data_ip = file_get_contents('tmp/temp.tdata');

    $stmt = $pdo->query("SELECT * FROM ads WHERE id = '1'");
    $row = $stmt->fetch();
    $text_ads = trim($row['ads_1'] ?? '');
    $ads_1 = trim($row['ads_1'] ?? '');
    $ads_2 = trim($row['ads_2'] ?? '');

    if (isset($_GET['logout'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        unset($_SESSION['token']);
        unset($_SESSION['oauth_uid']);
        unset($_SESSION['username']);
        session_destroy();
        exit;
    }

    if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
        $callback_stripslashes = function (&$val) {
            $val = stripslashes($val);
        };
        array_walk($_GET, $callback_stripslashes);
        array_walk($_POST, $callback_stripslashes);
        array_walk($_COOKIE, $callback_stripslashes);
    }

    $stmt = $pdo->query("SELECT MAX(id) AS last_id FROM page_view");
    $row = $stmt->fetch();
    $last_id = $row['last_id'] ?? null;

    if ($last_id) {
        $stmt = $pdo->prepare("SELECT * FROM page_view WHERE id = ?");
        $stmt->execute([$last_id]);
        $row = $stmt->fetch();
        $last_date = $row['date'] ?? '';

        if ($last_date == $date) {
            if (str_contains_polyfill($data_ip, $ip)) {
                $stmt = $pdo->prepare("SELECT tpage FROM page_view WHERE id = ?");
                $stmt->execute([$last_id]);
                $last_tpage = (int) trim($stmt->fetchColumn()) + 1;
                $stmt = $pdo->prepare("UPDATE page_view SET tpage = ? WHERE id = ?");
                $stmt->execute([$last_tpage, $last_id]);
            } else {
                $stmt = $pdo->prepare("SELECT tpage, tvisit FROM page_view WHERE id = ?");
                $stmt->execute([$last_id]);
                $row = $stmt->fetch();
                $last_tpage = (int) trim($row['tpage'] ?? 0) + 1;
                $last_tvisit = (int) trim($row['tvisit'] ?? 0) + 1;
                $stmt = $pdo->prepare("UPDATE page_view SET tpage = ?, tvisit = ? WHERE id = ?");
                $stmt->execute([$last_tpage, $last_tvisit, $last_id]);
                file_put_contents('tmp/temp.tdata', $data_ip . "\r\n" . $ip);
            }
        } else {
            if (file_exists('tmp/temp.tdata')) {
                unlink('tmp/temp.tdata');
            }
            $data_ip = '';
            $stmt = $pdo->prepare("INSERT INTO page_view (date, tpage, tvisit) VALUES (?, '1', '1')");
            $stmt->execute([$date]);
            file_put_contents('tmp/temp.tdata', $data_ip . "\r\n" . $ip);
        }
    } else {
        if (file_exists('tmp/temp.tdata')) {
            unlink('tmp/temp.tdata');
        }
        $data_ip = '';
        $stmt = $pdo->prepare("INSERT INTO page_view (date, tpage, tvisit) VALUES (?, '1', '1')");
        $stmt->execute([$date]);
        file_put_contents('tmp/temp.tdata', $data_ip . "\r\n" . $ip);
    }

    $p_private_error = '0';
    if ($paste_id) {
        $stmt = $pdo->prepare("SELECT * FROM pastes WHERE id = ?");
        $stmt->execute([$paste_id]);
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            $p_title = (string) ($row['title'] ?? '');
            $p_content = (string) ($row['content'] ?? '');
            $p_visible = $row['visible'] ?? '0';
            $p_code = (string) ($row['code'] ?? 'text');
            $p_expiry = trim($row['expiry'] ?? 'NULL');
            $p_password = (string) ($row['password'] ?? 'NONE');
            $p_member = (string) ($row['member'] ?? '');
            $p_date = (string) ($row['date'] ?? '');
            $p_encrypt = $row['encrypt'] ?? '0';
            $p_views = (int) ($row['views'] ?? 0);
            error_log("Debug: paste.php - \$p_code: $p_code, \$p_content: " . substr($p_content, 0, 50) . "...");

            if ($p_visible == "2") {
                if (isset($_SESSION['username']) && $p_member == (string) ($_SESSION['username'] ?? '')) {
                    // Authorized
                } else {
                    $notfound = $lang['privatepaste'] ?? 'This is a private paste.';
                    $p_private_error = '1';
                    goto Not_Valid_Paste;
                }
            }
            if ($p_expiry != "NULL" && $p_expiry != "SELF") {
                $input_time = (int) $p_expiry;
                $current_time = time();
                if ($input_time < $current_time) {
                    $notfound = $lang['expired'] ?? 'This paste has expired.';
                    $p_private_error = '1';
                    goto Not_Valid_Paste;
                }
            }
            if ($p_encrypt == "1") {
                $p_content = decrypt($p_content, hex2bin(SECRET)) ?? '';
                if ($p_content === '') {
                    $error = ($lang['error'] ?? 'Error') . ': Decryption failed.';
                    goto Not_Valid_Paste;
                }
            }
            $op_content = trim(htmlspecialchars_decode($p_content));

            if (isset($_GET['download'])) {
                if ($p_password == "NONE" || (isset($_GET['password']) && password_verify($_GET['password'], $p_password))) {
                    doDownload($paste_id, $p_title, $op_content, $p_code);
                    exit();
                } else {
                    $error = isset($_GET['password']) ? ($lang['wrongpassword'] ?? 'Incorrect password.') : ($lang['pwdprotected'] ?? 'This paste is password-protected.');
                }
            }

            if (isset($_GET['raw'])) {
                if ($p_password == "NONE" || (isset($_GET['password']) && password_verify($_GET['password'], $p_password))) {
                    rawView($paste_id, $p_title, $op_content, $p_code);
                    exit();
                } else {
                    $error = isset($_GET['password']) ? ($lang['wrongpassword'] ?? 'Incorrect password.') : ($lang['pwdprotected'] ?? 'This paste is password-protected.');
                }
            }

            $highlight = [];
            $prefix_size = strlen('!highlight!');
            if ($prefix_size) {
                $lines = explode("\n", $p_content);
                $p_content = "";
                foreach ($lines as $idx => $line) {
                    if (substr($line, 0, $prefix_size) == '!highlight!') {
                        $highlight[] = $idx + 1;
                        $line = substr($line, $prefix_size);
                    }
                    $p_content .= $line . "\n";
                }
                $p_content = rtrim($p_content);
            }

            $p_content = htmlspecialchars_decode($p_content);
            if ($p_code == "markdown") {
                include($parsedown_path);
                $Parsedown = new Parsedown();
                $p_content = $Parsedown->text($p_content);
            } else {
                $geshi = new GeSHi($p_content, $p_code, $path);
                $geshi->enable_classes();
                $geshi->set_header_type(GESHI_HEADER_DIV);
                $geshi->set_line_style('color: #aaaaaa; width:auto;');
                $geshi->set_code_style('color: #757584;');
                if (count($highlight)) {
                    $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
                    $geshi->highlight_lines_extra($highlight);
                    $geshi->set_highlight_lines_extra_style('color:#399bff;background:rgba(38,92,255,0.14);');
                } else {
                    $geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS, 2);
                }
                $p_content = $geshi->parse_code();
                $ges_style = '<style>' . $geshi->get_stylesheet() . '</style>';
            }

            if (isset($_GET['embed'])) {
                if ($p_password == "NONE" || (isset($_GET['password']) && password_verify($_GET['password'], $p_password))) {
                    embedView($paste_id, $p_title, $p_content, $p_code, $title, $baseurl, $ges_style, $lang);
                    exit();
                } else {
                    $error = isset($_GET['password']) ? ($lang['wrongpassword'] ?? 'Incorrect password.') : ($lang['pwdprotected'] ?? 'This paste is password-protected.');
                }
            }
        } else {
            header("HTTP/1.1 404 Not Found");
            $notfound = $lang['notfound'] ?? 'Paste not found.';
        }
    } else {
        header("HTTP/1.1 404 Not Found");
        $notfound = $lang['notfound'] ?? 'Paste not found.';
    }

    require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/header.php');
    if ($p_password == "NONE") {
        updateMyView($pdo, $paste_id); // Increment view count first
        $p_download = $mod_rewrite == '1' ? $baseurl . "download/$paste_id" : $baseurl . "paste.php?download&id=$paste_id";
        $p_raw = $mod_rewrite == '1' ? $baseurl . "raw/$paste_id" : $baseurl . "paste.php?raw&id=$paste_id";
        $p_embed = $mod_rewrite == '1' ? $baseurl . "embed/$paste_id" : $baseurl . "paste.php?embed&id=$paste_id";
        require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/view.php');
        // Check views after increment
        $stmt = $pdo->prepare("SELECT views FROM pastes WHERE id = ?");
        $stmt->execute([$paste_id]);
        $current_views = (int) $stmt->fetchColumn();
        if ($p_expiry == "SELF" && $current_views >= 2) {
            deleteMyPaste($pdo, $paste_id);
        }
    } else {
        // Initialize $p_password from POST or session if not already set
        $p_password_input = isset($_POST['mypass']) ? trim($_POST['mypass']) : (isset($_SESSION['p_password']) ? $_SESSION['p_password'] : '');
        $p_download = $mod_rewrite == '1' ? $baseurl . "download/$paste_id?password=" . htmlspecialchars($p_password_input, ENT_QUOTES, 'UTF-8') : $baseurl . "paste.php?download&id=$paste_id&password=" . htmlspecialchars($p_password_input, ENT_QUOTES, 'UTF-8');
        $p_raw = $mod_rewrite == '1' ? $baseurl . "raw/$paste_id?password=" . htmlspecialchars($p_password_input, ENT_QUOTES, 'UTF-8') : $baseurl . "paste.php?raw&id=$paste_id&password=" . htmlspecialchars($p_password_input, ENT_QUOTES, 'UTF-8');
        $p_embed = $mod_rewrite == '1' ? $baseurl . "embed/$paste_id?password=" . htmlspecialchars($p_password_input, ENT_QUOTES, 'UTF-8') : $baseurl . "paste.php?embed&id=$paste_id&password=" . htmlspecialchars($p_password_input, ENT_QUOTES, 'UTF-8');
        if ($p_password_input && password_verify($p_password_input, $p_password)) {
            updateMyView($pdo, $paste_id); // Increment view count first
            require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/view.php');
            // Check views after increment
            $stmt = $pdo->prepare("SELECT views FROM pastes WHERE id = ?");
            $stmt->execute([$paste_id]);
            $current_views = (int) $stmt->fetchColumn();
            if ($p_expiry == "SELF" && $current_views >= 2) {
                deleteMyPaste($pdo, $paste_id);
            }
        } else {
            $error = $p_password_input ? ($lang['wrongpwd'] ?? 'Incorrect password.') : ($lang['pwdprotected'] ?? 'This paste is password-protected.');
            $_SESSION['p_password'] = $p_password_input; // Persist for retry
            require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/errors.php');
        }
    }

Not_Valid_Paste:
    if ($p_private_error == '1') {
        require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/header.php');
        require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/errors.php');
    }

    require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/footer.php');
} catch (PDOException $e) {
    error_log("paste.php: Database error: " . $e->getMessage());
    $error = ($lang['error'] ?? 'Database error.') . ': ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/header.php');
    require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/errors.php');
    require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/footer.php');
}
?>