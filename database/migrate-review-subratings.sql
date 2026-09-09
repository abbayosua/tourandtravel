-- ============================================================
-- migrate-review-subratings.sql — Fase 12: sub-rating per aspek review
-- Bersifat idempotent: CREATE IF NOT EXISTS.
-- ============================================================

CREATE TABLE IF NOT EXISTS review_subratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    aspect VARCHAR(30) NOT NULL COMMENT 'cleanliness|location|staff|value|facilities|comfort',
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_review_aspect (review_id, aspect),
    INDEX idx_sr_review (review_id),
    CONSTRAINT fk_sr_review FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
