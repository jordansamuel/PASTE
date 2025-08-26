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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See LICENCE for details.
 */

// Start output buffering
ob_start();

// Ensure JSON content type
header('Content-Type: application/json; charset=utf-8');

// Disable display errors
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// --- PHP Version Check (require 8.1+) ---
if (version_compare(PHP_VERSION, '8.1', '<')) {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => 'PHP 8.1 or higher is required. Current version: ' . PHP_VERSION
    ]);
    exit;
}

// Check required PHP extensions
$required_extensions = ['pdo_mysql', 'openssl', 'curl'];
$missing_required = array_filter($required_extensions, static fn($ext) => !extension_loaded($ext));
if (!empty($missing_required)) {
    ob_end_clean();
    error_log("configure.php: Missing required PHP extensions: " . implode(', ', $missing_required));
    echo json_encode([
        'status'  => 'error',
        'message' => 'Missing required PHP extensions: ' . implode(', ', $missing_required) . '. Enable them in php.ini.'
    ]);
    exit;
}

// Sanitize and validate POST data
$dbhost     = isset($_POST['data_host']) ? filter_var(trim($_POST['data_host']), FILTER_SANITIZE_SPECIAL_CHARS) : '';
$dbname     = isset($_POST['data_name']) ? filter_var(trim($_POST['data_name']), FILTER_SANITIZE_SPECIAL_CHARS) : '';
$dbuser     = isset($_POST['data_user']) ? filter_var(trim($_POST['data_user']), FILTER_SANITIZE_SPECIAL_CHARS) : '';
$dbpassword = isset($_POST['data_pass']) ? (string)$_POST['data_pass'] : ''; // Password may contain special chars

$enablegoog = (isset($_POST['enablegoog']) && $_POST['enablegoog'] === 'yes') ? 'yes' : 'no';
$enablefb   = (isset($_POST['enablefb'])   && $_POST['enablefb']   === 'yes') ? 'yes' : 'no';
$enablesmtp = (isset($_POST['enablesmtp']) && $_POST['enablesmtp'] === 'yes') ? 'yes' : 'no';

// Validate database name (alphanumeric and underscore only)
if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbname)) {
    ob_end_clean();
    error_log("configure.php: Invalid database name: $dbname");
    echo json_encode(['status' => 'error', 'message' => 'Database name must be alphanumeric with underscores only.']);
    exit;
}

if (empty($dbhost) || empty($dbname) || empty($dbuser)) {
    ob_end_clean();
    error_log("configure.php: Missing required database parameters");
    echo json_encode(['status' => 'error', 'message' => 'Please provide all required database information (host, database name, user).']);
    exit;
}

