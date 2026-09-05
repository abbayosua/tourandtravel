<?php
/**
 * AJAX: polling status pembayaran berdasarkan order_id.
 * GET: order_id → {status: pending|paid|...}
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payments.php';

header('Content-Type: application/json');

$orderId = trim($_GET['order_id'] ?? '');
if ($orderId === '') {
    echo json_encode(['status' => 'invalid']);
    exit;
}

$stmt = db()->prepare("SELECT status FROM payments WHERE order_id = ? LIMIT 1");
$stmt->execute([$orderId]);
$row = $stmt->fetch();

echo json_encode(['status' => $row['status'] ?? 'unknown']);
