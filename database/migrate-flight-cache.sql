-- migrate-flight-cache.sql — Cache for FlightList & Duffel API responses
-- Idempotent via CREATE IF NOT EXISTS.

CREATE TABLE IF NOT EXISTS flight_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(128) NOT NULL,
    source ENUM('flightlist', 'duffel', 'ferry') NOT NULL,
    response_json MEDIUMTEXT NOT NULL,
    offers_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    UNIQUE KEY uniq_fc_key (cache_key),
    INDEX idx_fc_expires (expires_at),
    INDEX idx_fc_source (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Auto-cleanup procedure: delete expired entries
DELIMITER $$
DROP PROCEDURE IF EXISTS flight_cache_cleanup $$
CREATE PROCEDURE flight_cache_cleanup()
BEGIN
    DELETE FROM flight_cache WHERE expires_at < NOW();
END $$
DELIMITER ;

-- Run cleanup on creation
CALL flight_cache_cleanup();
DROP PROCEDURE IF EXISTS flight_cache_cleanup;
