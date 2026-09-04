<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => t('Method not allowed')]);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => t('Alamat email tidak valid')]);
    exit;
}

try {
    // Cek duplikat
    $stmt = db()->prepare("SELECT COUNT(*) FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => t('Email sudah terdaftar')]);
        exit;
    }

    $stmt = db()->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
    $stmt->execute([$email]);
    echo json_encode(['success' => true, 'message' => t('Berhasil berlangganan! Cek email Anda untuk konfirmasi.')]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => t('Terjadi kesalahan. Coba lagi nanti.')]);
}