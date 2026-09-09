<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'method_not_allowed']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'not_logged_in']);
    exit;
}

$itemId = (int)($_POST['tour_id'] ?? $_POST['item_id'] ?? 0);
$itemType = in_array($_POST['item_type'] ?? 'tour', ['tour', 'hotel', 'attraction', 'esim'], true) ? ($_POST['item_type'] ?? 'tour') : 'tour';
$action = $_POST['action'] ?? 'toggle';

if (!$itemId) {
    echo json_encode(['status' => 'error']);
    exit;
}

$userId = $_SESSION['user_id'];

if ($action === 'add' || ($action === 'toggle' && !isWishlistedItem($userId, $itemType, $itemId))) {
    $stmt = db()->prepare("INSERT IGNORE INTO wishlists (user_id, item_type, item_id, tour_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $itemType, $itemId, $itemType === 'tour' ? $itemId : 0]);
    echo json_encode(['status' => 'added']);
} else {
    $stmt = db()->prepare("DELETE FROM wishlists WHERE user_id = ? AND item_type = ? AND item_id = ?");
    $stmt->execute([$userId, $itemType, $itemId]);
    echo json_encode(['status' => 'removed']);
}
