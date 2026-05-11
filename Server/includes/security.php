<?php
/**
 * Security Utilities
 * Handles password hashing, input sanitization, and token generation
 */

class Security {

    // Password Management
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    public static function validatePasswordStrength($password) {
        if (strlen($password) < 8) {
            return ['valid' => false, 'error' => 'Password must be at least 8 characters'];
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return ['valid' => false, 'error' => 'Password must contain at least one uppercase letter'];
        }
        if (!preg_match('/[0-9]/', $password)) {
            return ['valid' => false, 'error' => 'Password must contain at least one number'];
        }
        return ['valid' => true];
    }

    // Input Sanitization
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeEmail($email) {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    // NID Validation (Basic format check)
    public static function validateNID($nid) {
        // Assumes NID is numeric and between 10-20 characters
        if (strlen($nid) < 10 || strlen($nid) > 20) {
            return false;
        }
        return preg_match('/^[0-9]+$/', $nid) !== false;
    }

    // Age Validation
    public static function validateAge($age) {
        return is_numeric($age) && $age >= 18 && $age <= 120;
    }

    // Token Generation (Simple JWT-like token)
    public static function generateToken($data) {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode(array_merge($data, ['iat' => time()])));
        $signature = hash_hmac('sha256', "$header.$payload", JWT_SECRET, true);
        $signature = base64_encode($signature);
        return "$header.$payload.$signature";
    }

    // Token Verification
    public static function verifyToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        list($header, $payload, $signature) = $parts;
        $expectedSignature = base64_encode(
            hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)
        );

        if ($signature !== $expectedSignature) {
            return null;
        }

        $decoded = json_decode(base64_decode($payload), true);

        // Check token expiration (24 hours)
        if (isset($decoded['iat']) && (time() - $decoded['iat']) > 86400) {
            return null;
        }

        return $decoded;
    }

    // Get Token from Headers
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $parts = explode(' ', $headers['Authorization']);
            if (count($parts) === 2 && $parts[0] === 'Bearer') {
                return $parts[1];
            }
        }
        return null;
    }

    // CSRF Token Generation
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Verify CSRF Token
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // File Upload Validation
    public static function validateFileUpload($file) {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'No file uploaded'];
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            return ['valid' => false, 'error' => 'File size exceeds maximum of 5MB'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
            return ['valid' => false, 'error' => 'Invalid file type. Only JPEG and PNG allowed'];
        }

        return ['valid' => true, 'mimeType' => $mimeType];
    }

    // Read File as Binary
    public static function readFileAsBlob($filePath) {
        return file_get_contents($filePath);
    }

    // Get File Extension
    public static function getFileExtension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
}

?>
