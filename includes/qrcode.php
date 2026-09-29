<?php
/**
 * includes/qrcode.php — generator QR code.
 *
 * Satu-satunya tempat yang tahu cara membuat URL QR. Bila nanti ingin pindah
 * metode (library PHP / JS CDN / API lain), cukup ganti isi qrCodeUrl().
 */

/**
 * URL QR code untuk data tertentu.
 *
 * @param string  $data Teks yang di-encode (mis. kode booking dari DB)
 * @param int     $size Px (default 200)
 * @return string URL gambar QR
 */
function qrCodeUrl(string $data, int $size = 200): string {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . max(50, $size) . 'x' . max(50, $size) . '&margin=8&data=' . urlencode($data);
}
