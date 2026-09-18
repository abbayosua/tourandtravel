-- migrate-tour-brochure.sql: kolom brosur ala Balindo untuk tours + tier harga tour_dates.
-- Jalankan sekali: mysql -u root tourandtravel < database/migrate-tour-brochure.sql
-- (MySQL tidak mendukung ADD COLUMN IF NOT EXISTS; file ini hanya untuk dokumentasi/CI fresh-install.)
ALTER TABLE tours
  ADD COLUMN highlights TEXT NULL COMMENT 'Satu highlight per baris' AFTER description,
  ADD COLUMN includes TEXT NULL COMMENT 'Satu item include per baris' AFTER highlights,
  ADD COLUMN excludes TEXT NULL COMMENT 'Satu item exclude per baris' AFTER includes,
  ADD COLUMN flight_info TEXT NULL COMMENT 'Jadwal penerbangan, satu baris per leg' AFTER excludes,
  ADD COLUMN meeting_point VARCHAR(255) NULL AFTER flight_info,
  ADD COLUMN important_notes TEXT NULL COMMENT 'Satu catatan per baris' AFTER meeting_point,
  ADD COLUMN route_cities VARCHAR(255) NULL COMMENT 'Mis: SHANGHAI - HANGZHOU - SUZHOU' AFTER important_notes;

ALTER TABLE tour_dates
  ADD COLUMN price_adult DECIMAL(12,2) NULL AFTER available_slots,
  ADD COLUMN price_child DECIMAL(12,2) NULL AFTER price_adult,
  ADD COLUMN price_single DECIMAL(12,2) NULL AFTER price_child,
  ADD COLUMN note VARCHAR(100) NULL COMMENT 'Mis: Low Season / Imlek' AFTER price_single;
