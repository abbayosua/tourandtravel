-- Flight hero Voyage (flights.php hero rewrite): ZH untuk key baru.
-- Idempotent: INSERT IGNORE. Jalankan: mysql -u root tourandtravel < database/migrate-translations-zh-flight-hero.sql
INSERT IGNORE INTO translations (`key`, lang, value) VALUES
('Flight', 'zh', '航班'),
('flights', 'zh', '航班'),
('Tiket pesawat pilihan — booking instan, harga terbaik.', 'zh', '精选航班 — 即时预订，最优价格。'),
('Paket tour pilihan, villa & pengalaman — booking instan, harga terbaik.', 'zh', '精选旅游团、别墅与体验 — 即时预订，最优价格。'),
('Hotel, vila & resor pilihan — booking instan, harga terbaik.', 'zh', '精选酒店、别墅与度假村 — 即时预订，最优价格。'),
('Pesan tiket ferry — booking instan, harga terbaik.', 'zh', '预订船票 — 即时预订，最优价格。'),
('Tiket kereta pilihan — booking instan, harga terbaik.', 'zh', '精选火车票 — 即时预订，最优价格。'),
('Dipercaya traveler', 'zh', '深受旅行者信赖'),
('Dari (CGK)...', 'zh', '出发地 (CGK)...'),
('Ke (DPS)...', 'zh', '目的地 (DPS)...');
