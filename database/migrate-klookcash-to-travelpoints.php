<?php
/**
 * migrate-klookcash-to-travelpoints.php
 *
 * Rename istilah brand "KlookCash" -> "TravelPoints" di seluruh data yang
 * disimpan di database (translations, faq, points ledger).
 *
 * Idempotent: aman dijalankan berulang.
 * Jalankan: php database/migrate-klookcash-to-travelpoints.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$from = 'KlookCash';
$to   = 'TravelPoints';

function renameCol(string $table, string $col): int
{
    global $from, $to;
    try {
        $stmt = db()->prepare("UPDATE `$table` SET `$col` = REPLACE(`$col`, ?, ?) WHERE `$col` LIKE ?");
        $stmt->execute([$from, $to, '%' . $from . '%']);
        return $stmt->rowCount();
    } catch (Throwable $e) {
        echo "  SKIP $table.$col (" . $e->getMessage() . ")\n";
        return 0;
    }
}

$total = 0;

// 1. translations: pindahkan key lama -> key baru, lalu perbarui value.
echo "translations:\n";
$rows = db()->query("SELECT `key`, lang, value FROM translations WHERE `key` LIKE '%$from%' OR value LIKE '%$from%'")
    ->fetchAll(PDO::FETCH_ASSOC);
$del = db()->prepare("DELETE FROM translations WHERE `key` = ? AND lang = ?");
$ups = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
foreach ($rows as $r) {
    $newKey = str_replace($from, $to, $r['key']);
    $newVal = str_replace($from, $to, $r['value']);
    if ($newKey !== $r['key']) {
        $del->execute([$r['key'], $r['lang']]);
    }
    $ups->execute([$newKey, $r['lang'], $newVal]);
    $total++;
}
echo "  migrated " . count($rows) . " rows\n";

// 2. Konten tersimpan lainnya.
$total += renameCol('faq_categories', 'name');
$total += renameCol('faq_items', 'question');
$total += renameCol('faq_items', 'answer');
$total += renameCol('faq_items', 'question_en');
$total += renameCol('faq_items', 'answer_en');
$total += renameCol('faq_items', 'question_zh');
$total += renameCol('faq_items', 'answer_zh');
$total += renameCol('points_ledger', 'note');

echo "Done. Total baris terpengaruh: $total\n";
