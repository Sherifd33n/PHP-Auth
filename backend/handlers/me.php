<?php
// Returns the current logged-in user from the session, or 401.
session_start();
require_once __DIR__ . '/../config/api.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

http_response_code(200);
echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
exit();
