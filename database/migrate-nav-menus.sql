-- ============================================================
-- migrate-nav-menus.sql — menu header dinamis (nav_menus)
-- Idempotent: aman dijalankan berulang.
-- ============================================================
CREATE TABLE IF NOT EXISTS nav_menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(50) NOT NULL,
    url VARCHAR(150) NOT NULL,
    icon VARCHAR(50) DEFAULT 'bi-circle',
    match_key VARCHAR(50) DEFAULT '',
    show_in_tabs TINYINT(1) NOT NULL DEFAULT 0,
    show_in_menu TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO nav_menus (id, label, url, icon, match_key, show_in_tabs, show_in_menu, sort_order, is_active) VALUES
(1, 'Tour', 'tours.php', 'bi-map', 'tour', 1, 1, 1, 1),
(2, 'Hotel', 'hotels.php', 'bi-building', 'hotel', 1, 1, 2, 1),
(3, 'Pesawat', 'flights.php', 'bi-airplane', 'flight', 1, 1, 3, 1),
(4, 'Ferry', 'ferries.php', 'bi-ship', 'ferri', 0, 1, 4, 1),
(5, 'Rental', 'rental-cars.php', 'bi-car-front', 'rental-car', 0, 1, 5, 1),
(6, 'Kereta', 'trains.php', 'bi-train-front', 'train', 0, 1, 6, 1),
(7, 'Atraksi', 'attractions.php', 'bi-signpost-2', 'attraction', 0, 1, 7, 1),
(8, 'Transfer', 'transfers.php', 'bi-arrow-left-right', 'transfer', 0, 1, 8, 1),
(9, 'eSIM', 'esim.php', 'bi-sim', 'esim', 0, 1, 9, 1);
