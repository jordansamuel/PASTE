<?php
/*
 * Paste $v3.1 2025/08/21 https://github.com/boxlabss/PASTE
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

require_once 'includes/session.php';
require_once 'config.php';

// Load highlighter engine libs conditionally
if (($highlighter ?? 'geshi') === 'geshi') {
    require_once 'includes/geshi.php';
} else {
    require_once __DIR__ . '/includes/Highlight/bootstrap.php';
}

require_once 'includes/functions.php';

// ensure these are visible to all included templates (header/footer/sidebar)
global $pdo, $mod_rewrite;

// default to avoid notices if config hasn't set it (DB can override later)
if (!isset($mod_rewrite)) {
    $mod_rewrite = '0';
}

$path             = 'includes/geshi/';                        // GeSHi language files
$parsedown_path   = 'includes/Parsedown/Parsedown.php';       // Markdown
$ges_style        = '';                                       // no inline CSS injection
$require_password = false; // errors.php shows password box when true

// ---------------- Helpers ----------------

// --- highlight theme override (?theme= or ?highlight=) ---
if (($highlighter ?? 'geshi') === 'highlight') {
    $param = $_GET['theme'] ?? $_GET['highlight'] ?? null;
    if ($param !== null) {
        // normalize: accept "dracula", "dracula.css", or "atelier estuary dark"
        $t = strtolower((string)$param);
        $t = str_replace(['+', ' ', '_'], '-', $t);
        $t = preg_replace('~\.css$~', '', $t);
        $t = preg_replace('~[^a-z0-9.-]~', '', $t);

        $stylesRel = 'includes/Highlight/styles';
        $fs = __DIR__ . '/' . $stylesRel . '/' . $t . '.css';
        if (is_file($fs)) {
            // header.php will read this to seed the initial <link>
            $hl_style = $t . '.css';
        }
    }
}

// Map some legacy/GeSHi-style names to highlight.js ids
function map_to_hl_lang(string $code): string {
    static $map = [
        'text'        => 'plaintext',
        'html5'       => 'xml',
        'html4strict' => 'xml',
        'php-brief'   => 'php',
        'pycon'       => 'python',
        'postgresql'  => 'pgsql',   // fallback to sql below if missing
        'dos'         => 'dos',
        'vb'          => 'vbnet',
    ];
    $code = strtolower($code);
    return $map[$code] ?? $code;
}

// Wrap hljs tokens in a line-numbered <ol> so togglev() works
function hl_wrap_with_lines(string $value, string $hlLang, array $highlight_lines): string {
    $lines  = explode("\n", $value);
    $digits = max(2, strlen((string) count($lines))); // how many digits do we need?
    $hlset  = $highlight_lines ? array_flip($highlight_lines) : [];

    $out   = [];
    $out[] = '<pre class="hljs"><code class="hljs language-' . htmlspecialchars($hlLang, ENT_QUOTES, 'UTF-8') . '">';
    // expose digits to CSS so gutter is fixed (no JS needed)
    $out[] = '<ol class="hljs-ln" style="--ln-digits:' . (int)$digits . '">';
    foreach ($lines as $i => $lineHtml) {
        $ln  = $i + 1;
        $cls = isset($hlset[$ln]) ? ' class="hljs-ln-line hljs-hl"' : ' class="hljs-ln-line"';
        $out[] = '<li' . $cls . '><span class="hljs-ln-n">' . $ln . '</span><span class="hljs-ln-c">' . $lineHtml . '</span></li>';
    }
    $out[] = '</ol></code></pre>';
    return implode('', $out);
}

// Add a class to specific <li> lines in GeSHi output (no inline styles)
function geshi_add_line_highlight_class(string $html, array $highlight_lines, string $class = 'hljs-hl'): string {
    if (!$highlight_lines) return $html;
    $targets = array_flip($highlight_lines);
    $i = 0;
    return preg_replace_callback('/<li\b([^>]*)>/', static function($m) use (&$i, $targets, $class) {
        $i++;
        $attrs = $m[1];
        if (!isset($targets[$i])) return '<li' . $attrs . '>';
        if (preg_match('/\bclass="([^"]*)"/i', $attrs, $cm)) {
            $new = trim($cm[1] . ' ' . $class);
            $attrs = preg_replace('/\bclass="[^"]*"/i', 'class="' . htmlspecialchars($new, ENT_QUOTES, 'UTF-8') . '"', $attrs, 1);
        } else {
            $attrs .= ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"';
        }
        return '<li' . $attrs . '>';
    }, $html) ?? $html;
}

// --- Safe themed error renderers (header -> errors -> footer) ---
function themed_error_render(string $msg, int $http_code = 404, bool $show_password_form = false): void {
    global $default_theme, $lang, $baseurl, $site_name, $pdo, $mod_rewrite, $require_password, $paste_id;

    $site_name   = $site_name   ?? '';
    $p_title     = $lang['error'] ?? 'Error';
    $enablegoog  = 'no';
    $enablefb    = 'no';

    if (!headers_sent()) {
        http_response_code($http_code);
        header('Content-Type: text/html; charset=utf-8');
    }

    $require_password = $show_password_form;
    $error = $msg;

    $theme = 'theme/' . htmlspecialchars($default_theme ?? 'default', ENT_QUOTES, 'UTF-8');
    require_once $theme . '/header.php';
    require_once $theme . '/errors.php';
    require_once $theme . '/footer.php';
    exit;
}

function render_error_and_exit(string $msg, string $http = '404'): void {
    $code = ($http === '403') ? 403 : 404;
    themed_error_render($msg, $code, false);
}

function render_password_required_and_exit(string $msg): void {
    themed_error_render($msg, 403, true); // 401 invites browser auth dialogs
}

// --- Inputs ---
$p_password = '';
$paste_id   = null;
if (isset($_GET['id']) && $_GET['id'] !== '') {
    $paste_id = (int) trim((string) $_GET['id']);
} elseif (isset($_POST['id']) && $_POST['id'] !== '') {
    $paste_id = (int) trim((string) $_POST['id']);
}

try {
    // site_info
    $stmt = $pdo->query("SELECT * FROM site_info WHERE id='1'");
    $si   = $stmt->fetch() ?: [];
    $title       = trim($si['title'] ?? '');
    $des         = trim($si['des'] ?? '');
    $baseurl     = rtrim(trim($si['baseurl'] ?? ''), '/') . '/';
    $keyword     = trim($si['keyword'] ?? '');
    $site_name   = trim($si['site_name'] ?? '');
    $email       = trim($si['email'] ?? '');
    $twit        = trim($si['twit'] ?? '');
    $face        = trim($si['face'] ?? '');
    $gplus       = trim($si['gplus'] ?? '');
    $ga          = trim($si['ga'] ?? '');
    $additional_scripts = trim($si['additional_scripts'] ?? '');

    // Optional: allow DB to define mod_rewrite
    if (isset($si['mod_rewrite']) && $si['mod_rewrite'] !== '') {
        $mod_rewrite = (string) $si['mod_rewrite'];
    }

    // interface
    $stmt = $pdo->query("SELECT * FROM interface WHERE id='1'");
    $iface = $stmt->fetch() ?: [];
    $default_lang  = trim($iface['lang'] ?? 'en.php');
    $default_theme = trim($iface['theme'] ?? 'default');
    require_once("langs/$default_lang");

    // ban check
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (is_banned($pdo, $ip)) {
        render_error_and_exit($lang['banned'] ?? 'You are banned from this site.', '403');
    }

    // site permissions
    $stmt = $pdo->query("SELECT * FROM site_permissions WHERE id='1'");
    $perm = $stmt->fetch() ?: [];
    $disableguest = trim($perm['disableguest'] ?? 'off');
    $siteprivate  = trim($perm['siteprivate'] ?? 'off');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $siteprivate === "on") {
        $privatesite = "on";
    }

    // page views (best effort)
    $date = date('Y-m-d');
    try {
        $stmt = $pdo->prepare("SELECT id, tpage, tvisit FROM page_view WHERE date = ?");
        $stmt->execute([$date]);
        $pv = $stmt->fetch();
        if ($pv) {
            $page_view_id = (int) $pv['id'];
            $tpage  = (int) $pv['tpage'] + 1;
            $tvisit = (int) $pv['tvisit'];

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM visitor_ips WHERE ip = ? AND visit_date = ?");
            $stmt->execute([$ip, $date]);
            if ((int) $stmt->fetchColumn() === 0) {
                $tvisit++;
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

    // ads
    $stmt = $pdo->query("SELECT * FROM ads WHERE id='1'");
    $ads  = $stmt->fetch() ?: [];
    $text_ads = trim($ads['text_ads'] ?? '');
    $ads_1    = trim($ads['ads_1'] ?? '');
    $ads_2    = trim($ads['ads_2'] ?? '');

    // Guard ID
    if (!$paste_id) {
        render_error_and_exit($lang['notfound'] ?? 'Paste not found.');
    }

    // load paste
    $stmt = $pdo->prepare("SELECT * FROM pastes WHERE id = ?");
    $stmt->execute([$paste_id]);
    if ($stmt->rowCount() === 0) {
        render_error_and_exit($lang['notfound'] ?? 'Paste not found.');
    }
    $row = $stmt->fetch();

    // paste fields
    $p_title    = (string) ($row['title'] ?? '');
    $p_content  = (string) ($row['content'] ?? '');
    $p_visible  = (string) ($row['visible'] ?? '0');
    $p_code     = (string) ($row['code'] ?? 'text');
    $p_expiry   = trim((string) ($row['expiry'] ?? 'NULL'));
    $p_password = (string) ($row['password'] ?? 'NONE');
    $p_member   = (string) ($row['member'] ?? '');
    $p_date     = (string) ($row['date'] ?? '');
    $p_encrypt  = (string) ($row['encrypt'] ?? '0');
    $p_views    = getPasteViewCount($pdo, (int) $paste_id);

    // private?
    if ($p_visible === "2") {
        if (!isset($_SESSION['username']) || $p_member !== (string) ($_SESSION['username'] ?? '')) {
            render_error_and_exit($lang['privatepaste'] ?? 'This is a private paste.', '403');
        }
    }

    // expiry
    if ($p_expiry !== "NULL" && $p_expiry !== "SELF") {
        $input_time = (int) $p_expiry;
        if ($input_time > 0 && $input_time < time()) {
            render_error_and_exit($lang['expired'] ?? 'This paste has expired.');
        }
    }

    // decrypt if needed
    if ($p_encrypt === "1") {
        if (!defined('SECRET')) {
            render_error_and_exit(($lang['error'] ?? 'Error') . ': Missing SECRET.', '403');
        }
        $dec = decrypt($p_content, hex2bin(SECRET));
        if ($dec === null || $dec === '') {
            render_error_and_exit(($lang['error'] ?? 'Error') . ': Decryption failed.', '403');
        }
        $p_content = $dec;
    }
    $op_content = trim(htmlspecialchars_decode($p_content));

    // download/raw/embed
    if (isset($_GET['download'])) {
        if ($p_password === "NONE" || (isset($_GET['password']) && password_verify((string) $_GET['password'], $p_password))) {
            doDownload((int) $paste_id, $p_title, $op_content, $p_code);
            exit;
        }
        render_password_required_and_exit(
            isset($_GET['password'])
                ? ($lang['wrongpassword'] ?? 'Incorrect password.')
                : ($lang['pwdprotected'] ?? 'This paste is password-protected.')
        );
    }

    if (isset($_GET['raw'])) {
        if ($p_password === "NONE" || (isset($_GET['password']) && password_verify((string) $_GET['password'], $p_password))) {
            rawView((int) $paste_id, $p_title, $op_content, $p_code);
            exit;
        }
        render_password_required_and_exit(
            isset($_GET['password'])
                ? ($lang['wrongpassword'] ?? 'Incorrect password.')
                : ($lang['pwdprotected'] ?? 'This paste is password-protected.')
        );
    }

    if (isset($_GET['embed'])) {
        if ($p_password === "NONE" || (isset($_GET['password']) && password_verify((string) $_GET['password'], $p_password))) {
            // Embed view is standalone; we pass empty $ges_style as we don't inject CSS here.
            embedView((int) $paste_id, $p_title, $p_content, $p_code, $title, $baseurl, $ges_style, $lang);
            exit;
        }
        render_password_required_and_exit(
            isset($_GET['password'])
                ? ($lang['wrongpassword'] ?? 'Incorrect password.')
                : ($lang['pwdprotected'] ?? 'This paste is password-protected.')
        );
    }

    // highlight extraction
    $highlight = [];
    $prefix = '!highlight!';
    if ($prefix !== '') {
        $lines = explode("\n", $p_content);
        $p_content = '';
        foreach ($lines as $idx => $line) {
            if (strncmp($line, $prefix, strlen($prefix)) === 0) {
                $highlight[] = $idx + 1;
                $line = substr($line, strlen($prefix));
            }
            $p_content .= $line . "\n";
        }
        $p_content = rtrim($p_content);
    }

    // transform content 
    if ($p_code === "markdown") {
        // ---------- Markdown (keep using Parsedown, safe) ----------
        require_once $parsedown_path;
        $Parsedown = new Parsedown();

        $md_input = htmlspecialchars_decode($p_content);

        // Disable raw HTML and sanitize URLs during Markdown rendering
        if (method_exists($Parsedown, 'setSafeMode')) {
            $Parsedown->setSafeMode(true);
            if (method_exists($Parsedown, 'setMarkupEscaped')) {
                $Parsedown->setMarkupEscaped(true);
            }
        } else {
            // Fallback for very old Parsedown: escape raw HTML tags BEFORE parsing
            $md_input = preg_replace_callback('/<[^>]*>/', static function($m){
                return htmlspecialchars($m[0], ENT_QUOTES, 'UTF-8');
            }, $md_input);
        }

        $rendered  = $Parsedown->text($md_input);
        $p_content = '<div class="md-body">'.sanitize_allowlist_html($rendered).'</div>';

    } else {
        // ---------- Code (choose engine) ----------
        $code_input = htmlspecialchars_decode($p_content);

        if (($highlighter ?? 'geshi') === 'highlight') {
            // ---- Highlight.php (no Composer) ----
            $hlLang = map_to_hl_lang($p_code);
            try {
                $hl = function_exists('make_highlighter') ? make_highlighter() : null;
                if (!$hl) { throw new \RuntimeException('Highlighter not available'); }

                try {
                    $res = $hl->highlight($hlLang, $code_input);
                } catch (\Throwable $e) {
                    // Fallback: autodetect + generic SQL if pgsql missing, etc.
                    if ($hlLang === 'pgsql') {
                        $res = $hl->highlight('sql', $code_input);
                    } else {
                        $res = $hl->highlightAuto($code_input);
                    }
                }
                $inner = $res->value;                 // HTML with <span> tokens, content escaped
                $detectedLang = $res->language ?? $hlLang;     // whatever highlight chose
                $p_content = hl_wrap_with_lines($inner, $detectedLang, $highlight);
            } catch (\Throwable $t) {
                // Last resort: plain escaped
                $esc = htmlspecialchars($code_input, ENT_QUOTES, 'UTF-8');
                $p_content = hl_wrap_with_lines($esc, 'plaintext', $highlight);
            }

        } else {
            // ---- GeSHi (legacy) ----
            $geshi = new GeSHi($code_input, $p_code, $path);

            // Use classes, not inline CSS; let theme CSS style everything
            if (method_exists($geshi, 'enable_classes')) $geshi->enable_classes();
            if (method_exists($geshi, 'set_header_type')) $geshi->set_header_type(GESHI_HEADER_DIV);

            // Line numbers (NORMAL to avoid rollovers). No inline style.
            if (method_exists($geshi, 'enable_line_numbers')) $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
            if (!empty($highlight) && method_exists($geshi, 'highlight_lines_extra')) {
                // We only mark lines via an extra pass to add a class; no inline style.
                $geshi->highlight_lines_extra($highlight);
            }

            // force plain integer formatting
            if (method_exists($geshi, 'set_line_number_format')) {
                $geshi->set_line_number_format('%d', 0);
            }

            // Parse HTML (class-based markup)
            $p_content = $geshi->parse_code();

            // Add a class to the requested lines so theme CSS can style them
            if (!empty($highlight)) {
                $p_content = geshi_add_line_highlight_class($p_content, $highlight, 'hljs-hl');
            }

            // No stylesheet injection here; theme CSS handles it.
            $ges_style = '';
        }
    }

    // header
    $theme = 'theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8');
    require_once $theme . '/header.php';

    // view OR password prompt
    if ($p_password === "NONE") {
        updateMyView($pdo, (int) $paste_id);

        $p_download = $mod_rewrite == '1' ? $baseurl . "download/$paste_id" : $baseurl . "paste.php?download&id=$paste_id";
        $p_raw      = $mod_rewrite == '1' ? $baseurl . "raw/$paste_id"      : $baseurl . "paste.php?raw&id=$paste_id";
        $p_embed    = $mod_rewrite == '1' ? $baseurl . "embed/$paste_id"    : $baseurl . "paste.php?embed&id=$paste_id";

        require_once $theme . '/view.php';

        // View-once (SELF) cleanup after increment
        $current_views = getPasteViewCount($pdo, (int) $paste_id);
        if ($p_expiry === "SELF" && $current_views >= 2) {
            deleteMyPaste($pdo, (int) $paste_id);
        }
    } else {
        // Password-protected flow shows the prompt via errors.php (partial)
        $require_password = true;

        $p_password_input = isset($_POST['mypass'])
            ? trim((string) $_POST['mypass'])
            : (string) ($_SESSION['p_password'] ?? '');

        // Prebuild convenience links that carry the typed password
        $p_download = $mod_rewrite == '1'
            ? $baseurl . "download/$paste_id?password=" . rawurlencode($p_password_input)
            : $baseurl . "paste.php?download&id=$paste_id&password=" . rawurlencode($p_password_input);
        $p_raw = $mod_rewrite == '1'
            ? $baseurl . "raw/$paste_id?password=" . rawurlencode($p_password_input)
            : $baseurl . "paste.php?raw&id=$paste_id&password=" . rawurlencode($p_password_input);
        $p_embed = $mod_rewrite == '1'
            ? $baseurl . "embed/$paste_id?password=" . rawurlencode($p_password_input)
            : $baseurl . "paste.php?embed&id=$paste_id&password=" . rawurlencode($p_password_input);

        if ($p_password_input !== '' && password_verify($p_password_input, $p_password)) {
            updateMyView($pdo, (int) $paste_id);
            require_once $theme . '/view.php';

            $current_views = getPasteViewCount($pdo, (int) $paste_id);
            if ($p_expiry === "SELF" && $current_views >= 2) {
                deleteMyPaste($pdo, (int) $paste_id);
            }
        } else {
            $error = $p_password_input !== ''
                ? ($lang['wrongpwd'] ?? 'Incorrect password.')
                : ($lang['pwdprotected'] ?? 'This paste is password-protected.');
            $_SESSION['p_password'] = $p_password_input;

            require_once $theme . '/errors.php'; // partial renders password prompt
        }
    }

    // footer
    require_once $theme . '/footer.php';

} catch (PDOException $e) {
    error_log("paste.php: Database error: " . $e->getMessage());

    // Still render a readable error page (no password box)
    $error = ($lang['error'] ?? 'Database error.') . ': ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');

    global $default_theme, $baseurl, $mod_rewrite, $pdo, $require_password;
    $require_password = false;

    $theme = 'theme/' . htmlspecialchars($default_theme ?? 'default', ENT_QUOTES, 'UTF-8');
    require_once $theme . '/header.php';
    require_once $theme . '/errors.php';
    require_once $theme . '/footer.php';
}
