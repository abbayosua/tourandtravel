<?php
/**
 * migrate-passenger-profiles-email.php — simpan email di profil penumpang
 * Idempotent: cek information_schema sebelum ALTER (server DB tidak mendukung
 * ADD COLUMN IF NOT EXISTS).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$st = db()->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'passenger_profiles' AND COLUMN_NAME = 'email'");
$st->execute();
if (!(int)$st->fetchColumn()) {
    db()->exec("ALTER TABLE passenger_profiles ADD COLUMN email VARCHAR(200) DEFAULT NULL AFTER phone");
    echo "OK: kolom passenger_profiles.email ditambahkan\n";
} else {
    echo "SKIP: kolom passenger_profiles.email sudah ada\n";
}
