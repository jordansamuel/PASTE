<?php
// Credits: thatarchguy - https://bitbucket.org/j-samuel/paste/issues/5/upgrade-path-for-20

require_once('../includes/password.php');

// Required functions
require_once('../config.php');
require_once('../includes/captcha.php');
require_once('../includes/functions.php');

// Database Connection
$con = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);
if (mysqli_connect_errno()) {
    die("Unable to connect to database");
}

$query  = "SELECT * FROM paste";
$result = mysqli_query($con, $query);

$post = array();
while ($row = mysqli_fetch_assoc($result)) {
    $post[] = $row;
}

foreach ($post as $row) {
    $pid = $row['pid'];
    $poster = Trim(htmlspecialchars($row['poster']));
    $posted = $row['posted'];
    $code = htmlspecialchars($row['code']);
    $password = $row['password'];

    if ($password != "EMPTY"){
        $p_password = password_hash($password, PASSWORD_DEFAULT);
    } else {
        $p_password = "NONE";
    }

    $code = mysqli_real_escape_string($con, $code);

    $query = "INSERT INTO pastes (id,title,content,visible,code,expiry,password,encrypt,member,date,ip,now_time,views,s_date) VALUES
    ($pid, '$poster', '$code', '0', 'text', 'NULL', '$p_password', '0', 'Guest', DATE_FORMAT('$posted', '%D %M %Y %r'), '127.0.0.1',
    UNIX_TIMESTAMP('$posted'), '0', DATE_FORMAT('$posted', '%D %M %Y'))";

    $result    = mysqli_query($con, $query);
    var_dump($result);
    if (mysqli_error($con)) {
            echo mysqli_error($con);
    }
}
?>
