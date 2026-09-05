-- ============================================================
-- migrate-email.sql — log email transaksional (Fase 2 PRD I-2)
-- Idempotent: aman dijalankan berulang.
-- ============================================================

CREATE TABLE IF NOT EXISTS email_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(200) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    event VARCHAR(50) DEFAULT NULL,                -- booking_created|booking_status|invoice|reset_password|welcome
    driver VARCHAR(20) NOT NULL DEFAULT 'log',     -- log|api|smtp
    status VARCHAR(20) NOT NULL DEFAULT 'sent',    -- sent|failed
    error TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_log_to (to_email),
    INDEX idx_email_log_event (event),
    INDEX idx_email_log_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Setting email (idempotent)
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
    ('email_driver', 'log'),
    ('email_api_key', ''),
    ('email_api_endpoint', 'https://api.resend.com/emails'),
    ('email_from', 'noreply@tourandtravel.web.id');
