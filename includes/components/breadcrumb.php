<?php
/**
 * renderBreadcrumb — Klook-style breadcrumb
 *
 * @param array $items Array of ['label' => string, 'url' => string|null]
 * Last item (null url) = active (not linked)
 */
function renderBreadcrumb($items) {
    if (empty($items)) return;
    ?>
    <nav class="mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb small klook-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/"><i class="bi bi-house"></i></a></li>
            <?php foreach ($items as $item): ?>
                <?php if (!empty($item['url'])): ?>
                    <li class="breadcrumb-item"><a href="<?= $item['url'] ?>"><?= e($item['label']) ?></a></li>
                <?php else: ?>
                    <li class="breadcrumb-item active"><?= e($item['label']) ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
}