-- ============================================================
-- migrate-ab-tests.sql — A/B testing ringan (FOLLOW-20260909-104850)
-- ab_tests: definisi test. ab_variants: 2+ varian. ab_impressions: hit per user/session.
-- Idempotent.
-- ============================================================

CREATE TABLE IF NOT EXISTS ab_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(100) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ab_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(100) NOT NULL,
    variant VARCHAR(50) NOT NULL,
    payload TEXT DEFAULT NULL,
    UNIQUE KEY uq_test_variant (test_name, variant)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ab_impressions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(100) NOT NULL,
    variant VARCHAR(50) NOT NULL,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(64) NOT NULL,
    converted TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ab_test (test_name),
    UNIQUE KEY uq_ab_view (test_name, session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO ab_tests (test_name, is_active) VALUES ('tour_cta_text', 1);

INSERT IGNORE INTO ab_variants (test_name, variant, payload) VALUES
('tour_cta_text', 'A', NULL),
('tour_cta_text', 'B', NULL);
