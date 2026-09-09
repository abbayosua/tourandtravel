<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

try {
    $db = db();
    $sqlFile = __DIR__ . '/migrate-translations-zh-extra.sql';
    if (is_file($sqlFile)) {
        $db->exec(file_get_contents($sqlFile));
        echo "OK: extra seed executed\n";
    }
    $zh = $db->query("SELECT COUNT(*) FROM translations WHERE lang = 'zh'")->fetchColumn();
    echo "OK: baris translations lang=zh sekarang: {$zh}\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
