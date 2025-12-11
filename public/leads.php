<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['superadmin','agency_admin','agent','accountant']);
$csrf = generate_csrf();

$errors = [];
$flash = '';
$allowedStatus = ['new','contacted','qualified','lost','won'];

// metriche rapide
$leadTotal = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE deleted_at IS NULL")->fetchColumn();
$leadToday = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE deleted_at IS NULL AND DATE(created_at) = CURRENT_DATE()")
  ->fetchColumn();
$leadContacted = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE deleted_at IS NULL AND status = 'contacted'")
  ->fetchColumn();
$leadWon = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE deleted_at IS NULL AND status = 'won'")
  ->fetchColumn();

// elenco sorgenti distinte
$sourceRows = $pdo->query("SELECT DISTINCT source FROM leads WHERE source IS NOT NULL AND source <> '' ORDER BY source ASC LIMIT 50")
  ->fetchAll(PDO::FETCH_COLUMN);

// elenco proprietà per select (ultime 100)
$propertyOptions = $pdo->query("SELECT id, title FROM properties WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 100")
  ->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'create';
  $csrfPost = $_POST['csrf'] ?? '';
  if (!check_csrf($csrfPost)) { $errors[] = 'CSRF non valido'; }

  if ($action === 'delete' || $action === 'restore') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id && !$errors) {
      $set = $action === 'delete' ? 'deleted_at = NOW()' : 'deleted_at = NULL';
      $stmt = $pdo->prepare("UPDATE leads SET $set WHERE id = ? AND (agency_id = ? OR ? = 'superadmin')");
      $stmt->execute([$id, $_SESSION['agency_id'] ?? 0, current_user_role()]);
      if ($stmt->rowCount()) {
        log_activity($action === 'delete' ? 'lead_deleted' : 'lead_restored', ['lead_id' => $id]);
        $flash = $action === 'delete' ? 'Lead eliminato' : 'Lead ripristinato';
      } else { $errors[] = 'Operazione non consentita'; }
    }
  } else {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $source = trim($_POST['source'] ?? '');
    $propertyId = (int)($_POST['property_id'] ?? 0);
    $statusPost = trim($_POST['status'] ?? 'new');

    if ($name === '') { $errors[] = 'Nome obbligatorio'; }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email non valida'; }
    if (!in_array($statusPost, $allowedStatus, true)) { $errors[] = 'Stato non valido'; }

    if (!$errors) {
      if ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE leads SET name=?, email=?, phone=?, source=?, property_id=?, status=? WHERE id=? AND (agency_id = ? OR ? = 'superadmin')");
        $stmt->execute([$name, $email ?: null, $phone ?: null, $source ?: null, $propertyId ?: null, $statusPost, $id, $_SESSION['agency_id'] ?? 0, current_user_role()]);
        if ($stmt->rowCount()) { log_activity('lead_updated', ['lead_id'=>$id]); $flash = 'Lead aggiornato'; }
        else { $errors[] = 'Aggiornamento non consentito'; }
      } else {
        $stmt = $pdo->prepare("INSERT INTO leads (agency_id, name, email, phone, source, property_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
          $_SESSION['agency_id'] ?? null,
          $name,
          $email ?: null,
          $phone ?: null,
          $source ?: null,
          $propertyId ?: null,
          $statusPost
        ]);
        log_activity('lead_created', ['lead_id'=>$pdo->lastInsertId()]);
        $flash = 'Lead creato';
      }
    }
  }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;
$search = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$sourceFilter = trim($_GET['source'] ?? '');
$showDeleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] === '1';

