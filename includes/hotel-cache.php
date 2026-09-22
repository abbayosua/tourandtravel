<?php
/**
 * hotel-cache.php — Database caching for live hotel API (Booking.com, OYO, NusaTrip).
 * Cache key format: {source}:{...param}
 * TTL: booking 6 jam, oyo 4 jam, nusatrip 4 jam (harga live relatif stabil).
 *
 * Lihat HOTEL-ENDPOINTS.md dan includes/hotelapi.php.
 */

define('HOTEL_CACHE_TTL', [
    'booking'  => 6 * 3600,  // 6 hours
    'oyo'      => 4 * 3600,  // 4 hours
    'nusatrip' => 4 * 3600,  // 4 hours
]);

/**
 * Susun cache key dari source + daftar bagian.
 */
function hotelCacheKey(string $source, array $parts = []): string {
    $parts = array_map(static fn($p) => is_scalar($p) ? (string)$p : json_encode($p), $parts);
    return substr($source . ':' . implode(':', $parts), 0, 191);
}

/**
 * Ambil respons dari cache bila belum kedaluwarsa.
 * @return array|null Data atau null bila miss.
 */
function hotelCacheGet(string $cacheKey): ?array {
    try {
        $stmt = db()->prepare("SELECT response_json, items_count, expires_at FROM hotel_cache WHERE cache_key = ? AND expires_at > UTC_TIMESTAMP() LIMIT 1");
        $stmt->execute([$cacheKey]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $data = json_decode($row['response_json'], true);
        if (!is_array($data)) return null;
        $data['_cache_hit'] = true;
        $data['_cache_items'] = (int)$row['items_count'];
        return $data;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Simpan respons ke cache.
 */
function hotelCacheSet(string $cacheKey, string $source, array $data, int $itemsCount = 0): void {
    try {
        $ttl = HOTEL_CACHE_TTL[$source] ?? 3600;
        $expiresAt = gmdate('Y-m-d H:i:s', time() + $ttl);
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $stmt = db()->prepare("INSERT INTO hotel_cache (cache_key, source, response_json, items_count, expires_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE response_json = VALUES(response_json), items_count = VALUES(items_count), expires_at = VALUES(expires_at), created_at = NOW()");
        $stmt->execute([$cacheKey, $source, $json, $itemsCount, $expiresAt]);
    } catch (Throwable $e) {
        // Cache write failure is non-fatal
    }
}

/**
 * Hapus semua entri kedaluwarsa.
 */
function hotelCacheCleanup(): int {
    try {
        $stmt = db()->prepare("DELETE FROM hotel_cache WHERE expires_at < UTC_TIMESTAMP()");
        $stmt->execute();
        return $stmt->rowCount();
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Hapus cache untuk satu source tertentu atau semuanya.
 */
function hotelCacheClear(?string $source = null): int {
    try {
        if ($source) {
            $stmt = db()->prepare("DELETE FROM hotel_cache WHERE source = ?");
            $stmt->execute([$source]);
        } else {
            $stmt = db()->prepare("DELETE FROM hotel_cache");
            $stmt->execute();
        }
        return $stmt->rowCount();
    } catch (Throwable $e) {
        return 0;
    }
}
