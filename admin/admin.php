<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
require_once('../includes/password.php');
session_start();

// Check session and validate admin
if (!isset($_SESSION['admin_login']) || !isset($_SESSION['admin_id'])) {
    error_log("admin.php: Session validation failed - admin_login or admin_id not set. Session: " . json_encode($_SESSION));
    header("Location: ../index.php");
    exit();
}

$date = date('Y-m-d H:i:s'); // Use DATETIME format for database
$ip = $_SERVER['REMOTE_ADDR'];
require_once('../config.php');

try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Validate current admin using admin_id
    $stmt = $pdo->prepare("SELECT id, user, pass FROM admin WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_admin_id = $row ? $row['id'] : null;
    $adminid = $row ? trim($row['user']) : '';
    $password = $row ? trim($row['pass']) : '';

    if (!$row || $row['user'] !== $_SESSION['admin_login']) {
        error_log("admin.php: Admin validation failed - id: {$_SESSION['admin_id']}, user: {$_SESSION['admin_login']}, found: " . ($row ? json_encode($row) : 'null'));
        unset($_SESSION['admin_login']);
        unset($_SESSION['admin_id']);
        header("Location: ../index.php");
        exit();
    }

    // Handle logout
    if (isset($_GET['logout'])) {
        unset($_SESSION['admin_login']);
        unset($_SESSION['admin_id']);
        session_destroy();
        header("Location: ../index.php");
        exit();
    }

    // Log admin login
    $stmt = $pdo->query("SELECT MAX(id) AS last_id FROM admin_history");
    $last_id = $stmt->fetch(PDO::FETCH_ASSOC)['last_id'] ?? null;

    if ($last_id) {
        $stmt = $pdo->prepare("SELECT last_date, ip FROM admin_history WHERE id = ?");
        $stmt->execute([$last_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $last_date = $row['last_date'] ?? null;
        $last_ip = $row['ip'] ?? null;
    }

    if ($last_ip !== $ip || $last_date !== $date) {
        $stmt = $pdo->prepare("INSERT INTO admin_history (last_date, ip) VALUES (?, ?)");
        $stmt->execute([$date, $ip]);
    }

    // Handle update current admin
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_admin'])) {
        $adminid = trim($_POST['adminid']);
        $new_password = trim($_POST['password']);

        if (empty($adminid)) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Error: Username is required</div>';
        } elseif (strlen($adminid) < 3 || strlen($adminid) > 50 || !preg_match('/^[a-zA-Z0-9]+$/', $adminid)) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Error: Username must be 3–50 alphanumeric characters</div>';
        } elseif (!empty($new_password) && strlen($new_password) < 8) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Error: Password must be at least 8 characters</div>';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM admin WHERE user = ? AND id != ?");
                $stmt->execute([$adminid, $current_admin_id]);
                if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0) {
                    $msg = '<div class="paste-alert alert6" style="text-align: center;">Error: Username already exists</div>';
                } else {
                    $password = !empty($new_password) ? password_hash($new_password, PASSWORD_DEFAULT) : $password;
                    $stmt = $pdo->prepare("UPDATE admin SET user = ?, pass = ? WHERE id = ?");
                    $stmt->execute([$adminid, $password, $current_admin_id]);
                    $_SESSION['admin_login'] = $adminid; // Update session with new username
                    $msg = '<div class="paste-alert alert3" style="text-align: center;">Account details updated</div>';
                }
            } catch (PDOException $e) {
                $msg = '<div class="paste-alert alert6" style="text-align: center;">Error updating account: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }

    // Handle add new admin
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_admin'])) {
        $new_username = trim($_POST['new_username']);
        $new_password = trim($_POST['new_password']);

        if (empty($new_username) || empty($new_password)) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Error: Username and password are required</div>';
        } elseif (strlen($new_username) < 3 || strlen($new_username) > 50 || !preg_match('/^[a-zA-Z0-9]+$/', $new_username)) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Error: Username must be 3–50 alphanumeric characters</div>';
        } elseif (strlen($new_password) < 8) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Error: Password must be at least 8 characters</div>';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM admin WHERE user = ?");
                $stmt->execute([$new_username]);
                if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0) {
                    $msg = '<div class="paste-alert alert6" style="text-align: center;">Error: Username already exists</div>';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO admin (user, pass) VALUES (?, ?)");
                    $stmt->execute([$new_username, $hashed_password]);
                    $msg = '<div class="paste-alert alert3" style="text-align: center;">New admin added successfully</div>';
                }
            } catch (PDOException $e) {
                $msg = '<div class="paste-alert alert6" style="text-align: center;">Error adding admin: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }

    // Handle delete admin
    if (isset($_GET['delete_admin']) && is_numeric($_GET['delete_admin'])) {
        $admin_id = (int)$_GET['delete_admin'];
        if ($admin_id == $current_admin_id) {
            $msg = '<div class="paste-alert alert6" style="text-align: center;">Error: Cannot delete the current admin</div>';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM admin WHERE id = ?");
                $stmt->execute([$admin_id]);
                $msg = '<div class="paste-alert alert3" style="text-align: center;">Admin deleted successfully</div>';
            } catch (PDOException $e) {
                $msg = '<div class="paste-alert alert6" style="text-align: center;">Error deleting admin: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }

    // Fetch all admins
    $stmt = $pdo->query("SELECT id, user FROM admin ORDER BY id");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pagination for login history
    $rec_limit = 10;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $rec_limit;

    $stmt = $pdo->query("SELECT COUNT(id) AS cnt FROM admin_history");
    $rec_count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    $total_pages = ceil($rec_count / $rec_limit);

    $stmt = $pdo->prepare("SELECT last_date, ip FROM admin_history ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $rec_limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $history_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("admin.php: Database connection failed: " . $e->getMessage());
    die("Unable to connect to database: " . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Admin Settings</title>
    <link rel="shortcut icon" href="favicon.ico">
    <link href="css/paste.css" rel="stylesheet" type="text/css" />
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
                  <li><a href="admin.php">Settings</a></li>
                  <li><a href="?logout">Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>

    <div class="content">
        <div class="container-widget">
            <div class="row">
                <div class="col-md-12">
                    <ul class="panel quick-menu clearfix">
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="dashboard.php"><i class="fa fa-home"></i>Dashboard</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="configuration.php"><i class="fa fa-cogs"></i>Configuration</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="interface.php"><i class="fa fa-eye"></i>Interface</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1 menu-active"><a href="admin.php"><i class="fa fa-user"></i>Admin Account</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="pastes.php"><i class="fa fa-clipboard"></i>Pastes</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="users.php"><i class="fa fa-users"></i>Users</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="ipbans.php"><i class="fa fa-ban"></i>IP Bans</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="stats.php"><i class="fa fa-line-chart"></i>Statistics</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="ads.php"><i class="fa fa-gbp"></i>Ads</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="pages.php"><i class="fa fa-file"></i>Pages</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="sitemap.php"><i class="fa fa-map-signs"></i>Sitemap</a></li>
                        <li class="col-xs-3 col-sm-2 col-md-1"><a href="tasks.php"><i class="fa fa-tasks"></i>Tasks</a></li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-widget">
                        <div class="panel-body">
                            <div role="tabpanel">
                                <ul class="nav nav-tabs nav-line" role="tablist" style="text-align: center;">
                                    <li role="presentation" class="active"><a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">Settings</a></li>
                                    <li role="presentation"><a href="#manage_admins" aria-controls="manage_admins" role="tab" data-toggle="tab">Manage Admins</a></li>
                                    <li role="presentation"><a href="#logs" aria-controls="logs" role="tab" data-toggle="tab">Login History</a></li>
                                </ul>

                                <div class="tab-content">
                                    <div role="tabpanel" class="tab-pane active" id="settings">
                                        <h4>My Settings</h4>
                                        <?php if (isset($msg)) echo $msg; ?>
                                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="form-area" method="POST" id="admin-form">
                                            <input type="hidden" name="update_admin" value="1">
                                            <div class="group">
                                                <input type="text" id="adminid" name="adminid" class="form-control" placeholder="Username (3–50 alphanumeric characters)" value="<?php echo htmlspecialchars($adminid); ?>" required>
                                                <i class="fa fa-user"></i>
                                            </div>
                                            <div class="group">
                                                <input type="password" id="password" name="password" class="form-control" placeholder="Password (leave blank to keep current)">
                                                <i class="fa fa-key"></i>
                                            </div>
                                            <button type="submit" class="btn btn-default btn-block">Save</button>
                                        </form>
                                    </div>

                                    <div role="tabpanel" class="tab-pane" id="manage_admins">
                                        <h4>Manage Admins</h4>
                                        <h5>Add New Admin</h5>
                                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="form-area" method="POST" id="add-admin-form">
                                            <input type="hidden" name="add_admin" value="1">
                                            <div class="group">
                                                <input type="text" id="new_username" name="new_username" class="form-control" placeholder="New Admin Username (3–50 alphanumeric characters)" required>
                                                <i class="fa fa-user"></i>
                                            </div>
                                            <div class="group">
                                                <input type="password" id="new_password" name="new_password" class="form-control" placeholder="New Admin Password (minimum 8 characters)" required>
                                                <i class="fa fa-key"></i>
                                            </div>
                                            <button type="submit" class="btn btn-default btn-block">Add Admin</button>
                                        </form>

                                        <h5>Existing Admins</h5>
                                        <?php if (empty($admins)): ?>
                                            <p>No admins found.</p>
                                        <?php else: ?>
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Username</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($admins as $admin): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($admin['id']); ?></td>
                                                            <td><?php echo htmlspecialchars($admin['user']); ?></td>
                                                            <td>
                                                                <?php if ($admin['id'] != $current_admin_id): ?>
                                                                    <a href="?delete_admin=<?php echo $admin['id']; ?>" class="btn btn-danger btn-sm delete-admin" data-id="<?php echo $admin['id']; ?>">Delete</a>
                                                                <?php else: ?>
                                                                    <span>Current Admin</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>

                                    <div role="tabpanel" class="tab-pane" id="logs">
                                        <h4>Login History</h4>
                                        <?php if ($rec_count == 0): ?>
                                            <p>No login history available.</p>
                                        <?php else: ?>
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Login Date</th>
                                                        <th>IP</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($history_rows as $row): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($row['last_date']); ?></td>
                                                            <td><?php echo htmlspecialchars($row['ip']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <?php if ($total_pages > 1): ?>
                                                <nav aria-label="Page navigation">
                                                    <ul class="pagination">
                                                        <?php if ($page > 1): ?>
                                                            <li><a href="?page=<?php echo $page - 1; ?>" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>
                                                        <?php endif; ?>
                                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                            <li <?php if ($i == $page) echo 'class="active"'; ?>><a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                                                        <?php endfor; ?>
                                                        <?php if ($page < $total_pages): ?>
                                                            <li><a href="?page=<?php echo $page + 1; ?>" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </nav>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
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
    </div>

    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/bootstrap-select.js"></script>
    <script>
        $(document).ready(function() {
            $('#admin-form').on('submit', function(e) {
                if (!confirm('Are you sure you want to update your settings?')) {
                    e.preventDefault();
                }
            });
            $('#add-admin-form').on('submit', function(e) {
                if (!confirm('Are you sure you want to add a new admin?')) {
                    e.preventDefault();
                }
            });
            $('.delete-admin').on('click', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                const id = $(this).data('id');
                if (confirm('Are you sure you want to delete admin ID ' + id + '? This action cannot be undone.')) {
                    window.location.href = href;
                }
            });
        });
    </script>
</body>
</html>
<?php $pdo = null; ?>