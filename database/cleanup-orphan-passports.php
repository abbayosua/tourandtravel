<?php
/**
 * cleanup-orphan-passports.php — hapus foto paspor yatim di uploads/passports.
 *
 * Yatim = file yang tidak dirujuk booking_participants.passport_photo
 * maupun bookings.passport_photo, dan umurnya sudah lewat batas (default 24 jam,
 * agar upload yang sedang diisi user tidak terhapus).
 *
 * Pakai: php database/cleanup-orphan-passports.php [--hours=24] [--dry-run]
 * Cron contoh (harian 03:00):
 *   0 3 * * * cd /path/to/public_html && php database/cleanup-orphan-passports.php >> /dev/null 2>&1
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$hours = 24;
$dryRun = false;
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--hours=(\d+)$/', $arg, $m)) $hours = max(1, (int)$m[1]);
    if ($arg === '--dry-run') $dryRun = true;
}

$dir = __DIR__ . '/../uploads/passports';
if (!is_dir($dir)) {
    fwrite(STDERR, "Direktori tidak ditemukan: $dir\n");
    exit(1);
}

$referenced = [];
foreach (db()->query("SELECT passport_photo FROM booking_participants WHERE passport_photo IS NOT NULL") as $r) {
    $referenced[$r['passport_photo']] = true;
}
foreach (db()->query("SELECT passport_photo FROM bookings WHERE passport_photo IS NOT NULL") as $r) {
    $referenced[$r['passport_photo']] = true;
}

$cutoff = time() - ($hours * 3600);
$deleted = 0;
$freed = 0;
foreach (glob($dir . '/*.webp') as $path) {
    $name = basename($path);
    if (isset($referenced[$name])) continue;
    if (filemtime($path) > $cutoff) continue;
    $freed += (int)filesize($path);
    $deleted++;
    if ($dryRun) {
        echo "would delete: $name\n";
    } else {
        @unlink($path);
    }
}

printf("%s %d file yatim (>%d jam), %.1f KB\n", $dryRun ? 'DRY-RUN:' : 'Terhapus:', $deleted, $hours, $freed / 1024);
