<?php
require_once('../config.php');
// DB table to use
$table = 'users';

// Table's primary key
$primaryKey = 'id';

// Array of database columns which should be read and sent back to DataTables.
// The `db` parameter represents the column name in the database, while the `dt`
// parameter represents the DataTables column identifier. In this case simple
// indexes
$columns = array(
	array( 'db' => 'username', 'dt' => 0 ),
	array( 'db' => 'email_id',  'dt' => 1 ),
	array( 'db' => 'date',  'dt' => 2),
	array( 'db' => 'platform',   'dt' => 3 ),
	array( 'db' => 'oauth_uid',   'dt' => 4 ),
	array( 'db' => 'id',   'dt' => 5 ),
	array( 'db' => 'verified',   'dt' => 6 )
);

$columns2 = array(
	array( 'db' => 'username', 'dt' => 0 ),
	array( 'db' => 'email_id',  'dt' => 1 ),
	array( 'db' => 'date',  'dt' => 2),
	array( 'db' => 'platform',   'dt' => 3 ),
	array( 'db' => 'oauth_uid',   'dt' => 4 ),
	array( 'db' => 'ban',  'dt' => 5 ),
	array( 'db' => 'view',  'dt' => 6 ),
	array( 'db' => 'delete',   'dt' => 7)
);


// SQL server connection information
$sql_details = array(
	'user' => $dbuser,
	'pass' => $dbpassword,
	'db'   => $dbname,
	'host' => $dbhost
);


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * If you just want to use the basic configuration for DataTables with PHP
 * server-side, there is no need to edit below this line.
 */

require( 'ssp.users.php' );

echo json_encode(
	SSP::simple( $_GET, $sql_details, $table, $primaryKey, $columns, $columns2 )
);
?>