<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/incidents.php';
requireLogin();

$stmt = getDB()->prepare('SELECT * FROM incidents WHERE reported_by = ? ORDER BY created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$incidents = $stmt->fetchAll();

$pageTitle = 'My Incidents';
require_once __DIR__ . '/../includes/header.php';
?>

<h3 class="mb-4">My Reported Incidents</h3>

<?php if (empty($incidents)): ?>
  <div class="alert alert-info">
    You haven't reported any incidents yet.
    <a href="<?= BASE_URL ?>/incidents/report.php">Report one here</a>.
  </div>
<?php else: ?>
  <div class="card">
    <div class="card-body table-responsive">
      <table class="table align-middle">
        <thead>
          <tr><th>Tracking Code</th><th>Type</th><th>Location</th><th>Status</th><th>Reported</th></tr>
        </thead>
        <tbody>
          <?php foreach ($incidents as $i): ?>
            <tr>
              <td><code><?= sanitise($i['tracking_code']) ?></code></td>
              <td><?= sanitise($i['incident_type']) ?></td>
              <td><?= sanitise($i['location']) ?></td>
              <td><span class="badge <?= statusBadgeClass($i['status']) ?>"><?= sanitise(ucfirst($i['status'])) ?></span></td>
              <td><?= sanitise(date('M j, Y g:ia', strtotime($i['created_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
