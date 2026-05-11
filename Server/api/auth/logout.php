<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../includes/response.php');

// Logout is simple - just clear the client-side token
// Server-side sessions are not being used, tokens are verified on each request

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(Response::error('Method not allowed', 405));
}

echo Response::success(null, 'Logged out successfully');

?>
