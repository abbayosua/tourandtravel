<?php
/**
 * price-alert-ajax.php — CRUD price alerts via AJAX/POST.
 *
 * POST action=create  → create or update alert (upsert)
 * POST action=delete  → deactivate alert
 * GET                 → list user's active alerts (JSON)
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// GET: list user alerts
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = db()->prepare("SELECT pa.*, t.title AS tour_title, h.name AS hotel_title FROM price_alerts pa LEFT JOIN tours t ON pa.item_type = 'tour' AND pa.item_id = t.id LEFT JOIN hotels h ON pa.item_type = 'hotel' AND pa.item_id = h.id WHERE pa.user_id = ? ORDER BY pa.created_at DESC");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();
    echo json_encode(['alerts' => $rows]);
    exit;
}

// POST: create/delete
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $itemType = $_POST['item_type'] ?? '';
    $itemId = (int)($_POST['item_id'] ?? 0);
    $targetPrice = (float)($_POST['target_price'] ?? 0);
    $currency = $_POST['currency'] ?? 'IDR';

    if (!in_array($itemType, ['tour', 'hotel'], true) || $itemId <= 0 || $targetPrice <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_params']);
        exit;
    }

    // Upsert: INSERT ... ON DUPLICATE KEY UPDATE
    $stmt = db()->prepare("INSERT INTO price_alerts (user_id, item_type, item_id, target_price, currency, active) VALUES (?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE target_price = VALUES(target_price), currency = VALUES(currency), active = 1, notified_at = NULL");
    $stmt->execute([$userId, $itemType, $itemId, $targetPrice, $currency]);

    echo json_encode(['success' => true, 'message' => 'Alert saved']);
    exit;
}

if ($action === 'delete') {
    $alertId = (int)($_POST['alert_id'] ?? 0);
    if ($alertId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_params']);
        exit;
    }
    $stmt = db()->prepare("UPDATE price_alerts SET active = 0 WHERE id = ? AND user_id = ?");
    $stmt->execute([$alertId, $userId]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'unknown_action']);
