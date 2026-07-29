<?php
/**
 * Hash Generator Helper
 * If the admin login fails, open this in your browser:
 * http://localhost/campus_alert/gen_hash.php
 *
 * Copy the generated hash and run the UPDATE query shown below.
 * DELETE this file after you're done — it should not stay on production.
 */

$password = 'Admin@1234';
$hash     = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hash Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
<div class="card shadow-sm" style="max-width:700px;margin:auto;">
    <div class="card-header bg-primary text-white fw-bold">🔑 Password Hash Generator</div>
    <div class="card-body">
        <p><strong>Password:</strong> <code><?= htmlspecialchars($password) ?></code></p>
        <p><strong>Generated Hash:</strong></p>
        <div class="alert alert-info font-monospace small" style="word-break:break-all;"><?= $hash ?></div>
        <hr>
        <p class="fw-semibold">Run this SQL in phpMyAdmin to fix the admin login:</p>
        <div class="bg-dark text-white rounded p-3 font-monospace small">
            UPDATE users SET password_hash = '<?= $hash ?>'<br>
            WHERE email = 'admin@campus.ac.ke';
        </div>
        <div class="mt-3 alert alert-warning">
            ⚠ <strong>Delete this file</strong> after use: <code>campus_alert/gen_hash.php</code>
        </div>
    </div>
</div>
</body>
</html>