$where = [];
$params = [];
if ($search !== '') { $where[] = '(l.name LIKE ? OR l.email LIKE ? OR l.phone LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($status !== '') { $where[] = 'l.status = ?'; $params[] = $status; }
if ($sourceFilter !== '') { $where[] = 'l.source = ?'; $params[] = $sourceFilter; }
$byAgency = $_SESSION['agency_id'] ?? null;
if ($byAgency && current_user_role() !== 'superadmin') { $where[] = 'l.agency_id = ?'; $params[] = $byAgency; }
if (!$showDeleted) { $where[] = 'l.deleted_at IS NULL'; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS l.id, l.name, l.email, l.phone, l.status, l.source, l.property_id, l.created_at, l.deleted_at, p.title AS property_title FROM leads l LEFT JOIN properties p ON l.property_id = p.id $whereSql ORDER BY l.created_at DESC LIMIT :lim OFFSET :off");
foreach ($params as $i => $val) { $stmt->bindValue($i + 1, $val); }
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$leads = $stmt->fetchAll();
$total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Leads | DomusCore</title>
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
          <div class="badge-soft">Leads</div>
          <h1 class="h3 mb-0">Pipeline contatti</h1>
          <p class="text-muted small mb-0">Crea, qualifica e traccia i lead con stato e fonte.</p>
        </div>
        <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#leadModal" data-mode="create">Nuovo lead</button>
      </div>

      <?php if ($flash): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?></div><?php endif; ?>

      <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><div class="card metric-card"><div class="card-body"><div class="metric-label">Lead totali</div><div class="metric-value"><?php echo $leadTotal; ?></div></div></div></div>
        <div class="col-6 col-md-3"><div class="card metric-card"><div class="card-body"><div class="metric-label">Oggi</div><div class="metric-value"><?php echo $leadToday; ?></div></div></div></div>
        <div class="col-6 col-md-3"><div class="card metric-card"><div class="card-body"><div class="metric-label">Contattati</div><div class="metric-value"><?php echo $leadContacted; ?></div></div></div></div>
        <div class="col-6 col-md-3"><div class="card metric-card"><div class="card-body"><div class="metric-label">Vinti</div><div class="metric-value"><?php echo $leadWon; ?></div></div></div></div>
      </div>

      <div class="card shadow-soft">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
          <span>Elenco lead</span>
          <form class="ms-auto d-flex flex-wrap gap-2" method="get">
            <input class="form-control form-control-sm" style="max-width:160px" type="text" name="q" placeholder="Cerca nome/email/phone" value="<?php echo htmlspecialchars($search); ?>">
            <select class="form-select form-select-sm" name="status" style="max-width:140px">
              <option value="">Tutti gli stati</option>
              <?php foreach ($allowedStatus as $st): ?>
                <option value="<?php echo $st; ?>" <?php if ($status === $st) echo 'selected'; ?>><?php echo $st; ?></option>
              <?php endforeach; ?>
            </select>
            <select class="form-select form-select-sm" name="source" style="max-width:160px">
              <option value="">Tutte le fonti</option>
              <?php foreach ($sourceRows as $src): ?>
                <option value="<?php echo htmlspecialchars($src); ?>" <?php if ($sourceFilter === $src) echo 'selected'; ?>><?php echo htmlspecialchars($src); ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-check form-check-sm align-self-center">
              <input class="form-check-input" type="checkbox" id="showDeleted" name="show_deleted" value="1" <?php if ($showDeleted) echo 'checked'; ?>>
              <label class="form-check-label small" for="showDeleted">Mostra eliminati</label>
            </div>
            <button class="btn btn-sm btn-outline-secondary" type="submit">Filtra</button>
          </form>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead><tr><th>Nome</th><th>Contatti</th><th>Fonte</th><th>Stato</th><th>Proprietà</th><th>Creato</th><th class="text-end">Azioni</th></tr></thead>
            <tbody>
              <?php foreach ($leads as $row): ?>
              <tr class="<?php echo $row['deleted_at'] ? 'table-danger' : ''; ?>">
                <td class="fw-semibold"><?php echo htmlspecialchars($row['name']); ?></td>
                <td>
                  <div class="small text-muted">Email: <?php echo htmlspecialchars($row['email'] ?: '—'); ?></div>
                  <div class="small text-muted">Tel: <?php echo htmlspecialchars($row['phone'] ?: '—'); ?></div>
                </td>
                <td><?php echo htmlspecialchars($row['source'] ?: ''); ?></td>
                <td><span class="badge bg-primary-subtle text-primary"><?php echo htmlspecialchars($row['status']); ?></span></td>
                <td><?php echo $row['property_title'] ? htmlspecialchars($row['property_title']) : ('#'.(int)$row['property_id']); ?></td>
                <td class="small text-muted"><?php echo htmlspecialchars($row['created_at']); ?></td>
                <td class="text-end d-flex gap-2 justify-content-end">
                  <?php if (!$row['deleted_at']): ?>
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#leadModal"
                      data-mode="edit"
                      data-id="<?php echo (int)$row['id']; ?>"
                      data-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>"
                      data-email="<?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?>"
                      data-phone="<?php echo htmlspecialchars($row['phone'], ENT_QUOTES); ?>"
                      data-source="<?php echo htmlspecialchars($row['source'], ENT_QUOTES); ?>"
                      data-status="<?php echo htmlspecialchars($row['status'], ENT_QUOTES); ?>"
                      data-property="<?php echo (int)($row['property_id'] ?? 0); ?>"
                    >Modifica</button>
                    <form method="post" onsubmit="return confirm('Eliminare il lead?');">
                      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit">Elimina</button>
                    </form>
                  <?php else: ?>
                    <form method="post" onsubmit="return confirm('Ripristinare il lead?');">
                      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                      <input type="hidden" name="action" value="restore">
                      <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                      <button class="btn btn-sm btn-success" type="submit">Ripristina</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$leads): ?><tr><td colspan="7" class="text-center text-muted">Nessun lead</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
          <div>Totale: <?php echo $total; ?></div>
          <div class="btn-group btn-group-sm">
            <?php for ($p=1; $p <= $pages; $p++): ?>
              <a class="btn <?php echo $p === $page ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="?page=<?php echo $p; ?>&status=<?php echo urlencode($status); ?>&q=<?php echo urlencode($search); ?>&source=<?php echo urlencode($sourceFilter); ?>&show_deleted=<?php echo $showDeleted ? '1' : '0'; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal lead -->
  <div class="modal fade" id="leadModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" id="leadForm">
          <div class="modal-header"><h5 class="modal-title">Lead</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="action" id="leadAction" value="create">
            <input type="hidden" name="id" id="leadId" value="">
            <div class="mb-2"><label class="form-label">Nome</label><input class="form-control" name="name" id="leadName" required></div>
            <div class="mb-2"><label class="form-label">Email</label><input class="form-control" name="email" id="leadEmail" type="email"></div>
            <div class="mb-2"><label class="form-label">Telefono</label><input class="form-control" name="phone" id="leadPhone"></div>
            <div class="mb-2"><label class="form-label">Fonte</label><input class="form-control" name="source" id="leadSource" list="sourceList"></div>
            <datalist id="sourceList">
              <?php foreach ($sourceRows as $src): ?><option value="<?php echo htmlspecialchars($src); ?>"><?php echo htmlspecialchars($src); ?></option><?php endforeach; ?>
            </datalist>
            <div class="mb-2"><label class="form-label">Proprietà</label>
              <select class="form-select" name="property_id" id="leadProperty">
                <option value="">Nessuna</option>
                <?php foreach ($propertyOptions as $opt): ?>
                  <option value="<?php echo (int)$opt['id']; ?>"><?php echo '#'.$opt['id'].' - '.htmlspecialchars($opt['title']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2"><label class="form-label">Stato</label>
              <select class="form-select" name="status" id="leadStatus">
                <?php foreach ($allowedStatus as $st): ?><option value="<?php echo $st; ?>"><?php echo $st; ?></option><?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button><button type="submit" class="btn btn-primary">Salva</button></div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/app.js"></script>
  <script>
    const leadForm = document.getElementById('leadForm');
    const leadAction = document.getElementById('leadAction');
    const leadId = document.getElementById('leadId');
    const leadName = document.getElementById('leadName');
    const leadEmail = document.getElementById('leadEmail');
    const leadPhone = document.getElementById('leadPhone');
    const leadSource = document.getElementById('leadSource');
    const leadProperty = document.getElementById('leadProperty');
    const leadStatus = document.getElementById('leadStatus');

    document.getElementById('leadModal').addEventListener('show.bs.modal', event => {
      const btn = event.relatedTarget;
      const mode = btn?.getAttribute('data-mode') || (btn?.hasAttribute('data-id') ? 'edit' : 'create');
      leadForm.reset();
      leadAction.value = mode === 'edit' ? 'update' : 'create';
      if (mode === 'edit') {
        leadId.value = btn.getAttribute('data-id') || '';
        leadName.value = btn.getAttribute('data-name') || '';
        leadEmail.value = btn.getAttribute('data-email') || '';
        leadPhone.value = btn.getAttribute('data-phone') || '';
        leadSource.value = btn.getAttribute('data-source') || '';
        leadProperty.value = btn.getAttribute('data-property') || '';
        leadStatus.value = btn.getAttribute('data-status') || 'new';
      } else {
        leadId.value = '';
        leadStatus.value = 'new';
      }
    });
  </script>
</body>
</html>
