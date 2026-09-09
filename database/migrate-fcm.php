<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$sqlFile = __DIR__ . '/migrate-fcm.sql';
if (!is_file($sqlFile)) {
    echo "ERROR: migrate-fcm.sql not found\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    echo "ERROR: cannot read migrate-fcm.sql\n";
    exit(1);
}

try {
    db()->exec($sql);
    echo "OK: fcm_tokens table migrated\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
