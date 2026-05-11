<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/security.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    die(Response::error('Method not allowed', 405));
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;

// Limit boundaries
$limit = min($limit, 100);
$limit = max($limit, 1);

$medicines = $db->getAllMedicines($limit, $offset);
$total = $db->getMedicinesCount();

// Remove BLOB data from response (images would be too large)
foreach ($medicines as &$med) {
    unset($med['med_image']);
    unset($med['med_image_type']);
}

echo Response::paginated($medicines, $page, $limit, $total, 'Medicines retrieved successfully');

?>
