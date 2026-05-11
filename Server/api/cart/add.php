<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/auth.php');
require_once(__DIR__ . '/../../includes/logger.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(Response::error('Method not allowed', 405));
}

// Require authentication
$user = Auth::requireAuth($db);

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['med_id']) || !is_numeric($input['med_id'])) {
    die(Response::error('medicine ID required', 400));
}

$medId = (int)$input['med_id'];
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;

if ($quantity < 1) {
    die(Response::error('Quantity must be at least 1', 400));
}

// Get the medicine
$medicine = $db->getMedicine($medId);
if (!$medicine) {
    die(Response::error('Medicine not found', 404));
}

// Check stock
if ($medicine['stock_level'] < $quantity) {
    die(Response::error('Insufficient stock', 400));
}

// Get or create pending order for this user
$pendingOrder = $db->getPendingOrder($user['id']);

if (!$pendingOrder) {
    $orderId = $db->createOrder($user['id'], 1); // Status 1 = Pending
} else {
    $orderId = $pendingOrder['order_id'];
}

// Check if medicine already in cart
$existingItem = $db->getOrderItemByMedicine($orderId, $medId);
if ($existingItem) {
    // Update quantity
    $newQuantity = $existingItem['quantity'] + $quantity;
    if ($medicine['stock_level'] < $newQuantity) {
        die(Response::error('Cannot add more, insufficient stock', 400));
    }
    // For now, we'll need to delete and re-add or update via SQL
    $db->removeOrderItem($existingItem['order_item_id']);
    $db->addOrderItem($orderId, $medId, $newQuantity);
} else {
    // Add new item
    $db->addOrderItem($orderId, $medId, $quantity);
}

// Log the action
Logger::logItemAddedToCart($db, $user['id'], $medicine['name'], $orderId);

// Get updated cart
$cartItems = $db->getOrderItems($orderId);
$cartTotal = 0;
foreach ($cartItems as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
}

$response = [
    'order_id' => $orderId,
    'items_count' => count($cartItems),
    'cart_total' => $cartTotal,
    'item_added' => [
        'med_id' => $medId,
        'name' => $medicine['name'],
        'quantity' => $quantity,
        'price' => $medicine['price']
    ]
];

echo Response::success($response, 'Item added to cart', 201);

?>
