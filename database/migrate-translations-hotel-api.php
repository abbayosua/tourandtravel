<?php
/**
 * migrate-translations-hotel-api.php
 *
 * Terjemahan halaman admin/hotel-api-settings.php yang belum ada baris en/zh.
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-hotel-api.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Urutan prioritas (menang atas Sumber utama)' => 'Priority order (overrides Primary source)',
        'Dipakai bila Urutan kosong.' => 'Used when Order is empty.',
        'Coba berurutan hingga ada hasil. Checkout ikut sumber: NusaTrip native, OYO/Booking link provider, lokal form sendiri. Kosongkan = ikut Sumber utama.' => 'Tried in order until results are found. Checkout follows the source: NusaTrip native, OYO/Booking provider link, local own form. Empty = follow Primary source.',
        'Lokal saja (tanpa live)' => 'Local only (no live)',
        'Urutan sumber hanya boleh: nusatrip, oyo, lokal (pisah koma)' => 'Source order may only contain: nusatrip, oyo, lokal (comma-separated)',
    ],
    'zh' => [
        'Urutan prioritas (menang atas Sumber utama)' => '优先级排序（优先于主要来源）',
        'Dipakai bila Urutan kosong.' => '排序为空时使用。',
        'Coba berurutan hingga ada hasil. Checkout ikut sumber: NusaTrip native, OYO/Booking link provider, lokal form sendiri. Kosongkan = ikut Sumber utama.' => '按顺序尝试直到有结果。结账跟随来源：NusaTrip原生、OYO/Booking供应商链接、本地自有表单。留空=跟随主要来源。',
        'Lokal saja (tanpa live)' => '仅本地（无实时）',
        'Urutan sumber hanya boleh: nusatrip, oyo, lokal (pisah koma)' => '来源排序仅允许：nusatrip、oyo、lokal（逗号分隔）',
        'OYO' => 'OYO',
    ],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $count++;
    }
}
echo "Upserted $count hotel-api translation rows.\n";
