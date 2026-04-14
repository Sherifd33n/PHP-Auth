<?php
/**
 * deactivate.php
 *
 * Deletes the currently logged-in user's account permanently.
 * Also cleans up their password reset tokens and destroys the session.
 *
 * WHY DELETE password_resets first?
 *   Clean up — once the user is gone, their tokens are useless.
 *   This prevents orphaned rows in the DB.
 *
 * This endpoint requires an active session — unauthenticated users
 * cannot delete other people's accounts.
 */

session_start();
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../config/database.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

// Must be logged in — the session tells us WHICH user to delete.
// This prevents unauthenticated users from calling this endpoint.
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

$userId    = (int) $_SESSION['user']['id'];
$userEmail = $_SESSION['user']['email'];

// --- Step 1: Delete the user's password reset tokens ---
$delTokens = mysqli_prepare($conn, 'DELETE FROM password_resets WHERE email = ?');
mysqli_stmt_bind_param($delTokens, 's', $userEmail);
mysqli_stmt_execute($delTokens);
mysqli_stmt_close($delTokens);

// --- Step 2: Delete the user account itself ---
$stmt = mysqli_prepare($conn, 'DELETE FROM users WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $userId);

if (!mysqli_stmt_execute($stmt)) {
    error_log('Account delete failed: ' . mysqli_error($conn));
    mysqli_stmt_close($stmt);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to deactivate account. Please try again.']);
    exit();
}
mysqli_stmt_close($stmt);

// --- Step 3: Destroy the session ---
// The user is gone, so their session must also be invalidated immediately.
session_unset();
session_regenerate_id(true);
session_destroy();

// Expire the session cookie in the browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Your account has been deactivated.']);
exit();
