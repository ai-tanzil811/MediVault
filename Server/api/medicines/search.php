<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/security.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    die(Response::error('Method not allowed', 405));
}

if (empty($_GET['q'])) {
    die(Response::error('Search query required', 400));
}

$query = Security::sanitizeInput($_GET['q']);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;

// Limit boundaries
$limit = min($limit, 100);
$limit = max($limit, 1);

// For simplicity, we'll get all matches and then paginate
$allMedicines = $db->searchMedicines($query, 1000, 0);
$total = count($allMedicines);

$medicines = array_slice($allMedicines, $offset, $limit);

// Remove BLOB data from response
foreach ($medicines as &$med) {
    unset($med['med_image']);
    unset($med['med_image_type']);
}

echo Response::paginated($medicines, $page, $limit, $total, 'Search results');

?>
