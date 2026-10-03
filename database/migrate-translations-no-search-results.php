<?php
/**
 * migrate-translations-no-search-results.php
 *
 * Pesan empty state saat pencarian daftar admin tidak menghasilkan apa-apa
 * (berbeda dari "Belum ada data.").
 *
 * Idempotent.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$dict = [
    'en' => ['Tidak ada hasil untuk pencarian Anda.' => 'No results for your search.'],
    'zh' => ['Tidak ada hasil untuk pencarian Anda.' => '未找到符合搜索的结果。'],
];

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$count = 0;
foreach ($dict as $lang => $rows) {
    foreach ($rows as $key => $value) { $stmt->execute([$key, $lang, $value]); $count++; }
}
echo "Upserted $count no-search-results translation rows.\n";
