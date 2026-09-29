<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
$keys = [
    'Paket tour pilihan, villa & pengalaman — booking instan, harga terbaik.',
    'Temukan',
    'Perfect',
    'Trip',
];
foreach ($keys as $k) {
    echo "KEY: $k\n";
    foreach (['en','zh'] as $lang) {
        $stmt = db()->prepare("SELECT value FROM translations WHERE `key` = ? AND lang = ? LIMIT 1");
        $stmt->execute([$k, $lang]);
        $row = $stmt->fetch();
        echo "  $lang: " . ($row ? $row['value'] : 'MISSING') . "\n";
    }
}
echo "LANG_NOW: " . getCurrentLang() . "\n";
