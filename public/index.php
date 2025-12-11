<?php
require_once __DIR__ . '/../includes/init.php';
$csrf = generate_csrf();
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

  <main style="margin-left:260px; padding-top:12px;">
    <div class="container-fluid py-2">
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
              <div class="metric-value">128</div>
              <div class="metric-trend text-success">+12% vs mese scorso</div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card metric-card">
            <div class="card-body">
              <div class="metric-label">Lead aperti</div>
              <div class="metric-value">46</div>
              <div class="metric-trend text-warning">In follow-up</div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card metric-card">
            <div class="card-body">
              <div class="metric-label">Visite pianificate</div>
              <div class="metric-value">23</div>
              <div class="metric-trend text-info">Oggi +5</div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card metric-card">
            <div class="card-body">
              <div class="metric-label">Fatturato YTD</div>
              <div class="metric-value">€ 1.24M</div>
              <div class="metric-trend text-success">+8.4% YoY</div>
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
                <div class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <div class="fw-semibold">Attico Piazza Navona</div>
                    <div class="text-muted small">Roma • € 1.2M • In trattativa</div>
                  </div>
                  <span class="badge bg-success-subtle text-success">Hot</span>
                </div>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <div class="fw-semibold">Loft Brera</div>
                    <div class="text-muted small">Milano • € 850k • Visite in corso</div>
                  </div>
                  <span class="badge bg-info-subtle text-info">Visite</span>
                </div>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <div class="fw-semibold">Villa Posillipo</div>
                    <div class="text-muted small">Napoli • € 2.4M • Nuovo</div>
                  </div>
                  <span class="badge bg-primary-subtle text-primary">Nuovo</span>
                </div>
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
              <div class="d-grid gap-2">
                <a class="btn btn-primary w-100" href="/public/properties.php">Crea annuncio</a>
                <a class="btn btn-outline-light w-100" href="/public/leads.php">Importa lead</a>
                <a class="btn btn-outline-light w-100" href="/public/visits.php">Pianifica visita</a>
                <a class="btn btn-outline-light w-100" href="/public/analytics.php">Apri analytics</a>
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
