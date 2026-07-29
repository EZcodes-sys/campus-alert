<?php
/**
 * Campus Emergency Alert System (CEAS)
 * Global configuration constants.
 * Adjust DB_USER / DB_PASS to match your local MySQL/XAMPP setup.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'campus_alert_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // default XAMPP root password is blank

define('APP_NAME', 'Campus Emergency Alert System');
define('SESSION_TIMEOUT_SECONDS', 1800); // 30 minutes
define('BASE_URL', '/campus_alert'); // change if deployed in a subfolder

// ------------------------------------------------------------------
// Email (SMTP) settings for alert broadcasting.
// Set MAIL_ENABLED to true and fill in real SMTP credentials to send
// real emails (e.g. a Gmail account with an "App Password").
// When disabled/misconfigured, emails are safely logged to
// logs/mail.log instead of being sent, so the demo never breaks.
// ------------------------------------------------------------------
define('MAIL_ENABLED', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_gmail_app_password');
define('SMTP_FROM_EMAIL', 'your_email@gmail.com');
define('SMTP_FROM_NAME', 'Campus Emergency Alert System');

// Session cookie hardening — must run before session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1); // set to 0 in production