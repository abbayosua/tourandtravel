# DONE — Admin Multilingual ID/EN/ZH (selesai)

> Semua teks admin dibungkus t() + EN/ZH di translations.
> Ganti bahasa via flag sidebar (?lang=id/en/zh). Helpers: bookingStatusLabel(), enumLabel().
> Kamus EN: database/regenerate-en.php + php database/regenerate-en.php. Seed ZH: batch1 80 + batch2 148.
> Test: Brand 6/6, Email 9/9, HotelApi 14/14; E2E brand+polish 8/8; visual resellers ID/EN/ZH (screenshot).

## Arsip audit awal (semua titik sudah dikerjakan)
## Pola lintas-file (perbaiki dulu = sapu terbanyak)

### 1. `confirm('...')` JS hardcoded — 20 titik
| File | Baris | Teks |
|---|---|---|
| tours.php | 82 | `Set bahasa konten untuk tour yang dipilih?` |
| tours.php | 128 | `Yakin ingin menghapus tour ini?` |
| tour-add.php | — | (tidak ada confirm, aman) |
| tour-edit.php | 353 | `Hapus tanggal ini?` |
| tour-edit.php | 430 | `Hapus itinerary ini?` |
| tour-edit.php | 470 | `Hapus gambar ini?` |
| flights.php | 19 | `Hapus?` |
| hotels.php | 30 | `Hapus?` |
| ferries.php | 16 | `Hapus?` |
| rental-cars.php | 15 | `Hapus?` |
| attractions.php | 32 | `Hapus?` |
| transfers.php | 31 | `Hapus?` |
| trains.php | 32 | `Hapus?` |
| esim.php | 31 | `Hapus?` |
| collections.php | 155 | `Hapus koleksi?` |
| promo-codes.php | 150 | `Hapus kode promo?` |
| nav-menus.php | 134 | `Hapus menu?` |
| reseller-pricing.php | 157–158 | `Toggle status?`, `Hapus harga reseller ini?` |
| wa-settings.php | 351 | `Yakin ingin memutuskan koneksi WhatsApp?` |
| bookings.php | 351 | `Hapus booking ini?` |
| **SUDAH `t()`** | — | bookings refund-approve, accounting, price-alerts, reviews, flash-sales, hotel-rooms, posts, hero-slides, faq, faq-category |

### 2. `Aktif`/`Nonaktif` status tabel — 12 file
| File | Baris | Teks |
|---|---|---|
| tours.php | 122 | `Aktif` / `Nonaktif` |
| attractions.php | 30 | `Aktif` / `Nonaktif` |
| transfers.php | 29 | sama |
| trains.php | 30 | sama |
| esim.php | 29 | sama |
| faq.php | 31 | sama |
| nav-menus.php | 132 | sama |
| promo-codes.php | 148 | sama |
| reseller-pricing.php | 153 | sama |
| ab-tests.php | 26 | sama |
| corporate-rates.php | 101 | sama |
| price-alerts.php | 57,59 | `Aktif` / `Nonaktif` (label radio) |

### 3. `ucfirst($var)` enum DB tanpa kamus — 8 titik
| File | Baris | Nilai mentah |
|---|---|---|
| bookings.php | 323 | `ucfirst($b['status'])` → pending/confirmed/cancelled |
| bookings.php | 298 | badge `Reseller` hardcode + `title="Reseller booking"` (EN) |
| flights.php | 17 | `ucfirst($i['class'])` → economy/business |
| rental-cars.php | 13 | `ucfirst($i['transmission'])` → manual/matic |
| price-alerts.php | 55 | `ucfirst($r['item_type'])` → tour/hotel |
| reseller-topups.php | 136 | `ucfirst($t['status'])` → pending/approved/rejected |
| dashboard.php | 64 | judul aktivitas `ucfirst($p['status'])` |
| analytics.php | 123 | `ucfirst(t($st))` — t() tanpa key kamus, EN/zh jatuh ke ID |

### 4. `title=""` / `aria-label=""` tooltip EN/ID — 6 titik
| File | Baris | Teks |
|---|---|---|
| admin-header.php | 87 | `title="Toggle Sidebar"` |
| tours.php | 126–128 | `title="Lihat"/"Edit"/"Hapus"` |
| tour-edit.php | 470 | `title="Hapus"` |
| bookings.php | 298,305 | `title="Reseller booking"`, `aria-label="COGS"` |
| resellers.php | 87 | `title="Topup History"` |
| reseller-pricing.php | 156–158 | `title="Edit"/"Toggle"/"Hapus"` |

