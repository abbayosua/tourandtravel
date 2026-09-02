<?php
/**
 * renderDestCard — Klook-style destination card with overlay
 *
 * @param array $dest  ['city' => string, 'image' => string, 'count' => int]
 * @param array $options  Optional override keys
 */
function renderDestCard($dest, $options = []) {
    $city = $dest['city'] ?? '';
    $image = $dest['image'] ?? getDestinasiImage($city);
    $count = $dest['count'] ?? 0;
    $link = $options['link'] ?? 'destinasi.php?city=' . urlencode($city);
    ?>
    <div class="col-4 col-lg-2">
        <a href="<?= $link ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm overflow-hidden dest-card klook-dest-card">
                <div class="dest-img klook-dest-img" style="background-image: url('<?= $image ?>'); aspect-ratio: 4/3;">
                    <div class="dest-overlay d-flex align-items-end p-2">
                        <div>
                            <span class="fw-semibold text-white small d-block"><?= e($city) ?></span>
                            <small class="text-white-50" style="font-size: 10px;"><?= $count ?> <?= t('paket') ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php
}