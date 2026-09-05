# PRD — Homepage Template per Fokus Bisnis (Site Focus Customization)

| | |
|---|---|
| **Status** | Draft — menunggu approval |
| **Versi** | 1.0 |
| **Tanggal** | 2026-09-04 |
| **Pemilik** | Admin produk (abbayosua) |
| **Fitur terkait** | Admin Settings, Homepage |
| **Prioritas** | Tinggi |

---

## 1. Latar Belakang & Masalah

Website saat ini adalah **travel marketplace satu vertikal**: seluruh halaman
utama dirancang untuk **menjual paket tour** (mengikuti gaya Klook). Padahal
platform sudah memiliki modul jualan lengkap untuk produk lain:

- Hotels (penginapan)
- Flights (tiket pesawat)
- Ferries, Trains, Transfers, Attractions, eSIM, Rental Cars

**Masalah:** pemilik bisnis tidak bisa menyatakan "bisnis saya utamanya jualan
hotel" atau "utamanya tiket pesawat" — halaman utama selalu menyajikan tour
sebagai produk utama, sehingga:

1. Pengunjung yang butuh hotel/tiket harus mencari sendiri menu-nya.
2. Owner yang fokus di satu vertikal mendapat homepage yang tidak menjual
   produk utamanya secara optimal.
3. Menambah penekanan produk tertentu saat ini hanya bisa dengan **edit kode**,
   bukan lewat admin.

## 2. Tujuan & Non-Tujuan

### Tujuan
1. Admin dapat memilih **fokus bisnis** website melalui halaman admin
   (satu dropdown, tanpa sentuh kode).
2. Halaman utama **langsung tersusun ulang** mengikuti fokus yang dipilih —
   hero, urutan section, penekanan produk, dan bahasa visual mengikuti
   referensi marketplace vertikal terkait.
3. Ada **3 orientasi template**: Tour (Klook-style), Hotel (Agoda-style),
   Flight (Tiket.com-style). Fokus lain (ferries, trains, dst.) di masa depan
   dapat ditambahkan mengikuti pola yang sama tanpa redesain arsitektur.
4. Slide hero dikelola lewat admin (CRUD), tidak lagi hardcoded.

### Non-Tujuan (eksplisit keluar dari scope)
- Tidak mengubah halaman **selain homepage** (tours.php, hotels.php, dst.
  tetap seperti sekarang).
- Tidak membuat nav / footer / tagline berubah mengikuti fokus — hanya
  homepage.
- Tidak menambah modul produk baru.
- Tidak ada editor drag-and-drop bebas; admin hanya memilih preset fokus.

## 3. Keputusan Desain (disetujui owner)

| # | Keputusan | Pilihan |
|---|-----------|---------|
| 1 | Mekanisme | **Modular sections** — homepage tersusun dari pool section yang dapat dinyalakan/dimatikan dan diurutkan per preset (bukan 3 file template terpisah) |
| 2 | Kedalaman visual | **Layout berbeda sungguhan per vertikal** — Agoda-style & Tiket.com-style dibuat dengan bahasa desainnya masing-masing, bukan sekadar swap gambar hero |
| 3 | Kontrol admin | **Satu dropdown fokus** — Tour / Hotel / Flight. Preset mengatur komposisi section secara otomatis (tidak ada drag-and-drop di fase ini) |
| 4 | Hero slides | **CRUD admin lengkap** (gambar, judul, subtitle, CTA, urutan, aktif) — per fokus |
| 5 | Cakupan perubahan | **Murni homepage** — nav, footer, dan halaman lain tidak terpengaruh |

## 4. User Stories

### US-1 — Owner memilih fokus bisnis
> Sebagai owner, saya ingin memilih fokus jualan website (Tour/Hotel/Flight)
> dari halaman admin, sehingga homepage langsung menampilkan produk utama
> saya tanpa saya harus mengerti kode.

**Kriteria penerimaan:**
- Dropdown "Fokus Website" di admin dengan 3 opsi + deskripsi singkat tiap opsi.
- Setelah simpan, homepage publik langsung tersusun sesuai fokus (tanpa restart/cache khusus).
- Nilai default untuk instalasi baru: **Tour** (perilaku sama seperti sekarang).
- Perubahan tidak merusak halaman lain maupun data.

### US-2 — Homepage tersusun otomatis per fokus
> Sebagai pengunjung, saya ingin homepage menonjolkan produk utama yang
> sedang dijual (tour / hotel / tiket pesawat) begitu saya membuka situs.

**Kriteria penerimaan:**
- Fokus **Tour** → homepage Klook-style seperti sekarang (regresi nol).
- Fokus **Hotel** → homepage Agoda-style: search bar hotel dominan di hero
  (kota, check-in/out, tamu), dilanjutkan penawaran hotel, harga per malam,
  lalu section pendukung (tour, attraction, dll. sebagai "juga tersedia").
