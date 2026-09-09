-- ============================================================
-- migrate-passenger-profiles.sql — Fase 15: saved passenger profiles
-- 1-click checkout: simpan data penumpang rutin.
-- Idempotent: CREATE TABLE IF NOT EXISTS.
-- ============================================================

CREATE TABLE IF NOT EXISTS passenger_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    passport_no VARCHAR(50) DEFAULT NULL,
    nationality VARCHAR(50) DEFAULT NULL,
    dob DATE DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pp_user (user_id),
    CONSTRAINT fk_pp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
