<?php
require_once __DIR__ . '/../includes/init.php';
$csrf = generate_csrf();
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Profilo | DomusCore</title>
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
          <div class="badge-soft">Profilo</div>
          <h1 class="h3 mb-0">Il tuo account</h1>
          <p class="text-muted small mb-0">Aggiorna i tuoi dati personali.</p>
        </div>
        <button class="btn btn-primary" type="submit" form="profile-form">Aggiorna</button>
      </div>
      <form id="profile-form" class="card shadow-soft" method="post" action="#">
        <div class="card-body">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nome</label>
              <input class="form-control" type="text" name="name" placeholder="Il tuo nome">
            </div>
            <div class="col-md-6">
              <label class="form-label">Ruolo</label>
              <input class="form-control" type="text" name="role" placeholder="Agente, Admin, ...">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input class="form-control" type="email" name="email" placeholder="email@dominio.it">
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefono</label>
              <input class="form-control" type="tel" name="phone" placeholder="+39 ...">
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
