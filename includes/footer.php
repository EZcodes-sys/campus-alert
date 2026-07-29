<?php if (($showNav ?? true)): ?></main><?php endif; ?>
<footer class="bg-dark text-light text-center py-3 mt-5">
  <div class="container">
    <small>&copy; <?= date('Y') ?> Campus Emergency Alert System — Built for campus safety.</small>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($loggedIn ?? false): ?>
<script>
  // Config consumed by assets/js/alert-notify.js
  window.CEAS = {
    baseUrl: <?= json_encode(BASE_URL) ?>,
    pollMs: 12000
  };
</script>
<script src="<?= BASE_URL ?>/assets/js/alert-notify.js"></script>
<?php endif; ?>
</body>
</html>
