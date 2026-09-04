<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=600');

$rates = getExchangeRates();

if ($rates) {
    echo json_encode(['success' => true, 'rates' => $rates]);
} else {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => t('Gagal mengambil kurs')]);
}
