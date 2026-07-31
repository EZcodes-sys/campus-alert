<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$contacts = getDB()->query("SELECT * FROM emergency_contacts ORDER BY category, name")->fetchAll();

$grouped = [];
foreach ($contacts as $c) {
    $grouped[$c['category']][] = $c;
}

$pageTitle = 'Emergency Contacts';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">Emergency Contact Directory</h3>
  <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
    <a href="<?= BASE_URL ?>/admin/contacts.php" class="btn btn-sm btn-outline-danger">Manage Contacts</a>
  <?php endif; ?>
</div>

<div class="row g-3">
  <?php foreach ($grouped as $category => $items): ?>
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header bg-white"><strong><?= sanitise($category) ?></strong></div>
        <ul class="list-group list-group-flush">
          <?php foreach ($items as $c): ?>
            <li class="list-group-item">
              <div class="fw-semibold"><?= sanitise($c['name']) ?></div>
              <div class="small text-muted"><?= sanitise($c['designation']) ?></div>
              <div>📞 <a href="tel:<?= sanitise($c['phone']) ?>"><?= sanitise($c['phone']) ?></a></div>
              <?php if (!empty($c['email'])): ?>
                <div class="small">✉️ <a href="mailto:<?= sanitise($c['email']) ?>"><?= sanitise($c['email']) ?></a></div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (empty($grouped)): ?>
    <div class="col-12"><div class="alert alert-info">No emergency contacts have been added yet.</div></div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
