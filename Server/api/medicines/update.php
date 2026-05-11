<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/security.php');
require_once(__DIR__ . '/../../includes/validation.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/auth.php');
require_once(__DIR__ . '/../../includes/logger.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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

$input = json_decode(file_get_contents('php://input'), true);

// Build update data from provided fields
$updateData = [];

if (isset($input['name'])) {
    $updateData['name'] = Security::sanitizeInput($input['name']);
}

if (isset($input['brand'])) {
    $updateData['brand'] = Security::sanitizeInput($input['brand']);
}

if (isset($input['category'])) {
    $updateData['category'] = Security::sanitizeInput($input['category']);
}

if (isset($input['description'])) {
    $updateData['description'] = Security::sanitizeInput($input['description']);
}

if (isset($input['dosage_instructions'])) {
    $updateData['dosaage_instructions'] = Security::sanitizeInput($input['dosage_instructions']);
}

if (isset($input['storage_instructions'])) {
    $updateData['storage_instructions'] = Security::sanitizeInput($input['storage_instructions']);
}

if (isset($input['price'])) {
    if (!is_numeric($input['price']) || $input['price'] <= 0) {
        die(Response::error('Invalid price', 400));
    }
    $updateData['price'] = (float)$input['price'];
}

if (isset($input['stock_level'])) {
    if (!is_numeric($input['stock_level']) || $input['stock_level'] < 0) {
        die(Response::error('Invalid stock level', 400));
    }
    $updateData['stock_level'] = (int)$input['stock_level'];
}

if (isset($input['exp_date'])) {
    $expDate = DateTime::createFromFormat('Y-m-d', $input['exp_date']);
    if (!$expDate || $expDate->format('Y-m-d') !== $input['exp_date']) {
        die(Response::error('Invalid date format (use YYYY-MM-DD)', 400));
    }
    $updateData['exp_date'] = $input['exp_date'];
}

if (isset($input['prescription_required'])) {
    $updateData['prescription_required'] = (bool)$input['prescription_required'];
}

if (empty($updateData)) {
    die(Response::error('No fields to update', 400));
}

try {
    $db->updateMedicine($medId, $updateData);

    // Log the action
    Logger::logMedicineUpdated($db, $medicine['name']);

    $updatedMedicine = $db->getMedicine($medId);
    echo Response::success($updatedMedicine, 'Medicine updated successfully');
} catch (Exception $e) {
    error_log('Medicine update error: ' . $e->getMessage());
    die(Response::error('Failed to update medicine', 500));
}

?>
