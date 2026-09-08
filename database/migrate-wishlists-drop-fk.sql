-- ============================================================
-- migrate-wishlists-drop-fk.sql — fase 9: lepas FK tour_id
-- (wishlist polimorfik menyimpan non-tour dengan tour_id=0,
--  FK lama memblokir insert. Data tour tetap aman di kolom.)
-- Idempotent via information_schema.
-- ============================================================
DELIMITER $$
DROP PROCEDURE IF EXISTS wl_drop_fk $$
CREATE PROCEDURE wl_drop_fk()
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'wishlists' AND constraint_name = 'wishlists_ibfk_2' AND constraint_type = 'FOREIGN KEY') THEN
        ALTER TABLE wishlists DROP FOREIGN KEY wishlists_ibfk_2;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'wishlists' AND column_name = 'tour_id' AND is_nullable = 'NO') THEN
        ALTER TABLE wishlists MODIFY tour_id INT DEFAULT NULL;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'wishlists' AND index_name = 'unique_wish') THEN
        ALTER TABLE wishlists DROP INDEX unique_wish;
    END IF;
END $$
DELIMITER ;
CALL wl_drop_fk();
DROP PROCEDURE IF EXISTS wl_drop_fk;
