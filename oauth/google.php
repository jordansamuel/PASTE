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

// Required functions and config
require_once('../config.php');
require_once('../includes/functions.php');
require_once('../oauth/vendor/autoload.php');

use Google_Client;
use Google_Service_Oauth2;

// Current Date & User IP
$date = date('jS F Y');
$ip = $_SERVER['REMOTE_ADDR'];

try {
    // Use existing PDO connection from config.php
    global $pdo;
    if (!$pdo) {
        throw new Exception("PDO connection not found. Check config.php.");
    }

    // Initialize Google Client
    $client = new Google_Client();
    $client->setApplicationName(G_APPLICATION_NAME);
    $client->setClientId(G_CLIENT_ID);
    $client->setClientSecret(G_CLIENT_SECRET);
    $client->setRedirectUri(G_REDIRECT_URI);
    foreach (G_SCOPES as $scope) {
        $client->addScope($scope);
    }

    // Handle OAuth callback
    if (isset($_GET['code'])) {
        $client->authenticate($_GET['code']);
        $access_token = $client->getAccessToken();
        $_SESSION['access_token'] = $access_token;
        $refresh_token = $access_token['refresh_token'] ?? null;

        // Get user info
        $oauth2 = new Google_Service_Oauth2($client);
        $user = $oauth2->userinfo->get();
        $client_email = filter_var($user->email, FILTER_SANITIZE_EMAIL);
        $client_name = filter_var($user->name, FILTER_SANITIZE_STRING);
        $client_id = filter_var($user->id, FILTER_SANITIZE_STRING);
        $client_pic = $user->picture;

        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE oauth_uid = ?");
        $stmt->execute([$client_id]);
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            $user_username = $row['username'];
            $db_verified = $row['verified'];

            if ($db_verified == "2") {
                throw new Exception($lang['banned'] ?? "Your account has been suspended.");
            }

            $_SESSION['username'] = $user_username;
            $_SESSION['token'] = md5($row['id'] . $user_username);
            $_SESSION['oauth_uid'] = $client_id;
            $_SESSION['pic'] = $client_pic;

            // Update refresh token if available
            if ($refresh_token) {
                $stmt = $pdo->prepare("UPDATE users SET refresh_token = ? WHERE oauth_uid = ?");
                $stmt->execute([$refresh_token, $client_id]);
            }

            header("Location: ../");
            exit;
        } else {
            // Generate unique username
            $username_base = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($client_name));
            $username = $username_base;
            $counter = 1;
            while ($pdo->query("SELECT COUNT(*) FROM users WHERE username = '$username'")->fetchColumn() > 0) {
                $username = $username_base . $counter++;
            }

            $_SESSION['username'] = $username;
            $_SESSION['token'] = md5(uniqid($client_id, true));
            $_SESSION['oauth_uid'] = $client_id;
            $_SESSION['pic'] = $client_pic;

            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (oauth_uid, username, email_id, full_name, platform, password, verified, picture, date, ip, refresh_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$client_id, $username, $client_email, $client_name, 'Google', '', '1', $client_pic, $date, $ip, $refresh_token]);

            header("Location: ../oauth.php?new_user=1");
            exit;
        }
    } elseif (isset($_GET['login'])) {
        header("Location: " . $client->createAuthUrl());
        exit;
    } else {
        throw new Exception("Invalid OAuth request.");
    }

} catch (Google_Service_Exception $e) {
    error_log("Google OAuth error in oauth/google.php: " . $e->getMessage());
    header("Location: ../oauth.php?error=" . urlencode($lang['oauth_error'] ?? "OAuth authentication failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')));
    exit;
} catch (Exception $e) {
    error_log("Error in oauth/google.php: " . $e->getMessage());
    header("Location: ../oauth.php?error=" . urlencode($lang['oauth_error'] ?? "Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')));
    exit;
}
?>