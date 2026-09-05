CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'info',
    title VARCHAR(200) NOT NULL,
    body TEXT,
    link VARCHAR(255) DEFAULT NULL,
    read_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_user (user_id, read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
