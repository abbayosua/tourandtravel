<?php
/**
 * I18nEmailTest — regresi terjemahan email & PDF per bahasa.
 *
 * Menjamin template email transaksional TIDAK menyisakan teks Indonesia saat
 * dirender dalam bahasa en/zh, dan label PDF brosur tersedia di ketiga bahasa.
 */

require_once __DIR__ . '/../../includes/email.php';

/** Kata penanda khas Indonesia (di luar kata serapan yang juga dipakai EN). */
function i18nEmailIdMarkers(): array {
    return [
        'Kode Booking', 'Tanggal Keberangkatan', 'Jumlah Peserta', 'Titik Kumpul',
        'Bayar Sekarang', 'Lacak Booking', 'Paket Termasuk', 'Terima kasih',
        'Selamat datang', 'Klik tombol', 'Atur Password Baru', 'abaikan email',
        'disetujui', 'ditolak', 'Catatan admin', 'Lihat Dashboard', 'Alasan penolakan',
        'Ajukan Topup Baru', 'Pembayaran diterima', 'Termasuk Asuransi', 'Harga Turun',
        'sekarang', 'Buka', 'Notifikasi dari',
    ];
}

/** Event email yang punya template sendiri (generic.php dikecualikan). */
function i18nEmailEvents(): array {
    return [
        'booking-created' => ['booking_code' => 'TAT-1', 'total' => 'Rp 100', 'pay_link' => 'http://x/pay', 'track_link' => 'http://x/track'],
        'booking-status' => ['booking_code' => 'TAT-2', 'status' => 'paid', 'track_link' => 'http://x/track'],
        'invoice' => ['order_id' => 'ORD-1', 'amount' => 'Rp 50', 'insurance_premi' => 1000, 'insurance_amount' => 'Rp 1.000'],
        'reset-password' => ['reset_link' => 'http://x/reset'],
        'topup-approved' => ['amount' => 'Rp 100.000', 'admin_note' => 'ok', 'track_link' => 'http://x/t'],
        'topup-rejected' => ['amount' => 'Rp 100.000', 'admin_note' => 'no', 'track_link' => 'http://x/t'],
        'welcome' => [],
        'price-alert' => ['item_title' => 'Bali Tour', 'current_price' => 'Rp 900', 'target_price' => 'Rp 1.000', 'link' => 'http://x/t'],
    ];
}

function testEmailTemplatesHaveNoIndonesianInEnZh(): void {
    foreach (i18nEmailEvents() as $event => $data) {
        foreach (['en', 'zh'] as $lang) {
            $t = renderEmailTemplate($event, $data, $lang);
            foreach (i18nEmailIdMarkers() as $marker) {
                assertTrue(
                    mb_strpos($t['html'], $marker) === false,
                    "template '$event' ($lang) masih mengandung teks Indonesia: '$marker'"
                );
            }
        }
    }
}

function testEmailTemplatesIndonesianKeepsIdStrings(): void {
    // Sanity: template memang memuat label ID saat bahasa id (bukan kebetulan kosong).
    $t = renderEmailTemplate('booking-created', i18nEmailEvents()['booking-created'], 'id');
    assertContains('Kode Booking', $t['html']);
    assertContains('Bayar Sekarang', $t['html']);
}

function testPriceAlertEmailTrilingual(): void {
    $data = i18nEmailEvents()['price-alert'];
    $id = renderEmailTemplate('price-alert', $data, 'id');
    assertContains('Harga Turun', $id['html']);
    assertContains('Lihat Sekarang', $id['html']);

    $en = renderEmailTemplate('price-alert', $data, 'en');
    assertContains('Price Dropped', $en['html']);
    assertContains('View Now', $en['html']);

    $zh = renderEmailTemplate('price-alert', $data, 'zh');
    assertContains('价格下降', $zh['html']);
    assertContains('立即查看', $zh['html']);
}

function testGenericEmailTrilingual(): void {
    $data = ['cta_link' => 'http://x/go'];
    $id = renderEmailTemplate('event-tak-ada', $data, 'id');
    assertContains('Notifikasi dari', $id['html']);
    assertContains('>Buka<', $id['html']);

    $en = renderEmailTemplate('event-tak-ada', $data, 'en');
    assertContains('Notification from', $en['html']);
    assertContains('>Open<', $en['html']);
    assertTrue(mb_strpos($en['html'], '>Buka<') === false, 'generic en tidak boleh menyisakan "Buka"');

    $zh = renderEmailTemplate('event-tak-ada', $data, 'zh');
    assertContains('的通知', $zh['html']);
    assertContains('打开', $zh['html']);
}

function testDefaultEmailSubjectsLocalized(): void {
    $id = renderEmailTemplate('booking-created', ['booking_code' => 'TAT-1'], 'id');
    assertContains('Pemesanan Diterima', $id['subject']);

    $en = renderEmailTemplate('booking-created', ['booking_code' => 'TAT-1'], 'en');
    assertContains('Booking Received', $en['subject']);

    $zh = renderEmailTemplate('price-alert', ['item_title' => 'X'], 'zh');
    assertContains('价格下降', $zh['subject']);

    // Subject eksplisit tetap dihormati apa pun bahasanya.
    $custom = renderEmailTemplate('booking-created', ['subject' => 'Custom Subject'], 'zh');
    assertSame('Custom Subject', $custom['subject']);
}

function testPdfBrochureLabelsTrilingual(): void {
    require_once __DIR__ . '/../../includes/pdf-brochure.php';
    if (!function_exists('pdfBrochureLabels')) {
        return; // PDF lib tidak tersedia — lewati
    }
    $id = pdfBrochureLabels('id');
    $en = pdfBrochureLabels('en');
    $zh = pdfBrochureLabels('zh');
    assertTrue(count($id) > 0, 'label brosur tersedia');
    foreach ($id as $key => $val) {
        assertTrue(isset($en[$key]) && $en[$key] !== '', "label PDF '$key' belum ada versi en");
        assertTrue(isset($zh[$key]) && $zh[$key] !== '', "label PDF '$key' belum ada versi zh");
    }
    // Label Indonesia harus benar-benar berbeda dari versi en (bukan fallback).
    assertTrue($id['itinerary'] !== $en['itinerary'], 'label itinerary en masih sama dengan id');
    assertTrue($id['highlights'] !== $en['highlights'], 'label highlights en masih sama dengan id');
}

/** Email booking-status harus menampilkan label status per bahasa, bukan kode mentah. */
function testBookingStatusEmailLocalizesStatusLabel(): void {
    foreach (['en' => 'Paid', 'zh' => '已付款'] as $lang => $label) {
        $t = renderEmailTemplate('booking-status', ['booking_code' => 'TAT-9', 'status' => 'paid', 'track_link' => 'http://x'], $lang);
        assertContains($label, $t['html'], "label status $lang");
        assertTrue(strpos($t['html'], '>paid<') === false, "kode status mentah bocor di $lang");
    }
}
