<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/security.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/logger.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(Response::error('Method not allowed', 405));
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['new_password']) || empty($input['confirm_password'])) {
    die(Response::error('Password fields required', 400));
}

// Check if OTP was verified
$verified = $_COOKIE['password_reset_verified'] ?? null;
$userId = $_COOKIE['password_reset_user_id'] ?? null;

if (!$verified || !$userId) {
    die(Response::error('Password reset request not verified. Please complete OTP verification.', 401));
}

$newPassword = $input['new_password'];
$confirmPassword = $input['confirm_password'];

if ($newPassword !== $confirmPassword) {
    die(Response::error('Passwords do not match', 400));
}

// Validate password strength
$pwdCheck = Security::validatePasswordStrength($newPassword);
if (!$pwdCheck['valid']) {
    die(Response::error($pwdCheck['error'], 400));
}

// Hash new password
$passwordHash = Security::hashPassword($newPassword);

try {
    // Update user password
    $db->executeQuery(
        'UPDATE Users SET password_hash = ? WHERE user_id = ?',
        [$passwordHash, $userId]
    );

    // Log password reset
    Logger::log($db, 'PASSWORD_RESET_COMPLETED', 'INFO', "Password reset completed for user ID: " . $userId);

    // Clear reset cookies
    setcookie('password_reset_otp', '', time() - 3600, '/');
    setcookie('password_reset_email', '', time() - 3600, '/');
    setcookie('password_reset_user_id', '', time() - 3600, '/');
    setcookie('password_reset_token', '', time() - 3600, '/');
    setcookie('password_reset_verified', '', time() - 3600, '/');

    $response = [
        'user_id' => $userId,
        'message' => 'Password reset successfully'
    ];

    echo Response::success($response, 'Password reset completed');

} catch (Exception $e) {
    error_log('Password reset error: ' . $e->getMessage());
    die(Response::error('Failed to reset password', 500));
}

?>
