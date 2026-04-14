<?php
session_start();
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';

// Guard: only accept POST submissions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

// CSRF validation — same hash_equals logic as csrf_validate(), JSON response instead of redirect
$submitted = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submitted)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired security token. Please refresh and try again.']);
    exit();
}
unset($_SESSION['csrf_token']); // rotate the token

$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

$stmt = mysqli_prepare($conn, 'SELECT id, full_name, email, password FROM users WHERE email = ?');
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);

if ($user && password_verify($password, $user['password'])) {
    // Prevent session fixation on successful login
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'    => $user['id'],
        'name'  => $user['full_name'],
        'email' => $user['email'],
    ];

    mysqli_stmt_close($stmt);
    http_response_code(200);
    echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
    exit();
}

// Generic message — never reveal whether the email exists
mysqli_stmt_close($stmt);
http_response_code(401);
echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
exit();
