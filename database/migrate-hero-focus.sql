-- ============================================================
-- migrate-hero-focus.sql — kolom `focus` di hero_slides (homepage-templates)
-- Idempotent: aman dijalankan berulang.
-- ============================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS hero_add_focus_if_missing $$
CREATE PROCEDURE hero_add_focus_if_missing()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'hero_slides'
          AND column_name = 'focus'
    ) THEN
        ALTER TABLE hero_slides
            ADD COLUMN focus VARCHAR(10) NOT NULL DEFAULT 'all' AFTER cta_link,
            ADD INDEX idx_hero_focus (focus, is_active, sort_order);
    END IF;
END $$

DELIMITER ;

CALL hero_add_focus_if_missing();
DROP PROCEDURE IF EXISTS hero_add_focus_if_missing;

-- Slide seed lama (fallback array index.php lama) milik fokus tour
UPDATE hero_slides SET focus = 'tour' WHERE focus = 'all' AND id IN (1, 2, 3);

-- Seed slide tambahan: 2 untuk hotel, 2 untuk flight (idempotent via cek judul)
INSERT INTO hero_slides (image_url, title, subtitle, cta_text, cta_link, focus, sort_order, is_active)
SELECT * FROM (
    SELECT
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1920&q=80' AS image_url,
        'Menginap Nyaman, Harga Terbaik' AS title,
        'Ribuan hotel dari budget sampai bintang 5' AS subtitle,
        'Cari Hotel' AS cta_text,
        'hotels.php' AS cta_link,
        'hotel' AS focus,
        1 AS sort_order,
        1 AS is_active
    UNION ALL
    SELECT
        'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=1920&q=80',
        'Resort & Villa Pilihan',
        'Liburan santai dengan fasilitas lengkap',
        'Lihat Hotel',
        'hotels.php',
        'hotel',
        2,
        1
    UNION ALL
    SELECT
        'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1920&q=80',
        'Terbang ke Kota Impian',
        'Tiket pesawat murah ke ratusan kota',
        'Cari Penerbangan',
        'flights.php',
        'flight',
        1,
        1
    UNION ALL
    SELECT
        'https://images.unsplash.com/photo-1556388158-158ea5ccacbd?w=1920&q=80',
        'Promo Tiket Setiap Hari',
        'Bandara favorit, harga bersahabat',
        'Lihat Promo',
        'flights.php',
        'flight',
        2,
        1
) AS seed
WHERE NOT EXISTS (
    SELECT 1 FROM hero_slides hs WHERE hs.title = seed.title
);
