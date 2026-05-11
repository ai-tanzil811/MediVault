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
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = ($page - 1) * $limit;

// Build filters
$filters = [];
if (!empty($_GET['log_type'])) {
    $filters['logType'] = $_GET['log_type'];
}
if (!empty($_GET['severity'])) {
    $filters['severity'] = $_GET['severity'];
}

$logs = $db->getActivityLogs($limit, $offset, $filters);

// Count total logs with same filters
$sql = 'SELECT COUNT(*) as total FROM Activity_Logs WHERE 1=1';
$params = [];

if (!empty($filters['logType'])) {
    $sql .= ' AND log_type = ?';
    $params[] = $filters['logType'];
}

if (!empty($filters['severity'])) {
    $sql .= ' AND severity = ?';
    $params[] = $filters['severity'];
}

$totalResult = $db->fetchOne($sql, $params);
$total = $totalResult['total'];

echo Response::paginated($logs, $page, $limit, $total, 'Activity logs retrieved');

?>
