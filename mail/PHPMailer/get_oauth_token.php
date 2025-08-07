<?php
/*
 * Paste OAuth Token Handler
 * Initiates Google OAuth 2.0 authentication for Gmail SMTP
 */
session_start([
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
]);

// Restrict access to authenticated admins
if (!isset($_SESSION['admin_login']) || !isset($_SESSION['admin_id'])) {
    error_log("Unauthorized access attempt to get_oauth_token.php from IP: " . $_SERVER['REMOTE_ADDR']);
    header("Location: ../../admin/configuration.php?error=" . urlencode("Admin authentication required."));
    exit;
}

require_once '../../config.php';
require_once '../vendor/autoload.php';

use Google\Client;

try {
    // Connect to database
    global $pdo;
    if (!$pdo) {
        throw new Exception("PDO connection not found. Check config.php.");
    }

    // Fetch existing mail settings
    $stmt = $pdo->query("SELECT oauth_client_id, oauth_client_secret FROM mail WHERE id = 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $client_id = trim($row['oauth_client_id'] ?? '');
    $client_secret = trim($row['oauth_client_secret'] ?? '');

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $client_id = trim($_POST['client_id'] ?? '');
        $client_secret = trim($_POST['client_secret'] ?? '');

        if (empty($client_id) || empty($client_secret)) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Please fill in both Client ID and Client Secret.</div>';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE mail SET oauth_client_id = ?, oauth_client_secret = ? WHERE id = 1");
                $stmt->execute([$client_id, $client_secret]);
                header("Location: ../../login.php?smtp_oauth=1");
                exit;
            } catch (PDOException $e) {
                error_log("Failed to update OAuth credentials: " . $e->getMessage());
                $msg = '<div class="paste-alert alert6" style="text-align: center;">Failed to save credentials: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
            }
        }
    }
} catch (Exception $e) {
    error_log("Error in get_oauth_token.php: " . $e->getMessage());
    $msg = '<div class="paste-alert alert6" style="text-align: center;">Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Google OAuth Setup</title>
    <link rel="shortcut icon" href="../../admin/favicon.ico">
    <link href="../../admin/css/paste.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <div id="top" class="clearfix">
        <div class="applogo">
            <a href="../" class="logo">Paste</a>
        </div>
        <ul class="top-right">
            <li class="dropdown link">
                <a href="#" data-toggle="dropdown" class="dropdown-toggle profilebox"><b><?php echo htmlspecialchars($_SESSION['admin_login']); ?></b><span class="caret"></span></a>
                <ul class="dropdown-menu dropdown-menu-list dropdown-menu-right">
                    <li><a href="../../admin/admin.php">Settings</a></li>
                    <li><a href="../../admin/?logout">Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>

    <div class="content">
        <div class="container-widget">
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-widget">
                        <div class="panel-body">
                            <h2>Google OAuth 2.0 Setup for Gmail SMTP</h2>
                            <?php if (isset($msg)) echo $msg; ?>
                            <form class="form-horizontal" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                <div class="form-group">
                                    <label class="col-sm-2 control-label form-label">Client ID</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" name="client_id" placeholder="Google OAuth Client ID" value="<?php echo htmlspecialchars($client_id); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label form-label">Client Secret</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" name="client_secret" placeholder="Google OAuth Client Secret" value="<?php echo htmlspecialchars($client_secret); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-offset-2 col-sm-10">
                                        <button type="submit" class="btn btn-default">Authorize</button>
                                    </div>
                                </div>
                            </form>
                            <p><a href="https://console.developers.google.com" target="_blank">Create or manage your Google OAuth credentials</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row footer">
            <div class="col-md-6 text-left">
                <a href="https://github.com/jordansamuel/PASTE" target="_blank">Updates</a> &mdash; <a href="https://github.com/jordansamuel/PASTE/issues" target="_blank">Bugs</a>
            </div>
            <div class="col-md-6 text-right">
                Powered by <a href="https://phpaste.sourceforge.io" target="_blank">Paste</a>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="../../admin/js/jquery.min.js"></script>
    <script type="text/javascript" src="../../admin/js/bootstrap.min.js"></script>
</body>
</html>
<?php $pdo = null; ?>