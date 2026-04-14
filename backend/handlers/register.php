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

// CSRF validation
$submitted = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submitted)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired security token. Please refresh and try again.']);
    exit();
}
unset($_SESSION['csrf_token']);

$fullName = trim($_POST['fullName'] ?? '');
$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']         ?? '';
$repeat   = $_POST['repeat_password']  ?? '';

// Helper: return a JSON 422 error and stop — same validation logic as before
function fail(string $message): void
{
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

// --- Input validation (identical rules) ---

if ($fullName === '' || strlen($fullName) < 2) {
    fail('Full name must be at least 2 characters.');
}
if (strlen($fullName) > 100) {
    fail('Full name must not exceed 100 characters.');
}
if (!preg_match("/^[\p{L}\s'\-]+$/u", $fullName)) {
    fail('Full name contains invalid characters.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Please enter a valid email address.');
}
if (strlen($email) > 254) {
    fail('Email address is too long.');
}

if (strlen($password) < 8) {
    fail('Password must be at least 8 characters.');
}
if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    fail('Password must contain at least one letter and one number.');
}

if ($password !== $repeat) {
    fail('Passwords do not match. Please try again.');
}

// Duplicate email check
$checkStmt = mysqli_prepare($conn, 'SELECT 1 FROM users WHERE email = ?');
mysqli_stmt_bind_param($checkStmt, 's', $email);
mysqli_stmt_execute($checkStmt);
mysqli_stmt_store_result($checkStmt);

if (mysqli_stmt_num_rows($checkStmt) > 0) {
    fail('An account with that email address already exists.');
}
mysqli_stmt_close($checkStmt);

// All good — insert the new user
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = mysqli_prepare($conn, 'INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'sss', $fullName, $email, $hashedPassword);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    fail('Registration failed due to a server error. Please try again.');
}

mysqli_stmt_close($stmt);

http_response_code(201);
echo json_encode(['success' => true, 'message' => 'Account created! You can now log in.']);
exit();
