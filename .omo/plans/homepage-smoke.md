# Smoke Manual Homepage Templates — Checklist (langkah 26)

Metode: browser otomatis (Playwright chromium) — navigasi 3 preset × 2 bahasa,
submit form search tiap preset, currency switcher. Fokus diset via settings DB,
dikembalikan ke `tour` di akhir.

## Matriks render + submit

| Preset | Lang | html lang | I18N.lang | Render kunci | Form submit |
|---|---|---|---|---|---|
| Tour | id | id | id | ✓ Flash Deals | ✓ hero search → `tours.php?search=` |
| Tour | en | en | en | ✓ Recommended Tour | ✓ `tours.php?search=` |
| Hotel | id | id | id | ✓ Cari Hotel | ✓ → `hotels.php?city=Bali` |
| Hotel | en | en | en | ✓ Search Hotels | ✓ `hotels.php?city=Bali` |
| Flight | id | id | id | ✓ Cari Penerbangan | ✓ → `flights.php?from=…` |
| Flight | en | en | en | ✓ Search Flights | ✓ `flights.php?from=…` |

**ALL_SMOKE_OK: true** (6/6 kombinasi lulus)

## Currency switcher (preset hotel)

- IDR: `Rp 14,741,447` (EN separator — halaman dalam konteks EN saat pengujian)
- USD: `$ 835.89` — konversi + 2 desimal + re-render `.currency-price` ✓
- Dikembalikan ke IDR

## Detail interaksi terverifikasi

- Tour: hero search ketik "bali" + Enter → `tours.php?search=bali`
- Hotel: isi kota "Bali" → submit → `hotels.php?city=Bali` (halaman hasil)
- Flight: autocomplete pilih kota ( Jakarta (CGK)), toggle Round Trip menampilkan
  field Pulang, submit → `flights.php?from=…&trip_type=…`
- Switch bahasa: `<html lang>` + `window.I18N.lang` mengikuti `?lang=` di semua preset
- Fokus dikembalikan ke `tour` (default) di akhir smoke

## Kesimpulan

29 pengecekan (6 kombinasi × render+submit + currency) — semua lulus.
Fitur kustomisasi fokus bisnis bekerja penuh: admin pilih fokus, homepage
tersusun ulang, multilingual & currency konsisten di semua preset.
