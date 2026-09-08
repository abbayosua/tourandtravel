<?php
/**
 * Kartu generik hotel/attraction/esim dgn tombol hati wishlist polimorfik.
 * renderItemCard($item, $itemType, $wishlistedIds, $options)
 */
function renderItemCard(array $item, string $itemType, array $wishlistedIds = [], array $options = []): void {
    $link = $options['link'] ?? (
        $itemType === 'hotel' ? 'hotel-detail.php?slug=' . e($item['slug'])
        : ($itemType === 'attraction' ? 'attraction-detail.php?slug=' . e($item['slug'])
        : 'esim-detail.php?id=' . (int)$item['id'])
    );
    $title = $options['title'] ?? tContent($item, 'name');
    $image = $options['image'] ?? ($item['cover_image'] ?: 'https://placehold.co/400x200?text=' . urlencode($title));
    $priceHtml = $options['price'] ?? '';
    $isWl = in_array((int)$item['id'], $wishlistedIds);
    ?>
    <div class="col">
        <div class="card border-0 shadow-sm h-100 klook-hover-card position-relative">
            <a href="<?= $link ?>" class="text-decoration-none">
                <img src="<?= e($image) ?>" class="card-img-top" style="height: 160px; object-fit: cover;" alt="<?= e($title) ?>" loading="lazy">
            </a>
            <button class="btn btn-sm position-absolute top-0 end-0 m-1 like-btn wishlist-btn klook-wishlist-btn <?= $isWl ? 'text-danger' : 'text-white' ?>"
                data-item-type="<?= e($itemType) ?>"
                onclick="toggleWishlist(this, <?= (int)$item['id'] ?>, '<?= e($itemType) ?>')">
                <i class="bi bi-heart<?= $isWl ? '-fill' : '' ?>"></i>
            </button>
            <div class="card-body p-2">
                <a href="<?= $link ?>" class="text-decoration-none"><h6 class="fw-semibold small mb-1 text-dark"><?= e($title) ?></h6></a>
                <?php if (!empty($item['city'])): ?><small class="text-muted d-block"><i class="bi bi-geo-alt"></i> <?= e($item['city']) ?></small><?php endif; ?>
                <?php if ($priceHtml): ?><div class="fw-bold text-primary small mt-1"><?= $priceHtml ?></div><?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
