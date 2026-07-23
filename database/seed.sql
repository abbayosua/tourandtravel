-- Sample data untuk testing

-- Tours
INSERT INTO tours (title, slug, category, description, price, max_participants, cover_image, is_active) VALUES
('Bali Paradise 5D4N', 'bali-paradise-5d4n', 'Domestik', 'Nikmati keindahan pulau dewata dengan paket wisata lengkap mulai dari Kuta, Ubud, hingga Tanah Lot. Termasuk akomodasi hotel bintang 4, transportasi, dan tour guide profesional.', 2500000, 30, NULL, 1),
('Yogyakarta Cultural Tour 4D3N', 'yogyakarta-cultural-tour-4d3n', 'Domestik', 'Jelajahi kekayaan budaya Yogyakarta: Candi Borobudur, Candi Prambanan, Keraton, dan Malioboro. Dengan guide lokal yang berpengalaman.', 1800000, 25, NULL, 1),
('Raja Ampat Diving 7D6N', 'raja-ampat-diving-7d6n', 'Domestik', 'Surga bawah laut Raja Ampat dengan spot diving terbaik dunia. Paket lengkap: akomodasi resort, peralatan diving, sertifikat.', 7500000, 15, NULL, 1),
('Lombok Mandalika 3D2N', 'lombok-mandalika-3d2n', 'Domestik', 'Paket wisata Lombok: Pantai Kuta Mandalika, Gunung Rinjani, Gili Trawangan. Nikmati pesona alam Lombok yang memukau.', 2200000, 20, NULL, 1),
('Singapore - Malaysia 6D5N', 'singapore-malaysia-6d5n', 'Internasional', 'Jelajahi dua negara dalam satu perjalanan. Universal Studio Singapore, Petronas Tower KL, Genting Highlands. Hotel bintang 4.', 5500000, 30, NULL, 1),
('Japan Tokyo-Osaka 8D7N', 'japan-tokyo-osaka-8d7n', 'Internasional', 'Pengalaman tak terlupakan di Jepang: Shibuya, Sensoji Temple, Universal Studio Japan, Osaka Castle. Termasuk JR Pass 7 hari.', 18500000, 20, NULL, 1);

-- Tour Dates
INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots) VALUES
(1, '2026-08-10', '2026-08-14', 20),
(1, '2026-09-05', '2026-09-09', 25),
(1, '2026-10-12', '2026-10-16', 30),
(2, '2026-08-15', '2026-08-18', 15),
(2, '2026-09-20', '2026-09-23', 20),
(3, '2026-10-05', '2026-10-11', 10),
(3, '2026-11-10', '2026-11-16', 12),
(4, '2026-08-20', '2026-08-22', 15),
(5, '2026-09-15', '2026-09-20', 25),
(6, '2026-11-05', '2026-11-12', 15);

-- Itineraries
INSERT INTO itineraries (tour_id, day_number, title, description, meals, accommodation) VALUES
-- Bali Paradise (tour_id=1)
(1, 1, 'Kedatangan & Kuta Beach', 'Tiba di Bandara Ngurah Rai, check-in hotel, lalu free time di Kuta Beach. Sunset dinner di Jimbaran.', 'Makan malam', 'Hotel Kuta'),
(1, 2, 'Ubud & Monkey Forest', 'Sarapan, lanjut ke Ubud: Monkey Forest, Ubud Market, Tegalalang Rice Terrace. Makan malam bebas.', 'Sarapan, makan siang', 'Hotel Kuta'),
(1, 3, 'Tanah Lot & GWK', 'Sarapan, tour ke Tanah Lot, GWK Cultural Park, dan Pantai Pandawa. Makan malam di restoran lokal.', 'Sarapan, makan siang, makan malam', 'Hotel Kuta'),
(1, 4, 'Nusa Penida Tour', 'Full day Nusa Penida: Kelingking Beach, Angel Billabong, Broken Beach. Kembali ke hotel.', 'Sarapan, makan siang', 'Hotel Kuta'),
(1, 5, 'Check Out & Transfer', 'Sarapan, check out hotel, free time hingga transfer ke bandara.', 'Sarapan', '-'),

-- Yogyakarta (tour_id=2)
(2, 1, 'Kedatangan & Malioboro', 'Tiba di Yogyakarta, check-in hotel, jalan-jalan Malioboro, malam dinner angkringan.', 'Makan malam', 'Hotel Malioboro'),
(2, 2, 'Borobudur & Prambanan', 'Sunrise di Candi Borobudur, lanjut ke Candi Prambanan dan Candi Sewu.', 'Sarapan, makan siang, makan malam', 'Hotel Malioboro'),
(2, 3, 'Keraton & Kotagede', 'Tour Keraton Yogyakarta, Tamansari, dan wisata perak Kotagede.', 'Sarapan, makan siang', 'Hotel Malioboro'),
(2, 4, 'Check Out & Transfer', 'Sarapan, check out, beli oleh-oleh, transfer ke bandara.', 'Sarapan', '-'),

