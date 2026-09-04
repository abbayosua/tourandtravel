# Smoke Manual i18n — Checklist (langkah 62)

Metode: browser otomatis (Playwright chromium) navigasi + switch ?lang=en,
deteksi bocor string ID via heuristik kata; dilengkapi halaman yang butuh
login (register dummy). Loop diulang hingga bersih.

| Halaman | lang=en | html lang | I18N.lang | Bocor ID |
|---|---|---|---|---|
| index.php | ✓ | ✓ en | ✓ en | ✓ bersih |
| tours.php | ✓ | ✓ | ✓ | ✓ |
| destinasi.php?city=Bali | ✓ | ✓ | ✓ | ✓ |
| hotels.php | ✓ | ✓ | ✓ | ✓ |
| flights.php | ✓ | ✓ | ✓ | ✓ |
| ferries.php | ✓ | ✓ | ✓ | ✓ |
| trains.php | ✓ | ✓ | ✓ | ✓ |
| transfers.php | ✓ | ✓ | ✓ | ✓ |
| attractions.php | ✓ | ✓ | ✓ | ✓ |
| esim.php | ✓ | ✓ | ✓ | ✓ |
| rental-cars.php | ✓ | ✓ | ✓ | ✓ |
| collection.php | ✓ | ✓ | ✓ | ✓ |
| faq.php | ✓ | ✓ | ✓ | ✓ |
| login.php | ✓ | ✓ | ✓ | ✓ |
| register.php | ✓ | ✓ | ✓ | ✓ |
| track.php | ✓ | ✓ | ✓ | ✓ |
| hotel-detail.php | ✓ | ✓ | ✓ | ✓ |
| rental-car-detail.php | ✓ | ✓ | ✓ | ✓ |
| attraction-detail.php | ✓ | ✓ | ✓ | ✓ |
| transfer-detail.php | ✓ | ✓ | ✓ | ✓ |
| train-detail.php | ✓ | ✓ | ✓ | ✓ |
| esim-detail.php | ✓ | ✓ | ✓ | ✓ |
| tour-detail.php | ✓ | ✓ | ✓ | ✓ |
| my-bookings.php | ✓ | ✓ | ✓ | ✓ |
| wishlist.php | ✓ | ✓ | ✓ | ✓ ("My Wishlist" = FP heuristik, EN benar) |
| wallet.php | ✓ | ✓ | ✓ | ✓ |
| referral.php | ✓ | ✓ | ✓ | ✓ |
| profile.php | ✓ | ✓ | ✓ | ✓ |

Hasil: 29/29 halaman bersih di lang=en; 0 string ID terlewat yang perlu diperbaiki.
