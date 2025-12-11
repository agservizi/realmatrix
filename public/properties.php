<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['superadmin','agency_admin','agent']);
$csrf = generate_csrf();

$errors = [];
$flash = '';

$allowedStatus = ['available','booked','rented','sold'];
$allowedTypes = ['residenziale','commerciale','terreno','altro'];
$maxUploadBytes = 3 * 1024 * 1024; // 3MB
$maxImageSize = 8000; // max 8k px lato lungo

function upload_property_image(array $file, int $maxBytes, int $maxSizePx): array {
  $allowedExt = ['jpg','jpeg','png','webp'];
  $allowedMime = ['image/jpeg','image/png','image/webp'];
  if ($file['error'] !== UPLOAD_ERR_OK) { return [null, 'Errore upload immagine']; }
  if ($file['size'] > $maxBytes) { return [null, 'Immagine troppo pesante']; }
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, $allowedExt, true)) { return [null, 'Formato immagine non valido']; }
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($finfo, $file['tmp_name']);
  finfo_close($finfo);
  if (!in_array($mime, $allowedMime, true)) { return [null, 'MIME non consentito']; }
  $dims = @getimagesize($file['tmp_name']);
  if (!$dims) { return [null, 'Immagine non leggibile']; }
  if ($dims[0] > $maxSizePx || $dims[1] > $maxSizePx) { return [null, 'Dimensioni troppo grandi']; }
  $dir = realpath(__DIR__ . '/../uploads/properties');
  if (!$dir) { return [null, 'Cartella upload non trovata']; }
  $name = uniqid('prop_', true) . '.' . $ext;
  $dest = $dir . '/' . $name;
  if (!move_uploaded_file($file['tmp_name'], $dest)) { return [null, 'Salvataggio immagine fallito']; }
  return ['/uploads/properties/' . $name, null];
}

