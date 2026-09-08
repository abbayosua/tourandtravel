-- ============================================================
-- migrate-hotels-amenities.sql — fase 3: filter amenitas hotel
-- Seed amenities CSV per hotel (deterministik dari id) + bintang min filter support.
-- Idempotent: hanya isi bila amenities masih NULL.
-- ============================================================

UPDATE hotels
SET amenities = CONCAT_WS(',',
    'WiFi',
    IF(id % 2 = 1, 'Kolam', NULL),
    IF(id % 3 != 2, 'Parkir', NULL),
    IF(id % 4 != 3, 'Sarapan', NULL),
    IF(id % 5 IN (0, 1), 'Gym', NULL),
    IF(id % 3 = 1, 'Spa', NULL),
    IF(id % 4 = 1, 'Restoran', NULL)
)
WHERE amenities IS NULL OR amenities = '';
