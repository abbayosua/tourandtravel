<?php
/**
 * flash_sales helper — engine diskon waktu-terbatas.
 */
function getActiveFlashSale(string $itemType, int $itemId): ?array {
    static $cache = [];
    $key = $itemType . ':' . $itemId;
    if (array_key_exists($key, $cache)) return $cache[$key];
    $stmt = db()->prepare("SELECT * FROM flash_sales WHERE item_type = ? AND item_id = ? AND is_active = 1 AND starts_at <= NOW() AND ends_at > NOW() AND (stock_limit IS NULL OR sold_count < stock_limit) LIMIT 1");
    $stmt->execute([$itemType, $itemId]);
    $row = $stmt->fetch();
    $cache[$key] = $row ?: null;
    return $cache[$key];
}

/** Harga final (diskon bila flash sale aktif, harga normal bila tidak). */
function getFlashSalePrice(float $basePrice, string $itemType, int $itemId): array {
    $fs = getActiveFlashSale($itemType, $itemId);
    if (!$fs) return ['price' => $basePrice, 'flash' => null];
    $price = round($basePrice * (100 - (int)$fs['discount_percent']) / 100, 2);
    return ['price' => $price, 'flash' => $fs];
}
