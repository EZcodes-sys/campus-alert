<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/alerts.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $id = (int)($_POST['alert_id'] ?? 0);
    if ($id > 0) deactivateAlert($id);
}
header('Location: broadcast.php');
exit;
