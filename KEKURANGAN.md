# KEKURANGAN — Gap Fitur & Tampilan vs Klook, Traveloka, Agoda

> Baseline: situs sudah punya multilingual EN/ID, site focus (tour/hotel/flight), Midtrans, email, reset password, SEO+sitemap, blog, notifikasi, analytics, review kaya, kupon/referral, dark mode, PWA, wishlist (tour), multi-currency (IDR/SGD/USD), flight Duffel oneway/roundtrip.

---

## 🔴 Prioritas Tinggi (dampak konversi terbesar)

| # | Kekurangan | Benchmark | Realita Kita |
|---|---|---|---|
| 1 | **Kalender harga + tanggal fleksibel** | Klook "cheapest date picker", Traveloka calendar hijau/hitam | Tidak ada `price_calendar`; harga statis per produk. E2E juga tidak ada: `grep price_calendar|hourly|dynamic_pric` = 0 |
| 2 | **Hotel: tipe kamar & rate plan** | Agoda: bed type, breakfast, refundable/non-refundable per kamar | Hanya 1 harga per hotel (`hotels.php` flat). Tidak ada tabel `hotel_rooms`/`rate_plan` |
| 3 | **Search & filter vertikal yang dalam** | Klook/Traveloka: filter harga slider, durasi, waktu keberangkatan, maskapai, transit, amenitas | Filter kita dasar (kategori, kota, sort). Tidak ada filter amenitas hotel (kolam, parkir), rentang waktu flight |
| 4 | **Social proof dinamis** | "X orang memilih aktivitas ini hari ini" (Klook), "baru dipesan 5 mnt lalu" | 0 match `social_proof|recently_booked|terakhir.*dipesan`. Hanya badge statis (best_seller, instant_confirmation) |
| 5 | **Halaman admin booking hotel/flight detail** | Traveloka: e-ticket, voucher hotel printable, refund flow | Admin hanya punya booking tour yang kaya; hotel/flight/eSIM booking minimal |

## 🟡 Prioritas Menengah

| # | Kekurangan | Benchmark | Realita Kita |
|---|---|---|---|
| 6 | **Live chat & CS real-time** | Traveloka: chat 24/7, Klook: in-app chat | Hanya nomor WA statis `0812-3456-7890` + `webhook-wa.php`. Tidak ada widget tawk/crisp/intercom |
| 7 | **Flight multi-city & akumulasi miles** | Duffel support multi-slice; Traveloka: pilih kursi, baggage add-on | `duffel.php` kirim 1 slice saja (`slices => [[...]]`), cabin_class satu nilai |
| 8 | **Peta interaktif + "search by map"** | Agoda: peta heat harga per area, Klook: pin lokasi | Hanya **static map** Google di `tour-detail.php:222`, tidak ada peta interaktif hotel/attraction |
| 9 | **Wishlist lintas vertikal** | Semua competitor: simpan hotel, flight, attraction | `wishlist-ajax.php` & `functions.php:554` hanya `tour_id`. Hotel/attraction tidak bisa di-wishlist |
| 10 | **Dynamic pricing & flash sale engine** | Klook: countdown, stok terbatas, surge | `tour-flash-deals.php` hanya promo statis. Tidak ada countdown timer, limit stok per jam |
| 11 | **Loyalty/points program** | Traveloka XP, Agoda CashBack, Klook Credits | Ada `wallet.php` & referral, tapi belum ada tier/point/mutasi otomatis per transaksi |
| 12 | **Review: foto user tersaring + rating per aspek** | Agoda: cleanliness/location/staff sub-rating | Review kaya (foto, balasan, distribusi) tapi belum ada sub-rating per aspek |

## 🟢 Prioritas Rendah (polish)

| # | Kekurangan | Benchmark | Realita Kita |
|---|---|---|---|
| 13 | **Mobile app & deep-link** | Semua kompetitor native app | Web PWA saja |
| 14 | **Video di hero & tour detail** | Klook: video thumbnail auto-play | Hero hanya gambar (`tour-hero.php`) |
| 15 | **Itinerary builder / multi-day planner** | Klook: "Plan your trip" per day | Tour punya itinerary statis, tidak ada drag-drop planner |
| 16 | **Price alert / notif harga turun** | Traveloka: subscribe price alert | Tidak ada |
| 17 | **A/B testing & personalisasi homepage** | Kompetitor: rekomendasi ML "dipilih untukmu" | Homepage preset 3 fokus, belum berbasis riwayat user |
| 18 | **Multi-bahasa konten user-generated** | Klook: review per bahasa | Review teks disimpan single-language |
| 19 | **Passenger saved profile (1-click checkout)** | Traveloka/Klook: simpan KTP/pasport/pax rutin | Booking form masih isi manual tiap kali |
| 20 | **Hotel "travelling for work" / rate korporat** | Agoda: segmentasi bisnis | Tidak ada |

## 🎨 Tampilan (UI/UX)

- **Countdown & urgency**: belum ada timer "promo berakhir dalam X jam".
- **Skeleton loading & infinite scroll**: listing masih pagination klasik, Klook/Agoda pakai infinite scroll + shimmer.
- **Bottom nav mobile**: kompetitor punya tab bar bawah (Home/Search/Booking/Akun) di mobile — kita hanya header biasa.
- **Date-picker range 2 bulan**: date picker kita standar, Agoda punya dual-month calendar dengan harga per tanggal.
- **Image gallery lightbox swipe**: review photo grid ada, tapi belum ada zoom/lazy-load dengan blur placeholder.
- **Trust badge di atas fold**: Klook tampilkan "10 jt+ pengguna, pembayaran aman" di bawah tombol beli — kita `trust.php` hanya di homepage, tidak di detail.

## Ringkasan Saran 5 Langkah Berikutnya (ROI tertinggi)

1. **Kalender harga** (I-1 di atas) — reusable untuk tour/hotel/flight.
2. **Room types hotel** + filter refundable/breakfast.
3. **Social proof ticker** (murah, dampak psikologis besar).
4. **Sub-rating review** + filter wishlist ke semua vertikal.
5. **Widget live chat** (tawk.to gratis) + bottom-nav mobile.
