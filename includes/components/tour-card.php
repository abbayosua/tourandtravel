<?php
/**
 * renderTourCard — Klook-style tour card component
 * 
 * Retains existing class .tour-card-klook for backward E2E compatibility.
 * Adds new Klook-inspired classes alongside.
 *
 * @param array $tour       Tour row (id, title, slug, category, price, original_price, rating, total_reviews, description, cover_image, price_currency, content_language, instant_confirmation, free_cancellation, best_seller)
 * @param array $wishlistIds Array of wishlisted tour IDs for current user
 * @param array $options    Optional: 'show_description' => bool, 'show_wishlist' => bool, 'link_target' => string
 */
function renderTourCard($tour, $wishlistIds = [], $options = []) {
    $defaults = [
        'show_description' => true,
        'show_wishlist'    => true,
        'link_target'      => 'tour-detail.php?slug=' . e($tour['slug']),
    ];
    $opts = array_merge($defaults, $options);

    $isWishlisted = in_array($tour['id'], $wishlistIds);
    $diskon = getDiskonPersen($tour);
    ?>
    <div class="col-md-6 col-lg-4">
        <div class="card tour-card-klook border-0 shadow-sm h-100 klook-card">
            <div class="position-relative overflow-hidden rounded-top klook-card-img" style="height: 200px;">
                <img src="<?= getTourImage($tour, 'medium') ?>" 
                     onerror="this.src='<?= getTourImageFallback($tour, 'medium') ?>'" 
                     class="w-100 h-100" 
                     style="object-fit: cover;" 
                     alt="<?= e($tour['title']) ?>">

                <!-- Badge diskon -->
                <?php if ($diskon > 0): ?>
                    <span class="badge bg-danger position-absolute top-0 start-0 m-2 shadow-sm">-<?= $diskon ?>%</span>
                <?php elseif (!empty($tour['best_seller'])): ?>
                    <span class="badge bg-primary position-absolute top-0 start-0 m-2 shadow-sm">Best Seller</span>
                <?php endif; ?>

                <!-- Badge Instant Confirmation -->
                <?php if (!empty($tour['instant_confirmation'])): ?>
                    <span class="badge bg-success position-absolute top-0 end-0 m-2 shadow-sm" style="font-size: 10px;">
                        <i class="bi bi-lightning-charge-fill me-1"></i>Instan
                    </span>
                <?php endif; ?>

                <!-- Badge Free Cancellation -->
                <?php if (!empty($tour['free_cancellation'])): ?>
                    <span class="badge bg-info text-white position-absolute top-0 end-0 m-2 shadow-sm" style="font-size: 10px; margin-top: 28px !important;">
                        <i class="bi bi-shield-check me-1"></i>Batal Gratis
                    </span>
                <?php endif; ?>

                <!-- Wishlist button -->
                <?php if ($opts['show_wishlist']): ?>
                <button class="btn btn-sm position-absolute top-0 end-0 m-1 like-btn wishlist-btn klook-wishlist-btn <?= $isWishlisted ? 'text-danger' : 'text-white' ?>" 
                    data-tour-id="<?= $tour['id'] ?>" 
                    onclick="toggleWishlist(this, <?= $tour['id'] ?>)">
                    <i class="bi bi-heart<?= $isWishlisted ? '-fill' : '' ?>"></i>
                </button>
                <?php endif; ?>

                <!-- Category badge -->
                <span class="badge bg-white text-dark position-absolute top-0 start-0 m-2 shadow-sm" style="margin-top: 40px !important;"><?= e($tour['category']) ?></span>
            </div>

            <div class="card-body p-3 d-flex flex-column">
                <!-- Title -->
                <h6 class="fw-semibold mb-1 klook-card-title"><?= e(t($tour['title'], null, $tour['content_language'] ?? 'id')) ?></h6>
                
                <!-- Rating -->
                <div class="d-flex align-items-center gap-2 small mb-1">
                    <?= renderStars($tour['rating']) ?>
                    <span class="text-muted">(<?= (int)$tour['total_reviews'] ?>)</span>
                </div>

                <!-- Description -->
                <?php if ($opts['show_description']): ?>
                <p class="small text-muted flex-grow-1 mb-2"><?= substr(e($tour['description']), 0, 100) ?>...</p>
                <?php endif; ?>

                <!-- Price + CTA -->
                <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto">
                    <div>
                        <span class="fw-bold text-primary klook-price"><?= formatCurrencySpan($tour['price'], $tour['price_currency'] ?? 'IDR') ?></span>
                        <?php if ($diskon > 0 && !empty($tour['original_price'])): ?>
                            <small class="text-decoration-line-through text-muted ms-1"><?= formatCurrencySpan($tour['original_price'], $tour['price_currency'] ?? 'IDR') ?></small>
                        <?php endif; ?>
                        <small class="d-block text-muted">/<?= t('orang') ?></small>
                    </div>
                    <a href="<?= $opts['link_target'] ?>" class="btn btn-sm btn-primary rounded-pill px-3 klook-cta"><?= t('Detail') ?></a>
                </div>
            </div>
        </div>
    </div>
    <?php
}