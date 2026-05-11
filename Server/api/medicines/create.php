<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/security.php');
require_once(__DIR__ . '/../../includes/validation.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/auth.php');
require_once(__DIR__ . '/../../includes/logger.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(Response::error('Method not allowed', 405));
}

// Require admin authentication
$user = Auth::requireAdminAuth($db);

$input = json_decode(file_get_contents('php://input'), true);

// Validate input
$errors = Validation::validateMedicineInput($input);
if (!empty($errors)) {
    die(Response::error('Validation failed', 400, $errors));
}

// Sanitize inputs
$name = Security::sanitizeInput($input['name']);
$brand = Security::sanitizeInput($input['brand']);
$category = Security::sanitizeInput($input['category']);
$description = Security::sanitizeInput($input['description'] ?? '');
$dosage = Security::sanitizeInput($input['dosage_instructions'] ?? '');
$storage = Security::sanitizeInput($input['storage_instructions'] ?? '');
$price = (float)$input['price'];
$stock = (int)$input['stock_level'];
$expDate = $input['exp_date'];
$prescReq = isset($input['prescription_required']) ? (bool)$input['prescription_required'] : false;

// Handle optional medicine image
$medImage = null;
$medImageType = null;

if (isset($_FILES['medicine_image'])) {
    $fileValidation = Security::validateFileUpload($_FILES['medicine_image']);
    if ($fileValidation['valid']) {
        $medImage = Security::readFileAsBlob($_FILES['medicine_image']['tmp_name']);
        $medImageType = $fileValidation['mimeType'];
    }
}

try {
    $db->createMedicine($name, $brand, $category, $description, $dosage, $storage,
                       $price, $stock, $expDate, $prescReq, $medImage, $medImageType);
    $medId = $db->lastInsertId();

    // Log the action
    Logger::logMedicineAdded($db, $name);

    $response = [
        'med_id' => $medId,
        'name' => $name,
        'brand' => $brand,
        'category' => $category,
        'price' => $price,
        'stock_level' => $stock
    ];

    echo Response::success($response, 'Medicine added successfully', 201);
} catch (Exception $e) {
    error_log('Medicine creation error: ' . $e->getMessage());
    die(Response::error('Failed to add medicine', 500));
}

?>