### 5. `placeholder=""` contoh-ID — 14 titik
| File | Baris | Teks |
|---|---|---|
| brand-settings.php | 62 | `Your World of Joy` |
| hotel-api-settings.php | 85 | `opsional, legacy scraping` |
| nav-menus.php | 90,102 | `tours.php`, `tour` |
| hero-slide-edit.php | 105 | `tours.php` |
| tour-edit.php | 298–300,381–400 | `Low Season`, `Note (EN)`, `备注 (中文)`, `Title (EN)`, `标题 (中文)`, `Description (EN)`, `描述 (中文)`, `Sarapan, makan siang`, `Meals (EN)`, `餐饮 (中文)`, `Hotel`, `Hotel (EN)`, `酒店 (中文)` |
| attraction-edit.php | 84,86 | `Taman & Hiburan, Landmark, ...`, `1 hari, 2-3 jam, ...` |
| transfer-edit.php | 85 | `Sedan, MVP, ...` |
| train-edit.php | 64,74 | `5j 30m`, `Eksekutif, Bisnis, ...` |
| esim-edit.php | 72–73 | `Nasional, Regional, ...`, `5GB, 10GB, ...` |
| resellers.php | 43 | `Cari nama, email, telepon...` |
| reseller-topups.php | 143,149 | `Catatan (opsional)`, `Alasan (opsional)` |
| corporate-rates.php | 56–57,67 | `Nama perusahaan`, `Diskon % (mis. 10)`, `Email user` |
| promo-codes.php | 100–108,130 | `Kosongkan` ×3, `Deskripsi (opsional)` |
| push-notifications.php | 58 | `123` |
| wa-settings.php | 163,168 | `6285174488415`, `abbayosua` |

---

## Per file (detail)

### admin/ab-tests.php — 4 titik
- 12,18: heading `A/B Testing` ×2
- 26: `Aktif`/`Nonaktif`
- 30: label `Belum ada impresi.`
- 33: header `Varian,Impresi,Konversi,Rate`

### admin/accounting.php — 4 titik
- 14: option `Marketing,Operasional,Gaji,Sewa,Utilitas,Lainnya`
- 24: alert `Invalid CSRF token`
- 57: label `eSIM`
- 123 (via ucfirst, lihat pola §3)

### admin/analytics.php — 2 titik
- 18: label `eSIM`
- 123: `ucfirst(t($st))` tanpa kamus

### admin/sales-report.php — 2 titik
- 38: label `eSIM`
- 100: option `ucfirst(t($s))` tanpa kamus

### admin/dashboard.php — 3 titik
- 48,263: label `eSIM`, JS `Rp `, `jt`, `rb` (format angka singkat)
- 64: `ucfirst($p['status'])`

### admin/bookings.php — 14 titik
- 77,86,100–102: array `$MESSAGES` trilingual manual — `Status booking:`, `Booking`, `telah dikonfirmasi`, `Status Booking -`, `Status Booking:`, `Booking Status:`, `has been confirmed`, `订单状态：`, `订单`, `已确认` (duplikat logika t(), harusnya 1 key)
- 138–196: sufiks satuan tabel ` org`, ` tiket`, ` pax`, ` kursi`, ` pcs`, ` kamar / `, ` tamu`, ` → `, `: `
- 235: label `eSIM`
- 298: `title="Reseller booking"` + badge `Reseller`
- 305: `aria-label="COGS"`
- 323: `ucfirst($b['status'])`
- 326,330: label `Refund`, `Refund `
- 351: `confirm('Hapus booking ini?')`

### admin/tours.php — 8 titik
- 40: alert ` tour berhasil diatur ke bahasa ` (sambungan string)
- 50–53: alert `Tour berhasil ditambahkan/diperbarui/dihapus`, `Bulk update selesai`
- 82,128: confirm bahasa + hapus (lihat §1)
- 122: `Aktif`/`Nonaktif`
- 126–128: `title="Lihat"/"Edit"/"Hapus"`
- 151: JS ` tour dipilih`

### admin/tour-add.php — 2 titik
- 24: alert `Judul tour harus diisi`
- 27: alert `Max peserta minimal 1`

