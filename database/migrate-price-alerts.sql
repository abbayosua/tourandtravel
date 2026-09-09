-- ============================================================
-- migrate-price-alerts.sql — Fase 14: notifikasi harga turun
-- User set target price → sistem cek periodik → notif bila harga ≤ target.
-- Idempotent: CREATE IF NOT EXISTS.
-- ============================================================

CREATE TABLE IF NOT EXISTS price_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_type VARCHAR(20) NOT NULL DEFAULT 'tour',       -- tour|hotel
    item_id INT NOT NULL,
    target_price DECIMAL(12,2) NOT NULL,
    currency VARCHAR(5) NOT NULL DEFAULT 'IDR',
    active TINYINT(1) NOT NULL DEFAULT 1,
    notified_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_price_alert (user_id, item_type, item_id),
    INDEX idx_pa_active (active, item_type, item_id),
    CONSTRAINT fk_pa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
