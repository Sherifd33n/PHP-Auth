<?php
/**
 * mailer.php — PHPMailer factory
 *
 * Loads SMTP credentials from .env and returns a ready-to-use PHPMailer
 * instance. Every handler that needs to send an email calls createMailer().
 *
 * WHY a factory function?
 *   Keeps all mail configuration in one place. If you ever switch from
 *   Mailtrap to Gmail or SendGrid, you only change this file.
 */

// Load the three PHPMailer files we downloaded manually (no Composer needed)
require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Creates and returns a configured PHPMailer instance.
 *
 * @return PHPMailer
 * @throws Exception if SMTP configuration fails
 */
function createMailer(): PHPMailer
{
    $mail = new PHPMailer(true); // true = throw exceptions on error

    // --- Server settings ---
    $mail->isSMTP();                                    // Use SMTP protocol
    $mail->Host       = getenv('MAIL_HOST');            // e.g. sandbox.smtp.mailtrap.io
    $mail->SMTPAuth   = true;                           // Require username + password
    $mail->Username   = getenv('MAIL_USERNAME');
    $mail->Password   = getenv('MAIL_PASSWORD');
    $mail->SMTPSecure = false;                          // Disable TLS for local sandbox testing
    $mail->SMTPAutoTLS = false;                         // Stop PHPMailer from forcing TLS
    $mail->Port       = (int) getenv('MAIL_PORT');      // 2525

    // --- Sender identity ---
    $mail->setFrom(
        getenv('MAIL_FROM'),
        getenv('MAIL_FROM_NAME')
    );

    // --- Format ---
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';

    return $mail;
}
