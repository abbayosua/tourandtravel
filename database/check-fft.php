<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
foreach (['Flight', 'Ferry', 'Train', 'flights', 'ferries', 'trains'] as $k) {
    $s = db()->prepare('SELECT value FROM translations WHERE `key` = ? AND lang = ? LIMIT 1');
    $s->execute([$k, 'zh']);
    $r = $s->fetch();
    echo $k . ': ' . ($r ? $r['value'] : 'MISS') . "\n";
}
