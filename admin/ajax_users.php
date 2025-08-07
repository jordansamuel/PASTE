<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
require_once('../config.php');
// DB table to use
$table = 'users';

// Table's primary key
$primaryKey = 'id';

// Array of database columns which should be read and sent back to DataTables.
$columns = array(
    array('db' => 'username', 'dt' => 0),
    array('db' => 'email_id', 'dt' => 1),
    array('db' => 'date', 'dt' => 2),
    array('db' => 'platform', 'dt' => 3),
    array('db' => 'oauth_uid', 'dt' => 4),
    array('db' => 'id', 'dt' => 5),
    array('db' => 'verified', 'dt' => 6)
);

$columns2 = array(
    array('db' => 'username', 'dt' => 0),
    array('db' => 'email_id', 'dt' => 1),
    array('db' => 'date', 'dt' => 2),
    array('db' => 'platform', 'dt' => 3),
    array('db' => 'oauth_uid', 'dt' => 4),
    array('db' => 'ban', 'dt' => 5),
    array('db' => 'view', 'dt' => 6),
    array('db' => 'delete', 'dt' => 7)
);

// SQL server connection information
$sql_details = array(
    'user' => $dbuser,
    'pass' => $dbpassword,
    'db' => $dbname,
    'host' => $dbhost,
    'pdo' => $pdo
);

require('ssp.class.php');

echo json_encode(
    SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns, $columns2)
);
?>