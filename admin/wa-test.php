<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/send-wa.php';

cekLogin();

$phone = trim($_POST['test_phone'] ?? WA_ADMIN);
if (!$phone) {
    header('Location: wa-settings.php?error=' . urlencode(t('Nomor WA harus diisi')));
    exit;
}

$sent = sendWA($phone, "✅ *Test Notifikasi*\n\nHalo! Ini adalah pesan test dari *" . siteName() . "*.\nNotifikasi WhatsApp berfungsi dengan baik.\n\n" . date('d/m/Y H:i'));

if ($sent) {
    header('Location: wa-settings.php?message=' . urlencode(sprintf(t('Test WA berhasil dikirim ke %s'), $phone)));
} else {
    header('Location: wa-settings.php?error=' . urlencode(sprintf(t('Gagal kirim WA ke %s. Cek server log.'), $phone)));
}
exit;
