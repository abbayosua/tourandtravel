-- Flight hero Voyage (flights.php hero rewrite): ZH untuk key baru.
-- Idempotent: INSERT IGNORE. Jalankan: mysql -u root tourandtravel < database/migrate-translations-zh-flight-hero.sql
INSERT IGNORE INTO translations (`key`, lang, value) VALUES
('Flight', 'zh', '航班'),
('flights', 'zh', '航班'),
('Tiket pesawat pilihan — booking instan, harga terbaik.', 'zh', '精选航班 — 即时预订，最优价格。'),
('Dipercaya traveler', 'zh', '深受旅行者信赖'),
('Dari (CGK)...', 'zh', '出发地 (CGK)...'),
('Ke (DPS)...', 'zh', '目的地 (DPS)...');
