<?php
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$formErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
        $formErrors[] = 'Invalid form submission. Please try again.';
    } else {
        $result = registerUser(
            $_POST['name'] ?? '',
            $_POST['email'] ?? '',
            $_POST['password'] ?? '',
            $_POST['confirm_password'] ?? '',
            $_POST['department'] ?? ''
        );

        if ($result['success']) {
            setFlash('success', $result['message']);
            header('Location: ' . BASE_URL . '/auth/login.php');
            exit;
        }
        $formErrors[] = $result['message'];
    }
}

$pageTitle = 'Register';
$showNav   = false;
$bodyClass = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card auth-card">
  <div class="card-body">
    <h3 class="text-center mb-1">🚨 CEAS</h3>
    <p class="text-center text-muted mb-4">Create your student/staff account</p>

    <?php foreach ($formErrors as $err): ?>
      <div class="alert alert-danger"><?= sanitise($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" required
               value="<?= sanitise($_POST['name'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" required
               value="<?= sanitise($_POST['email'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Department (optional)</label>
        <input type="text" name="department" class="form-control"
               value="<?= sanitise($_POST['department'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required minlength="8">
        <div class="form-text">At least 8 characters, one uppercase letter, one number.</div>
      </div>

      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" required minlength="8">
      </div>

      <button type="submit" class="btn btn-danger w-100">Create Account</button>
    </form>

    <p class="text-center mt-3 mb-0">
      Already have an account? <a href="<?= BASE_URL ?>/auth/login.php">Log in</a>
    </p>
  </div>
</div>
<div class="emergency-banner">⚠️ In case of an active emergency, contact Campus Security immediately: 0700-100-100</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