### admin/tour-edit.php — 30 titik (terbanyak)
- 39: alert `Judul tour harus diisi`
- 206,210,214–220: label `Durasi (hari)`, `Durasi (malam)`, `Rute Kota (untuk brosur PDF)`, `Highlights (satu per baris — tampil di brosur PDF)`, `Jadwal Penerbangan (satu per baris)`, `Titik Kumpul`, `Paket Termasuk / Include (satu per baris)`, `Paket Belum Termasuk / Exclude (satu per baris)`, `Catatan Penting (satu per baris)`
- 279,369: tombol `+ Tambah`
- 297: label `Catatan (mis: Low Season)`
- 298–300,303–319: label + placeholder harga/tipe kamar (lihat §5)
- 335–337: header `Dewasa/Anak/Single`
- 353,430,470: confirm hapus (lihat §1)
- 470: `title="Hapus"`

### admin/flights.php — 3 titik
- 3: alert `OK`
- 17: `ucfirst($i['class'])`
- 19: `confirm('Hapus?')`

### admin/flight-edit.php — 1 titik
- 24: alert `Semua field wajib diisi`

### admin/hotels.php, ferries.php, rental-cars.php, attractions.php, transfers.php, trains.php, esim.php — 1–3 titik tiap file
- `confirm('Hapus?')` + `Aktif`/`Nonaktif` + `ucfirst` enum (lihat §1–§3)
- esim.php:27: sufiks ` hari`
- rental-cars.php:13: `ucfirst($i['transmission'])`

### admin/attraction-edit.php — 7 titik
- 32: alert `Nama dan kota wajib diisi`
- 59: heading `Tambah Tiket Wisata`/`Edit Tiket Wisata`
- 64,104: tombol `Kembali`, `Tambah`/`Simpan`
- 84,86: placeholder contoh (lihat §5)

### admin/transfer-edit.php — 3 titik
- 35: alert `Nama, asal, dan tujuan wajib diisi`
- 56: tombol `Kembali`
- 85: placeholder `Sedan, MVP, ...`

### admin/train-edit.php — 4 titik
- 32: alert `Nama, asal, dan tujuan wajib diisi`
- 55: tombol `Kembali`
- 64: label `Durasi (contoh: 5j 30m)` + placeholder
- 74: placeholder `Eksekutif, Bisnis, ...`

### admin/esim-edit.php — 5 titik
- 31: alert `Nama, negara, dan kuota wajib diisi`
- 51: heading ` eSIM` (sambungan string)
- 52: tombol `Kembali`
- 72–73: placeholder cakupan + kuota

### admin/ferry-edit.php — 1 titik
- 17: alert `Isi semua field`

### admin/rental-car-edit.php — 2 titik
- 15: alert `Isi semua field`
- 17: heading `Edit Rental Mobil`

### admin/faq-category.php — 3 titik
- 9: alert `Berhasil ditambahkan/diperbarui/dihapus`
- 28: `confirm(t('Hapus?'))` ✅ sudah t()

### admin/faq-edit.php — 1 titik
- 64: option `-- Pilih --`

### admin/reviews.php — 2 titik
- 35: label `Kebersihan,Lokasi,Staff,Nilai,Fasilitas,Kenyamanan` (kunci subrating)
- 50: label `Guest`

### admin/promo-codes.php — 8 titik
- 34: alert `Nilai diskon harus > 0`
- 100–108,130: placeholder `Kosongkan` ×3 + `Deskripsi (opsional)`
- 142: sufiks `%`/`Rp`
- 148: `Aktif`/`Nonaktif`
- 150: confirm hapus

### admin/collections.php — 2 titik
- 31: alert `Nama collection wajib diisi`
- 152: sufiks ` tour`
- 155: `confirm('Hapus koleksi?')`

### admin/hero-slide-edit.php — 2 titik
- 101: placeholder CTA `t('Cari Sekarang')` ✅ sudah t()
- 105: placeholder `tours.php` (contoh path, opsional)

### admin/nav-menus.php — 8 titik
- 89–90: label `URL` + placeholder
- 101–102: label `Match key` + placeholder
- 109–110: label `Tab`, `Menu`
- 132: `Aktif`/`Nonaktif`
- 134: confirm hapus

### admin/wa-settings.php — 12 titik
- 19,23: alert `Nomor WA admin harus diisi`, `Nomor WA harus diawali 62 (contoh: 6285174488415)`
- 126: label `Buka WhatsApp > Menu > Perangkat Tertaut >`
- 163,168: placeholder nomor + token
- 249,294,351,355,364,366,369: JS status koneksi `Terhubung/Putus`, `Hubungkan Nomor Baru`, confirm putus, `Memutuskan...`, `Putuskan Koneksi`, `Koneksi WhatsApp berhasil diputuskan.`, `Gagal memutuskan koneksi.`

