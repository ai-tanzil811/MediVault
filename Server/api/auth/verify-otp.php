<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/security.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(Response::error('Method not allowed', 405));
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['otp'])) {
    die(Response::error('OTP required', 400));
}

$providedOtp = Security::sanitizeInput($input['otp']);

// Get stored OTP from cookies (in production, use database)
$storedOtp = $_COOKIE['password_reset_otp'] ?? null;
$userId = $_COOKIE['password_reset_user_id'] ?? null;

if (!$storedOtp || !$userId) {
    die(Response::error('No password reset request found. Please start over.', 400));
}

if ($providedOtp !== $storedOtp) {
    die(Response::error('Invalid OTP. Please try again.', 401));
}

// OTP verified - generate temporary reset token
$resetToken = bin2hex(random_bytes(32));
setcookie('password_reset_token', $resetToken, time() + 1800, '/'); // 30 minutes
setcookie('password_reset_verified', '1', time() + 1800, '/');

$response = [
    'verified' => true,
    'token' => $resetToken,
    'message' => 'OTP verified successfully'
];

echo Response::success($response, 'OTP verified');

?>