// Test database connection
try {
    $dsn = "mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbuser, $dbpassword, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    error_log("configure.php: Database connection successful");
} catch (PDOException $e) {
    ob_end_clean();
    error_log("configure.php: Database connection failed: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')]);
    exit;
}

// Generate random key
try {
    $sec_key = bin2hex(random_bytes(32));
    error_log("configure.php: Generated random key");
} catch (Exception $e) {
    ob_end_clean();
    error_log("configure.php: Failed to generate random key: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to generate random key: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')]);
    exit;
}

// Calculate redirect URI for OAuth
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\'); // Adjust for /install directory
$baseurl   = $protocol . $_SERVER['SERVER_NAME'] . $base_path . '/';
$redirect_uri  = $baseurl . 'oauth/google.php';
$https_warning = ($enablegoog === 'yes' || $enablefb === 'yes') && $protocol === 'http://' ? 'Warning: OAuth is enabled without HTTPS. This is insecure and may cause issues with OAuth providers.' : '';

// --- File Permission Check for config.php and its directory ---
$config_file = '../config.php';
$parent_dir  = dirname($config_file);

// Try to guess web server user for help text only
$web_user = $_SERVER['USER']
    ?? getenv('APACHE_RUN_USER')
    ?? getenv('USER')
    ?? 'www-data';

// Helpers for POSIX info if available
$fmt_perms = static function (string $path): string {
    $st = @stat($path);
    return $st ? sprintf("%o", $st['mode'] & 0777) : '???';
};
$owner_name = static function (string $path): string {
    $st = @stat($path);
    if (!$st) return 'unknown';
    if (function_exists('posix_getpwuid')) {
        $pw = @posix_getpwuid($st['uid']);
        return $pw['name'] ?? (string)$st['uid'];
    }
    return (string)$st['uid'];
};
$group_name = static function (string $path): string {
    $st = @stat($path);
    if (!$st) return 'unknown';
    if (function_exists('posix_getgrgid')) {
        $gr = @posix_getgrgid($st['gid']);
        return $gr['name'] ?? (string)$st['gid'];
    }
    return (string)$st['gid'];
};

// If config.php exists, require it to be writable; else require its directory be writable
if (file_exists($config_file)) {
    if (!is_writable($config_file)) {
        ob_end_clean();
        error_log("configure.php: config.php exists but is not writable (owner: {$owner_name($config_file)}, group: {$group_name($config_file)}, perms: {$fmt_perms($config_file)})");
        echo json_encode([
            'status'  => 'error',
            'message' => "config.php exists but is not writable.<br>Run: <code>chmod 664 " .
                htmlspecialchars($config_file, ENT_QUOTES, 'UTF-8') .
                "</code> or adjust ownership: <code>chown $web_user " .
                htmlspecialchars($config_file, ENT_QUOTES, 'UTF-8') . "</code>"
        ]);
        exit;
    }
} else {
    if (!is_dir($parent_dir) || !is_writable($parent_dir)) {
        ob_end_clean();
        error_log("configure.php: Parent directory not writable: $parent_dir (owner: {$owner_name($parent_dir)}, group: {$group_name($parent_dir)}, perms: {$fmt_perms($parent_dir)})");
        echo json_encode([
            'status'  => 'error',
            'message' => "Cannot create <code>config.php</code> in <code>" .
                htmlspecialchars($parent_dir, ENT_QUOTES, 'UTF-8') .
                "</code>. Grant the web server write access to the directory.<br>" .
                "Example:<br><code>chmod 775 " . htmlspecialchars($parent_dir, ENT_QUOTES, 'UTF-8') .
                "</code><br><code>chown $web_user " . htmlspecialchars($parent_dir, ENT_QUOTES, 'UTF-8') . "</code>"
        ]);
        exit;
    }
}

// Build config.php content
$config_content = <<<EOD
<?php
/*
 * Paste \$v3.2 2025/08/16 https://github.com/boxlabss/PASTE
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

\$currentversion = 3.1;
\$pastelimit = "10"; // 10 MB

// OAuth settings (for signups)
\$enablefb = "$enablefb";
\$enablegoog = "$enablegoog";
\$enablesmtp = "$enablesmtp";

define('G_CLIENT_ID', '');
define('G_CLIENT_SECRET', '');
define('G_REDIRECT_URI', '$redirect_uri');
define('G_APPLICATION_NAME', 'Paste');
define('G_SCOPES', [
    'https://www.googleapis.com/auth/userinfo.profile',
    'https://www.googleapis.com/auth/userinfo.email'
]);

// Database information
\$dbhost = "$dbhost";
\$dbuser = "$dbuser";
\$dbpassword = "$dbpassword";
\$dbname = "$dbname";

// Secret key for encryption
\$sec_key = "$sec_key";
define('SECRET', \$sec_key);

// set to 1 to enable tidy urls
// see docs for an example nginx conf, or .htaccess
\$mod_rewrite = "0";

// Enable SMTP debug logging (uncomment)
// define('SMTP_DEBUG', true);

// Code highlighting engine for non-Markdown pastes: 'highlight' (default) or 'geshi'
\$highlighter = \$highlighter ?? 'highlight';

// Style theme for highlighter.php (see includes/Highlight/styles)
\$hl_style = 'hybrid.css';

/**
 * Build the list of selectable formats
 * - When using highlight.php, we get the json language files from includes/Highlight/languages
 * - When using GeSHi, we fall back to the classic list.
 */
\$popular_formats = []; // will be set below

if (\$highlighter === 'highlight') {
    require_once __DIR__ . '/includes/Highlight/list_languages.php';
    \$langs = highlight_supported_languages();

    // Friendly display labels
    \$label_map = [
        'cpp'        => 'C++',
        'csharp'     => 'C#',
        'fsharp'     => 'F#',
        'objectivec' => 'Objective-C',
        'plaintext'  => 'Plain Text',
        'xml'        => 'HTML/XML',
        'ini'        => 'INI',
        'dos'        => 'DOS/Batch',
        'pgsql'      => 'PostgreSQL',
    ];

    \$build_label = static function(string \$id) use (\$label_map): string {
        if (isset(\$label_map[\$id])) return \$label_map[\$id];
        \$t = str_replace(['-','_'], ' ', \$id);
        \$t = ucwords(\$t);
        \$t = preg_replace('/\bSql\b/i','SQL',\$t);
        \$t = preg_replace('/\bJson\b/i','JSON',\$t);
        \$t = preg_replace('/\bYaml\b/i','YAML',\$t);
        \$t = preg_replace('/\bXml\b/i','XML',\$t);
        return \$t;
    };

    // Build formats from highlight.php languages
    \$geshiformats = ['markdown' => 'Markdown', 'text' => 'Plain Text'];
    foreach (\$langs as \$L) {
        \$id = \$L['id'];
        if (\$id === 'plaintext') continue; // we already provide 'text'
        \$geshiformats[\$id] = \$build_label(\$id);
    }

    // Popular band for the top of the select (tweak freely)
    \$popular_formats = [
        'text','xml','css','javascript','json','yaml','php','python','sql','pgsql',
        'java','c','csharp','cpp','bash','markdown','go','ruby','rust','typescript','kotlin'
    ];

} else {
    // ---------- GeSHi formats (classic) ----------
    \$geshiformats = [
        '4cs' => 'GADV 4CS',
        '6502acme' => 'ACME Cross Assembler',
        '6502kickass' => 'Kick Assembler',
        '6502tasm' => 'TASM/64TASS 1.46',
        '68000devpac' => 'HiSoft Devpac ST 2',
        'abap' => 'ABAP',
        'actionscript' => 'ActionScript',
        'actionscript3' => 'ActionScript 3',
        'ada' => 'Ada',
        'aimms' => 'AIMMS3',
        'algol68' => 'ALGOL 68',
        'apache' => 'Apache',
        'applescript' => 'AppleScript',
        'arm' => 'ARM Assembler',
        'asm' => 'ASM',
        'asp' => 'ASP',
        'asymptote' => 'Asymptote',
        'autoconf' => 'Autoconf',
        'autohotkey' => 'Autohotkey',
        'autoit' => 'AutoIt',
        'avisynth' => 'AviSynth',
        'awk' => 'Awk',
        'bascomavr' => 'BASCOM AVR',
        'bash' => 'BASH',
        'basic4gl' => 'Basic4GL',
        'bf' => 'Brainfuck',
        'bibtex' => 'BibTeX',
        'blitzbasic' => 'BlitzBasic',
        'bnf' => 'BNF',
        'boo' => 'Boo',
        'c' => 'C',
        'c_loadrunner' => 'C (LoadRunner)',
        'c_mac' => 'C for Macs',
        'c_winapi' => 'C (WinAPI)',
        'caddcl' => 'CAD DCL',
        'cadlisp' => 'CAD Lisp',
        'cfdg' => 'CFDG',
        'cfm' => 'ColdFusion',
        'chaiscript' => 'ChaiScript',
        'chapel' => 'Chapel',
        'cil' => 'CIL',
        'clojure' => 'Clojure',
        'cmake' => 'CMake',
        'cobol' => 'COBOL',
        'coffeescript' => 'CoffeeScript',
        'cpp' => 'C++',
        'cpp-qt' => 'C++ (with QT extensions)',
        'cpp-winapi' => 'C++ (WinAPI)',
        'csharp' => 'C#',
        'css' => 'CSS',
        'cuesheet' => 'Cuesheet',
        'd' => 'D',
        'dcl' => 'DCL',
        'dcpu16' => 'DCPU-16 Assembly',
        'dcs' => 'DCS',
        'delphi' => 'Delphi',
        'diff' => 'Diff-output',
        'div' => 'DIV',
        'dos' => 'DOS',
        'dot' => 'dot',
        'e' => 'E',
        'ecmascript' => 'ECMAScript',
        'eiffel' => 'Eiffel',
        'email' => 'eMail (mbox)',
        'epc' => 'EPC',
        'erlang' => 'Erlang',
        'euphoria' => 'Euphoria',
        'ezt' => 'EZT',
        'f1' => 'Formula One',
        'falcon' => 'Falcon',
        'fo' => 'FO (abas-ERP)',
        'fortran' => 'Fortran',
        'freebasic' => 'FreeBasic',
        'fsharp' => 'F#',
        'gambas' => 'GAMBAS',
        'gdb' => 'GDB',
        'genero' => 'Genero',
        'genie' => 'Genie',
        'gettext' => 'GNU Gettext',
        'glsl' => 'glSlang',
        'gml' => 'GML',
        'gnuplot' => 'GNUPlot',
        'go' => 'Go',
        'groovy' => 'Groovy',
        'gwbasic' => 'GwBasic',
        'haskell' => 'Haskell',
        'haxe' => 'Haxe',
        'hicest' => 'HicEst',
        'hq9plus' => 'HQ9+',
        'html4strict' => 'HTML 4.01',
        'html5' => 'HTML 5',
        'icon' => 'Icon',
        'idl' => 'Uno Idl',
        'ini' => 'INI',
        'inno' => 'Inno Script',
        'intercal' => 'INTERCAL',
        'io' => 'IO',
        'ispfpanel' => 'ISPF Panel',
        'j' => 'J',
        'java' => 'Java',
        'java5' => 'Java 5',
        'javascript' => 'JavaScript',
        'jcl' => 'JCL',
        'jquery' => 'jQuery',
        'kixtart' => 'KiXtart',
        'klonec' => 'KLone C',
        'klonecpp' => 'KLone C++',
        'latex' => 'LaTeX',
        'lb' => 'Liberty BASIC',
        'ldif' => 'LDIF',
        'lisp' => 'Lisp',
        'llvm' => 'LLVM',
        'locobasic' => 'Locomotive Basic',
        'logtalk' => 'Logtalk',
        'lolcode' => 'LOLcode',
        'lotusformulas' => 'Lotus Notes @Formulas',
        'lotusscript' => 'LotusScript',
        'lscript' => 'Lightwave Script',
        'lsl2' => 'Linden Script',
        'lua' => 'LUA',
        'm68k' => 'Motorola 68000 Assembler',
        'magiksf' => 'MagikSF',
        'make' => 'GNU make',
        'mapbasic' => 'MapBasic',
        'markdown' => 'Markdown',
        'matlab' => 'Matlab M',
        'mirc' => 'mIRC Scripting',
        'mmix' => 'MMIX',
        'modula2' => 'Modula-2',
        'modula3' => 'Modula-3',
        'mpasm' => 'Microchip Assembler',
        'mxml' => 'MXML',
        'mysql' => 'MySQL',
        'nagios' => 'Nagios',
        'netrexx' => 'NetRexx',
        'newlisp' => 'NewLisp',
        'nginx' => 'Nginx',
        'nsis' => 'NSIS',
        'oberon2' => 'Oberon-2',
        'objc' => 'Objective-C',
        'objeck' => 'Objeck',
        'ocaml' => 'Ocaml',
        'ocaml-brief' => 'OCaml (Brief)',
        'octave' => 'GNU/Octave',
        'oobas' => 'OpenOffice.org Basic',
        'oorexx' => 'ooRexx',
        'oracle11' => 'Oracle 11 SQL',
        'oracle8' => 'Oracle 8 SQL',
        'oxygene' => 'Oxygene (Delphi Prism)',
        'oz' => 'OZ',
        'parasail' => 'ParaSail',
        'parigp' => 'PARI/GP',
        'pascal' => 'Pascal',
        'pcre' => 'PCRE',
        'per' => 'Per (forms)',
        'perl' => 'Perl',
        'perl6' => 'Perl 6',
        'pf' => 'OpenBSD Packet Filter',
        'php' => 'PHP',
        'php-brief' => 'PHP (Brief)',
        'pic16' => 'PIC16 Assembler',
        'pike' => 'Pike',
        'pixelbender' => 'Pixel Bender',
        'pli' => 'PL/I',
        'plsql' => 'PL/SQL',
        'postgresql' => 'PostgreSQL',
        'povray' => 'POVRAY',
        'powerbuilder' => 'PowerBuilder',
        'powershell' => 'PowerShell',
        'proftpd' => 'ProFTPd config',
        'progress' => 'Progress',
        'prolog' => 'Prolog',
        'properties' => 'Properties',
        'providex' => 'ProvideX',
        'purebasic' => 'PureBasic',
        'pycon' => 'Python (console mode)',
        'pys60' => 'Python for S60',
        'python' => 'Python',
        'qbasic' => 'QuickBASIC',
        'racket' => 'Racket',
        'rails' => 'Ruby on Rails',
        'rbs' => 'RBScript',
        'rebol' => 'REBOL',
        'reg' => 'Microsoft REGEDIT',
        'rexx' => 'Rexx',
        'robots' => 'robots.txt',
        'rpmspec' => 'RPM Specification File',
        'rsplus' => 'R / S+',
        'ruby' => 'Ruby',
        'sas' => 'SAS',
        'scala' => 'Scala',
        'scheme' => 'Scheme',
        'scilab' => 'SciLab',
        'scl' => 'SCL',
        'sdlbasic' => 'sdlBasic',
        'smalltalk' => 'Smalltalk',
        'smarty' => 'Smarty',
        'spark' => 'SPARK',
        'sparql' => 'SPARQL',
        'sql' => 'SQL',
        'stonescript' => 'StoneScript',
        'systemverilog' => 'SystemVerilog',
        'tcl' => 'TCL',
        'teraterm' => 'Tera Term Macro',
        'text' => 'Plain Text',
        'thinbasic' => 'thinBasic',
        'tsql' => 'T-SQL',
        'typoscript' => 'TypoScript',
        'unicon' => 'Unicon',
        'upc' => 'UPC',
        'urbi' => 'Urbi',
        'unrealscript' => 'Unreal Script',
        'vala' => 'Vala',
        'vb' => 'Visual Basic',
        'vbnet' => 'VB.NET',
        'vbscript' => 'VB Script',
        'vedit' => 'Vedit Macro',
        'verilog' => 'Verilog',
        'vhdl' => 'VHDL',
        'vim' => 'Vim',
        'visualfoxpro' => 'Visual FoxPro',
        'visualprolog' => 'Visual Prolog',
        'whitespace' => 'Whitespace',
        'whois' => 'WHOIS (RPSL format)',
        'winbatch' => 'WinBatch',
        'xbasic' => 'XBasic',
        'xml' => 'XML',
        'xorg_conf' => 'Xorg Config',
        'xpp' => 'X++',
        'yaml' => 'YAML',
        'z80' => 'ZiLOG Z80 Assembler',
        'zxbasic' => 'ZXBasic'
    ];

    \$popular_formats = [
        'text', 'html4strict', 'html5', 'css', 'javascript', 'php', 'perl',
        'python', 'postgresql', 'sql', 'xml', 'java', 'c', 'csharp', 'cpp', 'markdown'
    ];
}

?>
EOD;

// Write config.php
if (file_put_contents($config_file, $config_content, LOCK_EX) === false) {
    ob_end_clean();
    error_log("configure.php: Failed to write config.php");
    echo json_encode([
        'status'  => 'error',
        'message' => "Failed to write config.php.<br>Ensure the directory is writable.<br>Example:<br><code>chmod 775 " .
            htmlspecialchars($parent_dir, ENT_QUOTES, 'UTF-8') . "</code><br><code>chown $web_user " .
            htmlspecialchars($parent_dir, ENT_QUOTES, 'UTF-8') . "</code>"
    ]);
    exit;
}

// Set config.php permissions (owner read/write only)
@chmod($config_file, 0600);
error_log("configure.php: Successfully wrote config.php");

// Prepare success message
$success_message = 'Configuration saved successfully. Proceed above with your admin account and click submit to install the database.<br>';
if ($enablegoog === 'yes' || $enablefb === 'yes') {
    $success_message .= 'Install OAuth dependencies: <code>cd oauth && composer require google/apiclient:^2.12 league/oauth2-client</code><br>Ensure HTTPS is enabled for secure OAuth redirects.';
}
if ($enablesmtp === 'yes') {
    $success_message .= 'SMTP enabled. Install SMTP dependencies: <code>cd mail && composer require phpmailer/phpmailer</code><br>Configure SMTP settings in admin panel after installation.<br>';
}
if ($https_warning) {
    $success_message .= $https_warning . '<br>';
}
$success_message .= 'Ensure HTTPS is enabled for secure OAuth redirects.';

// Clean output buffer and send success response
ob_end_clean();
echo json_encode([
    'status'  => 'success',
    'message' => $success_message
]);