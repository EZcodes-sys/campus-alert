<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Sends an alert notification email via PHPMailer/SMTP.
 *
 * If MAIL_ENABLED is false, or the SMTP send fails for any reason
 * (no internet, wrong credentials, etc.), the email is written to
 * logs/mail.log instead so a broken/unset SMTP config never blocks
 * the alert-broadcasting workflow. Returns true on real or simulated
 * delivery, false only on an unexpected local error.
 */
function sendAlertEmail(string $toEmail, string $toName, string $subject, string $bodyHtml): bool
{
    if (!MAIL_ENABLED) {
        return logSimulatedEmail($toEmail, $subject, $bodyHtml, 'MAIL_ENABLED is false');
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 10;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody  = strip_tags($bodyHtml);

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        // SMTP failed (e.g. no internet, bad credentials on a school lab PC) —
        // fall back to a logged/simulated send so the alert workflow still completes.
        return logSimulatedEmail($toEmail, $subject, $bodyHtml, 'SMTP error: ' . $mail->ErrorInfo);
    }
}

function logSimulatedEmail(string $toEmail, string $subject, string $bodyHtml, string $reason): bool
{
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $entry = sprintf(
        "[%s] SIMULATED EMAIL (%s)\nTo: %s\nSubject: %s\nBody: %s\n%s\n\n",
        date('Y-m-d H:i:s'),
        $reason,
        $toEmail,
        $subject,
        strip_tags($bodyHtml),
        str_repeat('-', 60)
    );
    file_put_contents($logDir . '/mail.log', $entry, FILE_APPEND);
    return true;
}
