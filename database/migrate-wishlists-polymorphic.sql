-- ============================================================
-- migrate-wishlists-polymorphic.sql — fase 9: wishlist lintas vertikal
-- Struktur baru: item_type ('tour'|'hotel'|'attraction'|'esim') + item_id.
-- Backward-compatible: kolom tour_id dibiarkan; data lama dimigrasi ke baris baru.
-- Idempotent via information_schema + WHERE NOT EXISTS.
-- ============================================================
DELIMITER $$

DROP PROCEDURE IF EXISTS wl_polymorphic $$
CREATE PROCEDURE wl_polymorphic()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'wishlists' AND column_name = 'item_type') THEN
        ALTER TABLE wishlists
            ADD COLUMN item_type VARCHAR(20) NOT NULL DEFAULT 'tour' AFTER user_id,
            ADD COLUMN item_id INT NOT NULL DEFAULT 0 AFTER item_type,
            ADD UNIQUE KEY uniq_wl_item (user_id, item_type, item_id);
    END IF;
END $$

DELIMITER ;

CALL wl_polymorphic();
DROP PROCEDURE IF EXISTS wl_polymorphic;

-- Migrasi data lama (tour_id terisi, item_id belum): tour_id → item_id
UPDATE wishlists SET item_type = 'tour', item_id = tour_id WHERE tour_id > 0 AND item_id = 0;
