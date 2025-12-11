<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['superadmin','agency_admin','agent','accountant']);
$csrf = generate_csrf();

$errors = [];
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'create';
  $csrfPost = $_POST['csrf'] ?? '';
  if (!check_csrf($csrfPost)) { $errors[] = 'CSRF non valido'; }
  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$errors && $id) {
      $stmt = $pdo->prepare("UPDATE leads SET deleted_at = NOW() WHERE id = ? AND (agency_id = ? OR ? IN ('superadmin'))");
      $stmt->execute([$id, $_SESSION['agency_id'] ?? 0, current_user_role()]);
      if ($stmt->rowCount()) {
        log_activity('lead_deleted', ['lead_id'=>$id]);
        $flash = 'Lead eliminato';
      } else { $errors[] = 'Eliminazione non consentita'; }
    }
  } elseif ($action === 'restore') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$errors && $id) {
      $stmt = $pdo->prepare("UPDATE leads SET deleted_at = NULL WHERE id = ? AND (agency_id = ? OR ? IN ('superadmin'))");
      $stmt->execute([$id, $_SESSION['agency_id'] ?? 0, current_user_role()]);
      if ($stmt->rowCount()) {
        log_activity('lead_restored', ['lead_id'=>$id]);
        $flash = 'Lead ripristinato';
      } else { $errors[] = 'Ripristino non consentito'; }
    }
  } else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $source = trim($_POST['source'] ?? '');
    $propertyId = (int)($_POST['property_id'] ?? 0);
    $statusPost = trim($_POST['status'] ?? 'new');
    if ($name === '') { $errors[] = 'Nome obbligatorio'; }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email non valida'; }
    if (!$errors) {
      if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE leads SET name=?, email=?, phone=?, source=?, property_id=?, status=? WHERE id=? AND (agency_id = ? OR ? IN ('superadmin'))");
        $stmt->execute([$name, $email, $phone, $source, $propertyId ?: null, $statusPost, $id, $_SESSION['agency_id'] ?? 0, current_user_role()]);
        if ($stmt->rowCount()) {
          log_activity('lead_updated', ['lead_id'=>$id]);
          $flash = 'Lead aggiornato';
        } else { $errors[] = 'Aggiornamento non consentito'; }
      } else {
        $stmt = $pdo->prepare("INSERT INTO leads (agency_id, name, email, phone, source, property_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['agency_id'] ?? null, $name, $email, $phone, $source, $propertyId ?: null, $statusPost]);
        $leadId = $pdo->lastInsertId();
        log_activity('lead_created', ['lead_id'=>$leadId]);
        $flash = 'Lead creato';
      }
    }
  }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$search = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$showDeleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] === '1';

