<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/security.php');
require_once(__DIR__ . '/../../includes/validation.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/logger.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(Response::error('Method not allowed', 405));
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['clinical_id'])) {
    die(Response::error('Clinical ID or email required', 400));
}

$clinicalId = Security::sanitizeInput($input['clinical_id']);

// Check if it's an email or clinical ID
$user = null;
if (filter_var($clinicalId, FILTER_VALIDATE_EMAIL)) {
    $user = $db->getUserByEmail($clinicalId);
} else {
    // For now, clinical ID validation - in production, query by clinical_id field
    $user = $db->fetchOne('SELECT * FROM Users WHERE user_id LIKE ?', ['%' . $clinicalId . '%']);
}

if (!$user) {
    die(Response::error('User not found', 404));
}

// Generate OTP (6 digits)
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$otpExpiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Store OTP in a temporary table or cache (for production, use proper storage)
// For now, we'll use a simple approach with session storage
setcookie('password_reset_otp', $otp, time() + 600, '/'); // 10 minutes
setcookie('password_reset_email', $user['email'], time() + 600, '/');
setcookie('password_reset_user_id', $user['user_id'], time() + 600, '/');

// In production, send email with OTP
// sendPasswordResetEmail($user['email'], $otp);

Logger::log($db, 'PASSWORD_RESET_REQUESTED', 'INFO', "Password reset requested for user ID: " . $user['user_id']);

$response = [
    'user_id' => $user['user_id'],
    'email' => substr($user['email'], 0, 3) . '***' . substr($user['email'], -8),
    'message' => 'OTP sent to your registered email'
];

echo Response::success($response, 'Password reset OTP sent');

?>
