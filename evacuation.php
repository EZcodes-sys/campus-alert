<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$formSuccess = null;
$formErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_SESSION['user_role'] ?? '') === 'admin') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
        $formErrors[] = 'Invalid form submission. Please try again.';
    } else {
        $content = trim($_POST['content'] ?? '');
        $stmt = $db->prepare(
            "INSERT INTO site_settings (setting_key, setting_value) VALUES ('evacuation_info', ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        $stmt->execute([$content]);
        $formSuccess = 'Evacuation information updated.';
    }
}

$stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'evacuation_info'");
$stmt->execute();
$content = $stmt->fetch()['setting_value'] ?? 'No evacuation information has been published yet.';

$pageTitle = 'Evacuation Information';
require_once __DIR__ . '/includes/header.php';
?>

<h3 class="mb-4">📋 Evacuation Information</h3>

<?php foreach ($formErrors as $err): ?>
  <div class="alert alert-danger"><?= sanitise($err) ?></div>
<?php endforeach; ?>
<?php if ($formSuccess): ?>
  <div class="alert alert-success"><?= sanitise($formSuccess) ?></div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-body">
    <pre style="white-space: pre-wrap; font-family: inherit; margin: 0;"><?= sanitise($content) ?></pre>
  </div>
</div>

<?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
  <div class="card">
    <div class="card-header bg-white"><strong>Edit Evacuation Information</strong> <span class="text-muted small">(Admin only)</span></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <textarea name="content" class="form-control" rows="14"><?= sanitise($content) ?></textarea>
        <button type="submit" class="btn btn-danger mt-3">Save</button>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
