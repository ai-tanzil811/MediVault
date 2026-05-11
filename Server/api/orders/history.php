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

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;

$orders = $db->getUserOrders($user['id'], null, $limit, $offset);

// Enhance with status names and totals
foreach ($orders as &$order) {
    $statusId = $order['status_id'];
    $statusName = 'Unknown';
    if ($statusId == 1) $statusName = 'Pending Approval';
    else if ($statusId == 2) $statusName = 'Approved/Ready';
    else if ($statusId == 3) $statusName = 'Rejected';
    else if ($statusId == 4) $statusName = 'Completed';

    $order['status_name'] = $statusName;

    // Get items and calculate total
    $items = $db->getOrderItems($order['order_id']);
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    $order['total'] = round($total, 2);
    $order['item_count'] = count($items);
}

// Get total count of user orders
$allOrders = $db->getUserOrders($user['id'], null, 10000, 0);
$total = count($allOrders);

echo Response::paginated($orders, $page, $limit, $total, 'Order history retrieved');

?>
