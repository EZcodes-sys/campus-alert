<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/incidents.php';
requireLogin();

$formErrors = [];
$successCode = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
        $formErrors[] = 'Invalid form submission. Please try again.';
    } else {
        $result = submitIncident(
            (int) $_SESSION['user_id'],
            $_POST['incident_type'] ?? '',
            $_POST['location'] ?? '',
            $_POST['description'] ?? ''
        );
        if ($result['success']) {
            $successCode = $result['tracking_code'];
        } else {
            $formErrors[] = $result['message'];
        }
    }
}

$pageTitle = 'Report Incident';
require_once __DIR__ . '/../includes/header.php';
?>

<h3 class="mb-4">Report an Incident</h3>

<?php foreach ($formErrors as $err): ?>
  <div class="alert alert-danger"><?= sanitise($err) ?></div>
<?php endforeach; ?>

<?php if ($successCode): ?>
  <div class="alert alert-success">
    ✅ Your incident has been reported. Your tracking code is <strong><?= sanitise($successCode) ?></strong> —
    save this to check the status later under <a href="<?= BASE_URL ?>/incidents/my_incidents.php">My Incidents</a>.
  </div>
<?php else: ?>
  <div class="row">
    <div class="col-md-7">
      <div class="card">
        <div class="card-body">
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

            <div class="mb-3">
              <label class="form-label">Incident Type</label>
              <select name="incident_type" class="form-select" required>
                <option value="">-- Select --</option>
                <option>Fire</option>
                <option>Medical Emergency</option>
                <option>Security Threat</option>
                <option>Theft</option>
                <option>Suspicious Activity</option>
                <option>Infrastructure Hazard</option>
                <option>Other</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Location</label>
              <input type="text" name="location" class="form-control" required placeholder="e.g. Block C, 2nd Floor Lab">
            </div>

            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="5" required
                        placeholder="Describe what happened, who was involved, and any immediate danger..."></textarea>
            </div>

            <button type="submit" class="btn btn-danger">Submit Report</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="alert alert-warning">
        <strong>⚠️ In an active emergency</strong>, do not wait for this form —
        call Campus Security directly at <strong>0700-100-100</strong> first.
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
