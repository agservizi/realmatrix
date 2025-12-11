<?php
require_once __DIR__ . '/../includes/init.php';
$csrf = generate_csrf();
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Visite | DomusCore</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../includes/topbar.php'; ?>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <main style="margin-left:260px; padding-top:70px;">
    <div class="container-fluid">
      <div class="d-flex align-items-center mb-3">
        <h1 class="h3 mb-0">Visite / Booking</h1>
        <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#visitModal">Nuova visita</button>
      </div>

      <div class="card mb-3">
        <div class="card-header">Calendario (placeholder)</div>
        <div class="card-body">Integrazione futura con calendario/drag-drop.</div>
      </div>

      <div class="card">
        <div class="card-header">Prossime visite</div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Data</th><th>Cliente</th><th>Proprietà</th><th>Agente</th><th>Stato</th><th></th></tr></thead>
            <tbody>
              <tr><td>2025-12-12 10:00</td><td>Mario Rossi</td><td>Via Roma 10</td><td>Giulia</td><td>scheduled</td><td class="text-end"><button class="btn btn-sm btn-outline-secondary">Dettagli</button></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal nuova visita -->
  <div class="modal fade" id="visitModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form>
          <div class="modal-header"><h5 class="modal-title">Nuova visita</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <div class="mb-2"><label class="form-label">Data e ora</label><input class="form-control" type="datetime-local" name="scheduled_at"></div>
            <div class="mb-2"><label class="form-label">Proprietà (id)</label><input class="form-control" name="property_id" type="number"></div>
            <div class="mb-2"><label class="form-label">Lead/Cliente</label><input class="form-control" name="lead_name"></div>
            <div class="mb-2"><label class="form-label">Agente</label><input class="form-control" name="agent"></div>
            <div class="mb-2"><label class="form-label">Note</label><textarea class="form-control" name="note"></textarea></div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button><button type="submit" class="btn btn-primary">Salva</button></div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/app.js"></script>
</body>
</html>
