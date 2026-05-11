<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/auth.php');
require_once(__DIR__ . '/../../includes/logger.php');
require_once(__DIR__ . '/../../includes/security.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(Response::error('Method not allowed', 405));
}

// Require authentication
$user = Auth::requireAuth($db);

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['order_id'])) {
    die(Response::error('Order ID required', 400));
}

$orderId = (int)$input['order_id'];
$order = $db->getOrder($orderId);

if (!$order || $order['user_id'] != $user['id']) {
    die(Response::error('Order not found or unauthorized', 404));
}

// Get cart items
$cartItems = $db->getOrderItems($orderId);

if (empty($cartItems)) {
    die(Response::error('Cart is empty', 400));
}

// Check if any item requires prescription
$prescriptionRequired = false;
foreach ($cartItems as $item) {
    $med = $db->getMedicine($item['med_id']);
    if ($med && $med['prescription_required']) {
        $prescriptionRequired = true;
        break;
    }
}

// If prescription required, validate file upload
if ($prescriptionRequired) {
    if (!isset($_FILES['prescription'])) {
        die(Response::error('Prescription image required for this order', 400));
    }

    $fileValidation = Security::validateFileUpload($_FILES['prescription']);
    if (!$fileValidation['valid']) {
        die(Response::error($fileValidation['error'], 400));
    }

    $prescriptionBlob = Security::readFileAsBlob($_FILES['prescription']['tmp_name']);
    $prescriptionType = $fileValidation['mimeType'];

    // Store prescription in order
    $db->executeQuery(
        'UPDATE Orders SET prescription_image = ?, prescription_image_type = ? WHERE order_id = ?',
        [$prescriptionBlob, $prescriptionType, $orderId]
    );

    // Update status to Pending Approval and create prescription review
    $db->updateOrderStatus($orderId, 1); // Pending Approval
    $db->createPrescriptionReview($orderId, 'Pending');

    Logger::logPrescriptionUploaded($db, $orderId);
} else {
    // No prescription required, mark as approved
    $db->updateOrderStatus($orderId, 2); // Approved/Ready
}

Logger::logOrderPlaced($db, $orderId, $user['id']);

$cartTotal = 0;
foreach ($cartItems as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
}

$response = [
    'order_id' => $orderId,
    'status' => $prescriptionRequired ? 'Pending Approval' : 'Approved/Ready',
    'items_count' => count($cartItems),
    'total' => round($cartTotal, 2),
    'created_at' => $order['order_date']
];

echo Response::success($response, 'Order placed successfully', 201);

?>
