<?php
/**
 * Authentication Utilities
 */

class Auth {

    public static function requireAuth($db) {
        $token = Security::getTokenFromHeader();

        if (!$token) {
            http_response_code(401);
            die(json_encode(['success' => false, 'error' => 'Unauthorized: No token provided']));
        }

        $decoded = Security::verifyToken($token);
        if (!$decoded) {
            http_response_code(401);
            die(json_encode(['success' => false, 'error' => 'Unauthorized: Invalid token']));
        }

        return $decoded;
    }

    public static function requireAdminAuth($db) {
        $user = self::requireAuth($db);

        if ($user['role'] !== 'admin') {
            http_response_code(403);
            die(json_encode(['success' => false, 'error' => 'Forbidden: Admin access required']));
        }

        return $user;
    }

    public static function isAdmin($decoded) {
        return isset($decoded['role']) && $decoded['role'] === 'admin';
    }

    public static function getCurrentUser($db, $decoded) {
        if ($decoded['role'] === 'admin') {
            return $db->getAdmin($decoded['id']);
        } else {
            return $db->getUser($decoded['id']);
        }
    }

    public static function createUserToken($userId) {
        return Security::generateToken([
            'id' => $userId,
            'role' => 'user'
        ]);
    }

    public static function createAdminToken($adminId) {
        return Security::generateToken([
            'id' => $adminId,
            'role' => 'admin'
        ]);
    }
}

?>
