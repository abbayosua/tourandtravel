# Leaflet Maps (self-hosted)

Komponen: `includes/components/map-leaflet.php` → `renderMap($id,$points,$centerLat,$centerLng,$zoom)`.

## Penyebab bug lama (fixed)
Leaflet CSS dipanggil via CDN unpkg **dengan `integrity` (SRI) yang salah** → browser memblokir CSS
(`Failed to find a valid digest ... leaflet.css`). Tanpa CSS, `.leaflet-container` tak dapat
`position:relative; overflow:hidden` → tile berhamburan ("maps kemana-mana"). JS-nya kebetulan hash-nya benar.

## Fix
- Aset di-host **lokal** di `assets/vendor/leaflet/` (`leaflet.css`, `leaflet.js`, `images/*`) — tanpa CDN/SRI.
- `<link>`/`<script>` hanya di-emit **sekali** per halaman (static guard).
- Tambah `map.invalidateSize()` (window load + setTimeout 300ms) untuk kontainer yang sempat tersembunyi.
- Marker image otomatis dari `assets/vendor/leaflet/images/` (jalur relatif di CSS).

## Pemakai renderMap
`attractions.php`, `hotel-detail.php` (DB + live), `attraction-detail.php`, `hotels.php`. `tour-detail.php` ada di dalam HTML comment (sengaja di-hide).

## Test
`php tests/unit/run.php MapLeaflet` (aset lokal, tanpa unpkg/integrity, sekali muat).
