<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
$rows = db()->query('SELECT id, title_en, title_zh FROM tours WHERE is_active = 1 ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$out = '-- tour titles EN ZH' . "\n";
foreach ($rows as $r) {
    if (empty($r['title_en']) && empty($r['title_zh'])) continue;
    $out .= 'UPDATE tours SET title_en = ' . db()->quote($r['title_en']) . ', title_zh = ' . db()->quote($r['title_zh']) . ' WHERE id = ' . (int)$r['id'] . ";\n";
}
file_put_contents(__DIR__ . '/seed-tour-titles.sql', $out);
echo 'wrote ' . count($rows) . " rows\n";
