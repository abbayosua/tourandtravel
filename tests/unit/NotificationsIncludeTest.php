<?php
/**
 * NotificationsIncludeTest — regresi jalur include notifikasi.
 *
 * Bug yang dijaga: file di ROOT (mis. nusatrip-book.php) meng-include
 * `__DIR__ . '/notifications.php'` — itu HALAMAN /notifications.php (yang
 * me-redirect ke login saat belum login dan merender halaman Notifikasi),
 * bukan library `includes/notifications.php`. Akibatnya user login yang
 * menyelesaikan booking NusaTrip malah disajikan halaman Notifikasi,
 * bukan halaman kode bayar (step=result).
 */

/** File root tidak boleh meng-include halaman notifications.php. */
function testRootPagesUseNotificationsLibraryNotPage(): void {
    $root = dirname(__DIR__, 2);
    $bad = [];
    foreach (glob($root . '/*.php') as $f) {
        $src = file_get_contents($f);
        if ($src === false) continue;
        if (strpos($src, "__DIR__ . '/notifications.php'") !== false) {
            $bad[] = basename($f);
        }
    }
    assertSame([], $bad, 'root page meng-include halaman notifications.php (harus includes/notifications.php)');
}

/** Halaman booking NusaTrip meng-include library yang benar. */
function testNusatripPagesIncludeNotificationsLibrary(): void {
    $root = dirname(__DIR__, 2);
    foreach (['nusatrip-book.php', 'nusatrip-flight-book.php'] as $name) {
        $src = file_get_contents($root . '/' . $name);
        assertTrue($src !== false, "$name terbaca");
        assertContains("__DIR__ . '/includes/notifications.php'", $src, "$name memakai library notifications");
    }
}
