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
require_once('../config.php');

// Check if admin is already logged in
if (isset($_SESSION['admin_login']) && isset($_SESSION['admin_id'])) {
    try {
        $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare('SELECT id, user FROM admin WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['user'] === $_SESSION['admin_login']) {
            error_log("index.php: Admin already logged in - user: {$_SESSION['admin_login']}, redirecting to dashboard.php");
            header("Location: dashboard.php");
            exit();
        } else {
            error_log("index.php: Session validation failed - id: {$_SESSION['admin_id']}, user: {$_SESSION['admin_login']}, found: " . ($row ? json_encode($row) : 'null'));
            unset($_SESSION['admin_login']);
            unset($_SESSION['admin_id']);
        }
        $pdo = null;
    } catch (PDOException $e) {
        error_log("index.php: Database connection failed during session validation: " . $e->getMessage());
        unset($_SESSION['admin_login']);
        unset($_SESSION['admin_id']);
    }
}

try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        if (empty($username) || empty($password)) {
            error_log("index.php: Login failed - username or password empty. Username: '$username'");
            $msg = '<div class="paste-alert alert6" style="text-align:center;">Username and password are required</div>';
        } else {
            $stmt = $pdo->prepare('SELECT id, user, pass FROM admin WHERE user = :user LIMIT 1');
            $stmt->execute(['user' => $username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && password_verify($password, $row['pass'])) {
                $_SESSION['admin_login'] = $row['user'];
                $_SESSION['admin_id'] = $row['id'];
                error_log("index.php: Login successful for user: '$username', redirecting to dashboard.php");
                header("Location: dashboard.php");
                exit();
            } else {
                error_log("index.php: Login failed - invalid username or password. Username: '$username', Row: " . ($row ? json_encode($row) : 'null'));
                $msg = '<div class="paste-alert alert6" style="text-align:center;">Wrong User/Password</div>';
            }
        }
    }
} catch (PDOException $e) {
    error_log("index.php: Database connection failed: " . $e->getMessage());
    $msg = '<div class="paste-alert alert6" style="text-align:center;">Unable to connect to database: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paste - Login</title>
    <link href="css/paste.css" rel="stylesheet">
    <style type="text/css">
        body { background: #F5F5F5; }
    </style>
</head>
<body>
    <div class="login-form">
        <?php if (isset($msg)) echo $msg; ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="top">
                <h1>Paste</h1>
            </div>
            <div class="form-area">
                <div class="group">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" value="">
                    <i class="fa fa-user"></i>
                </div>
                <div class="group">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" value="">
                    <i class="fa fa-key"></i>
                </div>
                <button type="submit" class="btn btn-default btn-block">LOGIN</button>
            </div>
        </form>
    </div>
</body>
</html>
<?php $pdo = null; ?>