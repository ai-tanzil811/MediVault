<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/response.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    die(Response::error('Method not allowed', 405));
}

if (empty($_GET['id'])) {
    die(Response::error('Medicine ID required', 400));
}

$medId = (int)$_GET['id'];
$medicine = $db->getMedicine($medId);

if (!$medicine) {
    die(Response::error('Medicine not found', 404));
}

// Get conflicts for this medicine
$allConflicts = $db->getAllConflicts();
$conflicts = [];
foreach ($allConflicts as $conflict) {
    if ($conflict['drug1_id'] == $medId || $conflict['drug2_id'] == $medId) {
        $conflicts[] = $conflict;
    }
}

$medicine['conflicts'] = $conflicts;

// Remove BLOB from response for this endpoint too (if needed separately)
// Keep it here since user might want to see the image separately

echo Response::success($medicine, 'Medicine details retrieved');

?>
