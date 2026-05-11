<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/auth.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    die(Response::error('Method not allowed', 405));
}

// Require admin authentication
$user = Auth::requireAdminAuth($db);

$conflicts = $db->getAllConflicts();

// Enhance with medicine names
foreach ($conflicts as &$conflict) {
    $med1 = $db->getMedicine($conflict['drug1_id']);
    $med2 = $db->getMedicine($conflict['drug2_id']);

    $conflict['drug1_name'] = $med1 ? $med1['name'] : 'Unknown';
    $conflict['drug2_name'] = $med2 ? $med2['name'] : 'Unknown';
}

echo Response::success($conflicts, 'Conflicts retrieved successfully');

?>
