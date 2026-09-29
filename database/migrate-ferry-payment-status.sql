-- Tambah kolom payment_status di ferry_bookings (sinkron dgn vertikal lain)
ALTER TABLE ferry_bookings ADD COLUMN payment_status VARCHAR(20) DEFAULT NULL AFTER status;
