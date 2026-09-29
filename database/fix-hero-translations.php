<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Fix EN for 'Temukan'
$stmt = db()->prepare("UPDATE translations SET value = 'Find' WHERE `key` = 'Temukan' AND lang = 'en'");
$stmt->execute();
echo 'Fixed EN Temukan: ' . $stmt->rowCount() . "\n";

// Add ZH translations
$zh = [
    'Temukan' => '寻找',
    'Perfect' => '完美',
    'Trip' => '旅程',
];
foreach ($zh as $k => $v) {
    $stmt = db()->prepare("INSERT IGNORE INTO translations (`key`, lang, value) VALUES (?, 'zh', ?)");
    $stmt->execute([$k, $v]);
    echo "Added ZH $k: " . $stmt->rowCount() . "\n";
}
echo "DONE\n";
