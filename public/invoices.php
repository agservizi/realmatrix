<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['superadmin','agency_admin','accountant']);
$csrf = generate_csrf();

$errors = [];
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrfPost = $_POST['csrf'] ?? '';
  if (!check_csrf($csrfPost)) { $errors[] = 'CSRF non valido'; }
  $numero = trim($_POST['numero'] ?? '');
  $clienteId = (int)($_POST['cliente_id'] ?? 0);
  $importo = (float)($_POST['importo'] ?? 0);
  $stato = trim($_POST['stato'] ?? 'pending');
  $pdfPath = trim($_POST['pdf_path'] ?? '');
  if ($numero === '') { $errors[] = 'Numero obbligatorio'; }
  if ($importo <= 0) { $errors[] = 'Importo deve essere > 0'; }
  if (!$errors) {
    $stmt = $pdo->prepare("INSERT INTO fatture (agency_id, numero, cliente_id, importo, stato, pdf_path, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$_SESSION['agency_id'] ?? null, $numero, $clienteId ?: null, $importo, $stato, $pdfPath ?: null]);
    log_activity('invoice_created', ['invoice_id' => $pdo->lastInsertId()]);
    $flash = 'Fattura creata';
  }
}

$stmt = $pdo->query("SELECT id, numero, cliente_id, importo, stato, pdf_path, created_at FROM fatture ORDER BY created_at DESC LIMIT 50");
$invoices = $stmt->fetchAll();
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

  <main style="margin-left:260px; padding-top:4px;">
    <div class="container-fluid py-2">
      <div class="d-flex align-items-center mb-3">
        <div>
          <div class="badge-soft">Fatture</div>
          <h1 class="h3 mb-0">Fatture & Pagamenti</h1>
          <p class="text-muted small mb-0">Emissione, stato e PDF collegati.</p>
        </div>
        <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#invoiceModal">Nuova fattura</button>
      </div>

      <?php if ($flash): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?></div><?php endif; ?>

      <div class="card mb-3">
        <div class="card-header">Ultime fatture (50)</div>
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead><tr><th>ID</th><th>Numero</th><th>Cliente ID</th><th>Importo</th><th>Stato</th><th>PDF</th><th>Data</th></tr></thead>
            <tbody>
              <?php foreach ($invoices as $inv): ?>
                <tr>
                  <td><?php echo (int)$inv['id']; ?></td>
                  <td><?php echo htmlspecialchars($inv['numero']); ?></td>
                  <td><?php echo (int)$inv['cliente_id']; ?></td>
                  <td>€ <?php echo number_format((float)$inv['importo'], 2, ',', '.'); ?></td>
                  <td><span class="badge bg-primary-subtle text-primary"><?php echo htmlspecialchars($inv['stato']); ?></span></td>
                  <td><?php if ($inv['pdf_path']) echo '<a href="'.htmlspecialchars($inv['pdf_path']).'" target="_blank">PDF</a>'; ?></td>
                  <td><?php echo htmlspecialchars($inv['created_at']); ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$invoices): ?><tr><td colspan="7" class="text-center text-muted">Nessuna fattura</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-header">Pagamenti esterni</div>
        <div class="card-body text-muted">Integra gateway (Stripe/PayPal) tramite webhook e riconcilia qui.</div>
      </div>
    </div>
  </main>

  <!-- Modal nuova fattura -->
  <div class="modal fade" id="invoiceModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header"><h5 class="modal-title">Nuova fattura</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
            <div class="mb-2"><label class="form-label">Numero</label><input class="form-control" name="numero" required placeholder="INV-2025-001"></div>
            <div class="mb-2"><label class="form-label">Cliente ID</label><input class="form-control" name="cliente_id" type="number"></div>
            <div class="mb-2"><label class="form-label">Importo</label><input class="form-control" name="importo" type="number" step="0.01" required></div>
            <div class="mb-2"><label class="form-label">Stato</label><input class="form-control" name="stato" placeholder="pending/paid/overdue"></div>
            <div class="mb-2"><label class="form-label">Percorso PDF</label><input class="form-control" name="pdf_path" placeholder="/storage/...pdf"></div>
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
