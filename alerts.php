<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/alerts.php';
requireLogin();

$alerts = getActiveAlertsForUser((int) $_SESSION['user_id']);

// Mark all as read the moment the user views this page.
foreach ($alerts as $a) {
    if (!$a['is_read']) {
        markAlertRead((int) $a['alert_id'], (int) $_SESSION['user_id']);
    }
}

$pageTitle = 'Active Alerts';
require_once __DIR__ . '/includes/header.php';
?>

<h3 class="mb-4">Active Emergency Alerts</h3>

<?php if (empty($alerts)): ?>
  <div class="alert alert-success">✅ No active alerts. Everything is calm on campus right now.</div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($alerts as $i => $a):
      $isUrgent = in_array($a['severity'], ['critical', 'high'], true);
    ?>
      <div class="col-12">
        <div class="card severity-<?= sanitise($a['severity']) ?> alert-card-animated<?= $isUrgent ? ' severity-pulse' : '' ?>"
             style="animation-delay: <?= min($i * 90, 720) ?>ms;">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <h5 class="card-title mb-1"><?php if ($isUrgent): ?><span class="siren-dot"></span><?php endif; ?><?= sanitise($a['title']) ?></h5>
              <span class="badge severity-<?= sanitise($a['severity']) ?> border"><?= sanitise(ucfirst($a['severity'])) ?></span>
            </div>
            <p class="card-text mb-1" style="white-space: pre-line;"><?= sanitise($a['message']) ?></p>
            <div class="small text-muted"><?= sanitise(date('M j, Y g:ia', strtotime($a['created_at']))) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="mt-4">
  <a href="<?= BASE_URL ?>/evacuation.php" class="btn btn-outline-danger btn-sm">📋 View Evacuation Procedures</a>
  <a href="<?= BASE_URL ?>/contacts.php" class="btn btn-outline-secondary btn-sm">📞 Emergency Contacts</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
