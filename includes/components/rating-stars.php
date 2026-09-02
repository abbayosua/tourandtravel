<?php
/**
 * renderRatingStars — Klook-style star rating display
 *
 * @param float $rating  Rating value (e.g. 4.8)
 * @param int   $count   Number of reviews
 * @param bool  $showCount Whether to show review count
 */
function renderRatingStars($rating, $count = 0, $showCount = true) {
    ?>
    <div class="d-flex align-items-center gap-2 small mb-1 klook-rating">
        <span class="fw-bold text-warning"><?= number_format($rating, 1) ?></span>
        <?= renderStars($rating) ?>
        <?php if ($showCount && $count > 0): ?>
            <span class="text-muted">(<?= number_format($count) ?>)</span>
        <?php endif; ?>
    </div>
    <?php
}