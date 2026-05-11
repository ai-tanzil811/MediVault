<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/security.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(Response::error('Method not allowed', 405));
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['medicine_ids']) || !is_array($input['medicine_ids'])) {
    die(Response::error('medicine_ids array required', 400));
}

$medicineIds = array_map('intval', $input['medicine_ids']);
$conflicts = [];

// Get all known drug conflicts
$allConflicts = $db->getAllConflicts();

// Check for conflicts between medicines in the current request
for ($i = 0; $i < count($medicineIds); $i++) {
    for ($j = $i + 1; $j < count($medicineIds); $j++) {
        $drug1 = $medicineIds[$i];
        $drug2 = $medicineIds[$j];

        // Check if conflict exists
        foreach ($allConflicts as $conflict) {
            if (($conflict['drug1_id'] == $drug1 && $conflict['drug2_id'] == $drug2) ||
                ($conflict['drug1_id'] == $drug2 && $conflict['drug2_id'] == $drug1)) {
                $med1 = $db->getMedicine($drug1);
                $med2 = $db->getMedicine($drug2);

                $conflicts[] = [
                    'drug1_id' => $drug1,
                    'drug1_name' => $med1['name'],
                    'drug2_id' => $drug2,
                    'drug2_name' => $med2['name'],
                    'description' => $conflict['description'],
                    'severity' => $conflict['classification'] ?? 'moderate'
                ];
            }
        }
    }
}

// If user is authenticated, check purchase history (last 7 days)
if (!empty($input['user_id'])) {
    $userId = (int)$input['user_id'];
    $purchaseHistory = $db->getUserPurchaseHistory($userId, 7);

    // Check if any current medicines conflict with recent purchases
    foreach ($medicineIds as $medId) {
        foreach ($purchaseHistory as $purchase) {
            if ($purchase['med_id'] == $medId) {
                continue; // Skip same medicine
            }

            // Check conflict
            foreach ($allConflicts as $conflict) {
                if (($conflict['drug1_id'] == $medId && $conflict['drug2_id'] == $purchase['med_id']) ||
                    ($conflict['drug1_id'] == $purchase['med_id'] && $conflict['drug2_id'] == $medId)) {

                    $currentMed = $db->getMedicine($medId);
                    $previousMed = $db->getMedicine($purchase['med_id']);

                    $conflicts[] = [
                        'drug1_id' => $medId,
                        'drug1_name' => $currentMed['name'],
                        'drug2_id' => $purchase['med_id'],
                        'drug2_name' => $previousMed['name'],
                        'description' => $conflict['description'],
                        'severity' => $conflict['classification'] ?? 'moderate',
                        'note' => 'Conflict with purchase from ' . $purchase['order_date']
                    ];
                }
            }
        }
    }
}

echo Response::conflict($conflicts);

?>
