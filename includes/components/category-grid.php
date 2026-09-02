<?php
/**
 * renderCategoryGrid — Klook-style horizontal scrollable category grid
 *
 * @param array $categories Array of category strings
 * @param array $catIcons   Associative array: category => emoji or icon HTML
 * @param array $catCounts  Associative array: category => count
 */
function renderCategoryGrid($categories, $catIcons = [], $catCounts = []) {
    ?>
    <section class="py-4">
        <div class="container">
            <h5 class="fw-bold mb-3"><?= t('Kategori Wisata') ?></h5>
            <div class="kategori-scroll d-flex gap-2 overflow-auto pb-2">
                <?php foreach ($categories as $cat): 
                    $icon = $catIcons[$cat] ?? '🌏';
                    $count = $catCounts[$cat] ?? 0;
                ?>
                <a href="tours.php?category=<?= e($cat) ?>" class="text-decoration-none flex-shrink-0">
                    <div class="card border-0 shadow-sm text-center py-3 px-3 cat-card klook-cat-card" style="min-width: 100px;">
                        <div class="fs-2 mb-1"><?= $icon ?></div>
                        <h6 class="fw-semibold small mb-0 text-dark"><?= e($cat) ?></h6>
                        <small class="text-muted"><?= $count ?> <?= t('paket') ?></small>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}