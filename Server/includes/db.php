<?php
/**
 * Database Connection Class
 * Handles all database operations using PDO with prepared statements
 */

class Database {
    private $pdo;
    private $stmt;

    public function __construct() {
        try {
            $this->pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }

    public function executeQuery($sql, $params = []) {
        try {
            $this->stmt = $this->pdo->prepare($sql);
            $this->stmt->execute($params);
            return $this->stmt;
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            throw new Exception('Database query failed');
        }
    }

    public function fetchOne($sql, $params = []) {
        $this->executeQuery($sql, $params);
        return $this->stmt->fetch();
    }

    public function fetchAll($sql, $params = []) {
        $this->executeQuery($sql, $params);
        return $this->stmt->fetchAll();
    }

    public function rowCount() {
        return $this->stmt->rowCount();
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    // User Methods
    public function getUser($userId) {
        return $this->fetchOne('SELECT * FROM Users WHERE user_id = ?', [$userId]);
    }

    public function getUserByEmail($email) {
        return $this->fetchOne('SELECT * FROM Users WHERE email = ?', [$email]);
    }

    public function getUserByNID($nid) {
        return $this->fetchOne('SELECT * FROM Users WHERE nid = ?', [$nid]);
    }

    public function createUser($email, $passwordHash, $name, $nid, $age, $photoBlob = null, $photoType = null) {
        return $this->executeQuery(
            'INSERT INTO Users (email, password_hash, name, nid, age, photo_image, photo_image_type)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$email, $passwordHash, $name, $nid, $age, $photoBlob, $photoType]
        );
    }

    // Admin Methods
    public function getAdmin($adminId) {
        return $this->fetchOne('SELECT admin_id, name, email FROM Admins WHERE admin_id = ?', [$adminId]);
    }

    public function getAdminByEmail($email) {
        return $this->fetchOne('SELECT * FROM Admins WHERE email = ?', [$email]);
    }

    public function createAdmin($email, $passwordHash, $name) {
        return $this->executeQuery(
            'INSERT INTO Admins (email, password_hash, name) VALUES (?, ?, ?)',
            [$email, $passwordHash, $name]
        );
    }

    // Medicine Methods
    public function getMedicine($medId) {
        return $this->fetchOne('SELECT * FROM Medicines WHERE med_id = ?', [$medId]);
    }

    public function getAllMedicines($limit = 20, $offset = 0) {
        return $this->fetchAll(
            'SELECT * FROM Medicines ORDER BY name ASC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    public function getMedicinesCount() {
        $result = $this->fetchOne('SELECT COUNT(*) as total FROM Medicines');
        return $result['total'];
    }

    public function searchMedicines($query, $limit = 20, $offset = 0) {
        $searchTerm = '%' . $query . '%';
        return $this->fetchAll(
            'SELECT * FROM Medicines
             WHERE name LIKE ? OR brand LIKE ? OR category LIKE ? OR description LIKE ?
             ORDER BY name ASC LIMIT ? OFFSET ?',
            [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit, $offset]
        );
    }

    public function createMedicine($name, $brand, $category, $description, $dosage, $storage,
                                  $price, $stock, $expDate, $prescReq, $medImage = null, $medImageType = null) {
        return $this->executeQuery(
            'INSERT INTO Medicines (name, brand, category, description, dosaage_instructions,
             storage_instructions, price, stock_level, exp_date, prescription_required, med_image, med_image_type)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$name, $brand, $category, $description, $dosage, $storage, $price, $stock, $expDate, $prescReq, $medImage, $medImageType]
        );
    }

    public function updateMedicine($medId, $data) {
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $medId;
        $sql = 'UPDATE Medicines SET ' . implode(', ', $fields) . ' WHERE med_id = ?';
        return $this->executeQuery($sql, $values);
    }

    public function deleteMedicine($medId) {
        return $this->executeQuery('DELETE FROM Medicines WHERE med_id = ?', [$medId]);
    }

    // Drug Conflict Methods
    public function checkDrugConflict($drug1Id, $drug2Id) {
        return $this->fetchOne(
            'SELECT * FROM Drug_Conflicts
             WHERE (drug1_id = ? AND drug2_id = ?) OR (drug1_id = ? AND drug2_id = ?)',
            [$drug1Id, $drug2Id, $drug2Id, $drug1Id]
        );
    }

    public function getAllConflicts() {
        return $this->fetchAll('SELECT * FROM Drug_Conflicts');
    }

    public function addDrugConflict($drug1Id, $drug2Id, $description, $classification) {
        return $this->executeQuery(
            'INSERT INTO Drug_Conflicts (drug1_id, drug2_id, description, classification)
             VALUES (?, ?, ?, ?)',
            [$drug1Id, $drug2Id, $description, $classification]
        );
    }

    public function deleteDrugConflict($conflictId) {
        return $this->executeQuery('DELETE FROM Drug_Conflicts WHERE conflict_id = ?', [$conflictId]);
    }

    // Order Methods
    public function createOrder($userId, $statusId = 1) {
        $this->executeQuery(
            'INSERT INTO Orders (user_id, status_id) VALUES (?, ?)',
            [$userId, $statusId]
        );
        return $this->lastInsertId();
    }

    public function getOrder($orderId) {
        return $this->fetchOne('SELECT * FROM Orders WHERE order_id = ?', [$orderId]);
    }

    public function getUserOrders($userId, $statusId = null, $limit = 20, $offset = 0) {
        if ($statusId === null) {
            return $this->fetchAll(
                'SELECT * FROM Orders WHERE user_id = ? ORDER BY order_date DESC LIMIT ? OFFSET ?',
                [$userId, $limit, $offset]
            );
        }
        return $this->fetchAll(
            'SELECT * FROM Orders WHERE user_id = ? AND status_id = ? ORDER BY order_date DESC LIMIT ? OFFSET ?',
            [$userId, $statusId, $limit, $offset]
        );
    }

    public function updateOrderStatus($orderId, $statusId) {
        return $this->executeQuery(
            'UPDATE Orders SET status_id = ? WHERE order_id = ?',
            [$statusId, $orderId]
        );
    }

    public function getPendingOrder($userId) {
        return $this->fetchOne('SELECT * FROM Orders WHERE user_id = ? AND status_id = 1 LIMIT 1', [$userId]);
    }

    // Order Items Methods
    public function addOrderItem($orderId, $medId, $quantity) {
        return $this->executeQuery(
            'INSERT INTO Order_Items (order_id, med_id, quantity) VALUES (?, ?, ?)',
            [$orderId, $medId, $quantity]
        );
    }

    public function getOrderItems($orderId) {
        return $this->fetchAll(
            'SELECT oi.*, m.name, m.brand, m.price FROM Order_Items oi
             JOIN Medicines m ON oi.med_id = m.med_id
             WHERE oi.order_id = ?',
            [$orderId]
        );
    }

    public function removeOrderItem($orderItemId) {
        return $this->executeQuery('DELETE FROM Order_Items WHERE order_item_id = ?', [$orderItemId]);
    }

    public function clearOrderItems($orderId) {
        return $this->executeQuery('DELETE FROM Order_Items WHERE order_id = ?', [$orderId]);
    }

    public function getOrderItemByMedicine($orderId, $medId) {
        return $this->fetchOne(
            'SELECT * FROM Order_Items WHERE order_id = ? AND med_id = ?',
            [$orderId, $medId]
        );
    }

    // Prescription Review Methods
    public function createPrescriptionReview($orderId, $decision = 'Pending') {
        return $this->executeQuery(
            'INSERT INTO Prescription_Reviews (order_id, decision) VALUES (?, ?)',
            [$orderId, $decision]
        );
    }

    public function getPrescriptionReview($orderId) {
        return $this->fetchOne(
            'SELECT * FROM Prescription_Reviews WHERE order_id = ?',
            [$orderId]
        );
    }

    public function getPendingReviews($limit = 20, $offset = 0) {
        return $this->fetchAll(
            'SELECT pr.*, u.name, o.order_date
             FROM Prescription_Reviews pr
             JOIN Orders o ON pr.order_id = o.order_id
             JOIN Users u ON o.user_id = u.user_id
             WHERE pr.decision = "Pending"
             ORDER BY pr.created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    public function updatePrescriptionReview($reviewId, $decision, $adminId, $notes) {
        return $this->executeQuery(
            'UPDATE Prescription_Reviews SET decision = ?, admin_id = ?, notes = ?, reviewed_at = NOW()
             WHERE review_id = ?',
            [$decision, $adminId, $notes, $reviewId]
        );
    }

    // Activity Logs Methods
    public function createActivityLog($logType, $severity, $message, $orderId = null, $reviewId = null) {
        return $this->executeQuery(
            'INSERT INTO Activity_Logs (log_type, severity, message, order_id, review_id)
             VALUES (?, ?, ?, ?, ?)',
            [$logType, $severity, $message, $orderId, $reviewId]
        );
    }

    public function getActivityLogs($limit = 50, $offset = 0, $filters = []) {
        $sql = 'SELECT * FROM Activity_Logs WHERE 1=1';
        $params = [];

        if (!empty($filters['logType'])) {
            $sql .= ' AND log_type = ?';
            $params[] = $filters['logType'];
        }

        if (!empty($filters['severity'])) {
            $sql .= ' AND severity = ?';
            $params[] = $filters['severity'];
        }

        $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        return $this->fetchAll($sql, $params);
    }

    // Purchase History (Last N days)
    public function getUserPurchaseHistory($userId, $days = 7) {
        return $this->fetchAll(
            'SELECT DISTINCT m.med_id, m.name, m.brand, o.order_date
             FROM Order_Items oi
             JOIN Orders o ON oi.order_id = o.order_id
             JOIN Medicines m ON oi.med_id = m.med_id
             WHERE o.user_id = ? AND o.status_id IN (2, 4) AND o.order_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
             ORDER BY o.order_date DESC',
            [$userId, $days]
        );
    }
}

?>
