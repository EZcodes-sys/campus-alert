<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = currentUser();

// Redirect admins to the dedicated admin panel
if ($user['role'] === 'admin') {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$db = getDB();
$activeAlerts   = $db->query("SELECT COUNT(*) c FROM alerts WHERE is_active = 1")->fetch()['c'] ?? 0;
$myIncidents    = (function() use ($db, $user) {
    $stmt = $db->prepare("SELECT COUNT(*) c FROM incidents WHERE reported_by = ?");
    $stmt->execute([$user['user_id']]);
    return $stmt->fetch()['c'] ?? 0;
})();
$recentAlerts = $db->query("SELECT title, severity, created_at FROM alerts WHERE is_active = 1 ORDER BY created_at DESC LIMIT 5")->fetchAll();
$emergencyContacts = $db->query("SELECT name, designation, phone, category FROM emergency_contacts ORDER BY category")->fetchAll();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<h3 class="mb-4">Welcome, <?= sanitise($user['name']) ?> <span class="badge badge-role-<?= sanitise($user['role']) ?> text-white"><?= sanitise(roleLabel($user['role'])) ?></span></h3>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card stat-card p-3 text-center">
      <div class="text-muted small">Active Alerts</div>
      <div class="fs-2 fw-bold text-danger"><?= (int)$activeAlerts ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card stat-card p-3 text-center">
      <div class="text-muted small">My Reported Incidents</div>
      <div class="fs-2 fw-bold"><?= (int)$myIncidents ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card stat-card p-3 text-center">
      <div class="text-muted small">Account Status</div>
      <div class="fs-2 fw-bold text-success">Active</div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-md-7">
    <div class="card">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>Active Alerts</strong>
        <span class="badge bg-light text-muted" id="liveIndicator">🟢 live</span>
      </div>
      <div class="card-body" id="liveAlertsContainer">
        <?php if (empty($recentAlerts)): ?>
          <p class="text-muted mb-0" id="noAlertsMsg">No active alerts at this time.</p>
        <?php else: ?>
          <ul class="list-group list-group-flush" id="alertsList">
            <?php foreach ($recentAlerts as $i => $a): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center alert-card-animated" style="animation-delay: <?= $i * 80 ?>ms;">
                <span><?php if (in_array($a['severity'], ['critical','high'], true)): ?><span class="siren-dot"></span><?php endif; ?><?= sanitise($a['title']) ?></span>
                <span class="badge severity-<?= sanitise($a['severity']) ?>"><?= sanitise(ucfirst($a['severity'])) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
      <div class="card-footer bg-white text-end">
        <a href="<?= BASE_URL ?>/alerts.php" class="btn btn-sm btn-outline-danger">View All Alerts →</a>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-body d-flex gap-2 flex-wrap">
        <a href="<?= BASE_URL ?>/incidents/report.php" class="btn btn-danger btn-sm">📝 Report an Incident</a>
        <a href="<?= BASE_URL ?>/incidents/my_incidents.php" class="btn btn-outline-secondary btn-sm">My Incidents</a>
        <a href="<?= BASE_URL ?>/evacuation.php" class="btn btn-outline-secondary btn-sm">📋 Evacuation Info</a>
        <a href="<?= BASE_URL ?>/contacts.php" class="btn btn-outline-secondary btn-sm">📞 Emergency Contacts</a>
      </div>
    </div>
  </div>

  <div class="col-md-5">
    <div class="card">
      <div class="card-header bg-white"><strong>Emergency Contacts</strong></div>
      <div class="card-body">
        <ul class="list-group list-group-flush">
          <?php foreach ($emergencyContacts as $c): ?>
            <li class="list-group-item">
              <div class="fw-semibold"><?= sanitise($c['name']) ?></div>
              <div class="small text-muted"><?= sanitise($c['designation']) ?> — <?= sanitise($c['phone']) ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
// Polls the alerts feed every 10s and re-renders the Active Alerts card —
// gives a "real-time" dashboard experience without needing WebSockets.
(function () {
  const severityLabel = s => s.charAt(0).toUpperCase() + s.slice(1);
  const container = document.getElementById('liveAlertsContainer');
  const indicator = document.getElementById('liveIndicator');

  function render(alerts) {
    if (!alerts.length) {
      container.innerHTML = '<p class="text-muted mb-0">No active alerts at this time.</p>';
      return;
    }
    const items = alerts.slice(0, 5).map((a, i) => {
      const urgent = a.severity === 'critical' || a.severity === 'high';
      return `
      <li class="list-group-item d-flex justify-content-between align-items-center alert-card-animated" style="animation-delay:${i * 80}ms;">
        <span>${urgent ? '<span class="siren-dot"></span>' : ''}${escapeHtml(a.title)}</span>
        <span class="badge severity-${escapeHtml(a.severity)}">${escapeHtml(severityLabel(a.severity))}</span>
      </li>`;
    }).join('');
    container.innerHTML = `<ul class="list-group list-group-flush">${items}</ul>`;
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function poll() {
    fetch('<?= BASE_URL ?>/api/alerts_feed.php', { credentials: 'same-origin' })
      .then(r => r.json())
      .then(data => {
        render(data.alerts || []);
        indicator.textContent = '🟢 live';
      })
      .catch(() => { indicator.textContent = '⚪ offline'; });
  }

  setInterval(poll, 10000);
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
