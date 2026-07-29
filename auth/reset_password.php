<?php
require_once __DIR__ . '/../includes/auth.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$formErrors = [];
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
        $formErrors[] = 'Invalid form submission. Please try again.';
    } else {
        $result = resetPasswordWithToken($token, $_POST['password'] ?? '', $_POST['confirm_password'] ?? '');
        if ($result['success']) {
            setFlash('success', $result['message']);
            header('Location: ' . BASE_URL . '/auth/login.php');
            exit;
        }
        $formErrors[] = $result['message'];
    }
}

$pageTitle = 'Reset Password';
$showNav   = false;
$bodyClass = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card auth-card">
  <div class="card-body">
    <h3 class="text-center mb-1">🚨 CEAS</h3>
    <p class="text-center text-muted mb-4">Choose a new password</p>

    <?php foreach ($formErrors as $err): ?>
      <div class="alert alert-danger"><?= sanitise($err) ?></div>
    <?php endforeach; ?>

    <?php if (empty($token)): ?>
      <div class="alert alert-danger">Missing or invalid reset token.</div>
    <?php else: ?>
      <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="token" value="<?= sanitise($token) ?>">

        <div class="mb-3">
          <label class="form-label">New Password</label>
          <input type="password" name="password" class="form-control" required minlength="8">
          <div class="form-text">At least 8 characters, one uppercase letter, one number.</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" required minlength="8">
        </div>

        <button type="submit" class="btn btn-danger w-100">Reset Password</button>
      </form>
    <?php endif; ?>

    <p class="text-center mt-3 mb-0">
      <a href="<?= BASE_URL ?>/auth/login.php">Back to login</a>
    </p>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
