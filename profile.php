<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db   = getDB();
$user = currentUser();
$formErrors = [];
$formSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCSRFToken($_POST['csrf_token'] ?? null)) {
    $formErrors[] = 'Invalid form submission. Please try again.';
}

// --- Handle profile detail update ---
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $name       = sanitise($_POST['name'] ?? '');
    $phone      = sanitise($_POST['phone'] ?? '');
    $department = sanitise($_POST['department'] ?? '');

    if ($name === '') {
        $formErrors[] = 'Name cannot be empty.';
    } else {
        $stmt = $db->prepare('UPDATE users SET name = ?, phone = ?, department = ? WHERE user_id = ?');
        $stmt->execute([$name, $phone, $department, $user['user_id']]);
        $formSuccess = 'Profile updated successfully.';
        $user = currentUser(); // refresh
    }
}

// --- Handle password change ---
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_new_password'] ?? '';

    $stmt = $db->prepare('SELECT password_hash FROM users WHERE user_id = ?');
    $stmt->execute([$user['user_id']]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($current, $row['password_hash'])) {
        $formErrors[] = 'Current password is incorrect.';
    } elseif (!isStrongPassword($new)) {
        $formErrors[] = 'New password must be at least 8 characters, include an uppercase letter and a number.';
    } elseif ($new !== $confirm) {
        $formErrors[] = 'New passwords do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
        $stmt->execute([$hash, $user['user_id']]);
        $formSuccess = 'Password changed successfully.';
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>

<h3 class="mb-4">My Profile</h3>

<?php foreach ($formErrors as $err): ?>
  <div class="alert alert-danger"><?= sanitise($err) ?></div>
<?php endforeach; ?>
<?php if ($formSuccess): ?>
  <div class="alert alert-success"><?= sanitise($formSuccess) ?></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-white"><strong>Account Details</strong></div>
      <div class="card-body">
        <p class="mb-1"><strong>Email:</strong> <?= sanitise($user['email']) ?> <span class="text-muted small">(cannot be changed)</span></p>
        <p class="mb-3"><strong>Role:</strong> <span class="badge badge-role-<?= sanitise($user['role']) ?> text-white"><?= sanitise(roleLabel($user['role'])) ?></span></p>

        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
          <input type="hidden" name="action" value="update_profile">

          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" required value="<?= sanitise($user['name']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= sanitise($user['phone'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Department</label>
            <input type="text" name="department" class="form-control" value="<?= sanitise($user['department'] ?? '') ?>">
          </div>
          <button type="submit" class="btn btn-danger">Save Changes</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-white"><strong>Change Password</strong></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
          <input type="hidden" name="action" value="change_password">

          <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" required minlength="8">
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_new_password" class="form-control" required minlength="8">
          </div>
          <button type="submit" class="btn btn-outline-danger">Update Password</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
