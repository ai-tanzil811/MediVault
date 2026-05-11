<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/security.php');
require_once(__DIR__ . '/../../includes/logger.php');
require_once(__DIR__ . '/../../includes/db.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(Response::error('Method not allowed', 405));
}

// Check if there's an active password reset session
$email = $_COOKIE['password_reset_email'] ?? null;
$userId = $_COOKIE['password_reset_user_id'] ?? null;

if (!$email || !$userId) {
    die(Response::error('No active password reset session. Please start over.', 400));
}

// Generate new OTP
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$otpExpiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Update OTP cookie
setcookie('password_reset_otp', $otp, time() + 600, '/'); // 10 minutes

// Log resend request
Logger::log($db, 'OTP_RESENT', 'INFO', "Password reset OTP resent for user ID: " . $userId);

// In production, send email with new OTP
// sendPasswordResetEmail($email, $otp);

$response = [
    'email' => substr($email, 0, 3) . '***' . substr($email, -8),
    'message' => 'New OTP sent to your email'
];

echo Response::success($response, 'OTP resent successfully');

?>
