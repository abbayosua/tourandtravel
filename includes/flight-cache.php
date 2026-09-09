<?php
/**
 * Flight API Cache — database-based caching for FlightList & Duffel responses.
 * Cache key format: {source}:{from}:{to}:{date}:{cabin}:{pax}
 * TTL: 6 hours (FlightList), 2 hours (Duffel)
 */

define('FLIGHT_CACHE_TTL', [
    'flightlist' => 6 * 3600,  // 6 hours
    'duffel'     => 2 * 3600,  // 2 hours
    'ferry'      => 4 * 3600,  // 4 hours (ferry schedules are stable)
]);

/**
 * Generate cache key from search parameters
 */
function flightCacheKey(string $source, string $from, string $to, string $date, string $cabin, int $pax): string {
    return $source . ':' . strtoupper($from) . ':' . strtoupper($to) . ':' . $date . ':' . $cabin . ':' . $pax;
}

/**
 * Get cached response if valid (not expired)
 * @return array|null Cached data or null if miss
 */
function flightCacheGet(string $cacheKey): ?array {
    try {
        $stmt = db()->prepare("SELECT response_json, offers_count, expires_at FROM flight_cache WHERE cache_key = ? AND expires_at > UTC_TIMESTAMP() LIMIT 1");
        $stmt->execute([$cacheKey]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $data = json_decode($row['response_json'], true);
        if (!is_array($data)) return null;
        $data['_cache_hit'] = true;
        $data['_cache_offers'] = $row['offers_count'];
        return $data;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Store response in cache
 */
function flightCacheSet(string $cacheKey, string $source, array $data, int $offersCount = 0): void {
    try {
        $ttl = FLIGHT_CACHE_TTL[$source] ?? 3600;
        $expiresAt = gmdate('Y-m-d H:i:s', time() + $ttl);
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $stmt = db()->prepare("INSERT INTO flight_cache (cache_key, source, response_json, offers_count, expires_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE response_json = VALUES(response_json), offers_count = VALUES(offers_count), expires_at = VALUES(expires_at), created_at = NOW()");
        $stmt->execute([$cacheKey, $source, $json, $offersCount, $expiresAt]);
    } catch (Throwable $e) {
        // Cache write failure is non-fatal
    }
}

/**
 * Clear all expired cache entries
 */
function flightCacheCleanup(): int {
    try {
        $stmt = db()->prepare("DELETE FROM flight_cache WHERE expires_at < UTC_TIMESTAMP()");
        $stmt->execute();
        return $stmt->rowCount();
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Clear cache for a specific source or all
 */
function flightCacheClear(?string $source = null): int {
    try {
        if ($source) {
            $stmt = db()->prepare("DELETE FROM flight_cache WHERE source = ?");
            $stmt->execute([$source]);
        } else {
            $stmt = db()->prepare("DELETE FROM flight_cache");
            $stmt->execute();
        }
        return $stmt->rowCount();
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Get cache stats
 */
function flightCacheStats(): array {
    try {
        $total = db()->query("SELECT COUNT(*) FROM flight_cache")->fetchColumn();
        $valid = db()->query("SELECT COUNT(*) FROM flight_cache WHERE expires_at > UTC_TIMESTAMP()")->fetchColumn();
        $expired = $total - $valid;
        $bySource = db()->query("SELECT source, COUNT(*) as cnt, SUM(offers_count) as total_offers FROM flight_cache GROUP BY source")->fetchAll(PDO::FETCH_ASSOC);
        return [
            'total' => (int)$total,
            'valid' => (int)$valid,
            'expired' => (int)$expired,
            'by_source' => $bySource,
        ];
    } catch (Throwable $e) {
        return ['total' => 0, 'valid' => 0, 'expired' => 0, 'by_source' => []];
    }
}
