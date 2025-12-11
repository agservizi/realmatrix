<?php
// public/api/upload_image.php
require_once __DIR__ . '/../../includes/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

enforce_rate_limit('upload_image', 10, 60);

$csrf = $_POST['csrf'] ?? '';
if (!check_csrf($csrf)) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token invalid']);
    exit;
}

$userId = current_user_id();
$role = current_user_role();
if (!$userId || !in_array($role, ['superadmin','agency_admin','agent'], true)) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'File upload missing or failed']);
    exit;
}

$file = $_FILES['image'];
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large']);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mime, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Unsupported MIME type']);
    exit;
}

$uploadDir = realpath(__DIR__ . '/../assets/uploads');
if (!$uploadDir) {
    http_response_code(500);
    echo json_encode(['error' => 'Upload directory missing']);
    exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$basename = sanitize_filename(pathinfo($file['name'], PATHINFO_FILENAME));
$filename = uniqid('img_', true) . ($ext ? '.' . $ext : '');
$targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to save file']);
    exit;
}

$relativePath = '/assets/uploads/' . $filename;

// Thumbnail generation (simple GD if available)
$thumbDir = realpath(__DIR__ . '/../assets/uploads/thumbs');
if ($thumbDir && function_exists('imagecreatefromstring')) {
    $imgData = file_get_contents($targetPath);
    $src = @imagecreatefromstring($imgData);
    if ($src !== false) {
        $w = imagesx($src); $h = imagesy($src);
        $maxSide = 480;
        $scale = min($maxSide / max($w, $h), 1);
        $nw = (int)($w * $scale); $nh = (int)($h * $scale);
        $thumb = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $thumbName = 'th_' . $filename;
        $thumbPath = $thumbDir . DIRECTORY_SEPARATOR . $thumbName;
        imagejpeg($thumb, $thumbPath, 82);
        imagedestroy($thumb);
        imagedestroy($src);
    }
}

// Placeholder for offloading to S3/MinIO with pre-signed URLs
// function push_to_object_storage($localPath, $key) { /* TODO */ }
// push_to_object_storage($targetPath, 'uploads/' . $filename);

$log = $pdo->prepare("INSERT INTO activity_logs (user_id, agency_id, action, meta) VALUES (?, ?, ?, ?)");
$log->execute([$userId, $_SESSION['agency_id'] ?? null, 'image_uploaded', json_encode(['path' => $relativePath])]);

echo json_encode(['ok' => true, 'path' => $relativePath]);
