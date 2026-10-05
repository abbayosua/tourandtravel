-- Tambah kolom payment_method di train_bookings (Virtual Account vs Transfer Bank penyedia).
ALTER TABLE train_bookings ADD COLUMN payment_method VARCHAR(40) DEFAULT NULL AFTER va_number;
