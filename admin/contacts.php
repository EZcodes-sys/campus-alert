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
        $action = $_POST['action'] ?? '';

        if ($action === 'add' || $action === 'edit') {
            $name        = sanitise($_POST['name'] ?? '');
            $designation = sanitise($_POST['designation'] ?? '');
            $phone       = sanitise($_POST['phone'] ?? '');
            $email       = sanitise($_POST['email'] ?? '');
            $category    = sanitise($_POST['category'] ?? 'General');

            if ($name === '' || $phone === '') {
                $formErrors[] = 'Name and phone number are required.';
            } else {
                if ($action === 'add') {
                    $stmt = $db->prepare(
                        'INSERT INTO emergency_contacts (name, designation, phone, email, category) VALUES (?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([$name, $designation, $phone, $email, $category]);
                    $formSuccess = 'Contact added.';
                } else {
                    $contactId = (int) ($_POST['contact_id'] ?? 0);
                    $stmt = $db->prepare(
                        'UPDATE emergency_contacts SET name=?, designation=?, phone=?, email=?, category=? WHERE contact_id=?'
                    );
                    $stmt->execute([$name, $designation, $phone, $email, $category, $contactId]);
                    $formSuccess = 'Contact updated.';
                }
            }
        } elseif ($action === 'delete') {
            $contactId = (int) ($_POST['contact_id'] ?? 0);
            $db->prepare('DELETE FROM emergency_contacts WHERE contact_id = ?')->execute([$contactId]);
            $formSuccess = 'Contact deleted.';
        }
    }
}

$contacts = $db->query("SELECT * FROM emergency_contacts ORDER BY category, name")->fetchAll();
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editing = null;
if ($editId) {
    foreach ($contacts as $c) {
        if ((int) $c['contact_id'] === $editId) { $editing = $c; break; }
    }
}

$pageTitle = 'Manage Contacts';
require_once __DIR__ . '/../includes/header.php';
?>

<h3 class="mb-4">Manage Emergency Contacts</h3>

<?php foreach ($formErrors as $err): ?>
  <div class="alert alert-danger"><?= sanitise($err) ?></div>
<?php endforeach; ?>
<?php if ($formSuccess): ?>
  <div class="alert alert-success"><?= sanitise($formSuccess) ?></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-md-4">
    <div class="card">
      <div class="card-header bg-white"><strong><?= $editing ? 'Edit Contact' : 'Add Contact' ?></strong></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
          <input type="hidden" name="action" value="<?= $editing ? 'edit' : 'add' ?>">
          <?php if ($editing): ?>
            <input type="hidden" name="contact_id" value="<?= (int)$editing['contact_id'] ?>">
          <?php endif; ?>

          <div class="mb-2">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required value="<?= sanitise($editing['name'] ?? '') ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Designation</label>
            <input type="text" name="designation" class="form-control" value="<?= sanitise($editing['designation'] ?? '') ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" required value="<?= sanitise($editing['phone'] ?? '') ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= sanitise($editing['email'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Category</label>
            <input type="text" name="category" class="form-control" value="<?= sanitise($editing['category'] ?? 'General') ?>">
          </div>
          <button type="submit" class="btn btn-danger w-100"><?= $editing ? 'Save Changes' : 'Add Contact' ?></button>
          <?php if ($editing): ?>
            <a href="<?= BASE_URL ?>/admin/contacts.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <div class="card">
      <div class="card-body table-responsive">
        <table class="table align-middle">
          <thead><tr><th>Name</th><th>Category</th><th>Phone</th><th>Email</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
            <?php foreach ($contacts as $c): ?>
              <tr>
                <td><?= sanitise($c['name']) ?><br><span class="small text-muted"><?= sanitise($c['designation']) ?></span></td>
                <td><?= sanitise($c['category']) ?></td>
                <td><?= sanitise($c['phone']) ?></td>
                <td><?= sanitise($c['email'] ?? '—') ?></td>
                <td class="text-end">
                  <a href="?edit=<?= (int)$c['contact_id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                  <form method="POST" class="d-inline" onsubmit="return confirm('Delete this contact?');">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="contact_id" value="<?= (int)$c['contact_id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
