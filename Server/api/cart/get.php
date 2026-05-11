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

// Get or create pending order
$pendingOrder = $db->getPendingOrder($user['id']);

if (!$pendingOrder) {
    // No cart yet
    echo Response::success([
        'order_id' => null,
        'items' => [],
        'cart_total' => 0,
        'item_count' => 0
    ], 'Cart is empty');
    exit;
}

$orderId = $pendingOrder['order_id'];
$cartItems = $db->getOrderItems($orderId);

$cartTotal = 0;
foreach ($cartItems as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
}

$response = [
    'order_id' => $orderId,
    'items' => $cartItems,
    'cart_total' => round($cartTotal, 2),
    'item_count' => count($cartItems)
];

echo Response::success($response, 'Cart retrieved successfully');

?>
