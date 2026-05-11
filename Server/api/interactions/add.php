<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/security.php');
require_once(__DIR__ . '/../../includes/validation.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/auth.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(Response::error('Method not allowed', 405));
}

// Require admin authentication
$user = Auth::requireAdminAuth($db);

$input = json_decode(file_get_contents('php://input'), true);

// Validate input
$errors = Validation::validateDrugConflictInput($input);
if (!empty($errors)) {
    die(Response::error('Validation failed', 400, $errors));
}

$drug1Id = (int)$input['drug1_id'];
$drug2Id = (int)$input['drug2_id'];
$description = Security::sanitizeInput($input['description']);
$classification = Security::sanitizeInput($input['classification'] ?? 'moderate');

// Verify both medicines exist
$med1 = $db->getMedicine($drug1Id);
$med2 = $db->getMedicine($drug2Id);

if (!$med1 || !$med2) {
    die(Response::error('One or both medicines not found', 404));
}

// Check if conflict already exists
$existingConflict = $db->checkDrugConflict($drug1Id, $drug2Id);
if ($existingConflict) {
    die(Response::error('Conflict already exists between these medicines', 400));
}

try {
    $db->addDrugConflict($drug1Id, $drug2Id, $description, $classification);
    $conflictId = $db->lastInsertId();

    $response = [
        'conflict_id' => $conflictId,
        'drug1_id' => $drug1Id,
        'drug1_name' => $med1['name'],
        'drug2_id' => $drug2Id,
        'drug2_name' => $med2['name'],
        'description' => $description,
        'classification' => $classification
    ];

    echo Response::success($response, 'Conflict added successfully', 201);
} catch (Exception $e) {
    error_log('Conflict creation error: ' . $e->getMessage());
    die(Response::error('Failed to add conflict', 500));
}

?>
