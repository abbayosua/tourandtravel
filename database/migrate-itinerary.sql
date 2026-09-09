-- ============================================================
-- migrate-itinerary.sql — Itinerary builder (FOLLOW-20260909-104850)
-- user_itineraries: 1 user punya banyak itinerary. user_itinerary_days: hari ke-n.
-- user_itinerary_items: item per hari, mengacu tour/hotel (polymorphic, NULLable).
-- Semua idempotent.
-- ============================================================

CREATE TABLE IF NOT EXISTS user_itineraries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    start_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_uitin_user (user_id),
    CONSTRAINT fk_uitin_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_itinerary_days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    itinerary_id INT NOT NULL,
    day_number INT NOT NULL DEFAULT 1,
    UNIQUE KEY uq_uitin_day (itinerary_id, day_number),
    CONSTRAINT fk_uitin_day FOREIGN KEY (itinerary_id) REFERENCES user_itineraries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_itinerary_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_id INT NOT NULL,
    item_type ENUM('tour','hotel','flight','custom') NOT NULL DEFAULT 'custom',
    tour_id INT DEFAULT NULL,
    hotel_id INT DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    note TEXT DEFAULT NULL,
    time_label VARCHAR(20) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uitem_day (day_id),
    CONSTRAINT fk_uitem_day FOREIGN KEY (day_id) REFERENCES user_itinerary_days(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
