<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/alerts.php';
requireAdmin();

$success = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $result = broadcastAlert(
            (int)$_SESSION['user_id'],
            $_POST['title']    ?? '',
            $_POST['message']  ?? '',
            $_POST['severity'] ?? 'medium',
            !empty($_POST['expires_at']) ? $_POST['expires_at'] : null
        );
        if ($result['success']) $success = $result['message'];
        else $error = $result['message'];
    }
}
$stats  = getAlertStats();
$alerts = getAllAlerts();
$csrf   = generateCsrfToken();
$unread = getUnreadCount((int)$_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcast Alert — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark admin-navbar px-3">
    <span class="navbar-brand fw-bold fs-5"><i class="bi bi-bell-fill me-2"></i>CampusAlert <span class="badge bg-danger ms-1">ADMIN</span></span>
    <div class="d-flex align-items-center gap-3">
        <?php if ($unread > 0): ?><span class="badge bg-warning text-dark"><i class="bi bi-bell-fill me-1"></i><?= $unread ?> unread</span><?php endif; ?>
        <span class="text-white small"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="../auth/logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</nav>
<div class="d-flex">
    <aside class="admin-sidebar">
        <nav class="nav flex-column pt-3">
            <a href="../dashboard.php"  class="nav-link sidebar-link"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
            <a href="broadcast.php"     class="nav-link sidebar-link active"><i class="bi bi-broadcast me-2"></i>Broadcast Alert</a>
            <a href="../alerts.php"     class="nav-link sidebar-link"><i class="bi bi-bell me-2"></i>View Alerts</a>
        </nav>
    </aside>
    <main class="admin-main">
        <h4 class="fw-bold mb-1">📢 Broadcast Emergency Alert</h4>
        <p class="text-muted small mb-4">Send an alert to all registered users instantly</p>

        <!-- Stat cards -->
        <div class="row g-3 mb-4">
            <?php foreach ([
                [$stats['total_active'],    'Active Alerts',  'bell-fill',       '#C00000'],
                [$stats['critical_active'], 'Critical',       'exclamation-triangle-fill','#BF9000'],
                [$stats['total_today'],     'Sent Today',     'send-fill',       '#2E75B6'],
                [$stats['total_notified'],  'Users Notified', 'people-fill',     '#375623'],
            ] as [$val, $lbl, $icon, $col]): ?>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm stat-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:<?= $col ?>;"><i class="bi bi-<?= $icon ?> text-white"></i></div>
                        <div><div class="stat-value"><?= $val ?></div><div class="stat-label"><?= $lbl ?></div></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-4">
            <!-- Broadcast form -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header admin-card-header"><i class="bi bi-send-fill me-2"></i>New Alert</div>
                    <div class="card-body">
                        <?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>
                        <?php if ($error):   ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
                        <form method="POST" id="broadcastForm">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Severity Level</label>
                                <div class="d-flex gap-2 flex-wrap">
                                    <?php foreach (['low'=>'success','medium'=>'primary','high'=>'warning','critical'=>'danger'] as $sev=>$cls): ?>
                                    <div class="form-check form-check-inline severity-btn">
                                        <input class="form-check-input" type="radio" name="severity" id="sev_<?= $sev ?>" value="<?= $sev ?>" <?= $sev==='medium'?'checked':'' ?>>
                                        <label class="form-check-label badge bg-<?= $cls ?> px-3 py-2" for="sev_<?= $sev ?>" style="cursor:pointer;font-size:.8rem;"><?= strtoupper($sev) ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Alert Title</label>
                                <input type="text" class="form-control" name="title" id="title" placeholder="e.g. Fire in Science Block" maxlength="200" required>
                                <div class="form-text"><span id="title-count">0</span>/200</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Message</label>
                                <textarea class="form-control" name="message" rows="4" placeholder="Clear instructions for students and staff..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Expires At <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="datetime-local" class="form-control" name="expires_at">
                            </div>
                            <div id="preview-box" class="alert-preview mb-3 d-none">
                                <div class="preview-severity-badge" id="preview-severity"></div>
                                <div class="preview-title" id="preview-title"></div>
                                <div class="preview-message" id="preview-message"></div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-secondary" id="previewBtn"><i class="bi bi-eye me-1"></i>Preview</button>
                                <button type="submit" class="btn btn-danger btn-lg fw-semibold"><i class="bi bi-broadcast me-1"></i>Broadcast to All Users</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Alerts table -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header admin-card-header d-flex justify-content-between">
                        <span><i class="bi bi-list-ul me-2"></i>All Alerts</span>
                        <span class="badge bg-secondary"><?= count($alerts) ?> total</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 alerts-table">
                                <thead><tr><th>Severity</th><th>Title</th><th>By</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                                <tbody>
                                <?php if (empty($alerts)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No alerts yet.</td></tr>
                                <?php else: foreach ($alerts as $a):
                                    $cfg = severityConfig($a['severity']); ?>
                                    <tr>
                                        <td><span class="badge bg-<?= $cfg['class'] ?>"><?= $cfg['label'] ?></span></td>
                                        <td class="fw-semibold" style="max-width:180px;" title="<?= htmlspecialchars($a['message']) ?>"><?= htmlspecialchars(mb_strimwidth($a['title'],0,35,'...')) ?></td>
                                        <td class="text-muted small"><?= htmlspecialchars($a['created_by_name']) ?></td>
                                        <td class="text-muted small"><?= date('d M, H:i', strtotime($a['created_at'])) ?></td>
                                        <td><span class="badge bg-<?= $a['is_active'] ? 'success' : 'secondary' ?>"><?= $a['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                                        <td>
                                            <?php if ($a['is_active']): ?>
                                            <form method="POST" action="deactivate_alert.php" class="d-inline" onsubmit="return confirm('Deactivate this alert?')">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                <input type="hidden" name="alert_id" value="<?= $a['alert_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i></button>
                                            </form>
                                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('title').addEventListener('input', function(){
    document.getElementById('title-count').textContent = this.value.length;
});
document.getElementById('previewBtn').addEventListener('click', () => {
    const title = document.getElementById('title').value.trim();
    const msg   = document.querySelector('textarea[name=message]').value.trim();
    const sev   = document.querySelector('input[name=severity]:checked')?.value || 'medium';
    const map   = {critical:{bg:'#FDDEDE',color:'#C00000',label:'🔴 CRITICAL'},high:{bg:'#FFF2CC',color:'#BF9000',label:'🟡 HIGH'},medium:{bg:'#EBF3FB',color:'#2E75B6',label:'🔵 MEDIUM'},low:{bg:'#E2EFDA',color:'#375623',label:'🟢 LOW'}};
    const cfg   = map[sev];
    const box   = document.getElementById('preview-box');
    box.style.background = cfg.bg; box.style.borderColor = cfg.color;
    document.getElementById('preview-severity').style.color = cfg.color;
    document.getElementById('preview-severity').textContent = cfg.label;
    document.getElementById('preview-title').textContent    = title || '(no title)';
    document.getElementById('preview-message').textContent  = msg   || '(no message)';
    box.classList.remove('d-none');
});
</script>
</body>
</html>
