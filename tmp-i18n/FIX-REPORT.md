# Laporan Perbaikan Terjemahan Tour — SELESAI

Semua item dikerjakan. Verifikasi: `curl -L ?lang={id,en,zh}` untuk 20 tour aktif (detail + listing + homepage) → **0 kebocoran judul Indonesia di halaman EN & ZH**.

## 1. Perbaikan kode (bug `t()` → `tContent()`)

| File | Perubahan |
|---|---|
| `includes/components/tour-card.php` | Judul, kategori, deskripsi, alt → `tContent()`. Ini penyebab judul tour di halaman listing tampil Indonesia di EN/ZH. |
| `blog-detail.php` | Judul tour terkait + alt → `tContent()` |
| `includes/homepage/tour-flash-deals.php` | Judul promo + alt → `tContent()` |
| `reseller-booking.php` | Judul & kategori tour → `tContent()` |
| `tour-detail.php:941` | `tourTitle` (JS) → `tContent()` (ini sumber judul Indonesia bocor di JSON/JS) |
| `tours.php:76` | Label filter kategori → `t()` (dilokalkan) |
| `tours.php:176` | Label `Loading...` di-wrap `t()` |
| `search-ajax.php` | Autocomplete cari di semua bahasa (`title/title_en/title_zh`, `category/category_en/category_zh`) & kembalikan label sesuai bahasa aktif |

## 2. Perbaikan data kolom konten (20 tour aktif)

Sebelum → Sesudah (jumlah kosong):
- `category_en` 19→0, `category_zh` 20→0
- `highlights_en` 19→0, `highlights_zh` 15→0
- `includes_en` 19→0, `excludes_en` 19→0
- `description_zh` (131) 1→0

Kategori yang salah juga dibetulkan sesuai destinasi (mis. tour Xi'an yang tadinya berlabel "Shenzhen" → "Xi'an").

## 3. Key UI di tabel `translations`

Ditambah/diperbaiki (en + zh): label fasilitas (`Hotel Bintang 4`→4-Star Hotel/四星级酒店, `Transport AC`, `Makan Sesuai Itinerary`, `Tour Guide Profesional`, `Dokumentasi`), header (`Paket Termasuk`→Package Includes/套餐包含, `Tidak Termasuk`), `Perlindungan pembatalan…`, `Simpan ke Itinerary Saya`, `Silakan`, `untuk menyimpan itinerary`, `Tambah Asuransi Perjalanan`, `Akun`, `Home`, `Subtotal`, `Loading...`, dan 12 nama kategori.

## 4. Verifikasi (curl)

Halaman **detail tour 148** `?lang=zh`: badge kategori `西安`, label fasilitas `四星级酒店/空调车/按行程用餐/专业导游/摄影记录`, header `套餐包含`/`不包含`, highlights & includes/excludes Mandarin → semua muncul.
Halaman **listing** `?lang=en`: judul `Half-Day Small Group Tour: Xian Terracotta Army`, filter kategori `北京/上海/西安/…`.
Sisa string yang tetap sama antar bahasa hanya yang memang bukan terjemahan: kode mata uang (IDR/USD/SGD), brand (TourAndTravel, PayPal, eSIM), alamat, email, label PDF.

## Catatan (di luar cakupan terjemahan)

- **39 tour nonaktif** (`is_active=0`) tetap tanpa kolom `_en/_zh` — tidak tampil di situs, jadi dibiarkan.
- `location_city` tidak dipakai di frontend (tidak perlu diterjemahkan).
- 629 key `translations` lain masih bernilai `en` = ID (mayoritas panel admin, bukan halaman tour) — bisa dikerjakan terpisah bila perlu.

## Cara reproduksi seed

```bash
php database/seed-tour-i18n-complete.php
```
