<?php
/**
 * Shared header/navbar. Include after requiring auth.php.
 * Expects an optional $pageTitle variable to be set before including.
 */
$pageTitle = $pageTitle ?? APP_NAME;
$loggedIn  = isLoggedIn();
$role      = $_SESSION['user_role'] ?? null;
$bodyClass = $bodyClass ?? '';
$showNav   = $showNav ?? true; // set $showNav = false on auth pages that render their own layout
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitise($pageTitle) ?> | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/auth.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/animations.css" rel="stylesheet">
</head>
<body class="<?= sanitise($bodyClass) ?>">

<?php if ($loggedIn): ?>
<div id="ceasToastStack" aria-live="polite" aria-label="Alert notifications"></div>
<?php endif; ?>

<?php if ($showNav): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-danger">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/dashboard.php">🚨 CEAS</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if ($loggedIn): ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Safety</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/alerts.php">Alerts</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/incidents/report.php">Report Incident</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/incidents/my_incidents.php">My Incidents</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/contacts.php">Emergency Contacts</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/evacuation.php">Evacuation Info</a></li>
            </ul>
          </li>

          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/profile.php">My Profile</a></li>

          <?php if (in_array($role, ['admin', 'security_officer'], true)): ?>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Admin</a>
              <ul class="dropdown-menu">
                <?php if ($role === 'admin'): ?>
                  <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/dashboard.php">Admin Dashboard</a></li>
                  <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/users.php">Manage Users</a></li>
                  <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/alerts.php">Broadcast Alerts</a></li>
                  <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/contacts.php">Manage Contacts</a></li>
                <?php endif; ?>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/incidents.php">Manage Incidents</a></li>
              </ul>
            </li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav align-items-lg-center">
        <?php if ($loggedIn): ?>
          <li class="nav-item me-1">
            <button type="button" id="soundToggle" class="btn btn-sm btn-outline-light border-0" title="Toggle alert sound">🔊</button>
          </li>
          <li class="nav-item me-2">
            <a class="nav-link position-relative" href="<?= BASE_URL ?>/alerts.php" title="Active alerts">
              <span id="bellIcon" class="bell-icon">🔔</span>
              <span id="unreadBadge" class="badge rounded-pill bg-warning text-dark d-none" style="font-size:0.65rem;">0</span>
            </a>
          </li>
          <li class="nav-item d-flex align-items-center me-3">
            <span class="badge bg-light text-danger">
              <?= sanitise($_SESSION['user_name']) ?> — <?= sanitise(roleLabel($role)) ?>
            </span>
          </li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/auth/logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/auth/login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/auth/register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<?php endif; ?>

<?php if ($showNav): ?>
<main class="container py-4">
<?php else: ?>
<div class="container pt-3">
<?php endif; ?>
  <?php foreach (getFlash() as $type => $msg): ?>
    <div class="alert alert-<?= $type === 'error' ? 'danger' : sanitise($type) ?> alert-dismissible fade show" role="alert">
      <?= sanitise($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endforeach; ?>
<?php if (!$showNav): ?></div><?php endif; ?>
