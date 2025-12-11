<?php
require_once __DIR__ . '/../../includes/init.php';

$type = $_GET['type'] ?? 'properties';
$format = $_GET['format'] ?? 'csv';
if (!in_array($type, ['properties','leads'], true)) {
    http_response_code(400); exit('Invalid type');
}
if ($format !== 'csv') { http_response_code(400); exit('Only CSV supported for now'); }
require_role(['superadmin','agency_admin','agent','accountant']);

$agencyId = $_SESSION['agency_id'] ?? null;

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="'.$type.'_export_'.date('Ymd_His').'.csv"');

$out = fopen('php://output', 'w');

if ($type === 'properties') {
    $status = $_GET['status'] ?? '';
    $q = trim($_GET['q'] ?? '');
    $params = [];
    $where = ['p.deleted_at IS NULL'];
    if ($status !== '') { $where[] = 'p.status = ?'; $params[] = $status; }
    if ($q !== '') { $where[] = '(p.title LIKE ? OR p.city LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
    if ($agencyId && current_user_role() !== 'superadmin') { $where[] = 'p.agency_id = ?'; $params[] = $agencyId; }
    $whereSql = $where ? ('WHERE '.implode(' AND ',$where)) : '';
    $sql = "SELECT p.id, p.title, p.city, p.price, p.status, p.agency_id, p.created_at FROM properties p $whereSql ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $i=>$v) { $stmt->bindValue($i+1,$v); }
    $stmt->execute();
    fputcsv($out, ['id','title','city','price','status','agency_id','created_at']);
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) { fputcsv($out, $row); }
    log_activity('export_properties', ['rows'=>$stmt->rowCount()]);
} else {
    $status = $_GET['status'] ?? '';
    $q = trim($_GET['q'] ?? '');
    $where = ['l.deleted_at IS NULL'];
    $params = [];
    if ($status !== '') { $where[] = 'l.status = ?'; $params[] = $status; }
    if ($q !== '') { $where[] = '(l.name LIKE ? OR l.email LIKE ? OR l.phone LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }
    if ($agencyId && current_user_role() !== 'superadmin') { $where[] = 'l.agency_id = ?'; $params[] = $agencyId; }
    $whereSql = $where ? ('WHERE '.implode(' AND ',$where)) : '';
    $sql = "SELECT l.id, l.name, l.email, l.phone, l.status, l.source, l.property_id, l.created_at FROM leads l $whereSql ORDER BY l.created_at DESC";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $i=>$v) { $stmt->bindValue($i+1,$v); }
    $stmt->execute();
    fputcsv($out, ['id','name','email','phone','status','source','property_id','created_at']);
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) { fputcsv($out, $row); }
    log_activity('export_leads', ['rows'=>$stmt->rowCount()]);
}

fclose($out);
exit;
