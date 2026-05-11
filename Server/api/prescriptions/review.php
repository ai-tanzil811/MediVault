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
$admin = Auth::requireAdminAuth($db);

$input = json_decode(file_get_contents('php://input'), true);

// Validate input
$errors = Validation::validatePrescriptionReviewInput($input);
if (!empty($errors)) {
    die(Response::error('Validation failed', 400, $errors));
}

$reviewId = (int)$input['review_id'];
$decision = Security::sanitizeInput($input['decision']);
$notes = Security::sanitizeInput($input['notes'] ?? '');

// Get the prescription review
$reviewData = $db->fetchOne(
    'SELECT * FROM Prescription_Reviews WHERE review_id = ?',
    [$reviewId]
);

if (!$reviewData) {
    die(Response::error('Prescription review not found', 404));
}

// Update prescription review
$db->updatePrescriptionReview($reviewId, $decision, $admin['admin_id'], $notes);

// Update order status based on decision
$orderId = $reviewData['order_id'];
if ($decision === 'Approved') {
    $newStatus = 2; // Approved/Ready
    Logger::logPrescriptionReview($db, $reviewId, $orderId, $decision, $admin['admin_id']);
} else {
    $newStatus = 3; // Rejected
    Logger::logPrescriptionRejected($db, $reviewId, $orderId, $notes);
}

$db->updateOrderStatus($orderId, $newStatus);

$response = [
    'review_id' => $reviewId,
    'order_id' => $orderId,
    'decision' => $decision,
    'reviewed_by' => $admin['name']
];

echo Response::success($response, 'Prescription review submitted');

?>