-- Raja Ampat (tour_id=3)
(3, 1, 'Jakarta - Sorong', 'Penerbangan Jakarta-Sorong, lanjut speedboat ke resort. Check-in dan briefing diving.', 'Makan malam', 'Resort Raja Ampat'),
(3, 2, 'Diving Day 1', 'Diving 3 spot: Cape Kri, Sardine Reef, Manta Sandy. Night dive optional.', 'Sarapan, makan siang, makan malam', 'Resort Raja Ampat'),
(3, 3, 'Diving Day 2', 'Diving 3 spot: Blue Magic, Chicken Reef, Mike''s Point. Island exploration.', 'Sarapan, makan siang, makan malam', 'Resort Raja Ampat'),
(3, 4, 'Diving Day 3', 'Diving 3 spot: Melissa Garden, Arborek Jetty, Sauwandarek. Village visit.', 'Sarapan, makan siang, makan malam', 'Resort Raja Ampat'),
(3, 5, 'Diving Day 4', 'Diving 2 spot, free time snorkeling di house reef.', 'Sarapan, makan siang, makan malam', 'Resort Raja Ampat'),
(3, 6, 'Island Tour', 'Full day island hopping: Piaynemo, Wayag Lagoon. Photo spots.', 'Sarapan, makan siang, makan malam', 'Resort Raja Ampat'),
(3, 7, 'Check Out & Transfer', 'Sarapan, check out, speedboat ke Sorong, penerbangan ke Jakarta.', 'Sarapan', '-'),

-- Lombok (tour_id=4)
(4, 1, 'Kedatangan & Mandalika', 'Tiba di Lombok, check-in hotel, explore Pantai Kuta Mandalika dan Bukit Merese.', 'Makan malam', 'Hotel Mandalika'),
(4, 2, 'Gili Trawangan', 'Speedboat ke Gili Trawangan, snorkeling, cycling keliling pulau.', 'Sarapan, makan siang, makan malam', 'Hotel Mandalika'),
(4, 3, 'Senggigi & Transfer', 'Sarapan, tour ke Senggigi dan Pusuk Monkey Forest, transfer ke bandara.', 'Sarapan, makan siang', '-'),

-- Singapore-Malaysia (tour_id=5)
(5, 1, 'Jakarta - Singapore', 'Penerbangan ke Singapore, check-in hotel, free time di Marina Bay.', 'Makan malam', 'Hotel Singapore'),
(5, 2, 'Singapore Full Day', 'Universal Studio Sentosa, Gardens by the Bay,金沙 SkyPark.', 'Sarapan, makan siang', 'Hotel Singapore'),
(5, 3, 'Singapore - KL', 'Pagi check out, naik bus ke KL, check-in hotel, Petronas Tower, KLCC.', 'Sarapan, makan malam', 'Hotel KL'),
(5, 4, 'KL Full Day', 'Batu Caves, Genting Highlands, Chinatown, Bukit Bintang.', 'Sarapan, makan siang, makan malam', 'Hotel KL'),
(5, 5, 'KL - Singapore', 'Pagi check out, bus kembali ke Singapore, free time di Orchard Road.', 'Sarapan', 'Hotel Singapore'),
(5, 6, 'Check Out & Fly', 'Sarapan, check out, free time hingga transfer ke bandara.', 'Sarapan', '-'),

-- Japan (tour_id=6)
(6, 1, 'Jakarta - Tokyo', 'Penerbangan ke Tokyo Narita, check-in hotel, free time Shinjuku.', 'Makan malam', 'Hotel Shinjuku'),
(6, 2, 'Tokyo Explore', 'Shibuya Crossing, Harajuku, Sensoji Temple, Akihabara, Tokyo Tower.', 'Sarapan, makan siang', 'Hotel Shinjuku'),
(6, 3, 'Tokyo - Osaka (Shinkansen)', 'Naik Shinkansen ke Osaka, check-in hotel, Dotonbori, Osaka Castle.', 'Sarapan, makan siang, makan malam', 'Hotel Osaka'),
(6, 4, 'Universal Studio Japan', 'Full day Universal Studio Japan (Super Nintendo World).', 'Sarapan', 'Hotel Osaka'),
(6, 5, 'Osaka - Kyoto', 'Day trip ke Kyoto: Fushimi Inari, Kinkakuji, Arashiyama Bamboo Grove.', 'Sarapan, makan siang', 'Hotel Osaka'),
(6, 6, 'Osaka - Tokyo (Shinkansen)', 'Kembali ke Tokyo, free time shopping di Ginza dan Roppongi.', 'Sarapan, makan malam', 'Hotel Shinjuku'),
(6, 7, 'Tokyo Free Day', 'Free time (disarankan: DisneySea atau Nikko day trip).', 'Sarapan', 'Hotel Shinjuku'),
(6, 8, 'Check Out & Fly', 'Sarapan, check out, transfer ke bandara Narita.', 'Sarapan', '-');

-- Sample Bookings
INSERT INTO bookings (tour_id, tour_date_id, name, email, phone, participants, total_price, status, notes) VALUES
(1, 1, 'Budi Santoso', 'budi@email.com', '08123456789', 2, 5000000, 'confirmed', 'Request kamar double bed'),
(2, 4, 'Siti Rahayu', 'siti@email.com', '08765432100', 1, 1800000, 'pending', '-'),
(3, 6, 'Ahmad Fauzi', 'ahmad@email.com', '08112233445', 3, 22500000, 'confirmed', '-');
