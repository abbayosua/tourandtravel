# Panduan Multilingual (EN/ID + Bahasa Baru)

Site ini multilingual penuh: semua string UI (PHP & JS), pesan AJAX, konten DB,
format tanggal/angka/mata uang, dan switcher bahasa mengikuti bahasa aktif.

## Arsitektur Singkat

| Komponen | Lokasi | Fungsi |
|---|---|---|
| Registry bahasa | `includes/functions.php` → `getSupportedLanguages()` | Sumber tunggal: kode, label, flag, locale |
| Deteksi bahasa | `getCurrentLang()` | session → cookie → `Accept-Language` → default `id`; tervalidasi registry |
| Terjemahan UI | tabel `translations` (UNIQUE `key`,`lang`) | key = string Indonesia sumber; helper `t($key)` |
| Konten DB | kolom `{field}_{lang}` + `content_language` | helper `tContent($row, $field)` |
| JS | `window.I18N` (inject `i18nJs()` di header) + `assets/js/i18n.js` | `I18N.t(key)`, `I18N.locale` |
| Tanggal/angka | `formatDate()`, `formatNumber()`, `formatRupiah()`, `formatCurrency()` | locale-aware otomatis |
| Audit | `php scripts/audit-translations.php` | laporan per-cluster ke `scripts/out/` |

## Menambah Bahasa Baru (contoh: `'ms'` = Bahasa Melayu)

### 1. Daftarkan bahasa (titik tunggal)

```php
// includes/functions.php → getSupportedLanguages()
'ms' => ['label' => 'Bahasa Melayu', 'flag' => '🇲🇾', 'locale' => 'ms_MY'],
```

Selesai untuk: validasi, `Accept-Language`, `<html lang>`, label switcher
(header & header-klook membaca registry otomatis), locale JS & formatter.

### 2. Seed terjemahan UI

Jalankan regenerator — key baru otomatis identity (aman), lalu isi terjemahannya:

```bash
php database/regenerate-en.php   # pola sama: duplikasi jadi regenerate-ms.php
```

Cara tercepat: salin `regenerate-en.php`, ganti `'en'` → `'ms'`, ganti kamus
`$manual` dengan kamus ms. Key tanpa kamus = identity (tampil ID) sampai
diterjemahkan — tidak pernah crash.

### 3. Kolom konten DB (hanya jika konten layanan ingin diterjemahkan)

```bash
# database/migrate-content-lang.sql — tambah blok kolom:
UNION ALL SELECT 'hotels', 'name_ms', 'varchar(200)', '', 'YES'
UNION ALL SELECT 'hotels', 'description_ms', 'text', NULL, 'YES'
# ... dst untuk tabel lain, lalu:
mysql -u root tourandtravel < database/migrate-content-lang.sql
```

Tanpa kolom `_{lang}`: `tContent()` otomatis fallback ke nilai asli — aman.

### 4. Selesai — tidak ada langkah lain

- Switcher bahasa: otomatis menampilkan 🇲🇾 dari registry (di kedua header).
- URL switch: `?lang=ms` — session + cookie 1 tahun, redirect dengan
  mempertahankan semua query params.
- Format tanggal/angka: `formatDate()` cabang `id` untuk Indonesia, bahasa lain
  memakai nama hari/bulan PHP native — tambahkan cabang di `formatDate()`
  jika butuh nama bulan custom.

## Aturan Menulis Kode (wajib untuk string baru)

1. **PHP UI**: selalu `t('String Indonesia')`. Placeholder dinamis pakai
   template: `str_replace(':city', $city, t('Paket Tour ke :city'))`.
2. **JS**: `I18N.t('String Indonesia')`; daftarkan key baru di
   `getJsI18nKeys()` (functions.php) lalu jalankan `regenerate-en.php`.
3. **AJAX**: endpoint load `config+functions` → session lang tersedia → pakai `t()`.
4. **Konten DB render**: `tContent($row, 'name')` — jangan akses `$row['name']` raw.
5. **Tanggal**: `formatDate($d)` — jangan `tglIndonesia()` untuk display baru.
6. **Angka/mata uang**: `formatNumber()`, `formatRupiah()`, `formatCurrency()` —
   jangan `number_format()` manual.
7. Setelah selesai: `php scripts/audit-translations.php` → wajib
   `Missing en di DB: 0` dan tidak ada kandidat hardcoded baru.

## Verifikasi Mandiri (1 menit)

```bash
# ID vs EN beda di 3 titik: HTML lang, teks, format angka
curl -s http://localhost/tourandtravel/ | grep '<html lang'          # id
curl -s -c /tmp/c.txt -b /tmp/c.txt "http://localhost/tourandtravel/?lang=en" > /dev/null
curl -s -b /tmp/c.txt http://localhost/tourandtravel/ | grep '<html lang'   # en
php scripts/audit-translations.php | grep Missing                    # 0
```

## File Terkait

- `database/migrate-content-lang.sql` — kolom `_{lang}` per tabel konten (idempotent)
- `database/seed-content-en.php` — terjemahan konten seed (kamus per-baris)
- `database/regenerate-en.php` — sinkronisasi key t() → terjemahan (idempotent)
- `scripts/audit-translations.php` — audit (missing keys + hardcoded per-cluster)
- `includes/functions.php` — `t()`, `tContent()`, `formatDate()`, `getSupportedLanguages()`, `i18nJs()`
