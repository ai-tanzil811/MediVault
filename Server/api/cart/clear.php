<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/auth.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    die(Response::error('Method not allowed', 405));
}

// Require authentication
$user = Auth::requireAuth($db);

if (empty($_GET['order_id'])) {
    die(Response::error('Order ID required', 400));
}

$orderId = (int)$_GET['order_id'];

// Verify order belongs to user
$order = $db->getOrder($orderId);
if (!$order || $order['user_id'] != $user['id']) {
    die(Response::error('Order not found or unauthorized', 404));
}

$db->clearOrderItems($orderId);

echo Response::success(['order_id' => $orderId], 'Cart cleared successfully');

?>
