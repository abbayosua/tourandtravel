<?php
/**
 * I18nNotifyTest — regresi terjemahan pesan notifikasi in-app (dibuat saat event,
 * jadi harus lokal saat dibuat — bukan hardcoded Indonesia).
 */

function testInAppNotificationStringsLocalized(): void {
    $keys = [
        'Pembayaran diterima', 'telah dibayar.', 'Status Topup',
        'telah disetujui. Saldo bertambah.', 'telah ditolak.',
    ];
    $prev = $_SESSION['lang'] ?? null;
    try {
        foreach (['en', 'zh'] as $lang) {
            $_SESSION['lang'] = $lang;
            foreach ($keys as $k) {
                assertTrue(t($k) !== $k, "notifikasi '$k' belum diterjemahkan untuk $lang");
            }
        }
    } finally {
        if ($prev === null) { unset($_SESSION['lang']); } else { $_SESSION['lang'] = $prev; }
    }
}
