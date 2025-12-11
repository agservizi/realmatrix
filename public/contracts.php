<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['superadmin','agency_admin','agent']);
$csrf = generate_csrf();

$errors = [];
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrfPost = $_POST['csrf'] ?? '';
  if (!check_csrf($csrfPost)) { $errors[] = 'CSRF non valido'; }
  $title = trim($_POST['titolo'] ?? '');
  $clienteId = (int)($_POST['cliente_id'] ?? 0);
  $immobileId = (int)($_POST['immobile_id'] ?? 0);
  $valore = (float)($_POST['valore'] ?? 0);
  $stato = trim($_POST['stato'] ?? 'bozza');
  $pdfPath = trim($_POST['pdf_path'] ?? '');
  if ($title === '') { $errors[] = 'Titolo obbligatorio'; }
  if ($valore <= 0) { $errors[] = 'Valore deve essere > 0'; }
  if (!$errors) {
    $stmt = $pdo->prepare("INSERT INTO contratti (agency_id, titolo, cliente_id, immobile_id, valore, stato, pdf_path, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$_SESSION['agency_id'] ?? null, $title, $clienteId ?: null, $immobileId ?: null, $valore, $stato, $pdfPath ?: null]);
    log_activity('contract_created', ['contract_id' => $pdo->lastInsertId()]);
    $flash = 'Contratto creato';
  }
}

$stmt = $pdo->query("SELECT id, titolo, cliente_id, immobile_id, valore, stato, pdf_path, created_at FROM contratti ORDER BY created_at DESC LIMIT 50");
$contracts = $stmt->fetchAll();
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Contratti | DomusCore</title>
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
          <div class="badge-soft">Contratti</div>
          <h1 class="h3 mb-0">Gestione contratti</h1>
          <p class="text-muted small mb-0">Monitora stato, firma e scadenze.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#contractModal">Nuovo contratto</button>
      </div>

      <?php if ($flash): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?></div><?php endif; ?>

      <div class="card shadow-soft">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Elenco</span>
          <span class="text-muted small">Ultimi 50</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead><tr><th>ID</th><th>Titolo</th><th>Cliente ID</th><th>Immobile ID</th><th>Valore</th><th>Stato</th><th>PDF</th><th>Creato</th></tr></thead>
            <tbody>
              <?php foreach ($contracts as $c): ?>
                <tr>
                  <td><?php echo (int)$c['id']; ?></td>
                  <td><?php echo htmlspecialchars($c['titolo']); ?></td>
                  <td><?php echo (int)$c['cliente_id']; ?></td>
                  <td><?php echo (int)$c['immobile_id']; ?></td>
                  <td>€ <?php echo number_format((float)$c['valore'], 2, ',', '.'); ?></td>
                  <td><span class="badge bg-primary-subtle text-primary"><?php echo htmlspecialchars($c['stato']); ?></span></td>
                  <td><?php if ($c['pdf_path']) echo '<a href="'.htmlspecialchars($c['pdf_path']).'" target="_blank">PDF</a>'; ?></td>
                  <td><?php echo htmlspecialchars($c['created_at']); ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$contracts): ?><tr><td colspan="8" class="text-center text-muted">Nessun contratto</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <div class="modal fade" id="contractModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header"><h5 class="modal-title">Nuovo contratto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
            <div class="mb-2"><label class="form-label">Titolo</label><input class="form-control" name="titolo" required></div>
            <div class="mb-2"><label class="form-label">Cliente ID</label><input class="form-control" name="cliente_id" type="number"></div>
            <div class="mb-2"><label class="form-label">Immobile ID</label><input class="form-control" name="immobile_id" type="number"></div>
            <div class="mb-2"><label class="form-label">Valore</label><input class="form-control" name="valore" type="number" step="0.01" required></div>
            <div class="mb-2"><label class="form-label">Stato</label><input class="form-control" name="stato" placeholder="bozza/firma/chiuso"></div>
            <div class="mb-2"><label class="form-label">Percorso PDF</label><input class="form-control" name="pdf_path" placeholder="/storage/...pdf"></div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button><button type="submit" class="btn btn-primary">Salva</button></div>
        </form>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/app.js"></script>
</body>
</html>
