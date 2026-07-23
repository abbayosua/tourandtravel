<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'not_logged_in']);
    exit;
}

$tourId = (int)($_GET['tour_id'] ?? 0);
$action = $_GET['action'] ?? 'toggle';

if (!$tourId) {
    echo json_encode(['status' => 'error']);
    exit;
}

$userId = $_SESSION['user_id'];

if ($action === 'add' || ($action === 'toggle' && !isWishlisted($userId, $tourId))) {
    // Add
    $stmt = db()->prepare("INSERT IGNORE INTO wishlists (user_id, tour_id) VALUES (?, ?)");
    $stmt->execute([$userId, $tourId]);
    echo json_encode(['status' => 'added']);
} else {
    // Remove
    $stmt = db()->prepare("DELETE FROM wishlists WHERE user_id = ? AND tour_id = ?");
    $stmt->execute([$userId, $tourId]);
    echo json_encode(['status' => 'removed']);
}
