-- migrate-hotel-cache.sql — Cache untuk live hotel API (Booking.com, OYO, NusaTrip)
-- Lihat HOTEL-ENDPOINTS.md + includes/hotelapi.php.
-- Idempotent via CREATE IF NOT EXISTS + INSERT IGNORE.

CREATE TABLE IF NOT EXISTS hotel_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(191) NOT NULL,
    source ENUM('booking', 'oyo', 'nusatrip') NOT NULL,
    response_json MEDIUMTEXT NOT NULL,
    items_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    UNIQUE KEY uniq_hc_key (cache_key),
    INDEX idx_hc_expires (expires_at),
    INDEX idx_hc_source (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Setting default: aktifkan live hotel API, utamakan NusaTrip.
-- nusatrip_rkey default = token dari HOTEL-ENDPOINTS.md (ganti bila kedaluwarsa).
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
    ('hotel_live_enabled', '1'),
    ('hotel_live_source', 'nusatrip'),
    ('nusatrip_rkey', '3bff981561ba69f3d32f21dca1d4f9a48d18837cac9f40e370c54d523ff547867d65f7abb9f7d6618faea23dc16a2cda');
