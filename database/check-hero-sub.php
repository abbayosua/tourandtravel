<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
$k = 'Paket tour pilihan, villa & pengalaman — booking instan, harga terbaik.';
foreach (['id','en','zh'] as $lang) {
    $stmt = db()->prepare("SELECT `key`, lang, value FROM translations WHERE `key` = ? AND lang = ? LIMIT 1");
    $stmt->execute([$k, $lang]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $lang . ': ' . ($row ? $row['value'] : 'MISSING') . "\n";
}
