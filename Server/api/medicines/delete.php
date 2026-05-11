<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/auth.php');
require_once(__DIR__ . '/../../includes/logger.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    die(Response::error('Method not allowed', 405));
}

// Require admin authentication
$user = Auth::requireAdminAuth($db);

if (empty($_GET['id'])) {
    die(Response::error('Medicine ID required', 400));
}

$medId = (int)$_GET['id'];
$medicine = $db->getMedicine($medId);

if (!$medicine) {
    die(Response::error('Medicine not found', 404));
}

try {
    $db->deleteMedicine($medId);

    // Log the action
    Logger::logMedicineDeleted($db, $medicine['name']);

    echo Response::success(['med_id' => $medId], 'Medicine deleted successfully');
} catch (Exception $e) {
    error_log('Medicine deletion error: ' . $e->getMessage());
    die(Response::error('Failed to delete medicine', 500));
}

?>