- Fokus **Flight** → homepage Tiket.com-style: search form penerbangan
  dominan di hero (asal-tujuan, tanggal, penumpang, one-way/round-trip),
  promo penerbangan, kota populer, lalu section pendukung.
- Section pendukung dari vertikal lain tetap hadir sebagai bagian bawah
  halaman (cross-sell), dengan proporsi lebih kecil.
- Semua angka/harga mengikuti format mata uang & bahasa aktif (i18n tetap bekerja).

### US-3 — Admin mengelola slide hero
> Sebagai admin, saya ingin menambah/mengubah/menghapus/mengurutkan slide hero
> per fokus, sehingga promo visual di homepage selalu terkini.

**Kriteria penerimaan:**
- Halaman admin daftar slide (tabel): gambar thumbnail, judul, subtitle,
  CTA text, CTA link, urutan, status aktif.
- Form tambah/edit: upload gambar (reuse mekanisme upload existing), semua
  field di atas; urutan angka; toggle aktif.
- Slide hanya tampil di homepage untuk **fokus yang sesuai** (slide dikelompokkan
  per fokus) — atau opsi "semua fokus".
- Slide non-aktif tidak tampil di publik.
- Hapus slide meminta konfirmasi.
- Bila tidak ada slide aktif: homepage menampilkan fallback default yang rapi
  (gradient + headline) — tidak boleh blank/broken.

### US-4 — Perubahan aman & dapat dikembalikan
> Sebagai developer, saya ingin perubahan fokus tidak menghancurkan apa pun
> dan mudah di-roll back.

**Kriteria penerimaan:**
- Fokus tersimpan sebagai satu baris setting key-value (pola existing).
- Memilih kembali fokus lama selalu mengembalikan homepage persis seperti
  sebelumnya (tidak ada data yang hilang saat ganti-ganti fokus).
- E2E untuk ketiga fokus: homepage render tanpa PHP error, elemen kunci
  masing-masing style terdeteksi, switch fokus idempotent.

## 5. Spesifikasi Fungsional

### 5.1 Setting Fokus
- Key setting: `site_focus`. Nilai yang valid: `tour` (default) | `hotel` | `flight`.
- Hanya muncul di admin; tidak diexpose ke publik kecuali lewat efek render.
- Halaman admin baru "Tampilan Homepage" (atau ditempatkan di settings existing):
  dropdown + tombol simpan + pesan sukses; ditampilkan juga preview mini
  (teks) apa yang berubah per pilihan.

### 5.2 Komposisi Section per Preset (urutan atas → bawah)

**Preset Tour (default, = kondisi sekarang):**
Hero carousel tour → Stats bar → Category grid → Flash deals → Destinasi
populer → Rekomendasi paket → Collections → Testimoni → Blog/Reward cards
(persis index.php sekarang; wajib regresi nol).

**Preset Hotel (Agoda-style):**
1. Hero hotel: latar foto/gradient, **search bar hotel besar** (kota,
   check-in, check-out, tamu, tombol Cari) — elemen paling dominan.
2. Deal hotel terbaik (grid/list kartu hotel: nama, bintang, kota, harga
   per malam, badge Best Seller/Batal Gratis/Konfirmasi Instan).
3. Hotel per kota populer (kartu kota dengan harga mulai).
4. Kenapa booking di sini (trust badges) + Testimoni singkat.
5. Cross-sell ringkas: "Jelajahi juga" — tour & attraction (baris kartu kecil).

**Preset Flight (Tiket.com-style):**
1. Hero flight: **search form penerbangan dominan** (one-way/round-trip,
   kota asal-tujuan, tanggal, penumpang, tombol Cari) dengan latar gradasi
   warna khas.
2. Banner promo penerbangan / rute populer.
3. Harga murah per rute populer (daftar rute → harga mulai).
4. Kenapa pesan di sini (trust badges) + Testimoni singkat.
5. Cross-sell ringkas: hotel & tour ("Lengkapi perjalananmu").

Aturan umum:
- Section pendukung memakai komponen kartu yang sudah ada bila memungkinkan
  (tour-card, dsb.) agar konsisten dan mudah dirawat.
- Setiap section wajib punya heading + tombol "Lihat semua" ke halaman
  vertikal terkait.
- Semua section harus punya **empty state** rapi ketika data kosong
  (mis. belum ada hotel aktif) — tidak boleh layout rusak.

### 5.3 Data Hero Slides
Per slide: gambar (wajib), judul, subjudul, teks CTA, link CTA, urutan,
status aktif, dan **fokus** tempat slide tampil (`tour`/`hotel`/`flight`/
semua). Upload gambar mengikuti konvensi upload gambar yang sudah ada di
admin. Validasi: tipe file gambar, ukuran wajar, URL CTA internal.

