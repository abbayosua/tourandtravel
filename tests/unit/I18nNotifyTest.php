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

function testRefundPaymentApiStringsLocalized(): void {
    $keys = [
        'Topup Disetujui', 'Topup Ditolak', 'Pembayaran Diterima',
        'Refund pembatalan booking', 'Refund ditolak', 'Refund disetujui:', 'dikredit ke wallet',
        'Tidak ada penerbangan untuk rute/tanggal ini.',
        'Format token tidak valid', 'Bahasa tidak didukung',
        'Token Google tidak valid atau email tidak terverifikasi', 'Gagal membuat session',
    ];
    $prev = $_SESSION['lang'] ?? null;
    try {
        foreach (['en', 'zh'] as $lang) {
            $_SESSION['lang'] = $lang;
            foreach ($keys as $k) {
                assertTrue(t($k) !== $k, "pesan '$k' belum diterjemahkan untuk $lang");
            }
        }
    } finally {
        if ($prev === null) { unset($_SESSION['lang']); } else { $_SESSION['lang'] = $prev; }
    }
}

function testEmailValidationStringLocalized(): void {
    $prev = $_SESSION['lang'] ?? null;
    try {
        foreach (['en', 'zh'] as $lang) {
            $_SESSION['lang'] = $lang;
            assertTrue(t('alamat email tidak valid') !== 'alamat email tidak valid', "email error belum diterjemahkan untuk $lang");
        }
    } finally {
        if ($prev === null) { unset($_SESSION['lang']); } else { $_SESSION['lang'] = $prev; }
    }
}
