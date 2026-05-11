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

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;

$reviews = $db->getPendingReviews($limit, $offset);

// Count total pending reviews
$totalResult = $db->fetchOne('SELECT COUNT(*) as total FROM Prescription_Reviews WHERE decision = "Pending"');
$total = $totalResult['total'];

echo Response::paginated($reviews, $page, $limit, $total, 'Pending reviews retrieved');

?>
