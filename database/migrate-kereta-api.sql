-- Kereta API (KAI) booking langsung ke penyedia klikmbc.biz.
-- Booking API tidak punya baris di tabel `trains` (katalog), jadi train_id dibuat nullable
-- dan identitas kereta + info pembayaran supplier disimpan di kolom baru.

ALTER TABLE train_bookings MODIFY train_id INT NULL;

ALTER TABLE train_bookings
    ADD COLUMN provider VARCHAR(20) NOT NULL DEFAULT 'local' AFTER train_id,
    ADD COLUMN payment_status VARCHAR(20) DEFAULT NULL AFTER status,
    ADD COLUMN provider_invoice VARCHAR(40) DEFAULT NULL,
    ADD COLUMN train_name VARCHAR(120) DEFAULT NULL,
    ADD COLUMN train_code VARCHAR(40) DEFAULT NULL,
    ADD COLUMN train_class VARCHAR(60) DEFAULT NULL,
    ADD COLUMN route_from VARCHAR(120) DEFAULT NULL,
    ADD COLUMN route_to VARCHAR(120) DEFAULT NULL,
    ADD COLUMN departure_datetime VARCHAR(80) DEFAULT NULL,
    ADD COLUMN va_bank VARCHAR(80) DEFAULT NULL,
    ADD COLUMN va_number VARCHAR(60) DEFAULT NULL,
    ADD COLUMN payment_total DECIMAL(12,2) DEFAULT NULL,
    ADD COLUMN payment_deadline VARCHAR(80) DEFAULT NULL,
    ADD COLUMN passenger_data TEXT DEFAULT NULL;

ALTER TABLE train_bookings MODIFY booking_code VARCHAR(40) DEFAULT NULL;
