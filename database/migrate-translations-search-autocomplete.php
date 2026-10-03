<?php
/**
 * migrate-translations-search-autocomplete.php
 *
 * Terjemahan en/zh untuk state loading/empty/error pada autocomplete search
 * (assets/js/script.js, initSearchAutocomplete: tours/nav/hero search).
 * Sebelumnya kegagalan fetch dibiarkan senyap (tanpa pesan).
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE.
 * Jalankan: php database/migrate-translations-search-autocomplete.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => [
        'Mencari...' => 'Searching...',
        'Tidak ada hasil ditemukan' => 'No results found',
        'Gagal memuat hasil. Coba lagi.' => 'Failed to load results. Try again.',
    ],
    'zh' => [
        'Mencari...' => '搜索中……',
        'Tidak ada hasil ditemukan' => '未找到结果',
        'Gagal memuat hasil. Coba lagi.' => '加载结果失败，请重试。',
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
echo "Upserted $count search-autocomplete translation rows.\n";
