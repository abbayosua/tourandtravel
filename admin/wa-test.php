<?php
require_once '../includes/config.php';
require_once '../includes/send-wa.php';

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$phone = trim($_POST['test_phone'] ?? WA_ADMIN);
if (!$phone) {
    header('Location: wa-settings.php?error=' . urlencode('Nomor WA harus diisi'));
    exit;
}

$sent = sendWA($phone, "✅ *Test Notifikasi*\n\nHalo! Ini adalah pesan test dari *TourAndTravel*.\nNotifikasi WhatsApp berfungsi dengan baik.\n\n" . date('d/m/Y H:i'));

if ($sent) {
    header('Location: wa-settings.php?message=' . urlencode("Test WA berhasil dikirim ke $phone"));
} else {
    header('Location: wa-settings.php?error=' . urlencode("Gagal kirim WA ke $phone. Cek server log."));
}
exit;
