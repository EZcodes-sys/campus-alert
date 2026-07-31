<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

const VALID_SEVERITIES = ['low', 'medium', 'high', 'critical'];

/**
 * Broadcasts a new emergency alert to every active user:
 *  1. Inserts the alert record.
 *  2. Inserts an 'in_app' notification row for every active user (drives the
 *     live dashboard banner / bell icon).
 *  3. Attempts to email every active user via PHPMailer, logging a
 *     'sent' or 'failed' notification row per recipient either way.
 *
 * Returns delivery counters so the admin UI can show a summary.
 */
function broadcastAlert(int $createdBy, string $title, string $message, string $severity): array
{
    $title   = sanitise($title);
    $message = trim($message); // allow line breaks; escaped on output instead
    $severity = in_array($severity, VALID_SEVERITIES, true) ? $severity : 'medium';

    if ($title === '' || $message === '') {
        return ['success' => false, 'message' => 'Title and message are required.'];
    }

    $db = getDB();
    $db->beginTransaction();

    try {
        $stmt = $db->prepare('INSERT INTO alerts (title, message, severity, created_by, is_active) VALUES (?, ?, ?, ?, 1)');
        $stmt->execute([$title, $message, $severity, $createdBy]);
        $alertId = (int) $db->lastInsertId();

        $recipients = $db->query("SELECT user_id, name, email FROM users WHERE is_active = 1")->fetchAll();

        $inAppCount = 0;
        $emailSent  = 0;
        $emailFailed = 0;

        $insertNotif = $db->prepare(
            'INSERT INTO notifications (alert_id, user_id, channel, status, is_read) VALUES (?, ?, ?, ?, 0)'
        );

        foreach ($recipients as $user) {
            // In-app notification — always recorded, drives the live dashboard.
            $insertNotif->execute([$alertId, $user['user_id'], 'in_app', 'sent']);
            $inAppCount++;

            // Email notification.
            $subject = '[' . strtoupper($severity) . ' ALERT] ' . $title;
            $body = renderAlertEmailBody($user['name'], $title, $message, $severity);
            $sent = sendAlertEmail($user['email'], $user['name'], $subject, $body);

            $insertNotif->execute([$alertId, $user['user_id'], 'email', $sent ? 'sent' : 'failed']);
            $sent ? $emailSent++ : $emailFailed++;
        }

        $db->commit();

        return [
            'success' => true,
            'alert_id' => $alertId,
            'message' => "Alert broadcast to {$inAppCount} users ({$emailSent} emails sent" . ($emailFailed ? ", {$emailFailed} failed" : '') . ").",
        ];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'Failed to broadcast alert. Please try again.'];
    }
}

function renderAlertEmailBody(string $recipientName, string $title, string $message, string $severity): string
{
    $safeName    = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
    $safeTitle   = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    $color = ['low' => '#0f5132', 'medium' => '#664d03', 'high' => '#842029', 'critical' => '#dc3545'][$severity] ?? '#664d03';

    return <<<HTML
    <div style="font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto;">
      <div style="background:{$color}; color:#fff; padding: 12px 16px; border-radius: 8px 8px 0 0;">
        <strong>Campus Emergency Alert System</strong>
      </div>
      <div style="border: 1px solid #ddd; border-top: none; padding: 16px; border-radius: 0 0 8px 8px;">
        <p>Hello {$safeName},</p>
        <p style="font-size: 18px; font-weight: bold;">{$safeTitle}</p>
        <p>{$safeMessage}</p>
        <p style="color:#666; font-size: 12px;">This is an automated safety alert. Do not reply to this email.</p>
      </div>
    </div>
    HTML;
}

/** Deactivates (closes/expires) a broadcast alert so it stops showing as "active". */
function deactivateAlert(int $alertId): bool
{
    $stmt = getDB()->prepare('UPDATE alerts SET is_active = 0 WHERE alert_id = ?');
    return $stmt->execute([$alertId]);
}

/** Marks a single alert as read for the given user (their in_app notification row). */
function markAlertRead(int $alertId, int $userId): bool
{
    $stmt = getDB()->prepare(
        "UPDATE notifications SET is_read = 1 WHERE alert_id = ? AND user_id = ? AND channel = 'in_app'"
    );
    return $stmt->execute([$alertId, $userId]);
}

/** Returns active alerts plus this user's read status, newest first — used by the dashboard + polling feed. */
function getActiveAlertsForUser(int $userId): array
{
    $stmt = getDB()->prepare(
        "SELECT a.alert_id, a.title, a.message, a.severity, a.created_at,
                COALESCE(n.is_read, 0) AS is_read
         FROM alerts a
         LEFT JOIN notifications n
                ON n.alert_id = a.alert_id AND n.user_id = ? AND n.channel = 'in_app'
         WHERE a.is_active = 1
         ORDER BY a.created_at DESC"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}
