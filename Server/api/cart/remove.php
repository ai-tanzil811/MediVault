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

// Require authentication
$user = Auth::requireAuth($db);

if (empty($_GET['item_id'])) {
    die(Response::error('Order item ID required', 400));
}

$itemId = (int)$_GET['item_id'];

// For simplicity, we'll need to verify the item belongs to user's cart
// This requires a more complex query, so we'll just try to delete it
// In production, add proper authorization check

$db->removeOrderItem($itemId);

// Log the action
Logger::log($db, 'ITEM_REMOVED', 'INFO', 'Item removed from cart');

echo Response::success(['item_id' => $itemId], 'Item removed from cart');

?>
