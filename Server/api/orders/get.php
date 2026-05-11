<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/auth.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    die(Response::error('Method not allowed', 405));
}

// Require authentication
$user = Auth::requireAuth($db);

if (empty($_GET['id'])) {
    die(Response::error('Order ID required', 400));
}

$orderId = (int)$_GET['id'];
$order = $db->getOrder($orderId);

if (!$order || $order['user_id'] != $user['id']) {
    die(Response::error('Order not found or unauthorized', 404));
}

// Get order items
$items = $db->getOrderItems($orderId);

// Get order status name
$statusId = $order['status_id'];
$statusName = 'Unknown';
if ($statusId == 1) $statusName = 'Pending Approval';
else if ($statusId == 2) $statusName = 'Approved/Ready';
else if ($statusId == 3) $statusName = 'Rejected';
else if ($statusId == 4) $statusName = 'Completed';

// Get prescription review if exists
$review = $db->getPrescriptionReview($orderId);

$cartTotal = 0;
foreach ($items as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
}

$response = [
    'order_id' => $orderId,
    'status_id' => $statusId,
    'status_name' => $statusName,
    'items' => $items,
    'total' => round($cartTotal, 2),
    'created_at' => $order['order_date'],
    'prescription_review' => $review
];

echo Response::success($response, 'Order retrieved successfully');

?>
