<?php
/**
 * migrate-translations-mulai.php
 *
 * Key 'Mulai' dipakai sebagai label tanggal mulai (kolom flash sale, form
 * flash sale, dan subtitle itinerary PDF) — artinya "Start", bukan "From".
 * Nilai en sebelumnya "From" (salah konteks). Perbaiki en -> "Start".
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-mulai.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$rows = [
    ['Mulai', 'en', 'Start'],
    ['Mulai', 'zh', '开始'],
];
$count = 0;
foreach ($rows as $r) { $stmt->execute($r); $count++; }
echo "Upserted $count 'Mulai' translation rows.\n";