$where = [];
$params = [];
if ($search !== '') { $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($status !== '') { $where[] = 'status = ?'; $params[] = $status; }
$byAgency = $_SESSION['agency_id'] ?? null;
if ($byAgency && current_user_role() !== 'superadmin') { $where[] = 'agency_id = ?'; $params[] = $byAgency; }
if (!$showDeleted) { $where[] = 'deleted_at IS NULL'; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS id, name, email, phone, status, source, property_id, created_at, deleted_at FROM leads $whereSql ORDER BY created_at DESC LIMIT :lim OFFSET :off");
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

  <main style="margin-left:260px; padding-top:70px;">
    <div class="container-fluid">
      <div class="d-flex align-items-center mb-3">
        <h1 class="h3 mb-0">Leads</h1>
        <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#leadModal">Nuovo lead</button>
      </div>

      <?php if ($flash): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) { echo '<div>'.htmlspecialchars($e).'</div>'; } ?></div><?php endif; ?>

      <div class="card mb-3">
        <div class="card-header d-flex align-items-center">
          <div>Elenco</div>
          <form class="ms-auto d-flex gap-2 flex-wrap" method="get">
            <input class="form-control form-control-sm" style="max-width:180px" type="text" name="q" placeholder="Cerca" value="<?php echo htmlspecialchars($search); ?>">
            <select class="form-select form-select-sm" name="status" style="max-width:150px">
              <option value="">Tutti</option>
              <?php foreach (['new','contacted','qualified','lost','won'] as $st): ?>
                <option value="<?php echo $st; ?>" <?php if ($status === $st) echo 'selected'; ?>><?php echo $st; ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-check form-check-sm align-self-center">
              <input class="form-check-input" type="checkbox" id="showDeletedLeads" name="show_deleted" value="1" <?php if ($showDeleted) echo 'checked'; ?>>
              <label class="form-check-label small" for="showDeletedLeads">Mostra eliminati</label>
            </div>
            <button class="btn btn-sm btn-outline-secondary" type="submit">Filtra</button>
          </form>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead><tr><th>Nome</th><th>Email</th><th>Telefono</th><th>Stato</th><th>Fonte</th><th>Prop.</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($leads as $row): ?>
              <tr class="<?php echo $row['deleted_at'] ? 'table-danger' : ''; ?>">
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td><?php echo htmlspecialchars($row['source']); ?></td>
                <td><?php echo (int)($row['property_id'] ?? 0); ?></td>
                <td class="text-end d-flex gap-2 justify-content-end">
                  <?php if (!$row['deleted_at']): ?>
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#leadModal"
                      data-id="<?php echo (int)$row['id']; ?>"
                      data-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>"
                      data-email="<?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?>"
                      data-phone="<?php echo htmlspecialchars($row['phone'], ENT_QUOTES); ?>"
                      data-source="<?php echo htmlspecialchars($row['source'], ENT_QUOTES); ?>"
                      data-status="<?php echo htmlspecialchars($row['status'], ENT_QUOTES); ?>"
                      data-property="<?php echo (int)($row['property_id'] ?? 0); ?>"
                    >Modifica</button>
                    <form method="post" onsubmit="return confirm('Eliminare il lead?');">
                      <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit">Elimina</button>
                    </form>
                  <?php else: ?>
                    <form method="post" onsubmit="return confirm('Ripristinare il lead?');">
                      <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                      <input type="hidden" name="action" value="restore">
                      <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                      <button class="btn btn-sm btn-success" type="submit">Ripristina</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$leads): ?>
              <tr><td colspan="7" class="text-center text-muted">Nessun lead</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer d-flex justify-content-between">
          <div>Totale: <?php echo $total; ?></div>
          <div class="btn-group btn-group-sm">
            <?php for ($p=1; $p <= $pages; $p++): ?>
              <a class="btn <?php echo $p === $page ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="?page=<?php echo $p; ?>&status=<?php echo urlencode($status); ?>&q=<?php echo urlencode($search); ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">Import CSV (stub)</div>
        <div class="card-body">
          <form>
            <div class="row g-2 align-items-center">
              <div class="col-auto"><input type="file" class="form-control" name="csv"></div>
              <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Importa</button></div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal nuovo lead -->
  <div class="modal fade" id="leadModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" id="leadForm">
          <div class="modal-header"><h5 class="modal-title">Nuovo lead</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
            <input type="hidden" name="action" id="leadAction" value="create">
            <input type="hidden" name="id" id="leadId" value="">
            <div class="mb-2"><label class="form-label">Nome</label><input class="form-control" name="name" id="leadName" required></div>
            <div class="mb-2"><label class="form-label">Email</label><input class="form-control" name="email" id="leadEmail" type="email"></div>
            <div class="mb-2"><label class="form-label">Telefono</label><input class="form-control" name="phone" id="leadPhone"></div>
            <div class="mb-2"><label class="form-label">Fonte</label><input class="form-control" name="source" id="leadSource"></div>
            <div class="mb-2"><label class="form-label">Proprietà (id)</label><input class="form-control" name="property_id" id="leadProperty" type="number"></div>
            <div class="mb-2"><label class="form-label">Stato</label><select class="form-select" name="status" id="leadStatus">
              <?php foreach (['new','contacted','qualified','lost','won'] as $st): ?>
                <option value="<?php echo $st; ?>"><?php echo $st; ?></option>
              <?php endforeach; ?>
            </select></div>
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

    document.querySelectorAll('button[data-bs-target="#leadModal"]').forEach(btn => {
      btn.addEventListener('click', () => {
        const isEdit = btn.hasAttribute('data-id');
        if (isEdit) {
          leadAction.value = 'update';
          leadId.value = btn.getAttribute('data-id');
          leadName.value = btn.getAttribute('data-name') || '';
          leadEmail.value = btn.getAttribute('data-email') || '';
          leadPhone.value = btn.getAttribute('data-phone') || '';
          leadSource.value = btn.getAttribute('data-source') || '';
          leadProperty.value = btn.getAttribute('data-property') || '';
          leadStatus.value = btn.getAttribute('data-status') || 'new';
        } else {
          leadAction.value = 'create';
          leadId.value = '';
          leadForm.reset();
        }
      });
    });
  </script>
</body>
</html>
