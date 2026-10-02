-- ============================================================
-- migrate-booking-participants.sql — multi-peserta tour
-- Data pemesan tetap di `bookings`, tiap peserta (nama + paspor)
-- disimpan di `booking_participants`.
-- Idempotent: CREATE TABLE IF NOT EXISTS.
-- ============================================================

CREATE TABLE IF NOT EXISTS booking_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    passport_photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_bp_booking (booking_id),
    CONSTRAINT fk_bp_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
