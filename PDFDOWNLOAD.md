# PDFDOWNLOAD.md — Download Itinerary PDF

## Overview

Generate PDF untuk setiap itinerary di halaman `my-itinerary.php` dan detail itinerary (`itinerary-ajax.php?action=get`). Desain cantik, background image transparan, gambar wisata/hotel.

## Tech Stack

**FPDF** — library PHP ringan (1 file `fpdf.php`, ~130 KB), tanpa dependency Composer.  
Alasan: server produksi tidak memiliki `composer`, dan FPDF berfungsi di PHP 8.2.  
Plus: support gambar, custom font, header/footer, alpha channel.

## Library

- **FPDF**: [http://www.fpdf.org/](http://www.fpdf.org/)
- Download: `wget -O includes/fpdf.php http://www.fpdf.org/en/fpdf.php?r=92`
- Atau copy dari `assets/lib/fpdf.php`
- **FPDI** (optional, untuk import halaman existing) — tidak diperlukan.

## Halaman Baru

### 1. `itinerary-pdf.php`

Endpoint: `itinerary-pdf.php?id=123` (dilindungi login)

Output: langsung `Content-Type: application/pdf`, attachment download.

Logika:

```php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/fpdf.php';

if (!isLoggedIn()) { header('Location: login.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
// Validasi ownership
$stmt = db()->prepare("SELECT * FROM user_itineraries WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$itinerary = $stmt->fetch();
if (!$itinerary) { http_response_code(404); exit; }

// Ambil days + items
$stmt = db()->prepare("SELECT d.id AS day_id, d.day_number, it.*
    FROM user_itinerary_days d
    LEFT JOIN user_itinerary_items it ON it.day_id = d.id
    WHERE d.itinerary_id = ?
    ORDER BY d.day_number, it.sort_order, it.id");
$stmt->execute([$id]);
$rows = $stmt->fetchAll();

// Group by day
$days = [];
foreach ($rows as $r) {
    $days[$r['day_number']]['items'][] = $r;
}

// Generate PDF
class PDF extends FPDF {
    protected $bgOpacity = 0.08; // 8% opacity background

    function Header() {
        // Background image (watermark)
        $bg = 'assets/img/bg-itinerary.jpg';
        if (is_file($bg)) {
            $this->SetAlpha($this->bgOpacity);
            $this->Image($bg, 0, 0, 210, 297); // A4
            $this->SetAlpha(1);
        }
        // Header bar
        $this->SetFillColor(0, 100, 210);
        $this->Rect(0, 0, 210, 12, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetXY(10, 3);
        $this->Cell(0, 6, 'TourAndTravel - Itinerary', 0, 0, 'C');
        $this->Ln(15);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(150);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function SetAlpha($alpha) {
        // Gunakan alpha blending via extended FPDF atau GD
        $this->_extgstates[$alpha] ??= $this->_newExtGState(['ca' => $alpha, 'CA' => $alpha]);
        $this->SetExtGState($this->_extgstates[$alpha]);
    }
}
```

### 2. Tambah tombol download di `my-itinerary.php`

```html
<a href="itinerary-pdf.php?id=<?= $it['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank">
    <i class="bi bi-download"></i> PDF
</a>
```

### 3. Halaman detail itinerary (akan datang) — tombol serupa.

## Fitur PDF

| Fitur | Detail |
|-------|--------|
| Background | Image `assets/img/bg-itinerary.jpg` (8% opacity) — ambil dari unsplash/gambar tour populer |
| Header | Logo + judul itinerary + nama user |
| Per Day | Card-style per hari dengan nomor hari (Day 1, Day 2...) |
| Item | Icon sesuai tipe (tour/hotel/flight/custom), title, time_label, note |
| Gambar | Jika `tour_id` / `hotel_id`, ambil foto pertama dari `tours.photo` / `hotels.image` |
| Warna | Brand blue `#0064D2`, teks dark `#1A1A2E`, secondary `#5A6178` |
| Font | Helvetica (built-in), bisa custom TTF jika diperlukan |
| Metadata | Tanggal generate, nomor halaman, footer copyright |

## Struktur Halaman PDF (A4, portrait)

```
+----------------------------------------------------+
| [HEADER] TourAndTravel — Nama Itinerary           |
| User: Nama User | Tgl: 12 Jan 2026                |
+----------------------------------------------------+
|                                                    |
| Day 1 — 12 Jan 2026                               |
| ┌────────────────────────────────────────────────┐ |
| │ 🏨 Hotel Santika                               │ |
| │ ⏰ 14:00 — Check-in                            │ |
| │ 📝 Kamar deluxe, view laut                    │ |
| │ [gambar hotel]                                 │ |
| └────────────────────────────────────────────────┘ |
| ┌────────────────────────────────────────────────┐ |
| │ 🎯 Tour Kota Tua                               │ |
| │ ⏰ 09:00 — 12:00                               │ |
| │ [gambar tour]                                  │ |
| └────────────────────────────────────────────────┘ |
|                                                    |
| Day 2 — 13 Jan 2026                               |
| ...                                                |
+----------------------------------------------------+
| [FOOTER] Page 1/3                                  |
+----------------------------------------------------+
```

## Edge Cases & Sad Paths

| Skenario | Penanganan |
|----------|-----------|
| Background image tidak ada | Fallback: gradient warna solid via rect |
| Gambar tour/hotel tidak ada | Tampilkan placeholder / skip area gambar |
| Itinerary tanpa items | Tampilkan "No activities planned" |
| User bukan pemilik | 404 / redirect |
| FPDF error (memory) | try-catch, log error, output JSON error |
| PHP < 8.0 | FPDF kompatibel ke belakang |
| Server tanpa GD | FPDF bisa tanpa GD untuk basic image |

## Background Image

Pilih 1 gambar landscape menarik (travel/nature) untuk watermark.

```bash
# Download dari Unsplash (gratis)
curl -sL "https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=1920" -o assets/img/bg-itinerary.jpg
```

Atau gunakan gambar tour pertama dari database yang sudah ada.

## Testing

1. Akses `my-itinerary.php` — klik tombol PDF
2. Verifikasi: file terdownload dengan ekstensi `.pdf`
3. Buka file: cek header, background transparan, daftar hari, item, gambar
4. Cek sad path: itinerary kosong, tanpa gambar, user lain (404)
5. Test di localhost dulu, lalu deploy

## Status Implementasi (Selesai)

| Komponen | File | Status |
|----------|------|--------|
| Download FPDF v1.82 + font | `includes/fpdf.php`, `includes/font/` | Selesai |
| `itinerary-pdf.php` | `itinerary-pdf.php` | Selesai — A4 portrait, header biru, card per item, gambar, background watermark |
| Background image | `assets/img/bg-itinerary.jpg` (Unsplash, 761 KB) | Selesai |
| Tombol download di UI | `my-itinerary.php` | Selesai — ikon download di setiap kartu itinerary |
| Testing | Lokal verified: PDF ~763 KB, valid PDF 1.3, semua halaman, gambar, font | Selesai |
