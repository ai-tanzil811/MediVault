<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/auth.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    die(Response::error('Method not allowed', 405));
}

// Require admin authentication
$admin = Auth::requireAdminAuth($db);

// Get stats
$totalUsers = $db->fetchOne('SELECT COUNT(*) as total FROM Users')['total'];
$totalOrders = $db->fetchOne('SELECT COUNT(*) as total FROM Orders')['total'];
$pendingReviews = $db->fetchOne('SELECT COUNT(*) as total FROM Prescription_Reviews WHERE decision = "Pending"')['total'];
$completedOrders = $db->fetchOne('SELECT COUNT(*) as total FROM Orders WHERE status_id = 4')['total'];

// Get recent activity (last 10 logs)
$recentActivity = $db->getActivityLogs(10, 0);

$response = [
    'stats' => [
        'total_users' => (int)$totalUsers,
        'total_orders' => (int)$totalOrders,
        'pending_reviews' => (int)$pendingReviews,
        'completed_orders' => (int)$completedOrders
    ],
    'recent_activity' => $recentActivity
];

echo Response::success($response, 'Dashboard data retrieved');

?>
