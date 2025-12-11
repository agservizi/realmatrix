<?php
require_once __DIR__ . '/../includes/init.php';
$csrf = generate_csrf();
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Fatture | DomusCore</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../includes/topbar.php'; ?>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <main style="margin-left:260px; padding-top:70px;">
    <div class="container-fluid">
      <div class="d-flex align-items-center mb-3">
        <h1 class="h3 mb-0">Fatture & Pagamenti</h1>
        <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#invoiceModal">Nuova fattura</button>
      </div>

      <div class="card mb-3">
        <div class="card-header">Fatture</div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>N.</th><th>Cliente</th><th>Importo</th><th>Stato</th><th>Scadenza</th><th></th></tr></thead>
            <tbody>
              <tr><td>INV-2025-001</td><td>Mario Rossi</td><td>€1.200,00</td><td><span class="badge bg-warning text-dark">pending</span></td><td>2025-12-31</td><td class="text-end"><button class="btn btn-sm btn-outline-secondary">Dettagli</button></td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-header">Pagamenti esterni (placeholder)</div>
        <div class="card-body">Integra gateway esterni (Stripe/PayPal) tramite webhook.</div>
      </div>
    </div>
  </main>

  <!-- Modal nuova fattura -->
  <div class="modal fade" id="invoiceModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form>
          <div class="modal-header"><h5 class="modal-title">Nuova fattura</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <div class="mb-2"><label class="form-label">Cliente</label><input class="form-control" name="customer"></div>
            <div class="mb-2"><label class="form-label">Importo</label><input class="form-control" name="amount" type="number" step="0.01"></div>
            <div class="mb-2"><label class="form-label">Scadenza</label><input class="form-control" name="due_date" type="date"></div>
            <div class="mb-2"><label class="form-label">Note</label><textarea class="form-control" name="note"></textarea></div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button><button type="submit" class="btn btn-primary">Crea</button></div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/app.js"></script>
</body>
</html>
