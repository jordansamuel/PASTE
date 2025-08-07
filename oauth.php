<?php
/*
 * Paste <https://github.com/boxlabss/PASTE>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License in GPL.txt for more details.
 */
session_start([
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
]);

// Required functions
require_once('config.php');
require_once('includes/functions.php');

// Database Connection (using PDO from config.php)
global $pdo;

try {
    // Current date & user IP
    $date = date('jS F Y');
    $ip = $_SERVER['REMOTE_ADDR'];

    // Get site info
    $stmt = $pdo->query("SELECT * FROM site_info WHERE id = 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $title = trim($row['title']);
        $des = trim($row['des']);
        $baseurl = trim($row['baseurl']);
        $keyword = trim($row['keyword']);
        $site_name = trim($row['site_name']);
        $email = trim($row['email']);
        $twit = trim($row['twit']);
        $face = trim($row['face']);
        $gplus = trim($row['gplus']);
        $ga = trim($row['ga']);
        $additional_scripts = trim($row['additional_scripts']);
    } else {
        throw new Exception("Unable to fetch site info from database.");
    }

    // Set theme and language
    $stmt = $pdo->query("SELECT * FROM interface WHERE id = 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $default_lang = trim($row['lang']);
        $default_theme = trim($row['theme']);
    } else {
        throw new Exception("Unable to fetch interface settings from database.");
    }

    require_once("langs/$default_lang");

    // Page title
    $p_title = $lang['login/register'];

    // Check if user is logged in
    $username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : null;

    // POST Handler
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_change'])) {
        $new_username = filter_var(trim($_POST['new_username'] ?? ''), FILTER_SANITIZE_STRING);
        if (empty($new_username)) {
            $error = $lang['usernotvalid'];
        } else {
            if (!isValidUsername($new_username)) {
                $error = $lang['usernotvalid'];
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $stmt->execute([$new_username]);
                if ($stmt->fetchColumn() > 0) {
                    $error = $lang['userexists'];
                } else {
                    $client_id = trim($_SESSION['oauth_uid'] ?? '');
                    if (empty($client_id)) {
                        $error = $lang['sessionerror'] ?? 'Invalid session. Please log in again.';
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE oauth_uid = ?");
                        $stmt->execute([$new_username, $client_id]);
                        if ($stmt->rowCount() > 0) {
                            $success = $lang['userchanged'];
                            $_SESSION['username'] = $new_username;
                        } else {
                            $error = $lang['databaseerror'];
                        }
                    }
                }
            }
        }
    }

    // Theme
    require_once("theme/$default_theme/header.php");
    require_once("theme/$default_theme/oauth.php");
    require_once("theme/$default_theme/footer.php");

} catch (PDOException $e) {
    error_log("Database error in oauth.php: " . $e->getMessage());
    die("Unable to connect to database: " . htmlspecialchars($e->getMessage()));
} catch (Exception $e) {
    error_log("Error in oauth.php: " . $e->getMessage());
    die("Error: " . htmlspecialchars($e->getMessage()));
}
?>