<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/incidents.php';
requireRole(['admin', 'security_officer']);

$formErrors = [];
$formSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
        $formErrors[] = 'Invalid form submission. Please try again.';
    } else {
        $ok = updateIncidentStatus((int) ($_POST['incident_id'] ?? 0), $_POST['status'] ?? '');
        $formSuccess = $ok ? 'Incident status updated.' : null;
        if (!$ok) {
            $formErrors[] = 'Could not update incident status.';
        }
    }
}

$statusFilter = $_GET['status'] ?? '';
$db = getDB();

if ($statusFilter && in_array($statusFilter, VALID_INCIDENT_STATUSES, true)) {
    $stmt = $db->prepare(
        "SELECT i.*, u.name AS reporter_name, u.email AS reporter_email
         FROM incidents i JOIN users u ON u.user_id = i.reported_by
         WHERE i.status = ? ORDER BY i.created_at DESC"
    );
    $stmt->execute([$statusFilter]);
} else {
    $stmt = $db->query(
        "SELECT i.*, u.name AS reporter_name, u.email AS reporter_email
         FROM incidents i JOIN users u ON u.user_id = i.reported_by
         ORDER BY i.created_at DESC"
    );
}
$incidents = $stmt->fetchAll();

$pageTitle = 'Manage Incidents';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">Manage Incidents</h3>
  <form method="GET" class="d-flex gap-2">
    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="">All statuses</option>
      <?php foreach (VALID_INCIDENT_STATUSES as $s): ?>
        <option value="<?= $s ?>" <?= $s === $statusFilter ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
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
          <th>Tracking Code</th><th>Type</th><th>Location</th><th>Reported By</th>
          <th>Description</th><th>Status</th><th>Reported</th><th class="text-end">Update</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($incidents as $i): ?>
          <tr>
            <td><code><?= sanitise($i['tracking_code']) ?></code></td>
            <td><?= sanitise($i['incident_type']) ?></td>
            <td><?= sanitise($i['location']) ?></td>
            <td><?= sanitise($i['reporter_name']) ?><br><span class="small text-muted"><?= sanitise($i['reporter_email']) ?></span></td>
            <td style="max-width:250px; white-space:pre-line;"><?= sanitise($i['description']) ?></td>
            <td><span class="badge <?= statusBadgeClass($i['status']) ?>"><?= sanitise(ucfirst($i['status'])) ?></span></td>
            <td><?= sanitise(date('M j, Y g:ia', strtotime($i['created_at']))) ?></td>
            <td class="text-end">
              <form method="POST" class="d-flex gap-1 justify-content-end">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="incident_id" value="<?= (int)$i['incident_id'] ?>">
                <select name="status" class="form-select form-select-sm">
                  <?php foreach (VALID_INCIDENT_STATUSES as $s): ?>
                    <option value="<?= $s ?>" <?= $s === $i['status'] ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-outline-primary">Save</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($incidents)): ?>
          <tr><td colspan="8" class="text-center text-muted">No incidents found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
