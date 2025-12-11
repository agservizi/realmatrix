<?php
require_once __DIR__ . '/../includes/init.php';
$csrf = generate_csrf();
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Impostazioni | DomusCore</title>
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
          <div class="badge-soft">Impostazioni</div>
          <h1 class="h3 mb-0">Preferenze account</h1>
          <p class="text-muted small mb-0">Profilo, sicurezza e notifiche.</p>
        </div>
        <button class="btn btn-primary" type="submit" form="settings-form">Salva</button>
      </div>
      <form id="settings-form" class="card shadow-soft" method="post" action="#">
        <div class="card-body">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nome</label>
              <input class="form-control" type="text" name="name" placeholder="Il tuo nome">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input class="form-control" type="email" name="email" placeholder="email@dominio.it">
            </div>
            <div class="col-md-6">
              <label class="form-label">Password</label>
              <input class="form-control" type="password" name="password" placeholder="••••••••">
            </div>
            <div class="col-md-6">
              <label class="form-label">Notifiche</label>
              <select class="form-select" name="notifications">
                <option value="all">Tutte</option>
                <option value="mentions">Solo menzioni</option>
                <option value="none">Nessuna</option>
              </select>
            </div>
          </div>
        </div>
      </form>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/app.js"></script>
</body>
</html>
