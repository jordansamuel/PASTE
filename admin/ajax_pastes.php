<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
header('Content-Type: application/json');

require_once('../config.php');

try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// DataTables parameters
$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
$search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
$orderColumnIndex = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';

// Map DataTables column index to database column
$columns = ['id', 'member', 'ip', 'visible'];
$orderColumn = $columns[$orderColumnIndex];

// Base query
$query = "SELECT id, member, ip, visible FROM pastes";
$countQuery = "SELECT COUNT(id) AS total FROM pastes";
$where = '';
$params = [];

// Search filter
if (!empty($search)) {
    $where = " WHERE member LIKE ? OR ip LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Total records
$stmt = $pdo->query($countQuery);
$totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Filtered records
if (!empty($where)) {
    $stmt = $pdo->prepare($countQuery . $where);
    $stmt->execute($params);
    $filteredRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} else {
    $filteredRecords = $totalRecords;
}

// Fetch data
$query .= $where;
$query .= " ORDER BY $orderColumn $orderDir LIMIT ?, ?";
$params[] = $start;
$params[] = $length;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $visibility = match ($row['visible']) {
        '0' => 'Public',
        '1' => 'Unlisted',
        '2' => 'Private',
        default => 'Unknown'
    };
    $data[] = [
        htmlspecialchars($row['id']),
        htmlspecialchars($row['member']),
        htmlspecialchars($row['ip']),
        $visibility
    ];
}

// JSON response
$response = [
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $filteredRecords,
    'data' => $data
];

echo json_encode($response);
exit();
?>