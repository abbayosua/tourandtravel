<?php
/**
 * Peta interaktif Leaflet — aset di-host LOKAL (assets/vendor/leaflet)
 * agar tidak bergantung CDN/SRI (sebelumnya CSS diblokir karena hash salah).
 * renderMap($id, $points, $centerLat, $centerLng, $zoom)
 *   $points: [['lat'=>, 'lng'=>, 'label'=>, 'price'=>, 'link'=>], ...]
 * Fallback: tanpa titik → pesan.
 */
function renderMap(string $id, array $points, float $centerLat, float $centerLng, int $zoom = 12): void {
    if (empty($points)) {
        echo '<div class="text-center text-muted small py-4">' . t('Peta tidak tersedia untuk item ini.') . '</div>';
        return;
    }

    // Aset Leaflet hanya dimuat sekali per halaman.
    static $assetsEmitted = false;
    if (!$assetsEmitted) {
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        echo '<link rel="stylesheet" href="' . e($base) . '/assets/vendor/leaflet/leaflet.css">' . "\n";
        echo '<script src="' . e($base) . '/assets/vendor/leaflet/leaflet.js"></script>' . "\n";
        $assetsEmitted = true;
    }
    ?>
    <div id="<?= e($id) ?>" style="height: 320px; border-radius: 8px;" data-testid="leaflet-map" data-points="<?= e(json_encode($points)) ?>"></div>
    <script>
    (function() {
        var el = document.getElementById('<?= e($id) ?>');
        if (!el || typeof L === 'undefined') return;
        var points = JSON.parse(el.dataset.points);
        var map = L.map(el).setView([<?= $centerLat ?>, <?= $centerLng ?>], <?= $zoom ?>);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
        var bounds = [];
        points.forEach(function(p) {
            var html = '<div style="font-weight:600;padding:2px 6px;">' + (p.price ? p.price : '') + '</div>';
            L.marker([p.lat, p.lng]).addTo(map).bindPopup('<b>' + p.label + '</b>' + (p.link ? '<br><a href="' + p.link + '">' + <?= json_encode(t('Lihat detail')) ?> + '</a>' : '') + html);
            bounds.push([p.lat, p.lng]);
        });
        if (bounds.length > 1) map.fitBounds(bounds, { padding: [30, 30] });
        // Kontainer bisa tersembunyi/berubah ukuran saat init → segarkan ukuran.
        function refresh() {
            map.invalidateSize();
            if (bounds.length > 1) map.fitBounds(bounds, { padding: [30, 30] });
        }
        window.addEventListener('load', refresh);
        setTimeout(refresh, 300);
    })();
    </script>
    <?php
}
