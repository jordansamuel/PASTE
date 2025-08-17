<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE> new: https://github.com/boxlabss/PASTE
 * Demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/ - https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
require_once 'includes/session.php';
require_once 'config.php';
require_once 'includes/functions.php';

// utf-8
header('Content-Type: text/html; charset=utf-8');

// common
$date = date('Y-m-d H:i:s');
$ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
global $pdo;

// JSON response for ajax delete
function send_json($ok, $msg = '', $extra = []) {
    header_remove('Content-Type');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => (bool)$ok, 'message' => $msg], $extra));
    exit;
}

try {
    // site_info
    $stmt = $pdo->query("SELECT * FROM site_info WHERE id = '1'");
    $si   = $stmt->fetch() ?: [];
    $title   = trim($si['title'] ?? '');
    $des     = trim($si['des'] ?? '');
    $baseurl = rtrim(trim($si['baseurl'] ?? ''), '/') . '/';
    $keyword = trim($si['keyword'] ?? '');
    $site_name = trim($si['site_name'] ?? '');
    $email     = trim($si['email'] ?? '');
    $twit      = trim($si['twit'] ?? '');
    $face      = trim($si['face'] ?? '');
    $gplus     = trim($si['gplus'] ?? '');
    $ga        = trim($si['ga'] ?? '');
    $additional_scripts = trim($si['additional_scripts'] ?? '');

    // interface
    $stmt = $pdo->query("SELECT * FROM interface WHERE id = '1'");
    $iface = $stmt->fetch() ?: [];
    $default_lang  = trim($iface['lang'] ?? 'en.php');
    $default_theme = trim($iface['theme'] ?? 'default');
    require_once("langs/$default_lang");

    // ban check
    if (is_banned($pdo, $ip)) {
        // ajax delete path?
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            send_json(false, $lang['banned'] ?? 'You are banned from this site.');
        }
        die(htmlspecialchars($lang['banned'] ?? 'You are banned from this site.', ENT_QUOTES, 'UTF-8'));
    }

    // permissions
    $stmt = $pdo->query("SELECT * FROM site_permissions WHERE id = '1'");
    $perm = $stmt->fetch() ?: [];
    $siteprivate = trim($perm['siteprivate'] ?? 'off');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $siteprivate === "1") {
        $privatesite = "1";
    }

    // profile username
    if (!isset($_GET['user'])) {
        header("Location: ../");
        exit;
    }
    $profile_username = trim($_GET['user']);
    if (!existingUser($pdo, $profile_username)) {
        header("Location: ../");
        exit;
    }

    $p_title = $profile_username . ($lang['user_public_pastes'] ?? 'Public Pastes');

    // stats for profile page
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pastes WHERE member = ?");
    $stmt->execute([$profile_username]);
    $profile_total_pastes = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pastes WHERE member = ? AND visible = 0");
    $stmt->execute([$profile_username]);
    $profile_total_public = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pastes WHERE member = ? AND visible = 1");
    $stmt->execute([$profile_username]);
    $profile_total_unlisted = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pastes WHERE member = ? AND visible = 2");
    $stmt->execute([$profile_username]);
    $profile_total_private = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COALESCE(COUNT(pv.id), 0) AS total_views
        FROM pastes p
        LEFT JOIN paste_views pv ON p.id = pv.paste_id
        WHERE p.member = ?
    ");
    $stmt->execute([$profile_username]);
    $profile_total_paste_views = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT date FROM users WHERE username = ?");
    $stmt->execute([$profile_username]);
    $profile_join_date = $stmt->fetchColumn() ?: '';

    // logout
    if (isset($_GET['logout'])) {
        $ref = $_SERVER['HTTP_REFERER'] ?? $baseurl;
        unset($_SESSION['token'], $_SESSION['oauth_uid'], $_SESSION['username']);
        session_destroy();
        header('Location: ' . $ref);
        exit;
    }

    // page views
    $view_date = date('Y-m-d');
    try {
        $stmt = $pdo->prepare("SELECT id, tpage, tvisit FROM page_view WHERE date = ?");
        $stmt->execute([$view_date]);
        $pv = $stmt->fetch();
        if ($pv) {
            $page_view_id = $pv['id'];
            $tpage = (int)$pv['tpage'] + 1;
            $tvisit = (int)$pv['tvisit'];
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM visitor_ips WHERE ip = ? AND visit_date = ?");
            $stmt->execute([$ip, $view_date]);
            if ((int)$stmt->fetchColumn() === 0) {
                $tvisit++;
                $stmt = $pdo->prepare("INSERT INTO visitor_ips (ip, visit_date) VALUES (?, ?)");
                $stmt->execute([$ip, $view_date]);
            }
            $stmt = $pdo->prepare("UPDATE page_view SET tpage = ?, tvisit = ? WHERE id = ?");
            $stmt->execute([$tpage, $tvisit, $page_view_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO page_view (date, tpage, tvisit) VALUES (?, ?, ?)");
            $stmt->execute([$view_date, 1, 1]);
            $stmt = $pdo->prepare("INSERT INTO visitor_ips (ip, visit_date) VALUES (?, ?)");
            $stmt->execute([$ip, $view_date]);
        }
    } catch (PDOException $e) {
        error_log("Page view tracking error: " . $e->getMessage());
    }

    // DELETE paste (supports AJAX via POST ajax=1 and anchor GET fallback)
    if (isset($_GET['del'])) {
        $is_ajax = (isset($_POST['ajax']) && $_POST['ajax'] === '1');

        if (empty($_SESSION['token']) || empty($_SESSION['username'])) {
            if ($is_ajax) {
                send_json(false, $lang['not_logged_in'] ?? 'You must be logged in to delete pastes.');
            }
            $error = $lang['not_logged_in'] ?? 'You must be logged in to delete pastes.';
        } else {
            $paste_id = (int)($_GET['id'] ?? 0);
            $owner    = (string)($_SESSION['username'] ?? '');

            if ($paste_id <= 0) {
                if ($is_ajax) send_json(false, $lang['delete_error_invalid'] ?? 'Invalid paste or not authorized to delete.');
                $error = $lang['delete_error_invalid'] ?? 'Invalid paste or not authorized to delete.';
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM pastes WHERE id = ? AND member = ?");
                $stmt->execute([$paste_id, $owner]);
                if ((int)$stmt->fetchColumn() === 0) {
                    if ($is_ajax) send_json(false, $lang['delete_error_invalid'] ?? 'Invalid paste or not authorized to delete.');
                    $error = $lang['delete_error_invalid'] ?? 'Invalid paste or not authorized to delete.';
                } else {
                    // perform delete
                    $stmt = $pdo->prepare("DELETE FROM pastes WHERE id = ? AND member = ?");
                    $stmt->execute([$paste_id, $owner]);
                    // also clean up views (optional)
                    try {
                        $stmt = $pdo->prepare("DELETE FROM paste_views WHERE paste_id = ?");
                        $stmt->execute([$paste_id]);
                    } catch (PDOException $e) {
                        // ignore
                    }
                    if ($is_ajax) {
                        send_json(true, $lang['pastedeleted'] ?? 'Paste deleted successfully.', ['id' => $paste_id]);
                    }
                    $success = $lang['pastedeleted'] ?? 'Paste deleted successfully.';
                    // redirect for non-ajax
                    $redirect = $baseurl . ($mod_rewrite ? 'user/' . urlencode($owner) : 'user.php?user=' . urlencode($owner));
                    header('Location: ' . $redirect);
                    exit;
                }
            }
        }
        // if we reach here and not ajax, fall through to render page with $error
    }

    // ads
    $stmt = $pdo->query("SELECT * FROM ads WHERE id = '1'");
    $ads  = $stmt->fetch() ?: [];
    $text_ads = trim($ads['text_ads'] ?? '');
    $ads_1    = trim($ads['ads_1'] ?? '');
    $ads_2    = trim($ads['ads_2'] ?? '');

    // theme
    require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/header.php');
    require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/user_profile.php');
    require_once('theme/' . htmlspecialchars($default_theme, ENT_QUOTES, 'UTF-8') . '/footer.php');
} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
