-- ============================================================
-- migrate-latlng.sql — fase 8: peta interaktif
-- Kolom lat/lng untuk hotels & attractions (nullable) + seed
-- koordinat demo untuk data tanpa koordinat.
-- Idempotent via information_schema.
-- ============================================================
DELIMITER $$

DROP PROCEDURE IF EXISTS add_latlng $$
CREATE PROCEDURE add_latlng(IN tbl VARCHAR(64))
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = tbl AND column_name = 'lat') THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN lat DECIMAL(10,7) DEFAULT NULL, ADD COLUMN lng DECIMAL(10,7) DEFAULT NULL, ADD INDEX idx_', tbl, '_latlng (lat, lng)');
        PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
    END IF;
END $$

DELIMITER ;

CALL add_latlng('hotels');
CALL add_latlng('attractions');
DROP PROCEDURE IF EXISTS add_latlng;

-- Seed koordinat demo bila NULL (per kota utama)
UPDATE hotels SET lat = -8.650000, lng = 115.216000 WHERE (lat IS NULL OR lng IS NULL) AND city LIKE '%Bali%';
UPDATE hotels SET lat = -6.208800, lng = 106.845600 WHERE (lat IS NULL OR lng IS NULL) AND city LIKE '%Jakarta%';
UPDATE hotels SET lat = -7.795600, lng = 110.369500 WHERE (lat IS NULL OR lng IS NULL) AND city LIKE '%Yogyakarta%';
UPDATE hotels SET lat = -6.914750, lng = 107.609800 WHERE (lat IS NULL OR lng IS NULL) AND city LIKE '%Bandung%';
UPDATE hotels SET lat = 1.130400, lng = 104.053000 WHERE (lat IS NULL OR lng IS NULL) AND city LIKE '%Batam%';
UPDATE hotels SET lat = -7.257500, lng = 112.752100 WHERE (lat IS NULL OR lng IS NULL) AND city LIKE '%Surabaya%';
UPDATE hotels SET lat = -8.506900, lng = 115.262500 WHERE (lat IS NULL OR lng IS NULL) AND (lat IS NULL OR lng IS NULL);

UPDATE attractions SET lat = -7.607900, lng = 110.203800 WHERE (lat IS NULL OR lng IS NULL) AND (name LIKE '%Borobudur%');
UPDATE attractions SET lat = -6.175400, lng = 106.827200 WHERE (lat IS NULL OR lng IS NULL) AND (name LIKE '%Monas%' OR name LIKE '%National Monument%');
UPDATE attractions SET lat = -6.229500, lng = 106.809000 WHERE (lat IS NULL OR lng IS NULL) AND (lat IS NULL OR lng IS NULL);
