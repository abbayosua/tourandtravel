<?php
/**
 * renderBadge — Klook-style badge component
 *
 * @param string $text   Badge text
 * @param string $color  Bootstrap color class (danger, success, primary, warning, etc.)
 * @param array  $options Optional: 'icon' => 'bi-lightning-charge-fill', 'size' => 'small', 'pill' => bool
 */
function renderBadge($text, $color = 'primary', $options = []) {
    $icon = $options['icon'] ?? '';
    $size = $options['size'] ?? 'small';
    $pill = $options['pill'] ?? true;
    $class = 'badge bg-' . $color;
    if ($pill) $class .= ' rounded-pill';
    ?>
    <span class="<?= $class ?> klook-badge" style="font-size: <?= $size === 'small' ? '10px' : '12px' ?>;">
        <?php if ($icon): ?><i class="bi <?= $icon ?> me-1"></i><?php endif; ?>
        <?= e($text) ?>
    </span>
    <?php
}

/**
 * renderBadgeDiskon — Discount percentage badge
 */
function renderBadgeDiskon($persen) {
    if ($persen <= 0) return;
    renderBadge('-' . $persen . '%', 'danger', ['icon' => 'bi-tag-fill']);
}

/**
 * renderBadgeBestSeller — Best Seller badge
 */
function renderBadgeBestSeller() {
    renderBadge('Best Seller', 'primary', ['icon' => 'bi-star-fill']);
}

/**
 * renderBadgeInstant — Instant Confirmation badge
 */
function renderBadgeInstant() {
    renderBadge(t('Konfirmasi Instan'), 'success', ['icon' => 'bi-lightning-charge-fill', 'size' => 'xs']);
}

/**
 * renderBadgeFreeCancel — Free Cancellation badge
 */
function renderBadgeFreeCancel() {
    renderBadge(t('Batal Gratis'), 'info', ['icon' => 'bi-shield-check']);
}