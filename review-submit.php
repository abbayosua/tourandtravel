<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$tourId = (int)($_POST['tour_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
$userId = $_SESSION['user_id'];

if (!$tourId || $rating < 1 || $rating > 5 || !$comment) {
    header('Location: tours.php');
    exit;
}

if (!canReview($userId, $tourId)) {
    header("Location: tour-detail.php?slug=" . ($_POST['slug'] ?? ''));
    exit;
}

$stmt = db()->prepare("INSERT INTO reviews (tour_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
$stmt->execute([$tourId, $userId, $rating, $comment]);

// Ambil slug untuk redirect
$tour = getTourById($tourId);
header("Location: tour-detail.php?slug=" . e($tour['slug']) . "&review=success");
exit;
