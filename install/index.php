<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */

$date = date('jS F Y');
$ip = $_SERVER['REMOTE_ADDR'];

// Security headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://code.jquery.com https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; connect-src 'self'");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// PHP version check
$php_version = phpversion();
$php_ok = version_compare($php_version, '7.0', '>=');

// Extension checks
$required_extensions = ['pdo_mysql'];
$optional_extensions = ['openssl', 'curl'];
$extension_status = [];
foreach ($required_extensions as $ext) {
    $extension_status[$ext] = extension_loaded($ext) ? 'Enabled' : 'Missing';
}
foreach ($optional_extensions as $ext) {
    $extension_status[$ext] = extension_loaded($ext) ? 'Enabled' : 'Missing (required for OAuth/SMTP)';
}

// Ensure tmp directory exists
$tmp_dir = '../tmp';
$web_user = $_SERVER['USER'] ?? 'www-data';
if (!is_dir($tmp_dir)) {
    if (!mkdir($tmp_dir, 0775, true)) {
        die("Failed to create tmp directory: $tmp_dir. Run: <code>mkdir -p " . htmlspecialchars($tmp_dir, ENT_QUOTES, 'UTF-8') . " && chmod 775 " . htmlspecialchars($tmp_dir, ENT_QUOTES, 'UTF-8') . " && chown $web_user " . htmlspecialchars($tmp_dir, ENT_QUOTES, 'UTF-8') . "</code>");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste 3 - Install</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link href="install.css" rel="stylesheet">
</head>
<body>
<div id="top" class="clearfix">
    <div class="applogo">
        <a href="#" class="logo">Paste</a>
    </div>
</div>

<div class="content container">
    <div class="row">
        <!-- INSTALL PANEL -->
        <div id="install">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Pre-installation Checks</h5>
                        <table class="table table-hover">
                            <tbody>
                                <tr>
                                    <th>PHP Version</th>
                                    <td>
                                        <span class="badge <?php echo $php_ok ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo htmlspecialchars($php_version); ?>
                                        </span>
                                        <?php if (!$php_ok): ?>
                                            <br><small class="text-danger">PHP 7.0 or higher is required. Please upgrade your PHP version.</small>
                                        </td>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php foreach ($extension_status as $ext => $status): ?>
                                    <tr>
                                        <th><?php echo htmlspecialchars($ext); ?></th>
                                        <td>
                                            <span class="badge <?php echo strpos($status, 'Enabled') !== false ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <th>File/Directory</th>
                                    <th>Status</th>
                                </tr>
                                <?php
                                $files = ['../config.php', '../tmp/temp.tdata', '../sitemap.xml'];
                                foreach ($files as $filename) {
                                    echo "<tr><td>" . htmlspecialchars(basename($filename)) . "</td>";
                                    $dir = dirname($filename);
                                    if (!is_dir($dir)) {
                                        echo '<td><span class="badge bg-danger">Directory Missing</span> Run: <code>mkdir -p ' . htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') . ' && chmod 775 ' . htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') . ' && chown ' . htmlspecialchars($web_user, ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') . '</code></td>';
                                    } elseif (is_writable($filename) || (!file_exists($filename) && is_writable($dir))) {
                                        echo '<td><span class="badge bg-success">Writable</span></td>';
                                    } else {
                                        echo '<td><span class="badge bg-danger">Not Writable</span> Run: <code>chmod 644 ' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . ' && chown ' . htmlspecialchars($web_user, ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . '</code></td>';
                                    }
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Database and Configuration</h5>
                        <div class="alert alert-danger" id="alertfailed" role="alert" style="display: none;">
                            Configuration failed. <span id="error-details"></span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <form id="db-form" class="row g-3" <?php echo !$php_ok ? 'style="display: none;"' : ''; ?>>
                            <div class="col-md-6">
                                <label for="data_host" class="form-label">Database Host</label>
                                <input type="text" class="form-control" id="data_host" name="data_host" value="localhost" required>
                            </div>
                            <div class="col-md-6">
                                <label for="data_name" class="form-label">Database Name</label>
                                <input type="text" class="form-control" id="data_name" name="data_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="data_user" class="form-label">Database Username</label>
                                <input type="text" class="form-control" id="data_user" name="data_user" required>
                            </div>
                            <div class="col-md-6">
                                <label for="data_pass" class="form-label">Database Password</label>
                                <input type="password" class="form-control" id="data_pass" name="data_pass">
                            </div>
                            <div class="col-md-6">
                                <label for="enablegoog" class="form-label">Enable Google OAuth User Logins</label>
                                <select class="form-select" id="enablegoog" name="enablegoog">
                                    <option value="no" selected>No</option>
                                    <option value="yes">Yes</option>
                                </select>
                                <small class="form-text text-muted">Enabling Google OAuth requires Google Cloud Console setup. HTTPS is recommended for security.</small>
                            </div>
                            <div class="col-md-6">
                                <label for="enablefb" class="form-label">Enable Facebook OAuth User Logins</label>
                                <select class="form-select" id="enablefb" name="enablefb">
                                    <option value="no" selected>No</option>
                                    <option value="yes">Yes</option>
                                </select>
                                <small class="form-text text-muted">Enabling Facebook OAuth requires Facebook Developer Portal setup. HTTPS is recommended for security.</small>
                            </div>
                            <div class="col-md-6">
                                <label for="enablesmtp" class="form-label">Enable SMTP Email (Gmail)</label>
                                <select class="form-select" id="enablesmtp" name="enablesmtp">
                                    <option value="no" selected>No</option>
                                    <option value="yes">Yes</option>
                                </select>
                                <small class="form-text text-muted">Enabling SMTP requires Gmail API setup or SMTP credentials. Configure in admin panel after installation.</small>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100">Configure</button>
                            </div>
                        </form>
                        <?php if (!$php_ok): ?>
                            <div class="alert alert-warning">
                                Installation is disabled because your PHP version (<?php echo htmlspecialchars($php_version); ?>) is too low. Please upgrade to PHP 7.0 or higher.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- END INSTALL PANEL -->

        <!-- CONFIGURATION PANEL -->
        <div id="configure" style="display: none;">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Configure Admin Account</h5>
                        <div class="alert alert-danger" id="admin-alertfailed" role="alert" style="display: none;">
                            Error admin setup failed. <span id="admin-error-details"></span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <form id="admin-form" class="row g-3">
                            <div class="col-md-6">
                                <label for="admin_user" class="form-label">Username</label>
                                <input type="text" class="form-control" id="admin_user" name="admin_user" required>
                            </div>
                            <div class="col-md-6">
                                <label for="admin_pass" class="form-label">Password</label>
                                <input type="password" class="form-control" id="admin_pass" name="admin_pass" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- END CONFIGURATION PANEL -->

        <div id="pre_load" style="display: none;">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Installing database schema for Paste. Please wait...</h5>
                    </div>
                </div>
            </div>
        </div>

        <div id="logpanel" class="col-md-12" style="display: none;">
            <div class="card mb-4">
                <div class="card-body">
                    <div id="log"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row footer">
        <div class="col-md-6 text-start">
            <a href="https://github.com/boxlabss/PASTE">Updates</a> &mdash; <a href="https://github.com/boxlabss/PASTE/issues">Bugs</a>
        </div>
        <div class="col-md-6 text-end">
            Powered by <a href="https://phpaste.sourceforge.io/" target="_blank">Paste 3</a>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
<script src="install.js"></script>
</body>
</html>