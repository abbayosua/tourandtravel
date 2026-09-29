<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
$fixed = 0;
$stmt = db()->query("SELECT `key`, value FROM translations WHERE lang = 'zh'");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $f = fixMojibake($row['value']);
    if ($f !== $row['value']) {
        $u = db()->prepare('UPDATE translations SET value = ? WHERE `key` = ? AND lang = ?');
        $u->execute([$f, $row['key'], 'zh']);
        echo "FIX: " . $row['key'] . "\n";
        $fixed++;
    }
}
saveTranslation('Train', 'zh', '火车');
saveTranslation('Flight', 'zh', '航班');
echo "FIXED: $fixed\nDONE\n";
