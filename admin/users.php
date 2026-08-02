<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();
$formErrors = [];
$formSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
        $formErrors[] = 'Invalid form submission. Please try again.';
    } else {
        $targetId = (int)($_POST['user_id'] ?? 0);
        $action   = $_POST['action'] ?? '';

        // Admins may not demote or deactivate their own account by accident.
        if ($targetId === (int)$_SESSION['user_id']) {
            $formErrors[] = 'You cannot change your own role or status here.';
        } elseif ($action === 'change_role') {
            $newRole = $_POST['role'] ?? '';
            if (!in_array($newRole, VALID_ROLES, true)) {
                $formErrors[] = 'Invalid role selected.';
            } else {
                $stmt = $db->prepare('UPDATE users SET role = ? WHERE user_id = ?');
                $stmt->execute([$newRole, $targetId]);
                $formSuccess = 'Role updated successfully.';
            }
        } elseif ($action === 'toggle_active') {
            $stmt = $db->prepare('UPDATE users SET is_active = NOT is_active WHERE user_id = ?');
            $stmt->execute([$targetId]);
            $formSuccess = 'Account status updated.';
        }
    }
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $db->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like]);
} else {
    $stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
}
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">Manage Users &amp; Roles</h3>
  <form method="GET" class="d-flex" role="search">
    <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Search name or email" value="<?= sanitise($search) ?>">
    <button class="btn btn-sm btn-outline-secondary">Search</button>
  </form>
</div>

<?php foreach ($formErrors as $err): ?>
  <div class="alert alert-danger"><?= sanitise($err) ?></div>
<?php endforeach; ?>
<?php if ($formSuccess): ?>
  <div class="alert alert-success"><?= sanitise($formSuccess) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Department</th>
          <th>Role</th>
          <th>Status</th>
          <th>Joined</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= sanitise($u['name']) ?></td>
            <td><?= sanitise($u['email']) ?></td>
            <td><?= sanitise($u['department'] ?? '—') ?></td>
            <td><span class="badge badge-role-<?= sanitise($u['role']) ?> text-white"><?= sanitise(roleLabel($u['role'])) ?></span></td>
            <td>
              <?php if ($u['is_active']): ?>
                <span class="badge bg-success">Active</span>
              <?php else: ?>
                <span class="badge bg-secondary">Disabled</span>
              <?php endif; ?>
            </td>
            <td><?= sanitise(date('M j, Y', strtotime($u['created_at']))) ?></td>
            <td class="text-end">
              <?php if ((int)$u['user_id'] === (int)$_SESSION['user_id']): ?>
                <span class="text-muted small">This is you</span>
              <?php else: ?>
                <div class="d-flex gap-2 justify-content-end">
                  <form method="POST" class="d-flex gap-1">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="change_role">
                    <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                    <select name="role" class="form-select form-select-sm">
                      <?php foreach (VALID_ROLES as $r): ?>
                        <option value="<?= $r ?>" <?= $r === $u['role'] ? 'selected' : '' ?>><?= sanitise(roleLabel($r)) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-primary">Update</button>
                  </form>

                  <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                    <button class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                      <?= $u['is_active'] ? 'Disable' : 'Enable' ?>
                    </button>
                  </form>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
