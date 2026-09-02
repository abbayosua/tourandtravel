<?php
/**
 * renderPrice — Klook-style price display
 *
 * @param float  $amount        The price amount
 * @param string $currency      Currency code (IDR/SGD/USD)
 * @param float  $originalAmount Original price for strikethrough (0 = none)
 * @param string $sourceCurrency Source currency for conversion
 * @param string $label         Optional label after price (e.g. "/orang")
 */
function renderPrice($amount, $currency = 'IDR', $originalAmount = 0, $sourceCurrency = null, $label = '') {
    ?>
    <div class="klook-price-display">
        <span class="fw-bold text-primary klook-price"><?= formatCurrencySpan($amount, $sourceCurrency ?? $currency) ?></span>
        <?php if ($originalAmount > 0): ?>
            <small class="text-decoration-line-through text-muted ms-1"><?= formatCurrencySpan($originalAmount, $sourceCurrency ?? $currency) ?></small>
        <?php endif; ?>
        <?php if ($label): ?>
            <small class="d-block text-muted"><?= t($label) ?></small>
        <?php endif; ?>
    </div>
    <?php
}