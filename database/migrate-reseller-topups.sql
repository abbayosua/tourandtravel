-- ============================================================
-- migrate-reseller-topups.sql — Topup request table for resellers
-- Idempotent: safe to run multiple times.
-- ============================================================

CREATE TABLE IF NOT EXISTS reseller_topups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM('bank_transfer','qris','ewallet') NOT NULL DEFAULT 'bank_transfer',
    proof_path VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    admin_note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_reseller_topup_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index for fast lookups by user and status (idempotent)
SET @idxUser := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reseller_topups' AND INDEX_NAME = 'idx_reseller_topups_user');
SET @sql1 := IF(@idxUser = 0, "CREATE INDEX idx_reseller_topups_user ON reseller_topups(user_id)", 'SELECT 1');
PREPARE s1 FROM @sql1; EXECUTE s1; DEALLOCATE PREPARE s1;

SET @idxStatus := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reseller_topups' AND INDEX_NAME = 'idx_reseller_topups_status');
SET @sql2 := IF(@idxStatus = 0, "CREATE INDEX idx_reseller_topups_status ON reseller_topups(status)", 'SELECT 1');
PREPARE s2 FROM @sql2; EXECUTE s2; DEALLOCATE PREPARE s2;
