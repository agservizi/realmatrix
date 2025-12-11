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
  $when = trim($_POST['data_appuntamento'] ?? '');
  $note = trim($_POST['note'] ?? '');
  if ($title === '') { $errors[] = 'Titolo obbligatorio'; }
  if ($when === '') { $errors[] = 'Data/ora obbligatorie'; }
  if (!$errors) {
    $stmt = $pdo->prepare("INSERT INTO appuntamenti (agency_id, titolo, cliente_id, immobile_id, data_appuntamento, note, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$_SESSION['agency_id'] ?? null, $title, $clienteId ?: null, $immobileId ?: null, $when, $note]);
    log_activity('visit_created', ['appointment_id' => $pdo->lastInsertId()]);
    $flash = 'Visita creata';
  }
}

$stmt = $pdo->query("SELECT id, titolo, cliente_id, immobile_id, data_appuntamento, note, created_at FROM appuntamenti ORDER BY data_appuntamento DESC LIMIT 50");
$visits = $stmt->fetchAll();
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

  <main style="margin-left:260px; padding-top:4px;">
    <div class="container-fluid py-2">
      <div class="d-flex align-items-center mb-3">
        <div>
          <div class="badge-soft">Visite</div>
          <h1 class="h3 mb-0">Agenda visite</h1>
          <p class="text-muted small mb-0">Appuntamenti programmati per proprietà e clienti.</p>
        </div>
        <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#visitModal">Nuova visita</button>
      </div>

      <?php if ($flash): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?></div><?php endif; ?>

      <div class="card">
        <div class="card-header">Prossime visite</div>
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead><tr><th>Data</th><th>Titolo</th><th>Cliente ID</th><th>Immobile ID</th><th>Note</th></tr></thead>
            <tbody>
              <?php foreach ($visits as $v): ?>
                <tr>
                  <td><?php echo htmlspecialchars($v['data_appuntamento']); ?></td>
                  <td><?php echo htmlspecialchars($v['titolo']); ?></td>
                  <td><?php echo (int)$v['cliente_id']; ?></td>
                  <td><?php echo (int)$v['immobile_id']; ?></td>
                  <td class="text-truncate" style="max-width:240px;"><?php echo htmlspecialchars($v['note']); ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$visits): ?><tr><td colspan="5" class="text-center text-muted">Nessuna visita</td></tr><?php endif; ?>
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
        <form method="post">
          <div class="modal-header"><h5 class="modal-title">Nuova visita</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
            <div class="mb-2"><label class="form-label">Titolo</label><input class="form-control" name="titolo" required></div>
            <div class="mb-2"><label class="form-label">Data e ora</label><input class="form-control" type="datetime-local" name="data_appuntamento" required></div>
            <div class="mb-2"><label class="form-label">Cliente ID</label><input class="form-control" name="cliente_id" type="number"></div>
            <div class="mb-2"><label class="form-label">Immobile ID</label><input class="form-control" name="immobile_id" type="number"></div>
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