function property_access_allowed(PDO $pdo, int $propertyId): bool {
  $stmt = $pdo->prepare("SELECT agency_id FROM properties WHERE id = ?");
  $stmt->execute([$propertyId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) { return false; }
  if (current_user_role() === 'superadmin') { return true; }
  return (int)$row['agency_id'] === (int)($_SESSION['agency_id'] ?? 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'create';

  $csrfPost = $_POST['csrf'] ?? '';
  if (!check_csrf($csrfPost)) { $errors[] = 'CSRF non valido'; }

  if ($action === 'gallery_upload') {
    $propId = (int)($_POST['property_id'] ?? 0);
    $alt = trim($_POST['alt'] ?? '');
    $priority = (int)($_POST['priority'] ?? 0);
    if (!$propId) { $errors[] = 'Property ID mancante'; }
    if ($propId && !property_access_allowed($pdo, $propId)) { $errors[] = 'Accesso non consentito'; }
    if (empty($_FILES['gallery_file']['name'])) { $errors[] = 'File immagine richiesto'; }
    if (!$errors) {
      [$stored, $err] = upload_property_image($_FILES['gallery_file'], $maxUploadBytes, $maxImageSize);
      if ($err) { $errors[] = $err; }
      else {
        $stmt = $pdo->prepare("INSERT INTO property_images (property_id, path, alt, priority, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$propId, $stored, $alt ?: null, $priority]);
        log_activity('property_image_uploaded', ['property_id' => $propId]);
        $flash = 'Immagine aggiunta';
      }
    }
  } elseif ($action === 'gallery_delete') {
    $imgId = (int)($_POST['image_id'] ?? 0);
    $propId = (int)($_POST['property_id'] ?? 0);
    if ($propId && !property_access_allowed($pdo, $propId)) { $errors[] = 'Accesso non consentito'; }
    if ($imgId && !$errors) {
      $stmt = $pdo->prepare("UPDATE property_images SET deleted_at = NOW() WHERE id = ? AND property_id = ?");
      $stmt->execute([$imgId, $propId]);
      if ($stmt->rowCount()) { $flash = 'Immagine rimossa'; log_activity('property_image_deleted', ['image_id' => $imgId]); }
    }
  } elseif ($action === 'gallery_update') {
    $imgId = (int)($_POST['image_id'] ?? 0);
    $priority = (int)($_POST['priority'] ?? 0);
    $propId = (int)($_POST['property_id'] ?? 0);
    if ($propId && !property_access_allowed($pdo, $propId)) { $errors[] = 'Accesso non consentito'; }
    if ($imgId && !$errors) {
      $stmt = $pdo->prepare("UPDATE property_images SET priority = ? WHERE id = ? AND property_id = ?");
      $stmt->execute([$priority, $imgId, $propId]);
      if ($stmt->rowCount()) { $flash = 'Priorità aggiornata'; }
    }
  } elseif ($action === 'delete' || $action === 'restore') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$errors && $id) {
      $set = $action === 'delete' ? 'deleted_at = NOW()' : 'deleted_at = NULL';
      $stmt = $pdo->prepare("UPDATE properties SET $set WHERE id = ? AND (agency_id = ? OR ? = 'superadmin')");
      $stmt->execute([$id, $_SESSION['agency_id'] ?? 0, current_user_role()]);
      if ($stmt->rowCount()) {
        log_activity($action === 'delete' ? 'property_deleted' : 'property_restored', ['property_id' => $id]);
        $flash = $action === 'delete' ? 'Proprietà eliminata' : 'Proprietà ripristinata';
      } else { $errors[] = 'Operazione non consentita'; }
    }
  } else {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $cap = trim($_POST['cap'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $type = trim($_POST['type'] ?? 'residenziale');
    $status = trim($_POST['status'] ?? 'available');
    $mainImage = trim($_POST['main_image_path'] ?? '');

    if (!in_array($status, $allowedStatus, true)) { $errors[] = 'Stato non valido'; }
    if (!in_array($type, $allowedTypes, true)) { $errors[] = 'Tipo non valido'; }
    if ($title === '') { $errors[] = 'Titolo obbligatorio'; }
    if ($price <= 0) { $errors[] = 'Prezzo deve essere > 0'; }

    if (!empty($_FILES['main_image_file']['name'])) {
      [$stored, $err] = upload_property_image($_FILES['main_image_file'], $maxUploadBytes, $maxImageSize);
      if ($err) { $errors[] = $err; }
      else { $mainImage = $stored; }
    }

    if (!$errors) {
      if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE properties SET title=?, description=?, address=?, city=?, province=?, cap=?, price=?, type=?, status=?, main_image_path=? WHERE id=? AND (agency_id=? OR ? = 'superadmin')");
        $stmt->execute([$title, $description ?: null, $address ?: null, $city ?: null, $province ?: null, $cap ?: null, $price, $type, $status, $mainImage ?: null, $id, $_SESSION['agency_id'] ?? 0, current_user_role()]);
        if ($stmt->rowCount()) {
          log_activity('property_updated', ['property_id' => $id]);
          $flash = 'Proprietà aggiornata';
        } else { $errors[] = 'Aggiornamento non consentito'; }
      } else {
        $stmt = $pdo->prepare("INSERT INTO properties (agency_id, title, description, address, city, province, cap, price, type, status, geo_lat, geo_lng, created_by, main_image_path, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
          $_SESSION['agency_id'] ?? null,
          $title,
          $description ?: null,
          $address ?: null,
          $city ?: null,
          $province ?: null,
          $cap ?: null,
          $price,
          $type ?: null,
          $status ?: 'available',
          null,
          null,
          $_SESSION['user_id'] ?? null,
          $mainImage ?: null
        ]);
        log_activity('property_created', ['property_id' => $pdo->lastInsertId()]);
        $flash = 'Proprietà creata';
      }
    }
  }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$statusFilter = trim($_GET['status'] ?? '');
$cityFilter = trim($_GET['city'] ?? '');
$minPrice = (float)($_GET['min_price'] ?? 0);
$maxPrice = (float)($_GET['max_price'] ?? 0);
$galleryFor = (int)($_GET['gallery_for'] ?? 0);

$where = [];
$params = [];
if ($statusFilter !== '') { $where[] = 'p.status = ?'; $params[] = $statusFilter; }
if ($cityFilter !== '') { $where[] = 'p.city LIKE ?'; $params[] = "%$cityFilter%"; }
if ($minPrice > 0) { $where[] = 'p.price >= ?'; $params[] = $minPrice; }
if ($maxPrice > 0) { $where[] = 'p.price <= ?'; $params[] = $maxPrice; }
$where[] = 'p.deleted_at IS NULL';
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS p.id, p.title, p.city, p.price, p.status, p.type, p.main_image_path, p.created_at, p.deleted_at, p.agency_id, p.created_by, a.name AS agency_name, u.name AS created_by_name, p.address, p.province, p.cap, p.description FROM properties p LEFT JOIN agencies a ON p.agency_id = a.id LEFT JOIN users u ON p.created_by = u.id $whereSql ORDER BY p.created_at DESC LIMIT :lim OFFSET :off");
foreach ($params as $i => $val) { $stmt->bindValue($i + 1, $val); }
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$properties = $stmt->fetchAll();
$total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

$galleryImages = [];
if ($galleryFor) {
  $stmtImg = $pdo->prepare("SELECT id, path, alt, priority, created_at FROM property_images WHERE property_id = ? AND deleted_at IS NULL ORDER BY priority ASC, id DESC");
  $stmtImg->execute([$galleryFor]);
  $galleryImages = $stmtImg->fetchAll();
}
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

  <main style="margin-left:260px; padding-top:4px;">
    <div class="container-fluid py-2">
      <div class="d-flex align-items-center mb-3">
        <div>
          <div class="badge-soft">Proprietà</div>
          <h1 class="h3 mb-0">Listing e schede</h1>
          <p class="text-muted small mb-0">Crea, modifica ed esegui soft delete sugli immobili.</p>
        </div>
        <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#propertyModal" data-mode="create">Nuova proprietà</button>
      </div>

      <?php if ($flash): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
      <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?></div><?php endif; ?>

      <div class="card shadow-soft">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
          <span>Elenco</span>
          <form class="ms-auto d-flex flex-wrap gap-2" method="get">
            <input class="form-control form-control-sm" style="max-width:160px" type="text" name="city" placeholder="Città" value="<?php echo htmlspecialchars($cityFilter); ?>">
            <input class="form-control form-control-sm" style="max-width:120px" type="number" name="min_price" step="1000" placeholder="Prezzo min" value="<?php echo $minPrice ?: ''; ?>">
            <input class="form-control form-control-sm" style="max-width:120px" type="number" name="max_price" step="1000" placeholder="Prezzo max" value="<?php echo $maxPrice ?: ''; ?>">
            <select class="form-select form-select-sm" name="status" style="max-width:140px">
              <option value="">Tutti gli stati</option>
              <?php foreach ($allowedStatus as $st): ?>
                <option value="<?php echo $st; ?>" <?php if ($statusFilter === $st) echo 'selected'; ?>><?php echo $st; ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit">Filtra</button>
          </form>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead><tr><th>ID</th><th>Img</th><th>Titolo</th><th>Città</th><th>Prezzo</th><th>Tipo</th><th>Stato</th><th>Agenzia</th><th>Creato da</th><th>Creato</th><th class="text-end">Azioni</th></tr></thead>
            <tbody>
              <?php foreach ($properties as $p): ?>
                <tr class="<?php echo $p['deleted_at'] ? 'table-danger' : ''; ?>">
                  <td><?php echo (int)$p['id']; ?></td>
                  <td style="width:60px;">
                    <?php if ($p['main_image_path']): ?><img src="<?php echo htmlspecialchars($p['main_image_path']); ?>" style="width:56px;height:40px;object-fit:cover;" alt=""><?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($p['title']); ?></td>
                  <td><?php echo htmlspecialchars($p['city']); ?></td>
                  <td>€ <?php echo number_format((float)$p['price'], 0, ',', '.'); ?></td>
                  <td><?php echo htmlspecialchars($p['type']); ?></td>
                  <td><span class="badge bg-primary-subtle text-primary"><?php echo htmlspecialchars($p['status']); ?></span></td>
                  <td><?php echo htmlspecialchars($p['agency_name'] ?? ('#'.$p['agency_id'])); ?></td>
                  <td><?php echo htmlspecialchars($p['created_by_name'] ?? '—'); ?></td>
                  <td><?php echo htmlspecialchars($p['created_at']); ?></td>
                  <td class="text-end d-flex gap-2 justify-content-end">
                    <?php if (!$p['deleted_at']): ?>
                      <a class="btn btn-sm btn-outline-primary" href="?gallery_for=<?php echo (int)$p['id']; ?>#gallery">Galleria</a>
                      <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#propertyModal"
                        data-mode="edit"
                        data-id="<?php echo (int)$p['id']; ?>"
                        data-title="<?php echo htmlspecialchars($p['title'], ENT_QUOTES); ?>"
                        data-city="<?php echo htmlspecialchars($p['city'], ENT_QUOTES); ?>"
                        data-address="<?php echo htmlspecialchars($p['address'] ?? '', ENT_QUOTES); ?>"
                        data-province="<?php echo htmlspecialchars($p['province'] ?? '', ENT_QUOTES); ?>"
                        data-cap="<?php echo htmlspecialchars($p['cap'] ?? '', ENT_QUOTES); ?>"
                        data-price="<?php echo htmlspecialchars($p['price']); ?>"
                        data-type="<?php echo htmlspecialchars($p['type'], ENT_QUOTES); ?>"
                        data-status="<?php echo htmlspecialchars($p['status'], ENT_QUOTES); ?>"
                        data-description="<?php echo htmlspecialchars($p['description'] ?? '', ENT_QUOTES); ?>"
                        data-image="<?php echo htmlspecialchars($p['main_image_path'] ?? '', ENT_QUOTES); ?>"
                      >Modifica</button>
                      <form method="post" onsubmit="return confirm('Eliminare la proprietà?');">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Elimina</button>
                      </form>
                    <?php else: ?>
                      <form method="post" onsubmit="return confirm('Ripristinare la proprietà?');">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                        <input type="hidden" name="action" value="restore">
                        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                        <button class="btn btn-sm btn-success" type="submit">Ripristina</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$properties): ?><tr><td colspan="11" class="text-center text-muted">Nessuna proprietà</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
          <div>Totale: <?php echo $total; ?></div>
          <div class="btn-group btn-group-sm">
            <?php for ($p=1; $p <= $pages; $p++): ?>
              <a class="btn <?php echo $p === $page ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="?page=<?php echo $p; ?>&status=<?php echo urlencode($statusFilter); ?>&city=<?php echo urlencode($cityFilter); ?>&min_price=<?php echo $minPrice; ?>&max_price=<?php echo $maxPrice; ?>&gallery_for=<?php echo $galleryFor; ?>#gallery"><?php echo $p; ?></a>
            <?php endfor; ?>
          </div>
        </div>
      </div>

      <?php if ($galleryFor): ?>
      <div id="gallery" class="card mt-3">
        <div class="card-header d-flex align-items-center gap-2">
          <div>Galleria proprietà #<?php echo $galleryFor; ?></div>
          <a class="btn btn-sm btn-outline-secondary ms-auto" href="?page=<?php echo $page; ?>&status=<?php echo urlencode($statusFilter); ?>&city=<?php echo urlencode($cityFilter); ?>&min_price=<?php echo $minPrice; ?>&max_price=<?php echo $maxPrice; ?>">Chiudi</a>
        </div>
        <div class="card-body">
          <form class="row g-2 align-items-end" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="action" value="gallery_upload">
            <input type="hidden" name="property_id" value="<?php echo $galleryFor; ?>">
            <div class="col-md-4"><label class="form-label">File immagine</label><input class="form-control" type="file" name="gallery_file" accept="image/*" required></div>
            <div class="col-md-3"><label class="form-label">Alt</label><input class="form-control" type="text" name="alt" placeholder="Descrizione"></div>
            <div class="col-md-2"><label class="form-label">Priorità</label><input class="form-control" type="number" name="priority" value="0"></div>
            <div class="col-md-3"><button class="btn btn-primary w-100" type="submit">Carica</button></div>
            <div class="col-12 text-muted small">Max 3MB, formati: jpg, jpeg, png, webp.</div>
          </form>

          <div class="table-responsive mt-3">
            <table class="table table-sm align-middle">
              <thead><tr><th>Img</th><th>Alt</th><th>Priorità</th><th>Caricato</th><th class="text-end">Azioni</th></tr></thead>
              <tbody>
                <?php foreach ($galleryImages as $img): ?>
                  <tr>
                    <td style="width:120px;"><img src="<?php echo htmlspecialchars($img['path']); ?>" style="width:110px;height:70px;object-fit:cover;" alt=""></td>
                    <td><?php echo htmlspecialchars($img['alt'] ?? ''); ?></td>
                    <td><?php echo (int)$img['priority']; ?></td>
                    <td><?php echo htmlspecialchars($img['created_at']); ?></td>
                    <td class="text-end">
                      <form method="post" onsubmit="return confirm('Rimuovere questa immagine?');" class="d-inline">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                        <input type="hidden" name="action" value="gallery_delete">
                        <input type="hidden" name="image_id" value="<?php echo (int)$img['id']; ?>">
                        <input type="hidden" name="property_id" value="<?php echo $galleryFor; ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Elimina</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$galleryImages): ?><tr><td colspan="5" class="text-center text-muted">Nessuna immagine</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </main>

  <div class="modal fade" id="propertyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="post" enctype="multipart/form-data" id="propertyForm">
          <div class="modal-header"><h5 class="modal-title">Proprietà</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="action" id="propAction" value="create">
            <input type="hidden" name="id" id="propId" value="">
            <div class="row g-2">
              <div class="col-md-6"><label class="form-label">Titolo</label><input class="form-control" name="title" id="propTitle" required></div>
              <div class="col-md-3"><label class="form-label">Tipo</label>
                <select class="form-select" name="type" id="propType">
                  <?php foreach ($allowedTypes as $t): ?><option value="<?php echo $t; ?>"><?php echo $t; ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3"><label class="form-label">Stato</label>
                <select class="form-select" name="status" id="propStatus">
                  <?php foreach ($allowedStatus as $st): ?><option value="<?php echo $st; ?>"><?php echo $st; ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6"><label class="form-label">Indirizzo</label><input class="form-control" name="address" id="propAddress"></div>
              <div class="col-md-3"><label class="form-label">Città</label><input class="form-control" name="city" id="propCity"></div>
              <div class="col-md-3"><label class="form-label">Provincia</label><input class="form-control" name="province" id="propProvince"></div>
              <div class="col-md-3"><label class="form-label">CAP</label><input class="form-control" name="cap" id="propCap"></div>
              <div class="col-md-3"><label class="form-label">Prezzo</label><input class="form-control" name="price" id="propPrice" type="number" step="1000" required></div>
              <div class="col-md-6"><label class="form-label">Percorso immagine principale</label><input class="form-control" name="main_image_path" id="propImagePath" placeholder="/uploads/properties/...jpg"></div>
              <div class="col-md-6"><label class="form-label">Oppure carica immagine</label><input class="form-control" type="file" name="main_image_file" accept="image/*"></div>
              <div class="col-12"><label class="form-label">Descrizione</label><textarea class="form-control" name="description" id="propDesc" rows="3"></textarea></div>
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
    const modal = document.getElementById('propertyModal');
    const form = document.getElementById('propertyForm');
    const actionField = document.getElementById('propAction');
    const idField = document.getElementById('propId');
    const titleField = document.getElementById('propTitle');
    const cityField = document.getElementById('propCity');
    const addressField = document.getElementById('propAddress');
    const provinceField = document.getElementById('propProvince');
    const capField = document.getElementById('propCap');
    const priceField = document.getElementById('propPrice');
    const typeField = document.getElementById('propType');
    const statusField = document.getElementById('propStatus');
    const descField = document.getElementById('propDesc');
    const imagePathField = document.getElementById('propImagePath');

    modal.addEventListener('show.bs.modal', event => {
      const button = event.relatedTarget;
      const mode = button?.getAttribute('data-mode') || 'create';
      form.reset();
      actionField.value = mode === 'edit' ? 'update' : 'create';
      if (mode === 'edit') {
        idField.value = button.getAttribute('data-id') || '';
        titleField.value = button.getAttribute('data-title') || '';
        cityField.value = button.getAttribute('data-city') || '';
        addressField.value = button.getAttribute('data-address') || '';
        provinceField.value = button.getAttribute('data-province') || '';
        capField.value = button.getAttribute('data-cap') || '';
        priceField.value = button.getAttribute('data-price') || '';
        typeField.value = button.getAttribute('data-type') || 'residenziale';
        statusField.value = button.getAttribute('data-status') || 'available';
        descField.value = button.getAttribute('data-description') || '';
        imagePathField.value = button.getAttribute('data-image') || '';
      } else {
        idField.value = '';
        typeField.value = 'residenziale';
        statusField.value = 'available';
      }
    });
  </script>
</body>
</html>
