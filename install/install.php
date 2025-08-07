<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */

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

// Check Composer dependencies if OAuth or SMTP is enabled
$required_packages = [];
if ($enablegoog === 'yes' || $enablefb === 'yes') {
    $required_packages['../oauth/vendor/autoload.php'] = ['google/apiclient:^2.12', 'league/oauth2-client'];
}
if ($enablesmtp === 'yes') {
    $required_packages['../mail/vendor/autoload.php'] = ['phpmailer/phpmailer'];
}
foreach ($required_packages as $autoload_file => $packages) {
    if (!file_exists($autoload_file)) {
        ob_end_clean();
        error_log("install.php: Missing Composer dependencies in " . dirname($autoload_file));
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing Composer dependencies. Run: <code>cd ' . dirname($autoload_file) . ' && composer require ' . implode(' ', $packages) . '</code>'
        ]);
        exit;
    }
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Sanitize input
$admin_user = isset($_POST['admin_user']) ? sanitizeInput($_POST['admin_user']) : '';
$admin_pass = isset($_POST['admin_pass']) ? password_hash($_POST['admin_pass'], PASSWORD_DEFAULT) : '';
$date = date('Y-m-d H:i:s');

// Connect to database using PDO
try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    ob_end_clean();
    error_log("install.php: Database connection failed: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')]);
    exit;
}

// Calculate base URL
$base_path = dirname($_SERVER['PHP_SELF'], 2);
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

// Function to check if column exists
function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("install.php: Error checking column $column in $table: " . $e->getMessage());
        return false;
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
            user VARCHAR(250) NOT NULL,
            pass VARCHAR(250) NOT NULL,
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "admin table created.";
    }
    
    // Check and insert admin user
    if ($admin_user && $admin_pass) {
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
    } else {
        $errors[] = "No admin user or password provided, skipping admin insertion.";
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
            'des' => 'Paste can store text, source code or sensitive data for a set period of time.',
            'keyword' => 'paste,pastebin.com,pastebin,text,paste,online paste',
            'site_name' => 'Paste',
            'email' => '',
            'twit' => 'https://twitter.com/',
            'face' => 'https://www.facebook.com/',
            'gplus' => 'https://plus.google.com/',
            'ga' => 'UA-',
            'additional_scripts' => '',
            'baseurl' => $baseurl
        ]);
        $output[] = "Site info inserted.";
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
        $stmt = $pdo->query("SELECT COUNT(*) FROM site_permissions WHERE id = 1");
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO site_permissions (id, disableguest, siteprivate) VALUES (1, 'off', 'off')");
            $output[] = "Site permissions inserted.";
        } else {
            $output[] = "Site permissions already exist, skipping insertion.";
        }
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
        if (!columnExists($pdo, 'pastes', 'encrypt')) {
            $pdo->exec("ALTER TABLE pastes ADD encrypt VARCHAR(1) NOT NULL DEFAULT '0'");
            $output[] = "Added encrypt column to pastes table.";
        }
    }

    // Users table
    if (!tableExists($pdo, 'users')) {
        $refresh_token_column = ($enablegoog === 'yes' || $enablefb === 'yes') ? ", refresh_token VARCHAR(255) DEFAULT NULL" : "";
        $pdo->exec("CREATE TABLE users (
            id INT NOT NULL AUTO_INCREMENT,
            oauth_uid VARCHAR(255),
            username VARCHAR(255) NOT NULL,
            email_id VARCHAR(255),
            full_name VARCHAR(255),
            platform VARCHAR(50),
            password VARCHAR(255),
            verified VARCHAR(10) NOT NULL DEFAULT '0',
            picture TEXT,
            date DATETIME NOT NULL,
            ip VARCHAR(45) NOT NULL
            $refresh_token_column,
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "users table created.";
    } elseif (($enablegoog === 'yes' || $enablefb === 'yes') && !columnExists($pdo, 'users', 'refresh_token')) {
        $pdo->exec("ALTER TABLE users ADD refresh_token VARCHAR(255) DEFAULT NULL");
        $output[] = "Added refresh_token column to users table.";
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
    }

    // Mail table
    if (!tableExists($pdo, 'mail')) {
        $pdo->exec("CREATE TABLE mail (
            id INT NOT NULL AUTO_INCREMENT,
            verification VARCHAR(20) NOT NULL DEFAULT 'enabled',
            smtp_host VARCHAR(255)" . ($enablesmtp === 'yes' ? " DEFAULT 'smtp.gmail.com'" : "") . ",
            smtp_username VARCHAR(255) DEFAULT '',
            smtp_password VARCHAR(255) DEFAULT '',
            smtp_port VARCHAR(10)" . ($enablesmtp === 'yes' ? " DEFAULT '587'" : "") . ",
            protocol VARCHAR(20) NOT NULL DEFAULT '2',
            auth VARCHAR(20) NOT NULL DEFAULT 'true',
            socket VARCHAR(20) NOT NULL DEFAULT 'tls',
            oauth_client_id VARCHAR(255) NOT NULL DEFAULT '',
            oauth_client_secret VARCHAR(255) NOT NULL DEFAULT '',
            oauth_refresh_token VARCHAR(255) NOT NULL DEFAULT '',
            PRIMARY KEY(id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $output[] = "mail table created.";

        $smtp_values = $enablesmtp === 'yes'
            ? "'enabled', 'smtp.gmail.com', '', '', '587', '2', 'true', 'tls', '', '', ''"
            : "'enabled', '', '', '', '', '2', 'true', 'tls', '', '', ''";
        $pdo->exec("INSERT INTO mail (verification, smtp_host, smtp_username, smtp_password, smtp_port, protocol, auth, socket, oauth_client_id, oauth_client_secret, oauth_refresh_token) 
            VALUES ($smtp_values)");
        $output[] = "Mail settings inserted" . ($enablesmtp === 'yes' ? " with Gmail SMTP defaults." : ".");
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
    }

    // Prepare post-installation message
    $post_install_message = 'Installation completed successfully. ';
    if ($enablegoog === 'yes') {
        $post_install_message .= 'Configure Google OAuth at <a href="https://console.developers.google.com" target="_blank">Google Cloud Console</a> with redirect URI: ' . htmlspecialchars($redirect_uri, ENT_QUOTES, 'UTF-8') . ' and scopes: userinfo.profile, userinfo.email, mail.google.com. Update G_CLIENT_ID and G_CLIENT_SECRET in config.php. ';
    }
    if ($enablefb === 'yes') {
        $post_install_message .= 'Configure Facebook OAuth at <a href="https://developers.facebook.com" target="_blank">Facebook Developer Portal</a> with redirect URI: ' . htmlspecialchars($redirect_uri, ENT_QUOTES, 'UTF-8') . '. Update FB_APP_ID and FB_APP_SECRET in config.php. ';
    }
    if ($enablesmtp === 'yes') {
        $post_install_message .= 'Configure SMTP settings in admin/configuration.php. ';
    }
    $post_install_message .= 'Remove the /install directory and set secure permissions on config.php (e.g., chmod 600 config.php). Proceed to the <a href="../" class="btn btn-primary">main site</a> or your <a href="../admin" class="btn btn-primary">dashboard</a>.';

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
}
?>