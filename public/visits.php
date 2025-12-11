<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['superadmin','agency_admin','agent']);
$csrf = generate_csrf();

$errors = [];
$flash = '';

$today = date('Y-m-d');
$agencyId = $_SESSION['agency_id'] ?? null;
$isSuper = current_user_role() === 'superadmin';

// opzioni proprietà per select (ultime 120)
$propertyOptions = $pdo->query("SELECT id, title FROM properties WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 120")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'create';
  $csrfPost = $_POST['csrf'] ?? '';
  if (!check_csrf($csrfPost)) { $errors[] = 'CSRF non valido'; }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id && !$errors) {
      $stmt = $pdo->prepare("DELETE FROM appuntamenti WHERE id = ? AND (agency_id = ? OR ? = 'superadmin')");
      $stmt->execute([$id, $agencyId, current_user_role()]);
      if ($stmt->rowCount()) { log_activity('visit_deleted', ['appointment_id' => $id]); $flash = 'Visita eliminata'; }
      else { $errors[] = 'Eliminazione non consentita'; }
    }
  } elseif ($action === 'status_update') {
    $id = (int)($_POST['id'] ?? 0);
    $newStatus = trim($_POST['new_status'] ?? '');
    $allowedStatuses = ['scheduled','completed','cancelled'];
    if (!in_array($newStatus, $allowedStatuses, true)) { $errors[] = 'Stato non valido'; }
    if ($id && !$errors) {
      $stmt = $pdo->prepare("UPDATE appuntamenti SET status = ? WHERE id = ? AND (agency_id = ? OR ? = 'superadmin')");
      $stmt->execute([$newStatus, $id, $agencyId, current_user_role()]);
      if ($stmt->rowCount()) { log_activity('visit_status_updated', ['appointment_id' => $id, 'status' => $newStatus]); $flash = 'Stato aggiornato'; }
      else { $errors[] = 'Aggiornamento stato non consentito'; }
    }
  } else {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['titolo'] ?? '');
    $clienteId = (int)($_POST['cliente_id'] ?? 0);
    $immobileId = (int)($_POST['immobile_id'] ?? 0);
    $when = trim($_POST['data_appuntamento'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $statusPost = trim($_POST['status'] ?? 'scheduled');

    $allowedStatuses = ['scheduled','completed','cancelled'];

    if ($title === '') { $errors[] = 'Titolo obbligatorio'; }
    if ($when === '' || strtotime($when) === false) { $errors[] = 'Data/ora non valida'; }
    if (!in_array($statusPost, $allowedStatuses, true)) { $errors[] = 'Stato non valido'; }

    if (!$errors) {
      if ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE appuntamenti SET titolo=?, cliente_id=?, immobile_id=?, data_appuntamento=?, status=?, note=? WHERE id=? AND (agency_id = ? OR ? = 'superadmin')");
        $stmt->execute([$title, $clienteId ?: null, $immobileId ?: null, $when, $statusPost, $note ?: null, $id, $agencyId, current_user_role()]);
        if ($stmt->rowCount()) { log_activity('visit_updated', ['appointment_id' => $id]); $flash = 'Visita aggiornata'; }
        else { $errors[] = 'Aggiornamento non consentito'; }
      } else {
        $stmt = $pdo->prepare("INSERT INTO appuntamenti (agency_id, titolo, cliente_id, immobile_id, data_appuntamento, status, note, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$agencyId, $title, $clienteId ?: null, $immobileId ?: null, $when, $statusPost, $note ?: null]);
        log_activity('visit_created', ['appointment_id' => $pdo->lastInsertId()]);
        $flash = 'Visita creata';
      }
    }
  }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;
$search = trim($_GET['q'] ?? '');
$propertyFilter = (int)($_GET['property_id'] ?? 0);
$dateFrom = trim($_GET['from'] ?? '');
$dateTo = trim($_GET['to'] ?? '');
$scope = $_GET['scope'] ?? 'upcoming'; // upcoming|past|all
$statusFilter = trim($_GET['status'] ?? '');

$where = [];
$params = [];
if ($search !== '') { $where[] = '(a.titolo LIKE ? OR a.note LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($propertyFilter > 0) { $where[] = 'a.immobile_id = ?'; $params[] = $propertyFilter; }
if ($dateFrom !== '') { $where[] = 'DATE(a.data_appuntamento) >= ?'; $params[] = $dateFrom; }
if ($dateTo !== '') { $where[] = 'DATE(a.data_appuntamento) <= ?'; $params[] = $dateTo; }
if ($scope === 'upcoming') { $where[] = 'a.data_appuntamento >= NOW()'; }
elseif ($scope === 'past') { $where[] = 'a.data_appuntamento < NOW()'; }
if ($statusFilter !== '') { $where[] = 'a.status = ?'; $params[] = $statusFilter; }
if (!$isSuper && $agencyId) { $where[] = 'a.agency_id = ?'; $params[] = $agencyId; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS a.id, a.titolo, a.cliente_id, a.immobile_id, a.data_appuntamento, a.status, a.note, a.created_at, p.title AS property_title FROM appuntamenti a LEFT JOIN properties p ON a.immobile_id = p.id $whereSql ORDER BY a.data_appuntamento DESC LIMIT :lim OFFSET :off");
foreach ($params as $i => $val) { $stmt->bindValue($i + 1, $val); }
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$visits = $stmt->fetchAll();
$total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

// metriche rapide
$metricWhere = !$isSuper && $agencyId ? 'WHERE agency_id = :aid' : '';
$metricWhereAnd = $metricWhere ? ($metricWhere . ' AND ') : 'WHERE ';
$metricStmt = $pdo->prepare("SELECT
  (SELECT COUNT(*) FROM appuntamenti $metricWhere) AS total_all,
  (SELECT COUNT(*) FROM appuntamenti $metricWhereAnd DATE(data_appuntamento) = CURDATE()) AS today_cnt,
  (SELECT COUNT(*) FROM appuntamenti $metricWhereAnd data_appuntamento >= NOW()) AS upcoming_cnt,
  (SELECT COUNT(*) FROM appuntamenti $metricWhereAnd data_appuntamento < NOW()) AS past_cnt");
if (!$isSuper && $agencyId) { $metricStmt->bindValue(':aid', $agencyId, PDO::PARAM_INT); }
$metricStmt->execute();
$metrics = $metricStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_all'=>0,'today_cnt'=>0,'upcoming_cnt'=>0,'past_cnt'=>0];
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
        <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#visitModal" data-mode="create">Nuova visita</button>
      </div>

      <?php if ($flash): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?></div><?php endif; ?>

      <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><div class="card metric-card"><div class="card-body"><div class="metric-label">Totali</div><div class="metric-value"><?php echo (int)$metrics['total_all']; ?></div></div></div></div>
        <div class="col-6 col-md-3"><div class="card metric-card"><div class="card-body"><div class="metric-label">Oggi</div><div class="metric-value"><?php echo (int)$metrics['today_cnt']; ?></div></div></div></div>
        <div class="col-6 col-md-3"><div class="card metric-card"><div class="card-body"><div class="metric-label">Future</div><div class="metric-value"><?php echo (int)$metrics['upcoming_cnt']; ?></div></div></div></div>
        <div class="col-6 col-md-3"><div class="card metric-card"><div class="card-body"><div class="metric-label">Passate</div><div class="metric-value"><?php echo (int)$metrics['past_cnt']; ?></div></div></div></div>
      </div>

      <div class="card shadow-soft">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
          <span>Elenco visite</span>
          <form class="ms-auto d-flex flex-wrap gap-2" method="get">
            <input class="form-control form-control-sm" style="max-width:160px" type="text" name="q" placeholder="Cerca titolo/note" value="<?php echo htmlspecialchars($search); ?>">
            <select class="form-select form-select-sm" name="property_id" style="max-width:180px">
              <option value="0">Tutte le proprietà</option>
              <?php foreach ($propertyOptions as $opt): ?>
                <option value="<?php echo (int)$opt['id']; ?>" <?php if ($propertyFilter === (int)$opt['id']) echo 'selected'; ?>><?php echo '#'.$opt['id'].' - '.htmlspecialchars($opt['title']); ?></option>
              <?php endforeach; ?>
            </select>
            <input class="form-control form-control-sm" style="max-width:150px" type="date" name="from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="Da">
            <input class="form-control form-control-sm" style="max-width:150px" type="date" name="to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="A">
            <select class="form-select form-select-sm" name="status" style="max-width:150px">
              <option value="">Tutti gli stati</option>
              <option value="scheduled" <?php if ($statusFilter === 'scheduled') echo 'selected'; ?>>scheduled</option>
              <option value="completed" <?php if ($statusFilter === 'completed') echo 'selected'; ?>>completed</option>
              <option value="cancelled" <?php if ($statusFilter === 'cancelled') echo 'selected'; ?>>cancelled</option>
            </select>
            <select class="form-select form-select-sm" name="scope" style="max-width:140px">
              <option value="upcoming" <?php if ($scope === 'upcoming') echo 'selected'; ?>>Future</option>
              <option value="past" <?php if ($scope === 'past') echo 'selected'; ?>>Passate</option>
              <option value="all" <?php if ($scope === 'all') echo 'selected'; ?>>Tutte</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit">Filtra</button>
          </form>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead><tr><th>Data</th><th>Titolo</th><th>Cliente ID</th><th>Proprietà</th><th>Stato</th><th>Note</th><th class="text-end">Azioni</th></tr></thead>
            <tbody>
              <?php foreach ($visits as $v): ?>
              <tr>
                <td class="small text-muted"><?php echo htmlspecialchars($v['data_appuntamento']); ?></td>
                <td class="fw-semibold"><?php echo htmlspecialchars($v['titolo']); ?></td>
                <td><?php echo (int)$v['cliente_id']; ?></td>
                <td><?php echo $v['property_title'] ? htmlspecialchars($v['property_title']) : ('#'.(int)$v['immobile_id']); ?></td>
                <td><span class="badge bg-primary-subtle text-primary"><?php echo htmlspecialchars($v['status'] ?: 'scheduled'); ?></span></td>
                <td class="text-truncate" style="max-width:260px;">
                  <?php echo htmlspecialchars($v['note']); ?>
                </td>
                <td class="text-end d-flex gap-2 justify-content-end">
                  <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#visitModal"
                    data-mode="edit"
                    data-id="<?php echo (int)$v['id']; ?>"
                    data-titolo="<?php echo htmlspecialchars($v['titolo'], ENT_QUOTES); ?>"
                    data-cliente="<?php echo (int)$v['cliente_id']; ?>"
                    data-immobile="<?php echo (int)$v['immobile_id']; ?>"
                    data-when="<?php echo $v['data_appuntamento'] ? htmlspecialchars(date('Y-m-d\\TH:i', strtotime($v['data_appuntamento']))) : ''; ?>"
                    data-note="<?php echo htmlspecialchars($v['note'], ENT_QUOTES); ?>"
                    data-status="<?php echo htmlspecialchars($v['status'] ?: 'scheduled', ENT_QUOTES); ?>"
                  >Modifica</button>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="action" value="status_update">
                    <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                    <input type="hidden" name="new_status" value="<?php echo $v['status'] === 'completed' ? 'cancelled' : 'completed'; ?>">
                    <button class="btn btn-sm btn-outline-success" type="submit"><?php echo $v['status'] === 'completed' ? 'Segna annullata' : 'Segna completata'; ?></button>
                  </form>
                  <form method="post" onsubmit="return confirm('Eliminare la visita?');">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit">Elimina</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$visits): ?><tr><td colspan="6" class="text-center text-muted">Nessuna visita</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
          <div>Totale: <?php echo $total; ?></div>
          <div class="btn-group btn-group-sm">
            <?php for ($p=1; $p <= $pages; $p++): ?>
              <a class="btn <?php echo $p === $page ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="?page=<?php echo $p; ?>&q=<?php echo urlencode($search); ?>&property_id=<?php echo $propertyFilter; ?>&from=<?php echo urlencode($dateFrom); ?>&to=<?php echo urlencode($dateTo); ?>&scope=<?php echo urlencode($scope); ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal visita -->
  <div class="modal fade" id="visitModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" id="visitForm">
          <div class="modal-header"><h5 class="modal-title">Visita</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="action" id="visitAction" value="create">
            <input type="hidden" name="id" id="visitId" value="">
            <div class="mb-2"><label class="form-label">Titolo</label><input class="form-control" name="titolo" id="visitTitolo" required></div>
            <div class="mb-2"><label class="form-label">Data e ora</label><input class="form-control" type="datetime-local" name="data_appuntamento" id="visitWhen" required></div>
            <div class="mb-2"><label class="form-label">Cliente ID</label><input class="form-control" name="cliente_id" id="visitCliente" type="number"></div>
            <div class="mb-2"><label class="form-label">Proprietà</label>
              <select class="form-select" name="immobile_id" id="visitImmobile">
                <option value="">Nessuna</option>
                <?php foreach ($propertyOptions as $opt): ?>
                  <option value="<?php echo (int)$opt['id']; ?>"><?php echo '#'.$opt['id'].' - '.htmlspecialchars($opt['title']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2"><label class="form-label">Stato</label>
              <select class="form-select" name="status" id="visitStatus">
                <option value="scheduled">scheduled</option>
                <option value="completed">completed</option>
                <option value="cancelled">cancelled</option>
              </select>
            </div>
            <div class="mb-2"><label class="form-label">Note</label><textarea class="form-control" name="note" id="visitNote"></textarea></div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button><button type="submit" class="btn btn-primary">Salva</button></div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/app.js"></script>
  <script>
    const visitForm = document.getElementById('visitForm');
    const visitAction = document.getElementById('visitAction');
    const visitId = document.getElementById('visitId');
    const visitTitolo = document.getElementById('visitTitolo');
    const visitWhen = document.getElementById('visitWhen');
    const visitCliente = document.getElementById('visitCliente');
    const visitImmobile = document.getElementById('visitImmobile');
    const visitStatus = document.getElementById('visitStatus');
    const visitNote = document.getElementById('visitNote');

    document.getElementById('visitModal').addEventListener('show.bs.modal', event => {
      const btn = event.relatedTarget;
      const mode = btn?.getAttribute('data-mode') || 'create';
      visitForm.reset();
      visitAction.value = mode === 'edit' ? 'update' : 'create';
      if (mode === 'edit') {
        visitId.value = btn.getAttribute('data-id') || '';
        visitTitolo.value = btn.getAttribute('data-titolo') || '';
        visitWhen.value = btn.getAttribute('data-when') || '';
        visitCliente.value = btn.getAttribute('data-cliente') || '';
        visitImmobile.value = btn.getAttribute('data-immobile') || '';
        visitStatus.value = btn.getAttribute('data-status') || 'scheduled';
        visitNote.value = btn.getAttribute('data-note') || '';
      } else {
        visitId.value = '';
        visitStatus.value = 'scheduled';
      }
    });
  </script>
</body>
</html>