### admin/wa-test.php — 4 titik (file TANPA `t()` sama sekali)
- 10: `Nomor WA harus diisi`
- 14: `✅ *Test Notifikasi* Halo! Ini adalah pesan test dari *` + `Notifikasi WhatsApp berfungsi dengan baik.`
- 17,19: `Test WA berhasil dikirim ke`, `Gagal kirim WA ke`, `Cek server log.`

### admin/currency-settings.php — 2 titik
- 17: alert `Mata uang default berhasil disimpan: `
- 23: alert `Kurs berhasil diperbarui: IDR=`

### admin/hotel-api-settings.php — 6 titik
- 78–80: option `NusaTrip (utamakan)`, `OYO`, `Auto (NusaTrip native, fallback OYO)`
- 85: placeholder `opsional, legacy scraping`
- 138–140: status `aktif`/`nonaktif`, `terisi`/`kosong`

### admin/resellers.php — 14 titik (halaman penuh hardcoded)
- 34,38: heading `Kelola Reseller` ×2
- 43: placeholder cari
- 47–49: option `Semua`, `Ada Saldo`, `Saldo Kosong`
- 52: tombol `Filter`
- 61–68: header `ID,Nama,Email,Telepon,Saldo,Booking,Total Belanja,Terdaftar`
- 74: `Belum ada reseller.`
- 87: `title="Topup History"`
- 99: label `Total: / reseller`

### admin/reseller-topups.php — 13 titik
- 82,86: heading `Topup Reseller` ×2
- 90: alert `Topup berhasil di-` (sambungan)
- 99–102: option `Semua Status,Pending,Approved,Rejected`
- 105,122,144,150: tombol `Filter`, `Lihat Bukti`, `Approve`, `Reject`
- 109: `Tidak ada data topup.`
- 136: `ucfirst($t['status'])`
- 143,149: placeholder catatan/alasan

### admin/reseller-pricing.php — 22 titik (terbanyak kedua)
- 40,45,50,52: alert validasi/sukses
- 74,78,87: heading halaman + form
- 92,94,103,107,113: label + option form
- 117,119: tombol `Update`/`Tambah`, `Batal`
- 134–139: header tabel
- 144: `Belum ada harga reseller.`
- 153: `Aktif`/`Nonaktif`
- 156–158: title + confirm

### admin/push-notifications.php — 7 titik
- 51–53: option `Indonesia`, `English`, `中文`
- 58: placeholder `123`
- 66: heading tab bahasa
- 107: header `Waktu`
- 170: JS ` terkirim, / gagal`

### admin/corporate-rates.php — 15 titik (file TANPA `t()` sama sekali)
- 19,33,35: alert `Perusahaan ditambahkan.`, `ditautkan./dilepas.`, `tidak ditemukan.`
- 42,46,53,64,83: heading ×5
- 56–58,67: placeholder + tombol form
- 69: option `— Lepas afiliasi —`
- 74: tombol `Tautkan`
- 85: `Belum ada perusahaan.`
- 88: header `Nama,Diskon %,Status`
- 101: `Aktif`/`Nonaktif`
- 106: tombol `Nonaktifkan`/`Aktifkan`

### admin/ab-tests.php — 5 titik (file TANPA `t()` sama sekali)
- 12,18: heading `A/B Testing` ×2
- 26: `Aktif`/`Nonaktif`
- 30: `Belum ada impresi.`
- 33: header `Varian,Impresi,Konversi,Rate`

### admin/payments.php — 4 titik
- 76: `ucfirst(t($st))` tanpa kamus
- 101–102: option `Midtrans Snap`, `Tripay`
- 160: label `Webhook Midtrans:`, `· Webhook Tripay:`

### admin/includes/admin-header.php — 1 titik
- 87: `title="Toggle Sidebar"`

## Prioritas saran
1. **Pola §1 confirm + §2 Aktif/Nonaktif** — 32 titik, satu helper/js-i18n sapu semua file CRUD.
2. **File tanpa `t()` sama sekali**: `wa-test.php`, `corporate-rates.php`, `ab-tests.php` — bungkus penuh.
3. **Halaman penuh hardcoded**: `resellers.php`, `reseller-topups.php`, `reseller-pricing.php` (~50 titik).
4. **Sambungan string alert** (`bookings.php` $MESSAGES, `tours.php:40`, `reseller-topups.php:90`, `esim-edit.php:51`) — pecah jadi key utuh per bahasa.
5. **Enum `ucfirst`** — buat kamus `status_*`, `class_*`, `transmission_*`, `item_type_*` di seeder terjemahan.
6. **Placeholder contoh** (§5) — bungkus `t()` atau tandai `data-no-i18n` bila memang contoh literal.

