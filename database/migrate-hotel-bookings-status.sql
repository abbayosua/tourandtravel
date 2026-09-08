-- ============================================================
-- migrate-hotel-bookings-status.sql — fase 5: kolom status di hotel_bookings
-- Idempotent via information_schema.
-- ============================================================
DELIMITER $$
DROP PROCEDURE IF EXISTS hb_add_status $$
CREATE PROCEDURE hb_add_status()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'hotel_bookings'
          AND column_name = 'status'
    ) THEN
        ALTER TABLE hotel_bookings
            ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER total_price,
            ADD INDEX idx_hb_status (status);
    END IF;
END $$
DELIMITER ;
CALL hb_add_status();
DROP PROCEDURE IF EXISTS hb_add_status;
