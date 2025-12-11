<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['superadmin','agency_admin','agent']);
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
      $stmt = $pdo->prepare("UPDATE properties SET deleted_at = NOW() WHERE id = ? AND (agency_id = ? OR ? IN ('superadmin'))");
      $stmt->execute([$id, $_SESSION['agency_id'] ?? 0, current_user_role()]);
      if ($stmt->rowCount()) {
        log_activity('property_deleted', ['property_id' => $id]);
        $flash = 'Proprietà eliminata';
      } else {
        $errors[] = 'Eliminazione non consentita';
      }
    }
  } elseif ($action === 'restore') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$errors && $id) {
      $stmt = $pdo->prepare("UPDATE properties SET deleted_at = NULL WHERE id = ? AND (agency_id = ? OR ? IN ('superadmin'))");
      $stmt->execute([$id, $_SESSION['agency_id'] ?? 0, current_user_role()]);
      if ($stmt->rowCount()) {
        log_activity('property_restored', ['property_id' => $id]);
        $flash = 'Proprietà ripristinata';
      } else { $errors[] = 'Ripristino non consentito'; }
    }
  } else {
    $title = trim($_POST['title'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = $_POST['status'] ?? 'available';
    $imagePath = trim($_POST['image_path'] ?? '');
    if ($title === '') { $errors[] = 'Titolo obbligatorio'; }
    if ($price <= 0) { $errors[] = 'Prezzo deve essere > 0'; }
    if (!$errors) {
      if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE properties SET title=?, description=?, address=?, city=?, price=?, status=?, main_image_path=? WHERE id=? AND (agency_id=? OR ? IN ('superadmin'))");
        $stmt->execute([$title, $description, $address, $city, $price, $status, $imagePath ?: null, $id, $_SESSION['agency_id'] ?? 0, current_user_role()]);
        if ($stmt->rowCount()) {
          log_activity('property_updated', ['property_id' => $id]);
          $flash = 'Proprietà aggiornata';
        } else { $errors[] = 'Aggiornamento non consentito'; }
      } else {
        $stmt = $pdo->prepare("INSERT INTO properties (agency_id, title, description, address, city, price, status, main_image_path, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
          $_SESSION['agency_id'] ?? null,
          $title,
          $description,
          $address,
          $city,
          $price,
          $status,
          $imagePath ?: null,
          current_user_id()
        ]);
        $newId = $pdo->lastInsertId();
        log_activity('property_created', ['property_id' => $newId]);
        $flash = 'Proprietà creata';
      }
    }
  }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$minPrice = (float)($_GET['min_price'] ?? 0);
$maxPrice = (float)($_GET['max_price'] ?? 0);
$cityFilter = trim($_GET['city'] ?? '');

