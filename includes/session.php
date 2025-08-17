<?php
/* 
 * session variables
 *
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

session_start([
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'cookie_httponly' => true,
    'use_strict_mode' => true,
]);

// Initialize CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize OAuth2 state for Google and other platforms
if (isset($_GET['login']) && in_array($_GET['login'], ['google', 'facebook']) && !isset($_SESSION['oauth2_state'])) {
    $_SESSION['oauth2_state'] = bin2hex(random_bytes(16));
}

// Initialize reCAPTCHA settings
require_once 'config.php';
try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $stmt = $pdo->query("SELECT cap_e, mode, recaptcha_version, recaptcha_sitekey, recaptcha_secretkey FROM captcha WHERE id = 1");
    $captcha_settings = $stmt->fetch() ?: [];
    $_SESSION['cap_e'] = $captcha_settings['cap_e'] ?? 'off';
    $_SESSION['mode'] = $captcha_settings['mode'] ?? 'normal';
    $_SESSION['recaptcha_version'] = $captcha_settings['recaptcha_version'] ?? 'v2';
    $_SESSION['recaptcha_sitekey'] = $captcha_settings['recaptcha_sitekey'] ?? '';
    $_SESSION['recaptcha_secretkey'] = $captcha_settings['recaptcha_secretkey'] ?? '';
    $_SESSION['captcha_settings_timestamp'] = time();

    /*
     * Determine the unified captcha mode and value.
     *
     * The rest of the application (e.g. footer.php, main.php) relies on the
     * `captcha_mode` session variable to decide how to render and validate
     * CAPTCHA challenges. Historically, index.php sets this value for the paste
     * submission page, but other entry points such as login.php never call
     * index.php and therefore never populate `captcha_mode`. Without this
     * variable set, pages that include footer.php will believe that reCAPTCHA
     * is disabled and will skip loading the necessary scripts. To ensure
     * consistent behaviour across the site, initialise `captcha_mode` here
     * based on the admin-configured captcha settings. When reCAPTCHA is
     * enabled (cap_e == 'on' and mode == 'reCAPTCHA'), pick the appropriate
     * variant (v2 or v3). Otherwise fall back to the legacy internal CAPTCHA
     * when cap_e == 'on', or disable CAPTCHA entirely when cap_e == 'off'.
     */
    if ($_SESSION['cap_e'] === 'on') {
        if ($_SESSION['mode'] === 'reCAPTCHA') {
            // Use reCAPTCHA (either v2 or v3)
            $_SESSION['captcha_mode'] = ($_SESSION['recaptcha_version'] === 'v3') ? 'recaptcha_v3' : 'recaptcha';
            // Store site key under a common name for convenience in templates
            $_SESSION['captcha'] = $_SESSION['recaptcha_sitekey'];
        } else {
            // Use internal CAPTCHA (text image or math). The actual image and code
            // will be generated later when needed by the page.
            $_SESSION['captcha_mode'] = 'internal';
            $_SESSION['captcha'] = null;
        }
    } else {
        // CAPTCHA disabled entirely
        $_SESSION['captcha_mode'] = 'none';
        $_SESSION['captcha'] = null;
    }
} catch (PDOException $e) {
    error_log("session.php: Failed to fetch captcha settings: " . $e->getMessage());
    $_SESSION['cap_e'] = 'off';
    $_SESSION['mode'] = 'normal';
    $_SESSION['recaptcha_version'] = 'v2';
    $_SESSION['recaptcha_sitekey'] = '';
    $_SESSION['recaptcha_secretkey'] = '';
    $_SESSION['captcha_settings_timestamp'] = time();
} finally {
    $pdo = null;
}