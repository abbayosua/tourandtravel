<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$keys = [
    'Booking Code:' => 'Booking Code:',
    'Contoh: pilih +62 lalu tulis 08517488415 — otomatis dikirim "62 8517488415"' => 'Example: select +62 then type 08517488415 — automatically sent "62 8517488415"',
    'Data Tamu' => 'Guest Data',
    'Diajukan' => 'Submitted',
    'Free day / no activities' => 'Free day / no activities',
    'Home' => 'Home',
    'Kartu Kredit / Debit' => 'Credit / Debit Card',
    'Kartu dummy akan ditolak bank (kode 12101) — booking tercatat tapi tidak terbayar.' => 'Dummy card will be rejected by bank (code 12101) — booking recorded but unpaid.',
    'Loading...' => 'Loading...',
    'Menunggu Pembayaran' => 'Waiting for Payment',
    'Minimal Rp 50.000' => 'Minimum Rp 50,000',
    'Modul NusaTrip:' => 'NusaTrip Module:',
    'Modul OYO:' => 'OYO Module:',
    'No activities planned yet.' => 'No activities planned yet.',
    'No itinerary details available.' => 'No itinerary details available.',
    'Nomor HP (pilih kode negara, tulis nomor lokal saja)' => 'Phone Number (select country code, type local number only)',
    'OYO' => 'OYO',
    'Pembayaran (langsung ke NusaTrip)' => 'Payment (direct to NusaTrip)',
    'Pembayaran Berhasil' => 'Payment Successful',
    'Pembayaran Gagal / Ditolak' => 'Payment Failed / Rejected',
    'Pesan tiket ferry — booking instan, harga terbaik.' => 'Book ferry tickets — instant booking, best prices.',
    'Status bayar:' => 'Payment status:',
    'Task:' => 'Task:',
    'Tiket kereta pilihan — booking instan, harga terbaik.' => 'Best train tickets — instant booking, best prices.',
    'Virtual Account' => 'Virtual Account',
    'ferries' => 'ferries',
    'trains' => 'trains',
];

$inserted = 0;
foreach ($keys as $key => $en) {
    $stmt = db()->prepare("INSERT IGNORE INTO translations (`key`, lang, value) VALUES (?, 'en', ?)");
    $stmt->execute([$key, $en]);
    if ($stmt->rowCount() > 0) $inserted++;
}

echo "Inserted $inserted EN translations\n";
