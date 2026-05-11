<?php
/**
 * Activity Logger
 */

class Logger {

    public static function log($db, $logType, $severity, $message, $orderId = null, $reviewId = null) {
        try {
            $db->createActivityLog($logType, $severity, $message, $orderId, $reviewId);
            return true;
        } catch (Exception $e) {
            error_log('Logging failed: ' . $e->getMessage());
            return false;
        }
    }

    public static function logUserRegistration($db, $userId, $userName) {
        self::log($db, 'USER_REGISTRATION', 'INFO', "New user registered: $userName (ID: $userId)");
    }

    public static function logUserLogin($db, $userId) {
        self::log($db, 'USER_LOGIN', 'INFO', "User logged in (ID: $userId)");
    }

    public static function logItemAddedToCart($db, $userId, $medName, $orderId) {
        self::log($db, 'ITEM_ADDED', 'INFO', "$medName added to cart by user $userId", $orderId);
    }

    public static function logItemRemovedFromCart($db, $orderId, $medName) {
        self::log($db, 'ITEM_REMOVED', 'INFO', "$medName removed from cart", $orderId);
    }

    public static function logOrderPlaced($db, $orderId, $userId) {
        self::log($db, 'ORDER_PLACED', 'INFO', "Order placed by user $userId", $orderId);
    }

    public static function logPrescriptionUploaded($db, $orderId) {
        self::log($db, 'PRESCRIPTION_UPLOADED', 'INFO', "Prescription uploaded for order $orderId", $orderId);
    }

    public static function logConflictWarning($db, $orderId, $drug1, $drug2) {
        self::log($db, 'CONFLICT_WARNING', 'WARNING', "Drug conflict detected: $drug1 conflicts with $drug2", $orderId);
    }

    public static function logPrescriptionReview($db, $reviewId, $orderId, $decision, $adminId) {
        self::log($db, 'PRESCRIPTION_REVIEW', 'INFO', "Prescription reviewed: $decision by admin $adminId", $orderId, $reviewId);
    }

    public static function logPrescriptionRejected($db, $reviewId, $orderId, $reason) {
        self::log($db, 'PRESCRIPTION_REJECTED', 'HIGH', "Prescription rejected: $reason", $orderId, $reviewId);
    }

    public static function logMedicineAdded($db, $medName) {
        self::log($db, 'MEDICINE_ADDED', 'INFO', "Medicine added: $medName");
    }

    public static function logMedicineUpdated($db, $medName) {
        self::log($db, 'MEDICINE_UPDATED', 'INFO', "Medicine updated: $medName");
    }

    public static function logMedicineDeleted($db, $medName) {
        self::log($db, 'MEDICINE_DELETED', 'WARNING', "Medicine deleted: $medName");
    }

    public static function logLowStockAlert($db, $medName, $stock) {
        self::log($db, 'LOW_STOCK_ALERT', 'WARNING', "Low stock alert: $medName has only $stock units");
    }

    public static function logExpiryAlert($db, $medName, $expDate) {
        self::log($db, 'EXPIRY_ALERT', 'WARNING', "Expiry alert: $medName expires on $expDate");
    }
}

?>
