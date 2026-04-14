<?php
session_start();
require_once __DIR__ . '/../config/api.php';

// Clear all session variables from memory
session_unset();

// Regenerate the session ID before destroying to prevent session fixation
session_regenerate_id(true);

// Destroy the session on the server
session_destroy();

// Expire the session cookie in the browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
exit();
