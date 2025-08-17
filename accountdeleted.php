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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in LICENCE for more details.
 */

declare(strict_types=1);

require_once 'includes/session.php';
require_once 'config.php';
require_once 'includes/functions.php';

// DB + site info
try {
    $stmt = $pdo->query("SELECT * FROM site_info WHERE id = 1");
    $site = $stmt->fetch() ?: [];
} catch (Throwable $e) { $site = []; }

$baseurl   = trim($site['baseurl'] ?? '');
$site_name = trim($site['site_name'] ?? 'Paste');

// Theme + language
try {
    $iface = $pdo->query("SELECT * FROM interface WHERE id = 1")->fetch() ?: [];
} catch (Throwable $e) { $iface = []; }
$default_lang  = trim($iface['lang'] ?? 'en.php');
$default_theme = trim($iface['theme'] ?? 'default');
require_once("langs/$default_lang");

// Page title + message (use errors.php to render)
$p_title = $lang['accountdeleted'] ?? 'Account Deleted';
$error   = $lang['goodbyemsg'] ?? 'Your account and all data have been permanently removed.';

// Render with error theme
require_once("theme/$default_theme/header.php");
require_once("theme/$default_theme/errors.php");
require_once("theme/$default_theme/footer.php");
