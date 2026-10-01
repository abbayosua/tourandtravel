# Analisa Terjemahan via curl — halaman Tour

Metode: `curl -L -c/b cookie "…?lang={id,en,zh}"` → strip tag → diff + `comm` (baris identik antar bahasa = tidak diterjemahkan).

File HTML mentah: `/tmp/t148_{id,en,zh}.html` (detail tour 148), `/tmp/tours_{id,en,zh}.html` (listing).

---

## 1. HALAMAN LISTING (`tours.php`) — JUDUL TOUR TIDAK DITERJEMAHKAN

Bukti (grep di HTML):
```
tours_en.html  "Half-Day Small Group Tour: Xian Terracotta Army"  -> 0
tours_en.html  "Tur Grup Kecil Setengah Hari..."                 -> 3   (tetap Indonesia)
tours_zh.html  "西安兵马俑半日小团游"                              -> 0
tours_zh.html  "Tur Grup Kecil Setengah Hari..."                 -> 3   (tetap Indonesia)
```

Penyebab di `includes/components/tour-card.php`:
- **L71** `e(t($tour['title'], null, $tour['content_language']))` → memakai tabel `translations` (key = judul), BUKAN kolom `title_en/title_zh`. Seharusnya `tContent($tour,'title')`.
- **L66** `e($tour['category'])` → badge kategori pakai kolom dasar, tak ada `category_en/zh`. Seharusnya `tContent($tour,'category')`.
- **L81** `substr(e($tour['description']),0,100)` → deskripsi kartu tak pakai `tContent`.
- **L33** `alt="<?= e($tour['title']) ?>"` → alt gambar tak diterjemahkan.

Pola bug sama juga di `blog-detail.php:41` dan `collection.php:63,65`.

Akibat: di EN & ZH, kartu tour menampilkan **judul + kategori + deskripsi versi Indonesia**.

## 2. HALAMAN DETAIL (`tour-detail.php?slug=…` tour 148)

Yang SUDAH diterjemahkan di EN & ZH: judul, deskripsi/lead, breadcrumb, header "Fasilitas Termasuk"/"Included Amenities", isi Includes/Excludes (dari kolom `_en`/`_zh`), itinerary, tombol booking, footer.

Yang BELUM (masih Indonesia di halaman ZH):
1. **Badge kategori = "Shenzhen"** (baris 289: `tContent($tour,'category')`). Kolom `category_zh`/`category_en` = NULL → badge tetap "Shenzhen". **Inilah "subtitle" yang belum ada Mandarinnya.**
2. **Label fasilitas** dari `getTourFacilities()` (`includes/functions.php:1113-1118`), key tidak ada di tabel `translations`:
   - `Hotel Bintang 4`, `Transport AC`, `Makan Sesuai Itinerary`, `Tour Guide Profesional`, `Dokumentasi`
3. **Header section**: `Paket Termasuk`, `Tidak Termasuk` → punya `en` tapi `zh` KOSONG.
4. `Perlindungan pembatalan, keterlambatan, dan kehilangan barang.` → key ada en, **zh kosong**.

Yang BELUM di halaman EN:
- Kelima label fasilitas di atas (tidak ada key).
- `Paket Termasuk`, `Tidak Termasuk` → nilai `en` masih sama dengan ID (bukan terjemahan).
- `Simpan ke Itinerary Saya`, `Silakan`, `untuk menyimpan itinerary` → nilai `en` masih sama dengan ID.
- Badge kategori & deskripsi kartu (sama seperti listing).

## 3. DATA — Kelengkapan tabel `translations` (UI string)

| Metrik | Jumlah |
|---|---|
| Total key UI | 1655 |
| Key `en` yang nilainya SAMA dengan ID (belum diterjemahkan) | **629** |
| Key punya `en` tapi TANPA `zh` | **339** |

## 4. DATA — Kolom konten tour (20 tour aktif)

| Field | EN terisi | ZH terisi |
|---|---|---|
| title | 20/20 | 20/20 |
| description | 20/20 | 19/20 |
| includes | 1/20 | 20/20 |
| excludes | 1/20 | 20/20 |
| highlights | 5/20 | 5/20 |
| **category** | **0/20** | **0/20** |
| **location_city** | **0/20** | **0/20** |

## Kesimpulan prioritas perbaikan

1. `tour-card.php` → ganti `t($tour['title']…)` jadi `tContent($tour,'title')`; `$tour['category']`→`tContent($tour,'category')`; deskripsi→`tContent($tour,'description')`.
2. Isi `category_en/zh` & `location_city_en/zh` untuk 20 tour aktif.
3. Tambah/lengkapi terjemahan key: `Hotel Bintang 4`, `Transport AC`, `Makan Sesuai Itinerary`, `Tour Guide Profesional`, `Dokumentasi`, `Paket Termasuk`, `Tidak Termasuk`, `Perlindungan pembatalan…` (zh), dan 629 key `en==id`.
4. Perbaiki pola sama di `blog-detail.php`, `collection.php`.
