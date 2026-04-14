<?php
/**
 * forgot-password.php
 *
 * Accepts a POST with the user's email address.
 * If the email belongs to a registered account:
 *   1. Generates a secure one-time-use reset token
 *   2. Saves it in the password_resets table with a 30-min expiry
 *   3. Emails the user a link containing the token
 *
 * SECURITY: We ALWAYS return a success response — even if the email
 * doesn't exist. This prevents "user enumeration" (an attacker
 * discovering which emails are registered by watching the response).
 */

session_start();
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/mailer.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

// CSRF check
$submitted = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submitted)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit();
}
unset($_SESSION['csrf_token']); // rotate after use

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit();
}

// The "safe" response we always return (prevents user enumeration)
$safeResponse = json_encode([
    'success' => true,
    'message' => 'If the email is registered, you will receive a reset link shortly.'
]);

// --- Check if the email exists in our users table ---
$stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ?');
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) === 0) {
    // Email not found — still return success to prevent enumeration
    mysqli_stmt_close($stmt);
    http_response_code(200);
    echo $safeResponse;
    exit();
}
mysqli_stmt_close($stmt);

// --- Generate a cryptographically secure token ---
// bin2hex(random_bytes(32)) gives us 64 hex characters
// random_bytes() uses the OS's secure random source (CSPRNG)
$token     = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes')); // expires in 30 min

// --- Delete any previous unused tokens for this email ---
// (prevents token pile-up if the user clicks "forgot password" multiple times)
$del = mysqli_prepare($conn, 'DELETE FROM password_resets WHERE email = ?');
mysqli_stmt_bind_param($del, 's', $email);
mysqli_stmt_execute($del);
mysqli_stmt_close($del);

// --- Insert the new token ---
$insert = mysqli_prepare($conn, 'INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)');
mysqli_stmt_bind_param($insert, 'sss', $email, $token, $expiresAt);

if (!mysqli_stmt_execute($insert)) {
    error_log('password_resets insert failed: ' . mysqli_error($conn));
    http_response_code(200);
    echo $safeResponse; // still return success to the user
    exit();
}
mysqli_stmt_close($insert);

// --- Build the reset URL ---
// APP_URL comes from .env so it works in both development and production
$appUrl    = rtrim(getenv('APP_URL'), '/');
$resetLink = "{$appUrl}/reset-password?token={$token}";

// --- Send the email ---
try {
    $mail = createMailer();
    $mail->addAddress($email); // recipient

    $mail->Subject = 'Reset Your Password — Main Auth';

    // Plain-text fallback (important for spam filters)
    $mail->AltBody = "You requested a password reset.\n\nClick this link to reset your password (valid for 30 minutes):\n{$resetLink}\n\nIf you did not request this, please ignore this email.";

    // HTML email body
    $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto;'>
            <h2 style='color: #7c3aed;'>Reset Your Password</h2>
            <p>We received a request to reset the password for your account.</p>
            <p>Click the button below to choose a new password. This link expires in <strong>30 minutes</strong>.</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='{$resetLink}'
                   style='background: #7c3aed; color: white; padding: 14px 28px;
                          text-decoration: none; border-radius: 8px; font-weight: bold;'>
                    Reset Password
                </a>
            </p>
            <p style='color: #888; font-size: 13px;'>
                If the button doesn't work, copy and paste this link into your browser:<br>
                <a href='{$resetLink}'>{$resetLink}</a>
            </p>
            <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
            <p style='color: #aaa; font-size: 12px;'>
                If you did not request a password reset, you can safely ignore this email.
                Your password will not change.
            </p>
        </div>
    ";

    $mail->send();
} catch (\Exception $e) {
    // Log the real error privately — never expose SMTP details to the user
    error_log('Password reset email failed: ' . $e->getMessage());
}

// Always return success
http_response_code(200);
echo $safeResponse;
exit();
