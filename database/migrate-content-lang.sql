-- ============================================================
-- migrate-content-lang.sql — kolom konten per-bahasa (Fase 7)
-- Idempotent: aman dijalankan berulang (prosedur add_col_if_missing).
-- Pola penambahan bahasa baru: tambah blok ADD COLUMN {field}_{kode}
-- dan UPDATE content_language bila perlu, lalu jalankan ulang file ini.
-- ============================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS add_col_if_missing $$
CREATE PROCEDURE add_col_if_missing()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE tbl VARCHAR(64);
    DECLARE col VARCHAR(64);
    DECLARE ddl TEXT;
    DECLARE cur CURSOR FOR (
        SELECT table_name, column_name, concat(
            'ALTER TABLE `', table_name, '` ADD COLUMN `', column_name, '` ', column_type,
            COALESCE(concat(' DEFAULT ''', column_default, ''''), ''),
            IF(is_nullable = 'YES', ' NULL', ' NOT NULL')
        )
        FROM (
            -- Definisi target: (tabel, kolom, tipe, default, nullable)
            SELECT 'hotels' table_name, 'name_en' column_name, 'varchar(200)' column_type, '' column_default, 'YES' is_nullable
            UNION ALL SELECT 'hotels', 'description_en', 'text', NULL, 'YES'
            UNION ALL SELECT 'rental_cars', 'name_en', 'varchar(200)', '', 'YES'
            UNION ALL SELECT 'rental_cars', 'description_en', 'text', NULL, 'YES'
            UNION ALL SELECT 'trains', 'name_en', 'varchar(200)', '', 'YES'
            UNION ALL SELECT 'trains', 'description_en', 'text', NULL, 'YES'
            UNION ALL SELECT 'transfers', 'name_en', 'varchar(200)', '', 'YES'
            UNION ALL SELECT 'transfers', 'description_en', 'text', NULL, 'YES'
            UNION ALL SELECT 'attractions', 'name_en', 'varchar(200)', '', 'YES'
            UNION ALL SELECT 'attractions', 'description_en', 'text', NULL, 'YES'
            UNION ALL SELECT 'connectivity_products', 'name_en', 'varchar(200)', '', 'YES'
            UNION ALL SELECT 'connectivity_products', 'description_en', 'text', NULL, 'YES'
            UNION ALL SELECT 'itineraries', 'title_en', 'varchar(200)', '', 'YES'
            UNION ALL SELECT 'itineraries', 'description_en', 'text', NULL, 'YES'
            UNION ALL SELECT 'collections', 'name_en', 'varchar(200)', '', 'YES'
            UNION ALL SELECT 'collections', 'description_en', 'text', NULL, 'YES'
            UNION ALL SELECT 'tours', 'title_en', 'varchar(255)', '', 'YES'
            UNION ALL SELECT 'tours', 'description_en', 'text', NULL, 'YES'
            UNION ALL SELECT 'tours', 'itinerary_en', 'text', NULL, 'YES'
        ) target
        WHERE NOT EXISTS (
            SELECT 1 FROM information_schema.columns c
            WHERE c.table_schema = DATABASE()
              AND c.table_name = target.table_name
              AND c.column_name = target.column_name
        )
    );
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;
    col_loop: LOOP
        FETCH cur INTO tbl, col, ddl;
        IF done THEN LEAVE col_loop; END IF;
        SET @sql = ddl;
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;
    CLOSE cur;

    -- content_language untuk tabel layanan yang belum punya
    SET @cl_tables = 'hotels,rental_cars,trains,transfers,attractions,connectivity_products,itineraries';
    -- (diproses satu per satu di bawah)
END $$

DELIMITER ;

CALL add_col_if_missing();
DROP PROCEDURE IF EXISTS add_col_if_missing;

-- content_language VARCHAR(5) DEFAULT 'id' untuk setiap tabel konten
-- (idempotent via information_schema check per kolom)
DELIMITER $$

DROP PROCEDURE IF EXISTS add_content_lang_if_missing $$
CREATE PROCEDURE add_content_lang_if_missing(IN tbl_name VARCHAR(64))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = tbl_name AND column_name = 'content_language'
    ) THEN
        SET @sql = concat('ALTER TABLE `', tbl_name, '` ADD COLUMN `content_language` VARCHAR(5) NOT NULL DEFAULT ''id''');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DELIMITER ;

CALL add_content_lang_if_missing('hotels');
CALL add_content_lang_if_missing('rental_cars');
CALL add_content_lang_if_missing('trains');
CALL add_content_lang_if_missing('transfers');
CALL add_content_lang_if_missing('attractions');
CALL add_content_lang_if_missing('connectivity_products');
CALL add_content_lang_if_missing('itineraries');
CALL add_content_lang_if_missing('collections');
CALL add_content_lang_if_missing('tours');

DROP PROCEDURE IF EXISTS add_content_lang_if_missing;
