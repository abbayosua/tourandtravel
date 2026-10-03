<?php
/**
 * migrate-translations-tawk-help.php
 *
 * Help text tawk.to di admin/chat-settings.php punya nilai en yang MASIH
 * bahasa Indonesia ("Dari dashboard tawk.to: ... Kosongkan untuk menonaktifkan
 * widget.") sehingga versi EN bocor. Perbaiki nilai en (zh sudah benar).
 *
 * Idempotent.
 * Jalankan: php database/migrate-translations-tawk-help.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$key = 'Dari dashboard tawk.to: Administration → Property ID. Kosongkan untuk menonaktifkan widget.';
$en  = 'From the tawk.to dashboard: Administration → Property ID. Leave empty to disable the widget.';

$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, 'en', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$stmt->execute([$key, $en]);
echo "Upserted tawk.to help en translation.\n";
