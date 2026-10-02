<?php
/**
 * Create tour_waitlist table for storing waitlist entries
 */

require_once __DIR__ . '/../includes/db.php';

try {
    db()->exec("
        CREATE TABLE IF NOT EXISTS tour_waitlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tour_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            status ENUM('notified', 'converted', 'expired') DEFAULT 'notified',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            notified_at DATETIME NULL,
            INDEX idx_tour_id (tour_id),
            INDEX idx_email (email),
            UNIQUE KEY unique_tour_email (tour_id, email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table tour_waitlist created successfully.\n";
} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
    exit(1);
}
