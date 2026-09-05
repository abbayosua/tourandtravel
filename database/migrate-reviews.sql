CREATE TABLE IF NOT EXISTS review_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    path VARCHAR(255) NOT NULL,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELIMITER $$
DROP PROCEDURE IF EXISTS reviews_add_reply $$
CREATE PROCEDURE reviews_add_reply()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='reviews' AND column_name='reply_text') THEN
        ALTER TABLE reviews ADD COLUMN reply_text TEXT DEFAULT NULL, ADD COLUMN reply_at DATETIME DEFAULT NULL;
    END IF;
END $$
DELIMITER ;
CALL reviews_add_reply();
DROP PROCEDURE IF EXISTS reviews_add_reply;
