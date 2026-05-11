<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/db.php');
require_once(__DIR__ . '/../../includes/security.php');
require_once(__DIR__ . '/../../includes/validation.php');
require_once(__DIR__ . '/../../includes/response.php');
require_once(__DIR__ . '/../../includes/auth.php');
require_once(__DIR__ . '/../../includes/logger.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(Response::error('Method not allowed', 405));
}

$input = json_decode(file_get_contents('php://input'), true);

// Validate input
$errors = Validation::validateLoginInput($input);
if (!empty($errors)) {
    die(Response::error('Validation failed', 400, $errors));
}

$email = Security::sanitizeEmail($input['email']);
$password = $input['password'];

// Check if user exists
$user = $db->getUserByEmail($email);
if (!$user) {
    die(Response::error('Invalid email or password', 401));
}

// Verify password
if (!Security::verifyPassword($password, $user['password_hash'])) {
    die(Response::error('Invalid email or password', 401));
}

// Check if email is admin (check Admins table too)
$admin = $db->getAdminByEmail($email);

if ($admin) {
    // Admin login
    if (!Security::verifyPassword($password, $admin['password_hash'])) {
        die(Response::error('Invalid email or password', 401));
    }

    Logger::logUserLogin($db, $admin['admin_id']);
    $token = Auth::createAdminToken($admin['admin_id']);

    $response = [
        'id' => $admin['admin_id'],
        'name' => $admin['name'],
        'email' => $admin['email'],
        'role' => 'admin',
        'token' => $token
    ];
} else {
    // Regular user login
    Logger::logUserLogin($db, $user['user_id']);
    $token = Auth::createUserToken($user['user_id']);

    $response = [
        'id' => $user['user_id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'nid' => $user['nid'],
        'age' => $user['age'],
        'role' => 'user',
        'token' => $token
    ];
}

echo Response::success($response, 'Login successful');

?>
