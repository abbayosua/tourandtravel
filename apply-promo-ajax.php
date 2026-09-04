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
    $stmt = db()->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$code]);
    $promo = $stmt->fetch();

    if (!$promo) {
        echo json_encode(['success' => false, 'message' => t('Kode promo tidak ditemukan')]);
        exit;
    }

    // Validasi periode berlaku
    $today = date('Y-m-d');
    if ($today < $promo['valid_from']) {
        echo json_encode(['success' => false, 'message' => t('Kode promo belum berlaku')]);
        exit;
    }
    if ($today > $promo['valid_until']) {
        echo json_encode(['success' => false, 'message' => t('Kode promo sudah kedaluwarsa')]);
        exit;
    }

    // Validasi usage limit
    if ($promo['usage_limit'] !== null && $promo['used_count'] >= $promo['usage_limit']) {
        echo json_encode(['success' => false, 'message' => t('Kode promo sudah mencapai batas pemakaian')]);
        exit;
    }

    // Validasi minimal pembelian
    if ($promo['min_purchase'] !== null && $subtotal < $promo['min_purchase']) {
        $minText = formatRupiah((float)$promo['min_purchase']);
        echo json_encode(['success' => false, 'message' => str_replace(':amount', $minText, t('Minimal pembelian :amount untuk kode ini'))]);
        exit;
    }

    // Hitung diskon
    if ($promo['discount_type'] === 'percentage') {
        $discount = $subtotal * ($promo['discount_value'] / 100);
        // Batasi max_discount jika ada
        if ($promo['max_discount'] !== null && $discount > $promo['max_discount']) {
            $discount = (float)$promo['max_discount'];
        }
    } else {
        $discount = (float)$promo['discount_value'];
    }

    $discount = min($discount, $subtotal); // tidak melebihi subtotal
    $total = $subtotal - $discount;

    echo json_encode([
        'success' => true,
        'message' => t('Kode promo berlaku!'),
        'code' => $code,
        'discount_type' => $promo['discount_type'],
        'discount_value' => (float)$promo['discount_value'],
        'discount' => $discount,
        'total' => $total,
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => t('Terjadi kesalahan. Coba lagi nanti.')]);
}