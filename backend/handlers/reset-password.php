<?php


session_start();
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../config/database.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

// Helper: return a JSON error and stop
function fail(string $message, int $code = 422): void
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

$token    = trim($_POST['token']           ?? '');
$password = $_POST['password']             ?? '';
$repeat   = $_POST['repeat_password']      ?? '';

// Basic presence checks
if (empty($token)) {
    fail('No reset token provided. Please use the link from your email.', 400);
}

// --- Validate the new password (same rules as registration) ---
if (strlen($password) < 8) {
    fail('Password must be at least 8 characters.');
}
if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    fail('Password must contain at least one letter and one number.');
}
if ($password !== $repeat) {
    fail('Passwords do not match. Please try again.');
}

// --- Look up the token in the DB ---
// The SQL query does all three checks in one shot:
//   - token must match
//   - expires_at must be in the future (expires_at > NOW())
//   - used must be 0 (not consumed yet)
$stmt = mysqli_prepare(
    $conn,
    'SELECT email FROM password_resets
      WHERE token = ?
        AND expires_at > NOW()
        AND used = 0
      LIMIT 1'
);
mysqli_stmt_bind_param($stmt, 's', $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row    = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row) {
    // Token is invalid, expired, or already used — one generic message
    fail('This reset link is invalid or has expired. Please request a new one.', 400);
}

$email = $row['email'];

// --- Verify the account still exists ---
// The user may have been deleted after the reset token was issued.
// Without this check, the UPDATE would silently affect 0 rows and return success.
$checkUser = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ?');
mysqli_stmt_bind_param($checkUser, 's', $email);
mysqli_stmt_execute($checkUser);
mysqli_stmt_store_result($checkUser);

if (mysqli_stmt_num_rows($checkUser) === 0) {
    mysqli_stmt_close($checkUser);
    fail('This account no longer exists. Please register a new account.', 400);
}
mysqli_stmt_close($checkUser);

// --- Hash and update the password ---
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$update = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE email = ?');
mysqli_stmt_bind_param($update, 'ss', $hashedPassword, $email);

if (!mysqli_stmt_execute($update)) {
    error_log('Password update failed: ' . mysqli_error($conn));
    fail('A server error occurred. Please try again later.', 500);
}
mysqli_stmt_close($update);

// --- Mark the token as used (one-time-use enforcement) ---
// We mark it used instead of deleting so we can audit past resets if needed
$markUsed = mysqli_prepare($conn, 'UPDATE password_resets SET used = 1 WHERE token = ?');
mysqli_stmt_bind_param($markUsed, 's', $token);
mysqli_stmt_execute($markUsed);
mysqli_stmt_close($markUsed);

http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Your password has been reset. You can now log in.']);
exit();
