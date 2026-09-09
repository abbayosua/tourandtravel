<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$sqlFile = __DIR__ . '/migrate-translations-zh.sql';
if (!is_file($sqlFile)) {
    echo "ERROR: migrate-translations-zh.sql not found\n";
    exit(1);
}

try {
    $db = db();

    // 1) Pastikan unique key (key, lang) ada
    $idx = $db->query(
        "SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE table_schema = DATABASE() AND table_name = 'translations'
           AND index_name = 'uniq_key_lang' AND non_unique = 0"
    )->fetchColumn();
    if (!$idx) {
        $db->exec("ALTER TABLE translations ADD UNIQUE KEY uniq_key_lang (`key`(100), lang)");
        echo "OK: unique key uniq_key_lang ditambahkan\n";
    } else {
        echo "OK: unique key uniq_key_lang sudah ada\n";
    }

    // 2) Seed terjemahan zh (idempotent)
    $sql = file_get_contents($sqlFile);
    $db->exec($sql);

    $zh = $db->query("SELECT COUNT(*) FROM translations WHERE lang = 'zh'")->fetchColumn();
    echo "OK: baris translations lang=zh sekarang: {$zh}\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
