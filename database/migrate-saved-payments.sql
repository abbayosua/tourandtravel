-- ============================================================
-- migrate-saved-payments.sql — Backlog #8: saved payment methods
-- Simpan token kartu/e-wallet Midtrans (tokenized) per user untuk 1-click pay.
-- Token disimpan dalam bentuk Midtrans tokenized (bukan PAN mentah — PCI-safe).
-- Idempotent: aman dijalankan berulang.
-- ============================================================

CREATE TABLE IF NOT EXISTS saved_payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,                -- Midtrans saved token
    brand VARCHAR(30) NULL DEFAULT NULL,        -- visa|mastercard|gopay|shopeepay|...
    masked_number VARCHAR(30) NULL DEFAULT NULL,-- mis: 4811-1111-****-1114
    expiry_month TINYINT NULL DEFAULT NULL,
    expiry_year SMALLINT NULL DEFAULT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_token (token),
    INDEX idx_spm_user (user_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
