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
        $result = loginUser($_POST['email'] ?? '', $_POST['password'] ?? '');

        if ($result['success']) {
            setFlash('success', 'Welcome back!');
            if ($result['role'] === 'admin') {
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . '/dashboard.php');
            }
            exit;
        }
        $formErrors[] = $result['message'];
    }
}

$pageTitle = 'Login';
$showNav   = false;
$bodyClass = 'auth-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card auth-card">
  <div class="card-body">
    <h3 class="text-center mb-1">🚨 CEAS</h3>
    <p class="text-center text-muted mb-4">Campus Emergency Alert System — Sign In</p>

    <?php foreach ($formErrors as $err): ?>
      <div class="alert alert-danger"><?= sanitise($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" required autofocus
               value="<?= sanitise($_POST['email'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>

      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="remember" id="remember">
        <label class="form-check-label" for="remember">Remember me</label>
      </div>

      <button type="submit" class="btn btn-danger w-100">Log In</button>
    </form>

    <p class="text-center mt-3 mb-1">
      <a href="<?= BASE_URL ?>/auth/forgot_password.php">Forgot your password?</a>
    </p>
    <p class="text-center mb-0">
      Don't have an account? <a href="<?= BASE_URL ?>/auth/register.php">Register</a>
    </p>

    <hr>
    <p class="text-center text-muted small mb-0">
      Demo accounts (password: <code>Admin@1234</code>):<br>
      admin@campus.ac.ke &middot; security@campus.ac.ke &middot; lecturer@campus.ac.ke &middot; student@campus.ac.ke
    </p>
  </div>
</div>
<div class="emergency-banner">⚠️ In case of an active emergency, contact Campus Security immediately: 0700-100-100</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
