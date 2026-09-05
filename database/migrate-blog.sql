CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt TEXT,
    body MEDIUMTEXT,
    cover_image VARCHAR(255) DEFAULT NULL,
    category VARCHAR(100) DEFAULT NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    published_at DATETIME DEFAULT NULL,
    content_language VARCHAR(5) NOT NULL DEFAULT 'id',
    title_en VARCHAR(255) DEFAULT NULL,
    excerpt_en TEXT,
    body_en MEDIUMTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_posts_status (status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO posts (title, slug, excerpt, body, category, status, published_at, title_en, excerpt_en, body_en) VALUES
('Tips Memilih Paket Tour Keluarga', 'tips-memilih-paket-tour-keluarga', 'Panduan singkat memilih paket tour yang ramah keluarga.', 'Beberapa hal penting sebelum memesan: durasi, agenda anak, hotel dekat pusat wisata, dan guide berbahasa Indonesia. Pastikan juga kebijakan pembatalan fleksibel.', 'Tips', 'published', NOW(),
 'Tips for Choosing a Family Tour Package', 'A quick guide to choosing a family-friendly tour package.', 'Key things before booking: duration, kids-friendly agenda, hotels near attractions, and Indonesian-speaking guides. Also check flexible cancellation policies.'),
('Destinasi Favorit Asia 2026', 'destinasi-favorit-asia-2026', 'Kota-kota Asia yang paling banyak dipesan tahun ini.', 'Tokyo, Seoul, dan Bangkok tetap favorit. Untuk pengalaman baru, coba Taiwan atau Vietnam dengan biaya lebih terjangkau.', 'Destinasi', 'published', NOW(),
 'Favorite Asian Destinations 2026', 'Asian cities most booked this year.', 'Tokyo, Seoul, and Bangkok remain favorites. For something new, try Taiwan or Vietnam at friendlier prices.'),
('Panduan Traveling Pertama ke Jepang', 'panduan-traveling-pertama-ke-jepang', 'Persiapan wajib untuk perjalanan pertama Anda ke Jepang.', 'Visa, JR Pass, eSIM, etika kereta, dan tips tukar uang. Semua yang perlu Anda tahu sebelum terbang.', 'Panduan', 'published', NOW(),
 'First-Timer Guide to Japan', 'Essential preparation for your first trip to Japan.', 'Visa, JR Pass, eSIM, train etiquette, and money exchange tips. Everything you need before you fly.');
