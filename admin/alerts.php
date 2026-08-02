<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/alerts.php';
requireAdmin();

$formErrors = [];
$formSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
        $formErrors[] = 'Invalid form submission. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'broadcast') {
        $result = broadcastAlert(
            (int) $_SESSION['user_id'],
            $_POST['title'] ?? '',
            $_POST['message'] ?? '',
            $_POST['severity'] ?? 'medium'
        );
        if ($result['success']) {
            $formSuccess = $result['message'];
        } else {
            $formErrors[] = $result['message'];
        }
    } elseif (($_POST['action'] ?? '') === 'deactivate') {
        deactivateAlert((int) ($_POST['alert_id'] ?? 0));
        $formSuccess = 'Alert deactivated.';
    }
}

$db = getDB();
$alerts = $db->query(
    "SELECT a.*, u.name AS creator_name,
            SUM(CASE WHEN n.channel='in_app' THEN 1 ELSE 0 END) AS in_app_count,
            SUM(CASE WHEN n.channel='email' AND n.status='sent' THEN 1 ELSE 0 END) AS email_sent,
            SUM(CASE WHEN n.channel='email' AND n.status='failed' THEN 1 ELSE 0 END) AS email_failed
     FROM alerts a
     JOIN users u ON u.user_id = a.created_by
     LEFT JOIN notifications n ON n.alert_id = a.alert_id
     GROUP BY a.alert_id
     ORDER BY a.created_at DESC"
)->fetchAll();

$pageTitle = 'Broadcast Alerts';
require_once __DIR__ . '/../includes/header.php';
?>

<h3 class="mb-4">Broadcast Emergency Alert</h3>

<?php foreach ($formErrors as $err): ?>
  <div class="alert alert-danger"><?= sanitise($err) ?></div>
<?php endforeach; ?>
<?php if ($formSuccess): ?>
  <div class="alert alert-success"><?= sanitise($formSuccess) ?></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-md-5">
    <div class="card">
      <div class="card-header bg-danger text-white"><strong>New Alert</strong></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
          <input type="hidden" name="action" value="broadcast">

          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" id="titleInput" class="form-control" required maxlength="150" placeholder="e.g. Building C Evacuation">
            <div class="form-text"><span id="titleCount">0</span>/150</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Severity</label>
            <div class="d-flex gap-2 flex-wrap">
              <?php foreach (['low' => 'success', 'medium' => 'primary', 'high' => 'warning', 'critical' => 'danger'] as $sev => $cls): ?>
                <div class="form-check form-check-inline severity-btn m-0">
                  <input class="form-check-input" type="radio" name="severity" id="sev_<?= $sev ?>" value="<?= $sev ?>" <?= $sev === 'medium' ? 'checked' : '' ?>>
                  <label class="form-check-label badge bg-<?= $cls ?> px-3 py-2" for="sev_<?= $sev ?>" style="cursor:pointer;font-size:.8rem;"><?= strtoupper($sev) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea name="message" class="form-control" rows="5" required
                      placeholder="Give clear, actionable instructions..."></textarea>
          </div>

          <button type="submit" id="broadcastBtn" class="btn btn-danger w-100" onclick="return confirm('Broadcast this alert to every active user (in-app + email)?');">
            🚨 Broadcast Now
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card">
      <div class="card-header bg-white"><strong>Broadcast History</strong></div>
      <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Title</th><th>Severity</th><th>Sent by</th>
              <th>In-App</th><th>Email</th><th>Status</th><th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($alerts as $a): ?>
              <tr>
                <td>
                  <?= sanitise($a['title']) ?><br>
                  <span class="text-muted small"><?= sanitise(date('M j, Y g:ia', strtotime($a['created_at']))) ?></span>
                </td>
                <td><span class="badge severity-<?= sanitise($a['severity']) ?>"><?= sanitise(ucfirst($a['severity'])) ?></span></td>
                <td><?= sanitise($a['creator_name']) ?></td>
                <td><?= (int)$a['in_app_count'] ?></td>
                <td>
                  <?= (int)$a['email_sent'] ?> sent
                  <?php if ($a['email_failed'] > 0): ?>
                    <span class="text-danger">/ <?= (int)$a['email_failed'] ?> failed</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($a['is_active']): ?>
                    <span class="badge bg-success">Active</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Deactivated</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($a['is_active']): ?>
                    <form method="POST" onsubmit="return confirm('Deactivate this alert?');">
                      <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                      <input type="hidden" name="action" value="deactivate">
                      <input type="hidden" name="alert_id" value="<?= (int)$a['alert_id'] ?>">
                      <button class="btn btn-sm btn-outline-secondary">Deactivate</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($alerts)): ?>
              <tr><td colspan="7" class="text-muted text-center">No alerts broadcast yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const titleInput = document.getElementById('titleInput');
  const titleCount = document.getElementById('titleCount');
  if (titleInput && titleCount) {
    titleCount.textContent = titleInput.value.length;
    titleInput.addEventListener('input', () => { titleCount.textContent = titleInput.value.length; });
  }

  const form = document.querySelector('form[method="POST"]');
  const btn = document.getElementById('broadcastBtn');
  if (form && btn) {
    form.addEventListener('submit', (e) => {
      if (e.defaultPrevented) return; // the confirm() dialog was cancelled
      btn.classList.add('btn-broadcasting');
      btn.innerHTML = '<span class="ceas-spinner"></span>Broadcasting…';
    });
  }

  <?php if ($formSuccess): ?>
  // Flash the success banner and give an audible confirmation chime,
  // reusing the same synthesized-tone engine as the live alert toasts.
  document.addEventListener('DOMContentLoaded', () => {
    const successBox = document.querySelector('.alert-success');
    if (successBox) successBox.classList.add('ceas-flash-success');
    if (window.CEASNotify && window.CEASNotify.soundEnabled()) {
      window.CEASNotify.playTone('low');
    }
  });
  <?php endif; ?>
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
