<?php
/**
 * Peta interaktif Leaflet (CDN).
 * renderMap($id, $points, $centerLat, $centerLng, $zoom)
 *   $points: [['lat'=>, 'lng'=>, 'label'=>, 'price'=>, 'link'=>], ...]
 * Fallback: tanpa titik → static map / pesan.
 */
function renderMap(string $id, array $points, float $centerLat, float $centerLng, int $zoom = 12): void {
    if (empty($points)) {
        echo '<div class="text-center text-muted small py-4">' . t('Peta tidak tersedia untuk item ini.') . '</div>';
        return;
    }
    ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIINjhmSrpZdKXCqVP5mXWSZ7pXSC1fRZjM=" crossorigin=""/>
    <div id="<?= e($id) ?>" style="height: 320px; border-radius: 8px;" data-testid="leaflet-map" data-points="<?= e(json_encode($points)) ?>"></div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
    (function() {
        var el = document.getElementById('<?= e($id) ?>');
        if (!el || typeof L === 'undefined') return;
        var points = JSON.parse(el.dataset.points);
        var map = L.map('<?= e($id) ?>').setView([<?= $centerLat ?>, <?= $centerLng ?>], <?= $zoom ?>);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
        points.forEach(function(p) {
            var html = '<div style="font-weight:600;padding:2px 6px;">' + (p.price ? p.price : '') + '</div>';
            L.marker([p.lat, p.lng]).addTo(map).bindPopup('<b>' + p.label + '</b>' + (p.link ? '<br><a href="' + p.link + '">' + <?= json_encode(t('Lihat detail')) ?> + '</a>' : '') + html);
        });
        if (points.length > 1) {
            map.fitBounds(points.map(function(p) { return [p.lat, p.lng]; }), { padding: [30, 30] });
        }
    })();
    </script>
    <?php
}
