<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
require_once('config.php');

// Database Connection (PDO from config.php)
global $pdo;

$username = htmlentities(trim($_GET['username']));
$code = htmlentities(trim($_GET['code']));

try {
    $stmt = $pdo->prepare("SELECT email_id, verified FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch();
        if ($row['verified'] == '1') {
            die("Account already verified.");
        }

        $ver_code = md5('4et4$55765' . $row['email_id'] . 'd94ereg');
        if ($ver_code == $code) {
            $stmt = $pdo->prepare("UPDATE users SET verified = '1' WHERE username = ?");
            $stmt->execute([$username]);
            header("Location: login.php?login");
            exit();
        } else {
            die("Invalid verification code.");
        }
    } else {
        die("Username not found.");
    }
} catch (PDOException $e) {
    die("Things went terribly wrong: " . $e->getMessage());
}
?>