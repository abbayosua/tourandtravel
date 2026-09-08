-- ============================================================
-- migrate-hotel-bookings-room.sql — fase 2: booking pakai tipe kamar
-- Kolom room_id di hotel_bookings (nullable — kompatibel booking lama).
-- Idempotent via information_schema.
-- ============================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS hb_add_room_id $$
CREATE PROCEDURE hb_add_room_id()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'hotel_bookings'
          AND column_name = 'room_id'
    ) THEN
        ALTER TABLE hotel_bookings
            ADD COLUMN room_id INT DEFAULT NULL AFTER hotel_id,
            ADD INDEX idx_hb_room (room_id);
    END IF;
END $$

DELIMITER ;

CALL hb_add_room_id();
DROP PROCEDURE IF EXISTS hb_add_room_id;
