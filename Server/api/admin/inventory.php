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

// Get all medicines
$allMeds = $db->getAllMedicines(10000, 0);

$lowStock = [];
$expiringMeds = [];
$alerts = [];

$today = date('Y-m-d');
$thirtyDaysFromNow = date('Y-m-d', strtotime('+30 days'));

foreach ($allMeds as $med) {
    // Check low stock (< 10)
    if ($med['stock_level'] < 10) {
        $alerts[] = [
            'type' => 'LOW_STOCK',
            'med_id' => $med['med_id'],
            'name' => $med['name'],
            'stock' => $med['stock_level']
        ];
    }

    // Check expiry (< 30 days)
    if ($med['exp_date'] <= $thirtyDaysFromNow && $med['exp_date'] > $today) {
        $alerts[] = [
            'type' => 'EXPIRING_SOON',
            'med_id' => $med['med_id'],
            'name' => $med['name'],
            'expiry_date' => $med['exp_date']
        ];
    }

    // Check expired
    if ($med['exp_date'] < $today) {
        $alerts[] = [
            'type' => 'EXPIRED',
            'med_id' => $med['med_id'],
            'name' => $med['name'],
            'expiry_date' => $med['exp_date']
        ];
    }
}

$response = [
    'total_medicines' => count($allMeds),
    'alert_count' => count($alerts),
    'alerts' => $alerts
];

echo Response::success($response, 'Inventory alerts retrieved');

?>
