<?php
require_once __DIR__ . '/../includes/init.php';
$csrf = generate_csrf();

// metriche reali
try {
  $propertyCount = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE deleted_at IS NULL")->fetchColumn();
  $leadCount = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE deleted_at IS NULL")->fetchColumn();
  $availableCount = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE deleted_at IS NULL AND status = 'available'")->fetchColumn();
  $soldCount = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE deleted_at IS NULL AND status = 'sold'")->fetchColumn();
  $leadsToday = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE deleted_at IS NULL AND DATE(created_at) = CURRENT_DATE()")->fetchColumn();

  $stmt = $pdo->query("SELECT id, title, city, price, status, main_image_path FROM properties WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 5");
  $recentProperties = $stmt->fetchAll();

  $stmt2 = $pdo->query("SELECT id, name, email, phone, status, source, created_at FROM leads WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 5");
  $recentLeads = $stmt2->fetchAll();
} catch (Exception $e) {
  $propertyCount = $leadCount = $availableCount = $soldCount = $leadsToday = 0;
  $recentProperties = $recentLeads = [];
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>DomusCore Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../includes/topbar.php'; ?>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <main style="margin-left:260px; padding-top:4px;">
    <div class="container-fluid py-1">
      <section class="hero gradient-shell mb-4">
        <div>
          <div class="badge-soft">Smart Real Estate OS</div>
          <h1 class="hero-title">DomusCore Control Room</h1>
          <p class="hero-sub">Pipeline, listing, vendite e lead in un unico pannello fluido.</p>
          <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="/public/properties.php">Nuova Proprietà</a>
            <a class="btn btn-outline-light" href="/public/leads.php">Gestisci Lead</a>
          </div>
        </div>
        <div class="hero-glow"></div>
      </section>

      <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card metric-card">
            <div class="card-body">
              <div class="metric-label">Proprietà attive</div>
              <div class="metric-value"><?php echo $propertyCount; ?></div>
              <div class="metric-trend text-success">Disponibili</div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card metric-card">
            <div class="card-body">
              <div class="metric-label">Lead totali</div>
              <div class="metric-value"><?php echo $leadCount; ?></div>
              <div class="metric-trend text-info">Oggi: <?php echo $leadsToday; ?></div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card metric-card">
            <div class="card-body">
              <div class="metric-label">Disponibili</div>
              <div class="metric-value"><?php echo $availableCount; ?></div>
              <div class="metric-trend text-muted">Stato available</div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card metric-card">
            <div class="card-body">
              <div class="metric-label">Vendute</div>
              <div class="metric-value"><?php echo $soldCount; ?></div>
              <div class="metric-trend text-muted">Stato sold</div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12 col-xl-7">
          <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span>Pipeline veloce</span>
              <a href="/public/properties.php" class="text-muted small">Vedi tutte</a>
            </div>
            <div class="card-body">
              <div class="list-group list-group-flush glass-list">
                <?php if ($recentProperties): ?>
                  <?php foreach ($recentProperties as $prop): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                      <div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($prop['title']); ?></div>
                        <div class="text-muted small"><?php echo htmlspecialchars($prop['city']); ?> • € <?php echo number_format((float)$prop['price'], 0, ',', '.'); ?> • <?php echo htmlspecialchars($prop['status']); ?></div>
                      </div>
                      <span class="badge bg-info-subtle text-info"><?php echo htmlspecialchars($prop['status']); ?></span>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="list-group-item text-muted small">Nessuna proprietà recente</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-xl-5">
          <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span>Azioni rapide</span>
              <span class="text-muted small">Team</span>
            </div>
            <div class="card-body">
              <div class="d-grid gap-2 mb-3">
                <a class="btn btn-primary w-100" href="/public/properties.php">Crea annuncio</a>
                <a class="btn btn-outline-light w-100" href="/public/leads.php">Importa lead</a>
                <a class="btn btn-outline-light w-100" href="/public/visits.php">Pianifica visita</a>
                <a class="btn btn-outline-light w-100" href="/public/analytics.php">Apri analytics</a>
              </div>
              <div class="list-group list-group-flush">
                <?php if ($recentLeads): ?>
                  <?php foreach ($recentLeads as $lead): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                      <div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($lead['name']); ?></div>
                        <div class="text-muted small"><?php echo htmlspecialchars($lead['email'] ?: $lead['phone']); ?> • <?php echo htmlspecialchars($lead['source']); ?></div>
                      </div>
                      <span class="badge bg-primary-subtle text-primary"><?php echo htmlspecialchars($lead['status']); ?></span>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="list-group-item text-muted small">Nessun lead recente</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/app.js"></script>
</body>
</html>
