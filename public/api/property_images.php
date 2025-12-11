<?php
require_once __DIR__ . '/../../includes/init.php';
header('Content-Type: application/json');

enforce_rate_limit('property_images', 30, 60);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error'=>'Method not allowed']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = $method === 'GET' ? $_GET : json_decode(file_get_contents('php://input'), true);
$input = $input ?: [];
$csrf = $method === 'GET' ? ($_GET['csrf'] ?? '') : ($input['csrf'] ?? '');
if (!check_csrf($csrf)) { http_response_code(403); echo json_encode(['error'=>'CSRF token invalid']); exit; }

$userId = current_user_id();
if (!$userId) { http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }

$action = $input['action'] ?? ($method === 'GET' ? 'list' : 'add');
$propertyId = (int)($input['property_id'] ?? 0);
if (!$propertyId) { http_response_code(400); echo json_encode(['error'=>'Missing property_id']); exit; }

// ownership check
$stmt = $pdo->prepare("SELECT agency_id FROM properties WHERE id=? AND deleted_at IS NULL");
$stmt->execute([$propertyId]);
$prop = $stmt->fetch();
if (!$prop) { http_response_code(404); echo json_encode(['error'=>'Property not found']); exit; }
if (($prop['agency_id'] ?? null) !== ($_SESSION['agency_id'] ?? null) && current_user_role() !== 'superadmin') {
    http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit; }

if ($action === 'list') {
    $stmt = $pdo->prepare("SELECT id, path, alt, priority FROM property_images WHERE property_id = ? AND deleted_at IS NULL ORDER BY priority ASC, id DESC");
    $stmt->execute([$propertyId]);
    echo json_encode(['ok'=>true, 'items'=>$stmt->fetchAll()]);
    exit;
}

if ($action === 'add') {
    $path = trim($input['path'] ?? '');
    $alt = trim($input['alt'] ?? '');
    if ($path === '') { http_response_code(400); echo json_encode(['error'=>'Missing path']); exit; }
    $stmt = $pdo->prepare("INSERT INTO property_images (property_id, path, alt) VALUES (?, ?, ?)");
    $stmt->execute([$propertyId, $path, $alt]);
    log_activity('property_image_added', ['property_id'=>$propertyId, 'path'=>$path]);
    echo json_encode(['ok'=>true]);
    exit;
}

if ($action === 'delete') {
    $imageId = (int)($input['image_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE property_images SET deleted_at = NOW() WHERE id = ? AND property_id = ?");
    $stmt->execute([$imageId, $propertyId]);
    if ($stmt->rowCount()) {
        log_activity('property_image_deleted', ['property_id'=>$propertyId, 'image_id'=>$imageId]);
        echo json_encode(['ok'=>true]);
    } else {
        http_response_code(404); echo json_encode(['error'=>'Not found']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error'=>'Bad action']);
