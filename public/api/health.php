<?php
require_once __DIR__ . '/../../includes/init.php';
header('Content-Type: application/json');

$status = ['ok' => true, 'time' => date('c')];
try {
    $pdo->query('SELECT 1');
    $status['db'] = 'up';
} catch (Throwable $e) {
    $status['ok'] = false;
    $status['db'] = 'down';
}

$status['metrics'] = [];
try {
    $status['metrics']['properties'] = (int)$pdo->query('SELECT COUNT(*) FROM properties WHERE deleted_at IS NULL')->fetchColumn();
    $status['metrics']['leads'] = (int)$pdo->query('SELECT COUNT(*) FROM leads WHERE deleted_at IS NULL')->fetchColumn();
} catch (Throwable $e) {}

echo json_encode($status);
