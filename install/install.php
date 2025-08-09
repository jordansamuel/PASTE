<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */

// Set default timezone
date_default_timezone_set('UTC');

// Start output buffering
ob_start();

// Ensure JSON content type
header('Content-Type: application/json; charset=utf-8');

// Disable display errors
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Check required files
$config_file = '../config.php';
if (!file_exists($config_file)) {
    ob_end_clean();
    error_log("install.php: config.php not found");
    echo json_encode(['status' => 'error', 'message' => 'config.php not found. Run configure.php first.']);
    exit;
}

try {
    require_once $config_file;
} catch (Exception $e) {
    ob_end_clean();
    error_log("install.php: Error including config.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to include config.php: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')]);
    exit;
}

// Check critical files and Composer autoload
$required_files = [
    '../oauth/vendor/autoload.php' => ['google/apiclient:^2.12', 'league/oauth2-client:^2.6'],
    '../mail/vendor/autoload.php' => ['phpmailer/phpmailer:^6.9'],
    '../theme/default/login.php' => [],
    '../oauth/google.php' => [],
    '../oauth/google_smtp.php' => [],
    '../mail/mail.php' => []
];
foreach ($required_files as $file => $packages) {
    if (!file_exists($file)) {
        ob_end_clean();
        $message = empty($packages) ? "Missing required file: $file" : "Missing Composer dependencies in " . dirname($file) . ". Run: <code>cd " . dirname($file) . " && composer require " . implode(' ', $packages) . "</code>";
        error_log("install.php: $message");
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit;
    }
}

// Sanitize input
$admin_user = isset($_POST['admin_user']) ? filter_var(trim($_POST['admin_user']), FILTER_SANITIZE_STRING) : '';
$admin_pass = isset($_POST['admin_pass']) ? password_hash($_POST['admin_pass'], PASSWORD_DEFAULT) : '';
$date = date('Y-m-d H:i:s');

// Validate admin credentials
if (empty($admin_user) || empty($_POST['admin_pass'])) {
    ob_end_clean();
    error_log("install.php: Missing admin user or password");
    echo json_encode(['status' => 'error', 'message' => 'Please provide both admin username and password.']);
    exit;
}

// Connect to database using PDO
try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) {
    ob_end_clean();
    error_log("install.php: Database connection failed: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')]);
    exit;
}

// Calculate base URL with trailing slash
$base_path = rtrim(dirname($_SERVER['PHP_SELF'], 2), '/') . '/';
$baseurl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $base_path;

// Function to check if table exists
function tableExists($pdo, $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("install.php: Error checking table $table: " . $e->getMessage());
        return false;
    }
}

// Function to get column definitions
function getColumnDefinition($pdo, $table, $column) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("install.php: Error checking column $column in $table: " . $e->getMessage());
        return false;
    }
}

// Function to check and update column
function ensureColumn($pdo, $table, $column, $expected_def, &$output, &$errors) {
    $current_def = getColumnDefinition($pdo, $table, $column);
    if (!$current_def) {
        try {
            $pdo->exec("ALTER TABLE `$table` ADD $column $expected_def");
            $output[] = "Added column $column to $table.";
        } catch (PDOException $e) {
            $errors[] = "Failed to add column $column to $table: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            error_log("install.php: Failed to add column $column to $table: " . $e->getMessage());
        }
    } elseif (strtolower($current_def['Type']) !== strtolower(preg_replace('/^[^ ]+/', '', $expected_def))) {
        try {
            $pdo->exec("ALTER TABLE `$table` MODIFY COLUMN $column $expected_def");
            $output[] = "Modified column $column in $table to match expected definition.";
        } catch (PDOException $e) {
            $errors[] = "Failed to modify column $column in $table: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            error_log("install.php: Failed to modify column $column in $table: " . $e->getMessage());
        }
    }
}

// Initialize output array
$output = [];
$errors = [];

