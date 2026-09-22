-- ============================================================
-- migrate-review-translations.sql — Backlog #9: review multi-bahasa
-- Tabel review_translations: terjemahan komentar review per bahasa (id/en/zh).
-- Review asli tetap di reviews.comment (lang = bahasa asal); terjemahan disimpan di sini.
-- Idempotent: aman dijalankan berulang.
-- ============================================================

CREATE TABLE IF NOT EXISTS review_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    lang VARCHAR(5) NOT NULL,
    comment TEXT NOT NULL,
    source VARCHAR(20) NOT NULL DEFAULT 'auto',   -- auto|manual
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_review_lang (review_id, lang),
    INDEX idx_rt_lang (lang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
