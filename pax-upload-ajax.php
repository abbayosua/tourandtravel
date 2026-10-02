<?php
/**
 * pax-upload-ajax.php — upload 1 foto paspor peserta (dipakai modal multi-peserta).
 * Upload per-file agar tidak kena batas max_file_uploads / post_max_size PHP.
 * Return: {success, filename}
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => t('Method not allowed')]);
    exit;
}

if (!csrfCheck()) {
    echo json_encode(['success' => false, 'message' => t('Sesi tidak valid, silakan muat ulang halaman.')]);
    exit;
}

if (!isset($_FILES['passport']) || (int)($_FILES['passport']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    echo json_encode(['success' => false, 'message' => t('Foto paspor wajib diupload')]);
    exit;
}

$upload = uploadWebP($_FILES['passport'], __DIR__ . '/uploads/passports');
if (!$upload['success']) {
    echo json_encode(['success' => false, 'message' => $upload['message']]);
    exit;
}

echo json_encode([
    'success' => true,
    'filename' => $upload['filename'],
    'url' => 'uploads/passports/' . $upload['filename'],
]);
