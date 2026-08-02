<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

$totalUsers      = $db->query("SELECT COUNT(*) c FROM users")->fetch()['c'];
$activeAlerts    = $db->query("SELECT COUNT(*) c FROM alerts WHERE is_active = 1")->fetch()['c'];
$openIncidents   = $db->query("SELECT COUNT(*) c FROM incidents WHERE status IN ('open','investigating')")->fetch()['c'];
$roleBreakdown   = $db->query("SELECT role, COUNT(*) c FROM users GROUP BY role")->fetchAll();
$recentUsers     = $db->query("SELECT name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

$emailStats = $db->query(
    "SELECT SUM(status='sent') AS sent, COUNT(*) AS total FROM notifications WHERE channel = 'email'"
)->fetch();
$deliveryRate = ($emailStats && $emailStats['total'] > 0)
    ? round(($emailStats['sent'] / $emailStats['total']) * 100) . '%'
    : '—';

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<h3 class="mb-4">Admin Dashboard</h3>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card stat-card p-3 text-center">
      <div class="text-muted small">Total Users</div>
      <div class="fs-2 fw-bold"><?= (int)$totalUsers ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card stat-card p-3 text-center">
      <div class="text-muted small">Active Alerts</div>
      <div class="fs-2 fw-bold text-danger"><?= (int)$activeAlerts ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card stat-card p-3 text-center">
      <div class="text-muted small">Open Incidents</div>
      <div class="fs-2 fw-bold text-warning"><?= (int)$openIncidents ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card stat-card p-3 text-center">
      <div class="text-muted small">Email Delivery Rate</div>
      <div class="fs-2 fw-bold text-success"><?= sanitise($deliveryRate) ?></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3"><a href="<?= BASE_URL ?>/admin/alerts.php" class="btn btn-outline-danger w-100">🚨 Broadcast Alerts</a></div>
  <div class="col-md-3"><a href="<?= BASE_URL ?>/admin/incidents.php" class="btn btn-outline-secondary w-100">📝 Manage Incidents</a></div>
  <div class="col-md-3"><a href="<?= BASE_URL ?>/admin/contacts.php" class="btn btn-outline-secondary w-100">📞 Manage Contacts</a></div>
  <div class="col-md-3"><a href="<?= BASE_URL ?>/evacuation.php" class="btn btn-outline-secondary w-100">📋 Evacuation Info</a></div>
</div>

<div class="row g-4">
  <div class="col-md-5">
    <div class="card">
      <div class="card-header bg-white"><strong>Users by Role</strong></div>
      <div class="card-body">
        <ul class="list-group list-group-flush">
          <?php foreach ($roleBreakdown as $r): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span class="badge badge-role-<?= sanitise($r['role']) ?> text-white"><?= sanitise(roleLabel($r['role'])) ?></span>
              <span class="fw-bold"><?= (int)$r['c'] ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
        <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-danger btn-sm mt-3">Manage Users →</a>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card">
      <div class="card-header bg-white"><strong>Recently Registered</strong></div>
      <div class="card-body table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
          <tbody>
            <?php foreach ($recentUsers as $u): ?>
              <tr>
                <td><?= sanitise($u['name']) ?></td>
                <td><?= sanitise($u['email']) ?></td>
                <td><span class="badge badge-role-<?= sanitise($u['role']) ?> text-white"><?= sanitise(roleLabel($u['role'])) ?></span></td>
                <td><?= sanitise(date('M j, Y', strtotime($u['created_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
