<?php
require_once __DIR__ . '/../includes/init.php';
$csrf = generate_csrf();

try {
  $propTotal = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE deleted_at IS NULL")->fetchColumn();
  $propAvailable = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE deleted_at IS NULL AND status='available'")->fetchColumn();
  $propSold = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE deleted_at IS NULL AND status='sold'")->fetchColumn();
  $leadTotal = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE deleted_at IS NULL")->fetchColumn();
  $leadToday = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE deleted_at IS NULL AND DATE(created_at)=CURRENT_DATE()")->fetchColumn();
  $byStatus = $pdo->query("SELECT status, COUNT(*) c FROM leads WHERE deleted_at IS NULL GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
  $bySource = $pdo->query("SELECT COALESCE(source,'n/d') s, COUNT(*) c FROM leads WHERE deleted_at IS NULL GROUP BY s ORDER BY c DESC LIMIT 5")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
  $propTotal = $propAvailable = $propSold = $leadTotal = $leadToday = 0;
  $byStatus = $bySource = [];
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Analytics | DomusCore</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../includes/topbar.php'; ?>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <main style="margin-left:260px; padding-top:4px;">
    <div class="container-fluid py-2">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <div class="badge-soft">Analytics</div>
          <h1 class="h3 mb-0">Performance e funnel</h1>
          <p class="text-muted small mb-0">Monitoraggio conversioni, listing e lead in tempo reale.</p>
        </div>
        <a class="btn btn-primary" href="/public/api/export.php?type=properties">Esporta report</a>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card metric-card h-100">
            <div class="card-body">
              <div class="metric-label">Proprietà totali</div>
              <div class="metric-value"><?php echo $propTotal; ?></div>
              <div class="metric-trend text-muted">Disponibili: <?php echo $propAvailable; ?> | Vendute: <?php echo $propSold; ?></div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card metric-card h-100">
            <div class="card-body">
              <div class="metric-label">Lead totali</div>
              <div class="metric-value"><?php echo $leadTotal; ?></div>
              <div class="metric-trend text-info">Oggi: <?php echo $leadToday; ?></div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card h-100">
            <div class="card-header">Lead per stato</div>
            <div class="card-body">
              <?php if ($byStatus): ?>
                <?php foreach ($byStatus as $st => $cnt): ?>
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted"><?php echo htmlspecialchars($st); ?></span>
                    <span class="fw-semibold"><?php echo (int)$cnt; ?></span>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="text-muted small">Nessun dato</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card h-100">
            <div class="card-header">Top sorgenti lead</div>
            <div class="card-body">
              <?php if ($bySource): ?>
                <?php foreach ($bySource as $src => $cnt): ?>
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted"><?php echo htmlspecialchars($src); ?></span>
                    <span class="fw-semibold"><?php echo (int)$cnt; ?></span>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="text-muted small">Nessun dato</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-soft">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Esporta</span>
          <span class="text-muted small">CSV dalle API di export</span>
        </div>
        <div class="card-body d-flex gap-2 flex-wrap">
          <a class="btn btn-outline-light" href="/public/api/export.php?type=properties">Esporta proprietà</a>
          <a class="btn btn-outline-light" href="/public/api/export.php?type=leads">Esporta lead</a>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/app.js"></script>
</body>
</html>