try {
    // Admin table
    if (!tableExists($pdo, 'admin')) {
        $pdo->exec("CREATE TABLE admin (
            id INT NOT NULL AUTO_INCREMENT,
            user VARCHAR(250) NOT NULL UNIQUE,
            pass VARCHAR(250) NOT NULL,
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "admin table created.";
    } else {
        ensureColumn($pdo, 'admin', 'id', 'INT NOT NULL AUTO_INCREMENT', $output, $errors);
        ensureColumn($pdo, 'admin', 'user', 'VARCHAR(250) NOT NULL UNIQUE', $output, $errors);
        ensureColumn($pdo, 'admin', 'pass', 'VARCHAR(250) NOT NULL', $output, $errors);
    }
    
    // Check and insert admin user
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE user = :user");
        $stmt->execute(['user' => $admin_user]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO admin (user, pass) VALUES (:user, :pass)");
            $stmt->execute(['user' => $admin_user, 'pass' => $admin_pass]);
            $output[] = "Admin user inserted.";
        } else {
            $output[] = "Admin user already exists, skipping insertion.";
        }
    } catch (PDOException $e) {
        $errors[] = "Failed to insert admin user: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        error_log("install.php: Admin user insertion failed: " . $e->getMessage());
    }

    // Admin history table
    if (!tableExists($pdo, 'admin_history')) {
        $pdo->exec("CREATE TABLE admin_history (
            id INT NOT NULL AUTO_INCREMENT,
            last_date DATETIME NOT NULL,
            ip VARCHAR(45) NOT NULL,
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "admin_history table created.";
    } else {
        ensureColumn($pdo, 'admin_history', 'id', 'INT NOT NULL AUTO_INCREMENT', $output, $errors);
        ensureColumn($pdo, 'admin_history', 'last_date', 'DATETIME NOT NULL', $output, $errors);
        ensureColumn($pdo, 'admin_history', 'ip', 'VARCHAR(45) NOT NULL', $output, $errors);
    }

    // Site info table
    if (!tableExists($pdo, 'site_info')) {
        $pdo->exec("CREATE TABLE site_info (
            id INT NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            des MEDIUMTEXT,
            keyword MEDIUMTEXT,
            site_name VARCHAR(255) NOT NULL,
            email VARCHAR(255),
            twit VARCHAR(255),
            face VARCHAR(255),
            gplus VARCHAR(255),
            ga VARCHAR(255),
            additional_scripts TEXT,
            baseurl TEXT NOT NULL,
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "site_info table created.";

        $stmt = $pdo->prepare("INSERT INTO site_info (title, des, keyword, site_name, email, twit, face, gplus, ga, additional_scripts, baseurl) 
            VALUES (:title, :des, :keyword, :site_name, :email, :twit, :face, :gplus, :ga, :additional_scripts, :baseurl)");
        $stmt->execute([
            'title' => 'Paste',
            'des' => 'Paste can store text, source code, or sensitive data for a set period of time.',
            'keyword' => 'paste,pastebin.com,pastebin,text,paste,online paste',
            'site_name' => 'Paste',
            'email' => 'admin@yourdomain.com',
            'twit' => 'https://twitter.com/',
            'face' => 'https://www.facebook.com/',
            'gplus' => 'https://plus.google.com/',
            'ga' => 'UA-',
            'additional_scripts' => '',
            'baseurl' => $baseurl
        ]);
        $output[] = "Site info inserted.";
    } else {
        ensureColumn($pdo, 'site_info', 'id', 'INT NOT NULL AUTO_INCREMENT', $output, $errors);
        ensureColumn($pdo, 'site_info', 'title', 'VARCHAR(255) NOT NULL', $output, $errors);
        ensureColumn($pdo, 'site_info', 'des', 'MEDIUMTEXT', $output, $errors);
        ensureColumn($pdo, 'site_info', 'keyword', 'MEDIUMTEXT', $output, $errors);
        ensureColumn($pdo, 'site_info', 'site_name', 'VARCHAR(255) NOT NULL', $output, $errors);
        ensureColumn($pdo, 'site_info', 'email', 'VARCHAR(255)', $output, $errors);
        ensureColumn($pdo, 'site_info', 'twit', 'VARCHAR(255)', $output, $errors);
        ensureColumn($pdo, 'site_info', 'face', 'VARCHAR(255)', $output, $errors);
        ensureColumn($pdo, 'site_info', 'gplus', 'VARCHAR(255)', $output, $errors);
        ensureColumn($pdo, 'site_info', 'ga', 'VARCHAR(255)', $output, $errors);
        ensureColumn($pdo, 'site_info', 'additional_scripts', 'TEXT', $output, $errors);
        ensureColumn($pdo, 'site_info', 'baseurl', 'TEXT NOT NULL', $output, $errors);

        try {
            $stmt = $pdo->prepare("UPDATE site_info SET baseurl = :baseurl WHERE id = 1");
            $stmt->execute(['baseurl' => $baseurl]);
            $output[] = "Updated baseurl in site_info.";
        } catch (PDOException $e) {
            $errors[] = "Failed to update baseurl in site_info: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            error_log("install.php: Failed to update baseurl in site_info: " . $e->getMessage());
        }
    }

    // Site permissions table
    if (!tableExists($pdo, 'site_permissions')) {
        $pdo->exec("CREATE TABLE site_permissions (
            id INT NOT NULL AUTO_INCREMENT,
            disableguest VARCHAR(10) NOT NULL DEFAULT 'off',
            siteprivate VARCHAR(10) NOT NULL DEFAULT 'off',
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "site_permissions table created.";

        $pdo->exec("INSERT INTO site_permissions (id, disableguest, siteprivate) VALUES (1, 'off', 'off')");
        $output[] = "Site permissions inserted.";
    } else {
        ensureColumn($pdo, 'site_permissions', 'id', 'INT NOT NULL AUTO_INCREMENT', $output, $errors);
        ensureColumn($pdo, 'site_permissions', 'disableguest', 'VARCHAR(10) NOT NULL DEFAULT \'off\'', $output, $errors);
        ensureColumn($pdo, 'site_permissions', 'siteprivate', 'VARCHAR(10) NOT NULL DEFAULT \'off\'', $output, $errors);
    }

    // Interface table
    if (!tableExists($pdo, 'interface')) {
        $pdo->exec("CREATE TABLE interface (
            id INT NOT NULL AUTO_INCREMENT,
            theme VARCHAR(50) NOT NULL DEFAULT 'default',
            lang VARCHAR(50) NOT NULL DEFAULT 'en.php',
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "interface table created.";

        $pdo->exec("INSERT INTO interface (theme, lang) VALUES ('default', 'en.php')");
        $output[] = "Interface settings inserted.";
    } else {
        ensureColumn($pdo, 'interface', 'id', 'INT NOT NULL AUTO_INCREMENT', $output, $errors);
        ensureColumn($pdo, 'interface', 'theme', 'VARCHAR(50) NOT NULL DEFAULT \'default\'', $output, $errors);
        ensureColumn($pdo, 'interface', 'lang', 'VARCHAR(50) NOT NULL DEFAULT \'en.php\'', $output, $errors);
    }

    // Pastes table
    if (!tableExists($pdo, 'pastes')) {
        $pdo->exec("CREATE TABLE pastes (
            id INT NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL DEFAULT 'Untitled',
            content LONGTEXT NOT NULL,
            visible VARCHAR(10) NOT NULL DEFAULT '0',
            code VARCHAR(50) NOT NULL DEFAULT 'text',
            expiry VARCHAR(50),
            password VARCHAR(255) NOT NULL DEFAULT 'NONE',
            encrypt VARCHAR(1) NOT NULL DEFAULT '0',
            member VARCHAR(255) NOT NULL DEFAULT 'Guest',
            date DATETIME NOT NULL,
            ip VARCHAR(45) NOT NULL,
            now_time VARCHAR(50),
            views INT NOT NULL DEFAULT 0,
            s_date DATE,
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "pastes table created.";
    } else {
        ensureColumn($pdo, 'pastes', 'id', 'INT NOT NULL AUTO_INCREMENT', $output, $errors);
        ensureColumn($pdo, 'pastes', 'title', 'VARCHAR(255) NOT NULL DEFAULT \'Untitled\'', $output, $errors);
        ensureColumn($pdo, 'pastes', 'content', 'LONGTEXT NOT NULL', $output, $errors);
        ensureColumn($pdo, 'pastes', 'visible', 'VARCHAR(10) NOT NULL DEFAULT \'0\'', $output, $errors);
        ensureColumn($pdo, 'pastes', 'code', 'VARCHAR(50) NOT NULL DEFAULT \'text\'', $output, $errors);
        ensureColumn($pdo, 'pastes', 'expiry', 'VARCHAR(50)', $output, $errors);
        ensureColumn($pdo, 'pastes', 'password', 'VARCHAR(255) NOT NULL DEFAULT \'NONE\'', $output, $errors);
        ensureColumn($pdo, 'pastes', 'encrypt', 'VARCHAR(1) NOT NULL DEFAULT \'0\'', $output, $errors);
        ensureColumn($pdo, 'pastes', 'member', 'VARCHAR(255) NOT NULL DEFAULT \'Guest\'', $output, $errors);
        ensureColumn($pdo, 'pastes', 'date', 'DATETIME NOT NULL', $output, $errors);
        ensureColumn($pdo, 'pastes', 'ip', 'VARCHAR(45) NOT NULL', $output, $errors);
        ensureColumn($pdo, 'pastes', 'now_time', 'VARCHAR(50)', $output, $errors);
        ensureColumn($pdo, 'pastes', 'views', 'INT NOT NULL DEFAULT 0', $output, $errors);
        ensureColumn($pdo, 'pastes', 's_date', 'DATE', $output, $errors);
    }

    // Users table
    if (!tableExists($pdo, 'users')) {
        $pdo->exec("CREATE TABLE users (
            id INT NOT NULL AUTO_INCREMENT,
            oauth_uid VARCHAR(255),
            username VARCHAR(50) NOT NULL UNIQUE,
            email_id VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            platform VARCHAR(50) NOT NULL,
            password VARCHAR(255) DEFAULT '',
            verified ENUM('0', '1', '2') NOT NULL DEFAULT '0',
            picture VARCHAR(255) DEFAULT 'NONE',
            date DATETIME NOT NULL,
            ip VARCHAR(45) NOT NULL,
            refresh_token VARCHAR(255) DEFAULT NULL,
            token VARCHAR(512) DEFAULT NULL,
			verification_code varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
			reset_code varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
			reset_expiry DATETIME DEFAULT NULL,
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "users table created.";
    } else {
        ensureColumn($pdo, 'users', 'id', 'INT NOT NULL AUTO_INCREMENT', $output, $errors);
        ensureColumn($pdo, 'users', 'oauth_uid', 'VARCHAR(255)', $output, $errors);
        ensureColumn($pdo, 'users', 'username', 'VARCHAR(50) NOT NULL UNIQUE', $output, $errors);
        ensureColumn($pdo, 'users', 'email_id', 'VARCHAR(255) NOT NULL', $output, $errors);
        ensureColumn($pdo, 'users', 'full_name', 'VARCHAR(255) NOT NULL', $output, $errors);
        ensureColumn($pdo, 'users', 'platform', 'VARCHAR(50) NOT NULL', $output, $errors);
        ensureColumn($pdo, 'users', 'password', 'VARCHAR(255) DEFAULT \'\'', $output, $errors);
        ensureColumn($pdo, 'users', 'verified', 'ENUM(\'0\', \'1\', \'2\') NOT NULL DEFAULT \'0\'', $output, $errors);
        ensureColumn($pdo, 'users', 'picture', 'VARCHAR(255) DEFAULT \'NONE\'', $output, $errors);
        ensureColumn($pdo, 'users', 'date', 'DATETIME NOT NULL', $output, $errors);
        ensureColumn($pdo, 'users', 'ip', 'VARCHAR(45) NOT NULL', $output, $errors);
        ensureColumn($pdo, 'users', 'refresh_token', 'VARCHAR(255) DEFAULT NULL', $output, $errors);
        ensureColumn($pdo, 'users', 'token', 'VARCHAR(512) DEFAULT NULL', $output, $errors);
		ensureColumn($pdo, 'users', 'verification_code', 'VARCHAR(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL', $output, $errors);
		ensureColumn($pdo, 'users', 'reset_code', 'VARCHAR(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL', $output, $errors);
		ensureColumn($pdo, 'users', 'reset_expiry', 'DATETIME DEFAULT NULL', $output, $errors);
    }

    // Ban user table
    if (!tableExists($pdo, 'ban_user')) {
        $pdo->exec("CREATE TABLE ban_user (
            id INT NOT NULL AUTO_INCREMENT,
            ip VARCHAR(45) NOT NULL,
            last_date DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "ban_user table created.";
    } else {
        ensureColumn($pdo, 'ban_user', 'id', 'INT NOT NULL AUTO_INCREMENT', $output, $errors);
        ensureColumn($pdo, 'ban_user', 'ip', 'VARCHAR(45) NOT NULL', $output, $errors);
        ensureColumn($pdo, 'ban_user', 'last_date', 'DATETIME NOT NULL', $output, $errors);
    }

    // Mail table
    if (!tableExists($pdo, 'mail')) {
        $pdo->exec("CREATE TABLE mail (
            id INT NOT NULL AUTO_INCREMENT,
            verification VARCHAR(20) NOT NULL DEFAULT 'enabled',
            smtp_host VARCHAR(255) DEFAULT '',
            smtp_username VARCHAR(255) DEFAULT '',
            smtp_password VARCHAR(255) DEFAULT '',
            smtp_port VARCHAR(10) DEFAULT '',
            protocol VARCHAR(20) NOT NULL DEFAULT '2',
            auth VARCHAR(20) NOT NULL DEFAULT 'true',
            socket VARCHAR(20) NOT NULL DEFAULT 'tls',
            oauth_client_id VARCHAR(255) DEFAULT NULL,
            oauth_client_secret VARCHAR(255) DEFAULT NULL,
            oauth_refresh_token VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "mail table created.";

        $smtp_values = "'enabled', 'smtp.gmail.com', '', '', '587', '2', 'true', 'tls', NULL, NULL, NULL";
        $pdo->exec("INSERT INTO mail (verification, smtp_host, smtp_username, smtp_password, smtp_port, protocol, auth, socket, oauth_client_id, oauth_client_secret, oauth_refresh_token) 
            VALUES ($smtp_values)");
        $output[] = "Mail settings inserted with Gmail SMTP defaults.";
    } else {
        ensureColumn($pdo, 'mail', 'id', 'INT NOT NULL AUTO_INCREMENT', $output, $errors);
        ensureColumn($pdo, 'mail', 'verification', 'VARCHAR(20) NOT NULL DEFAULT \'enabled\'', $output, $errors);
        ensureColumn($pdo, 'mail', 'smtp_host', 'VARCHAR(255) DEFAULT \'\'', $output, $errors);
        ensureColumn($pdo, 'mail', 'smtp_username', 'VARCHAR(255) DEFAULT \'\'', $output, $errors);
        ensureColumn($pdo, 'mail', 'smtp_password', 'VARCHAR(255) DEFAULT \'\'', $output, $errors);
        ensureColumn($pdo, 'mail', 'smtp_port', 'VARCHAR(10) DEFAULT \'\'', $output, $errors);
        ensureColumn($pdo, 'mail', 'protocol', 'VARCHAR(20) NOT NULL DEFAULT \'2\'', $output, $errors);
        ensureColumn($pdo, 'mail', 'auth', 'VARCHAR(20) NOT NULL DEFAULT \'true\'', $output, $errors);
        ensureColumn($pdo, 'mail', 'socket', 'VARCHAR(20) NOT NULL DEFAULT \'tls\'', $output, $errors);
        ensureColumn($pdo, 'mail', 'oauth_client_id', 'VARCHAR(255) DEFAULT NULL', $output, $errors);
        ensureColumn($pdo, 'mail', 'oauth_client_secret', 'VARCHAR(255) DEFAULT NULL', $output, $errors);
        ensureColumn($pdo, 'mail', 'oauth_refresh_token', 'VARCHAR(255) DEFAULT NULL', $output, $errors);
    }

    // Pages table
    if (!tableExists($pdo, 'pages')) {
        $pdo->exec("CREATE TABLE pages (
            id INT NOT NULL AUTO_INCREMENT,
            last_date DATETIME NOT NULL,
            page_name VARCHAR(255) NOT NULL,
            page_title MEDIUMTEXT NOT NULL,
            page_content LONGTEXT,
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "pages table created.";
    } else {
        ensureColumn($pdo, 'pages', 'id', 'INT NOT NULL AUTO_INCREMENT', $output, $errors);
        ensureColumn($pdo, 'pages', 'last_date', 'DATETIME NOT NULL', $output, $errors);
        ensureColumn($pdo, 'pages', 'page_name', 'VARCHAR(255) NOT NULL', $output, $errors);
        ensureColumn($pdo, 'pages', 'page_title', 'MEDIUMTEXT NOT NULL', $output, $errors);
        ensureColumn($pdo, 'pages', 'page_content', 'LONGTEXT', $output, $errors);
    }

    // Page view table
    if (!tableExists($pdo, 'page_view')) {
        $pdo->exec("CREATE TABLE page_view (
            id INT NOT NULL AUTO_INCREMENT,
            date DATE NOT NULL,
            tpage INT NOT NULL DEFAULT 0,
            tvisit INT NOT NULL DEFAULT 0,
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "page_view table created.";
    } else {
        ensureColumn($pdo, 'page_view', 'id', 'INT NOT NULL AUTO_INCREMENT', $output, $errors);
        ensureColumn($pdo, 'page_view', 'date', 'DATE NOT NULL', $output, $errors);
        ensureColumn($pdo, 'page_view', 'tpage', 'INT NOT NULL DEFAULT 0', $output, $errors);
        ensureColumn($pdo, 'page_view', 'tvisit', 'INT NOT NULL DEFAULT 0', $output, $errors);
    }

    // Ads table
    if (!tableExists($pdo, 'ads')) {
        $pdo->exec("CREATE TABLE ads (
            id INT NOT NULL AUTO_INCREMENT,
            text_ads TEXT,
            ads_1 TEXT,
            ads_2 TEXT,
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "ads table created.";

        $pdo->exec("INSERT INTO ads (text_ads, ads_1, ads_2) VALUES ('', '', '')");
        $output[] = "Ads settings inserted.";
    } else {
        ensureColumn($pdo, 'ads', 'id', 'INT NOT NULL AUTO_INCREMENT', $output, $errors);
        ensureColumn($pdo, 'ads', 'text_ads', 'TEXT', $output, $errors);
        ensureColumn($pdo, 'ads', 'ads_1', 'TEXT', $output, $errors);
        ensureColumn($pdo, 'ads', 'ads_2', 'TEXT', $output, $errors);
    }

    // Sitemap options table
    if (!tableExists($pdo, 'sitemap_options')) {
        $pdo->exec("CREATE TABLE sitemap_options (
            id INT NOT NULL AUTO_INCREMENT,
            priority VARCHAR(10) NOT NULL DEFAULT '0.9',
            changefreq VARCHAR(20) NOT NULL DEFAULT 'daily',
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "sitemap_options table created.";

        $pdo->exec("INSERT INTO sitemap_options (id, priority, changefreq) VALUES (1, '0.9', 'daily')");
        $output[] = "Sitemap options inserted.";
    } else {
        ensureColumn($pdo, 'sitemap_options', 'id', 'INT NOT NULL AUTO_INCREMENT', $output, $errors);
        ensureColumn($pdo, 'sitemap_options', 'priority', 'VARCHAR(10) NOT NULL DEFAULT \'0.9\'', $output, $errors);
        ensureColumn($pdo, 'sitemap_options', 'changefreq', 'VARCHAR(20) NOT NULL DEFAULT \'daily\'', $output, $errors);
    }

    // Captcha table
    if (!tableExists($pdo, 'captcha')) {
        $pdo->exec("CREATE TABLE captcha (
            id INT NOT NULL AUTO_INCREMENT,
            cap_e VARCHAR(10) NOT NULL DEFAULT 'off',
            mode VARCHAR(50) NOT NULL DEFAULT 'Normal',
            mul VARCHAR(10) NOT NULL DEFAULT 'off',
            allowed TEXT NOT NULL,
            color VARCHAR(7) NOT NULL DEFAULT '#000000',
            recaptcha_sitekey TEXT,
            recaptcha_secretkey TEXT,
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "captcha table created.";

        $pdo->exec("INSERT INTO captcha (cap_e, mode, mul, allowed, color, recaptcha_sitekey, recaptcha_secretkey) 
            VALUES ('off', 'Normal', 'off', 'ABCDEFGHIJKLMNOPQRSTUVYXYZabcdefghijklmnopqrstuvwxyz0123456789', '#000000', '', '')");
        $output[] = "Captcha settings inserted.";
    } else {
        ensureColumn($pdo, 'captcha', 'id', 'INT NOT NULL AUTO_INCREMENT', $output, $errors);
        ensureColumn($pdo, 'captcha', 'cap_e', 'VARCHAR(10) NOT NULL DEFAULT \'off\'', $output, $errors);
        ensureColumn($pdo, 'captcha', 'mode', 'VARCHAR(50) NOT NULL DEFAULT \'Normal\'', $output, $errors);
        ensureColumn($pdo, 'captcha', 'mul', 'VARCHAR(10) NOT NULL DEFAULT \'off\'', $output, $errors);
        ensureColumn($pdo, 'captcha', 'allowed', 'TEXT NOT NULL', $output, $errors);
        ensureColumn($pdo, 'captcha', 'color', 'VARCHAR(7) NOT NULL DEFAULT \'#000000\'', $output, $errors);
        ensureColumn($pdo, 'captcha', 'recaptcha_sitekey', 'TEXT', $output, $errors);
        ensureColumn($pdo, 'captcha', 'recaptcha_secretkey', 'TEXT', $output, $errors);
    }

    // Prepare post-installation message
    $post_install_message = 'Installation and schema update completed successfully. ';
    if ($enablegoog === 'yes') {
        $post_install_message .= "Configure Google OAuth at <a href=\"https://console.developers.google.com\" target=\"_blank\">Google Cloud Console</a> with redirect URI: {$baseurl}oauth/google.php and scopes: openid, userinfo.profile, userinfo.email. Update G_CLIENT_ID and G_CLIENT_SECRET in config.php. ";
    }
    if ($enablefb === 'yes') {
        $post_install_message .= "Configure Facebook OAuth at <a href=\"https://developers.facebook.com\" target=\"_blank\">Facebook Developer Portal</a> with redirect URI: {$baseurl}oauth/facebook.php. Update FB_APP_ID and FB_APP_SECRET in config.php. ";
    }
    if ($enablesmtp === 'yes') {
        $post_install_message .= "Configure Gmail SMTP OAuth at <a href=\"https://console.developers.google.com\" target=\"_blank\">Google Cloud Console</a> with redirect URI: {$baseurl}oauth/google_smtp.php and scope: gmail.send. Set credentials in admin/configuration.php. ";
    }
    $post_install_message .= 'Remove the /install directory and set secure permissions on config.php (chmod 600 config.php). Proceed to the <a href="../" class="btn btn-primary">main site</a> or your <a href="../admin" class="btn btn-primary">dashboard</a>.';

    // Include any non-critical errors in the message
    if (!empty($errors)) {
        $post_install_message .= '<br>Warnings: ' . implode('<br>', $errors);
    }

    ob_end_clean();
    echo json_encode([
        'status' => 'success',
        'message' => implode('<br>', $output) . '<br>' . $post_install_message
    ]);
} catch (PDOException $e) {
    ob_end_clean();
    error_log("install.php: Installation error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Installation failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')]);
} catch (Exception $e) {
    ob_end_clean();
    error_log("install.php: Unexpected error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Unexpected error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')]);
} finally {
    $pdo = null;
}
?>