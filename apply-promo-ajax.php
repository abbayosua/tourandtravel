<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => t('Method not allowed')]);
    exit;
}

$code = strtoupper(trim($_POST['code'] ?? ''));
$subtotal = (float)($_POST['subtotal'] ?? 0);

if (!$code) {
    echo json_encode(['success' => false, 'message' => t('Masukkan kode promo')]);
    exit;
}

if ($subtotal <= 0) {
    echo json_encode(['success' => false, 'message' => t('Subtotal tidak valid')]);
    exit;
}

try {
    $promo = validatePromoCode((string)($_POST['code'] ?? ''), (float)($_POST['subtotal'] ?? 0));
    if (!$promo) {
        echo json_encode(['success' => false, 'message' => t('Kode promo tidak berlaku')]);
        exit;
    }
    echo json_encode([
        'success' => true,
        'message' => t('Kode promo berlaku!'),
        'code' => $promo['code'],
        'discount_type' => 'percentage',
        'discount_value' => (float)$promo['discount'],
        'discount' => $promo['discount'],
        'total' => (float)$_POST['subtotal'] - $promo['discount'],
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => t('Terjadi kesalahan. Coba lagi nanti.')]);
}