$where = [];
$params = [];
if ($statusFilter !== '') { $where[] = 'status = ?'; $params[] = $statusFilter; }
if ($search !== '') { $where[] = '(title LIKE ? OR city LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
$byAgency = $_SESSION['agency_id'] ?? null;
if ($byAgency && current_user_role() !== 'superadmin') {
  $where[] = 'agency_id = ?';
  $params[] = $byAgency;
}
if ($minPrice > 0) { $where[] = 'price >= ?'; $params[] = $minPrice; }
if ($maxPrice > 0) { $where[] = 'price <= ?'; $params[] = $maxPrice; }
if ($cityFilter !== '') { $where[] = 'city LIKE ?'; $params[] = "%$cityFilter%"; }
$showDeleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] === '1';
if (!$showDeleted) {
  $where[] = 'deleted_at IS NULL';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS id, title, city, price, status, agency_id, main_image_path, deleted_at, address, description FROM properties $whereSql ORDER BY created_at DESC LIMIT :lim OFFSET :off");
foreach ($params as $i => $val) {
  $stmt->bindValue($i + 1, $val);
}
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$properties = $stmt->fetchAll();
$total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Proprietà | DomusCore</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../includes/topbar.php'; ?>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <main style="margin-left:260px; padding-top:70px;">
    <div class="container-fluid">
      <div class="d-flex align-items-center mb-3">
        <h1 class="h3 mb-0">Proprietà</h1>
        <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#propertyModal">Nuova proprietà</button>
      </div>

      <?php if ($flash): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <?php foreach ($errors as $e): ?><div><?php echo htmlspecialchars($e); ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="card mb-3">
        <div class="card-header d-flex align-items-center">
          <div>Elenco</div>
          <form class="ms-auto d-flex gap-2 flex-wrap" method="get">
            <input class="form-control form-control-sm" style="max-width:160px" type="text" name="q" placeholder="Cerca" value="<?php echo htmlspecialchars($search); ?>">
            <input class="form-control form-control-sm" style="max-width:130px" type="text" name="city" placeholder="Città" value="<?php echo htmlspecialchars($cityFilter); ?>">
            <input class="form-control form-control-sm" style="max-width:110px" type="number" step="0.01" name="min_price" placeholder="Prezzo min" value="<?php echo $minPrice ?: ''; ?>">
            <input class="form-control form-control-sm" style="max-width:110px" type="number" step="0.01" name="max_price" placeholder="Prezzo max" value="<?php echo $maxPrice ?: ''; ?>">
            <select class="form-select form-select-sm" name="status" style="max-width:150px">
              <option value="">Tutti gli stati</option>
              <?php foreach (['available','booked','rented','sold'] as $st): ?>
                <option value="<?php echo $st; ?>" <?php if ($statusFilter === $st) echo 'selected'; ?>><?php echo $st; ?></option>
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
            <thead>
              <tr><th>Img</th><th>Titolo</th><th>Città</th><th>Prezzo</th><th>Stato</th><th>Agenzia</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($properties as $row): ?>
              <tr class="<?php echo $row['deleted_at'] ? 'table-danger' : ''; ?>">
                <td style="width:60px;">
                  <?php if (!empty($row['main_image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($row['main_image_path']); ?>" alt="" style="width:56px;height:40px;object-fit:cover;">
                  <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['city']); ?></td>
                <td>€<?php echo number_format((float)$row['price'], 2, ',', '.'); ?></td>
                <td><span class="badge bg-<?php echo $row['status']==='available'?'success':($row['status']==='sold'?'secondary':'warning'); ?> text-<?php echo $row['status']==='available'?'light':'dark'; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                <td><?php echo (int)$row['agency_id']; ?></td>
                <td class="text-end d-flex gap-2 justify-content-end">
                  <?php if (!$row['deleted_at']): ?>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#shareModal" data-prop="<?php echo (int)$row['id']; ?>">Condividi</button>
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#propertyModal"
                      data-id="<?php echo (int)$row['id']; ?>"
                      data-title="<?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>"
                      data-price="<?php echo htmlspecialchars($row['price']); ?>"
                      data-city="<?php echo htmlspecialchars($row['city'], ENT_QUOTES); ?>"
                      data-address="<?php echo htmlspecialchars($row['address'] ?? '', ENT_QUOTES); ?>"
                      data-status="<?php echo htmlspecialchars($row['status'], ENT_QUOTES); ?>"
                      data-description="<?php echo htmlspecialchars($row['description'] ?? '', ENT_QUOTES); ?>"
                      data-image="<?php echo htmlspecialchars($row['main_image_path'] ?? '', ENT_QUOTES); ?>"
                    >Modifica</button>
                    <form method="post" onsubmit="return confirm('Eliminare la proprietà?');">
                      <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit">Elimina</button>
                    </form>
                  <?php else: ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('Ripristinare la proprietà?');">
                      <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                      <input type="hidden" name="action" value="restore">
                      <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                      <button class="btn btn-sm btn-success" type="submit">Ripristina</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$properties): ?>
              <tr><td colspan="6" class="text-center text-muted">Nessuna proprietà trovata</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer d-flex justify-content-between">
          <div>Totale: <?php echo $total; ?></div>
          <div class="btn-group btn-group-sm">
            <?php for ($p=1; $p <= $pages; $p++): ?>
              <a class="btn <?php echo $p === $page ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="?page=<?php echo $p; ?>&status=<?php echo urlencode($statusFilter); ?>&q=<?php echo urlencode($search); ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal nuova proprietà -->
  <div class="modal fade" id="propertyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="post" id="propertyForm">
          <div class="modal-header"><h5 class="modal-title">Nuova proprietà</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body row g-3">
            <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
            <input type="hidden" name="action" id="propAction" value="create">
            <input type="hidden" name="id" id="propId" value="">
            <div class="col-md-6"><label class="form-label">Titolo</label><input class="form-control" name="title" id="propTitle" required></div>
            <div class="col-md-6"><label class="form-label">Prezzo</label><input class="form-control" name="price" id="propPrice" type="number" step="0.01" required></div>
            <div class="col-md-12"><label class="form-label">Descrizione</label><textarea class="form-control" rows="3" name="description" id="propDesc"></textarea></div>
            <div class="col-md-6"><label class="form-label">Città</label><input class="form-control" name="city" id="propCity"></div>
            <div class="col-md-6"><label class="form-label">Indirizzo</label><input class="form-control" name="address" id="propAddress"></div>
            <div class="col-md-6"><label class="form-label">Stato</label><select class="form-select" name="status" id="propStatus"><option>available</option><option>booked</option><option>rented</option><option>sold</option></select></div>
            <div class="col-md-6"><label class="form-label">Geoloc (placeholder)</label><input class="form-control" placeholder="lat,lng"></div>
            <div class="col-md-6"><label class="form-label">Immagine principale</label><input class="form-control" type="file" id="propImage"></div>
            <input type="hidden" name="image_path" id="propImagePath" value="">
            <div class="col-md-6"><img id="propImagePreview" src="" alt="" style="max-width:100%; display:none;"></div>
            <div class="col-12">
              <label class="form-label">Galleria (immagini esistenti)</label>
              <div id="propGallery" class="d-flex flex-wrap gap-2"></div>
            </div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button><button type="submit" class="btn btn-primary">Salva</button></div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal share (stub) -->
  <div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="shareForm">
          <div class="modal-header"><h5 class="modal-title">Condividi proprietà</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
            <div class="mb-2">
              <label class="form-label">Agenzia</label>
              <select id="toAgency" class="form-select" name="to_agency_id">
                <option value="1">Agency A</option>
                <option value="2">Agency B</option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Permessi</label>
              <div><label><input type="checkbox" id="perm_view" checked> Visualizza</label></div>
              <div><label><input type="checkbox" id="perm_contact"> Contatta</label></div>
              <div><label><input type="checkbox" id="perm_book"> Prenotazione visite</label></div>
            </div>
            <div class="mb-2"><label class="form-label">Note</label><textarea id="shareNote" class="form-control"></textarea></div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button><button type="submit" class="btn btn-primary">Invia</button></div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/app.js"></script>
  <script>
    const propModal = document.getElementById('propertyModal');
    const propForm = document.getElementById('propertyForm');
    const propAction = document.getElementById('propAction');
    const propId = document.getElementById('propId');
    const propTitle = document.getElementById('propTitle');
    const propPrice = document.getElementById('propPrice');
    const propCity = document.getElementById('propCity');
    const propAddress = document.getElementById('propAddress');
    const propStatus = document.getElementById('propStatus');
    const propDesc = document.getElementById('propDesc');

    // reset to create on open via "Nuova" button
    document.querySelectorAll('button[data-bs-target="#propertyModal"]').forEach(btn => {
      btn.addEventListener('click', () => {
        const isEdit = btn.hasAttribute('data-id');
        if (isEdit) {
          propAction.value = 'update';
          propId.value = btn.getAttribute('data-id');
          propTitle.value = btn.getAttribute('data-title') || '';
          propPrice.value = btn.getAttribute('data-price') || '';
          propCity.value = btn.getAttribute('data-city') || '';
          propAddress.value = btn.getAttribute('data-address') || '';
          propStatus.value = btn.getAttribute('data-status') || 'available';
          propDesc.value = btn.getAttribute('data-description') || '';
          const img = btn.getAttribute('data-image') || '';
          propImagePath.value = img;
          if (img) { propImagePreview.src = img; propImagePreview.style.display = 'block'; }
          else { propImagePreview.style.display = 'none'; propImagePreview.src = ''; }
          renderGallery(propId.value);
        } else {
          propAction.value = 'create';
          propId.value = '';
          propForm.reset();
          propImagePath.value = '';
          propImagePreview.style.display = 'none';
          if (propGallery) { propGallery.innerHTML = '<span class="text-muted small">Salva per gestire galleria</span>'; }
        }
      });
    });

    document.querySelectorAll('[data-bs-target="#shareModal"]').forEach(btn => {
      btn.addEventListener('click', () => {
        const pid = btn.getAttribute('data-prop');
        document.getElementById('shareForm').dataset.propertyId = pid;
      });
    });

    document.getElementById('shareForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const toAgencyId = document.getElementById('toAgency').value;
      const permissions = { view: document.getElementById('perm_view').checked, contact: document.getElementById('perm_contact').checked, book: document.getElementById('perm_book').checked };
      const note = document.getElementById('shareNote').value;
      const csrf = '<?php echo $csrf; ?>';
      const propertyId = document.getElementById('shareForm').dataset.propertyId;
      const res = await requestShare(propertyId, toAgencyId, permissions, note, csrf);
      if (res.ok) {
        bootstrap.Modal.getInstance(document.getElementById('shareModal')).hide();
        alert('Richiesta inviata');
      } else {
        alert('Errore: ' + (res.error || 'unknown'));
      }
    });

    const propImage = document.getElementById('propImage');
    const propImagePreview = document.getElementById('propImagePreview');
    const propImagePath = document.getElementById('propImagePath');
    const propGallery = document.getElementById('propGallery');
    if (propImage) {
      propImage.addEventListener('change', async () => {
        const file = propImage.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
          propImagePreview.src = e.target.result;
          propImagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
        const res = await uploadImage(file, '<?php echo $csrf; ?>');
        if (res.ok) {
          propImagePath.value = res.path;
        } else {
          alert('Upload fallito: ' + (res.error || 'unknown'));
        }
      });
    }

    async function renderGallery(propertyId) {
      if (!propGallery) return;
      propGallery.innerHTML = '<span class="text-muted small">Caricamento...</span>';
      const data = await listPropertyImages(propertyId, '<?php echo $csrf; ?>');
      if (!data.ok) { propGallery.innerHTML = '<span class="text-danger small">Errore galleria</span>'; return; }
      if (!data.items.length) { propGallery.innerHTML = '<span class="text-muted small">Nessuna immagine</span>'; return; }
      propGallery.innerHTML = '';
      data.items.forEach(item => {
        const wrap = document.createElement('div');
        wrap.className = 'border rounded p-1 d-flex flex-column align-items-center';
        wrap.style.width = '110px';
        wrap.innerHTML = `<img src="${item.path}" style="width:100px;height:70px;object-fit:cover;" alt=""><button class="btn btn-sm btn-outline-danger mt-1" data-id="${item.id}">Del</button>`;
        wrap.querySelector('button').addEventListener('click', async () => {
          if (!confirm('Eliminare immagine?')) return;
          const res = await deletePropertyImage(propertyId, item.id, '<?php echo $csrf; ?>');
          if (res.ok) { renderGallery(propertyId); }
          else { alert('Errore eliminazione'); }
        });
        propGallery.appendChild(wrap);
      });
    }
  </script>
</body>
</html>