### 5.4 Perilaku khusus
- Mengganti fokus **tidak** mengubah data produk apa pun; hanya komposisi render.
- Jika fokus Hotel/Flight namun data vertikal kosong → tampilkan section
  hero + empty-state edukatif (mis. "Belum ada hotel — hubungi admin"),
  homepage tidak boleh kosong total.
- Bahasa & mata uang aktif (i18n, currency) wajib tetap bekerja di semua
  preset — semua string baru melalui sistem terjemahan.
- Performa: homepage tetap satu halaman; tidak boleh bertambah query
  secara signifikan (target: tidak lebih lambat terasa dari sekarang).

## 6. Ekspektasi Visual per Preset (ringkas)

| Aspek | Tour (Klook) | Hotel (Agoda) | Flight (Tiket.com) |
|---|---|---|---|
| Hero | Carousel foto destinasi + search tour | Search hotel besar di atas foto | Search penerbangan di atas gradasi |
| Produk utama | Kartu paket tour | Kartu hotel (harga/malam) | Rute & promo harga tiket |
| Warna dominan | Biru primer existing | Biru-kebiruan/teal khas hotel OTA | Merah-oranye khas airline OTA |
| Trust elements | Rating & testimoni tour | Bintang hotel, batal gratis | Maskapai, durasi, transit |
| Cross-sell bawah | Blog/Reward | Tour & attraction | Hotel & tour |

*(Final look mengikuti bahasa desain existing: Bootstrap 5 + design tokens
yang sudah dipakai situs.)*

## 7. Admin UX

- Lokasi menu: sidebar admin — baru "Homepage / Tampilan".
- Halaman 1: **Pengaturan Fokus** — dropdown, deskripsi tiap opsi, simpan.
- Halaman 2: **Kelola Hero Slides** — tabel + form, dikelompokkan per fokus.
- Feedback selalu jelas: pesan sukses/gagal (mengikuti pola flash message admin).
- Akses: hanya admin (guard login admin existing).

## 8. Verifikasi & Penerimaan (QA)

1. **Regresi nol preset Tour**: semua E2E existing yang menyentuh homepage
   tetap hijau.
2. **Switch fokus**: ganti Tour→Hotel→Flight→Tour; setiap kali render sesuai
   spesifikasi, tidak ada PHP error/warning, data tidak berubah.
3. **i18n**: di lang=en semua preset tampil Inggris; lang=id Indonesia
   (termasuk string baru).
4. **Hero CRUD**: tambah/edit/urutkan/non-aktifkan/hapus slide terlihat
   benar di publik sesuai fokusnya; fallback rapi saat kosong.
5. **Empty data**: fokus Hotel tanpa data hotel → homepage tetap utuh &
   informatif.
6. **Smoke lintas halaman**: nav ke tours/hotels/flights dari homepage tiap
   preset tetap benar (params, link 200).
7. **Idempotensi setting**: simpan berulang tidak menimpa hal lain di tabel
   settings.

## 9. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Homepage Klook (default) berubah tak sengaja | Preset Tour = kode existing dipertahankan; E2E homepage menjadi pagar regresi |
| Dua header ter-load di index (janggal existing) | Dirapikan sebagai bagian pekerjaan; diverifikasi visual |
| Kartu hotel/flight butuh komponen baru | Reuse komponen kartu yang ada; komponen baru hanya jika memang perlu |
| Bocor string Indonesia ke EN (i18n) | Semua string baru via t(); audit translations wajib lulus |
| Salah fokus tersimpan | Validasi nilai whitelist; default aman = tour |

## 10. Batasan Teknis & Konvensi

- PHP vanilla + Bootstrap 5 (tanpa framework baru), mengikuti struktur file
  & konvensi repo (page kebab-case, admin di `admin/`, komponen di
  `includes/components/`).
- Setting mengikuti pola tabel `settings` yang sudah ada.
- Semua teks baru bilingual (id sumber + en) sesuai sistem multilingual.
- Data hero: memakai tabel `hero_slides` yang sudah tersedia (diperluas
  sesuai kebutuhan, migration idempotent).

## 11. Definisi Selesai (Definition of Done)

- [ ] Dropdown fokus berfungsi & tersimpan; default `tour`.
- [ ] Ketiga preset render sesuai spesifikasi section & ekspektasi visual.
- [ ] CRUD hero slides berfungsi lengkap + fallback.
- [ ] Semua string baru bilingual; audit translations: missing 0.
- [ ] E2E baru untuk 3 preset + CRUD hero; seluruh suite lulus
      (`--workers=1`, 0 failure baru).
- [ ] Smoke manual homepage 3 preset di id & en.
- [ ] `git status` bersih; ter-commit dengan pesan per fase.
