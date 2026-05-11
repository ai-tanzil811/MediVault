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
$errors = Validation::validateRegisterInput($input);
if (!empty($errors)) {
    die(Response::error('Validation failed', 400, $errors));
}

// Sanitize inputs
$email = Security::sanitizeEmail($input['email']);
$name = Security::sanitizeInput($input['name']);
$nid = Security::sanitizeInput($input['nid']);
$age = (int)$input['age'];

// Check if email already exists
$existingUser = $db->getUserByEmail($email);
if ($existingUser) {
    die(Response::error('Email already registered', 400));
}

// Check if NID already exists
$existingNID = $db->getUserByNID($nid);
if ($existingNID) {
    die(Response::error('NID already registered', 400));
}

// Hash password
$passwordHash = Security::hashPassword($input['password']);

// Handle optional photo upload
$photoBlob = null;
$photoType = null;

if (isset($_FILES['photo'])) {
    $fileValidation = Security::validateFileUpload($_FILES['photo']);
    if ($fileValidation['valid']) {
        $photoBlob = Security::readFileAsBlob($_FILES['photo']['tmp_name']);
        $photoType = $fileValidation['mimeType'];
    }
}

// Create user
try {
    $db->createUser($email, $passwordHash, $name, $nid, $age, $photoBlob, $photoType);
    $userId = $db->lastInsertId();

    // Log registration
    Logger::logUserRegistration($db, $userId, $name);

    // Generate token
    $token = Auth::createUserToken($userId);

    $response = [
        'user_id' => $userId,
        'name' => $name,
        'email' => $email,
        'token' => $token
    ];

    echo Response::success($response, 'User registered successfully', 201);
} catch (Exception $e) {
    error_log('Registration error: ' . $e->getMessage());
    die(Response::error('Registration failed', 500));
}

?>
