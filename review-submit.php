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
$reviewLang = in_array($_POST['review_lang'] ?? 'id', ['id', 'en'], true) ? ($_POST['review_lang'] ?? 'id') : 'id';
$userId = $_SESSION['user_id'];

if (!$tourId || $rating < 1 || $rating > 5 || !$comment) {
    header('Location: tours.php');
    exit;
}

if (!canReview($userId, $tourId)) {
    header("Location: tour-detail.php?slug=" . ($_POST['slug'] ?? ''));
    exit;
}

$stmt = db()->prepare("INSERT INTO reviews (tour_id, user_id, rating, comment, lang) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$tourId, $userId, $rating, $comment, $reviewLang]);
$reviewId = (int)db()->lastInsertId();

// Simpan sub-rating per aspek
$validAspects = ['cleanliness', 'location', 'staff', 'value', 'facilities', 'comfort'];
$subratings = $_POST['subrating'] ?? [];
if (is_array($subratings) && $reviewId > 0) {
    $srStmt = db()->prepare("INSERT IGNORE INTO review_subratings (review_id, aspect, rating) VALUES (?, ?, ?)");
    foreach ($subratings as $aspect => $val) {
        $aspect = strtolower(trim($aspect));
        $val = (int)$val;
        if (in_array($aspect, $validAspects, true) && $val >= 1 && $val <= 5) {
            $srStmt->execute([$reviewId, $aspect, $val]);
        }
    }
}

// Simpan hingga 3 foto ulasan
require_once 'includes/functions.php';
if (!is_dir(__DIR__ . '/uploads/reviews')) mkdir(__DIR__ . '/uploads/reviews', 0775, true);
for ($i = 1; $i <= 3; $i++) {
    if (!empty($_FILES['review_photo']['name'][$i - 1])) {
        $file = ['name' => $_FILES['review_photo']['name'][$i - 1], 'type' => $_FILES['review_photo']['type'][$i - 1], 'tmp_name' => $_FILES['review_photo']['tmp_name'][$i - 1], 'error' => $_FILES['review_photo']['error'][$i - 1], 'size' => $_FILES['review_photo']['size'][$i - 1]];
        $up = uploadGambar($file, __DIR__ . '/uploads/reviews');
        if ($up['success']) {
            db()->prepare("INSERT INTO review_images (review_id, path) VALUES (?, ?)")->execute([$reviewId, 'uploads/reviews/' . $up['filename']]);
        }
    }
}

// Ambil slug untuk redirect
$tour = getTourById($tourId);
header("Location: tour-detail.php?slug=" . e($tour['slug']) . "&review=success");
exit;
