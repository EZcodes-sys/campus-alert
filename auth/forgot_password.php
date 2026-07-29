<?php
require_once __DIR__ . '/../includes/auth.php';

$formErrors = [];
$resultMessage = null;
$debugLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
        $formErrors[] = 'Invalid form submission. Please try again.';
    } else {
        $result = requestPasswordReset($_POST['email'] ?? '');
        $resultMessage = $result['message'];
        if (!empty($result['debug_token'])) {
            // Demo/local-only convenience: in production this link is emailed via PHPMailer, never shown on-screen.
            $debugLink = BASE_URL . '/auth/reset_password.php?token=' . $result['debug_token'];
        }
    }
}

$pageTitle = 'Forgot Password';
$showNav   = false;
$bodyClass = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card auth-card">
  <div class="card-body">
    <h3 class="text-center mb-1">🚨 CEAS</h3>
    <p class="text-center text-muted mb-4">Reset your password</p>

    <?php foreach ($formErrors as $err): ?>
      <div class="alert alert-danger"><?= sanitise($err) ?></div>
    <?php endforeach; ?>

    <?php if ($resultMessage): ?>
      <div class="alert alert-info"><?= sanitise($resultMessage) ?></div>
    <?php endif; ?>

    <?php if ($debugLink): ?>
      <div class="alert alert-warning small">
        <strong>Local demo only</strong> — in production this is emailed via PHPMailer, not shown here.<br>
        Reset link: <a href="<?= sanitise($debugLink) ?>"><?= sanitise($debugLink) ?></a>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" required autofocus>
      </div>
      <button type="submit" class="btn btn-danger w-100">Send Reset Link</button>
    </form>

    <p class="text-center mt-3 mb-0">
      <a href="<?= BASE_URL ?>/auth/login.php">Back to login</a>
    </p>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
