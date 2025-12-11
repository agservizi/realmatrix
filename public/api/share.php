<?php
// public/api/share.php
require_once __DIR__ . '/../../includes/init.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

enforce_rate_limit('share_api', 30, 60);

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$csrf = $input['csrf'] ?? '';
if (!check_csrf($csrf)) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token invalid']);
    exit;
}

$userId = current_user_id();
if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($action === 'request') {
    $propertyId = (int)($input['property_id'] ?? 0);
    $toAgencyId = (int)($input['to_agency_id'] ?? 0);
    $permissions = $input['permissions'] ?? ['view' => true];
    $note = $input['note'] ?? '';

    $stmt = $pdo->prepare("SELECT agency_id FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    $prop = $stmt->fetch();
    if (!$prop) {
        http_response_code(404);
        echo json_encode(['error' => 'Property not found']);
        exit;
    }
    $fromAgencyId = (int)$prop['agency_id'];

    $stmt = $pdo->prepare("INSERT INTO property_shares
        (property_id, from_agency_id, to_agency_id, permissions, note, created_by)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $propertyId,
        $fromAgencyId,
        $toAgencyId,
        json_encode($permissions),
        $note,
        $userId
    ]);
    $shareId = $pdo->lastInsertId();

    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, agency_id, action, meta)
                          VALUES (?, ?, ?, ?)");
    $log->execute([$userId, $fromAgencyId, 'share_requested',
        json_encode(['share_id'=>$shareId,'to_agency'=>$toAgencyId,'property'=>$propertyId])]);

    // notify target agency (stub hooks)
    enqueue_job('webhook', ['event'=>'share_requested','share_id'=>$shareId,'to_agency'=>$toAgencyId]);
    enqueue_job('email', ['template'=>'share_requested','to_agency'=>$toAgencyId]);

    echo json_encode(['ok' => true, 'share_id' => $shareId]);
    exit;
}

if ($action === 'action') {
    $shareId = (int)($input['share_id'] ?? 0);
    $decision = $input['decision'] ?? 'rejected';

    $stmt = $pdo->prepare("SELECT * FROM property_shares WHERE id = ?");
    $stmt->execute([$shareId]);
    $share = $stmt->fetch();
    if (!$share) { http_response_code(404); echo json_encode(['error'=>'Share not found']); exit; }

    $stmt = $pdo->prepare("SELECT agency_id, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user || $user['agency_id'] != $share['to_agency_id'] || !in_array($user['role'], ['superadmin','agency_admin'], true)) {
        http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit;
    }

    $stmt = $pdo->prepare("UPDATE property_shares
        SET status = ?, actioned_at = NOW(), actioned_by = ?
        WHERE id = ?");
    $stmt->execute([$decision, $userId, $shareId]);

    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, agency_id, action, meta)
                          VALUES (?, ?, ?, ?)");
    $log->execute([$userId, $user['agency_id'], 'share_'.$decision,
        json_encode(['share_id'=>$shareId])]);

    enqueue_job('webhook', ['event'=>'share_'.$decision,'share_id'=>$shareId]);

    echo json_encode(['ok' => true, 'status' => $decision]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Bad request']);
