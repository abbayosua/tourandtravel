-- seed-tour63-jiangnan.sql: lengkapi tour 63 (8D7N Jiangnan) agar brosur PDF setara contoh Balindo.
UPDATE tours SET
  duration_days = 8,
  duration_nights = 7,
  route_cities = 'SHANGHAI - HANGZHOU - WUXI - SUZHOU - WUZHEN - SHANGHAI',
  meeting_point = 'Bandara Internasional Soekarno-Hatta, Terminal 3 (3 jam sebelum keberangkatan)',
  flight_info = 'CGK - PVG (transit, full service, bagasi 25KG)
PVG - CGK (transit, full service, bagasi 25KG)',
  highlights = 'The Bund — skyline ikonik Shanghai di tepi Sungai Huangpu, tercantik di malam hari
Hangzhou West Lake — danau UNESCO dengan suasana tepi danau yang memesona
Longjing Tea Plantation — petik teh + pengalaman kostum Hanfu
Suzhou Gardens — Couple''s Retreat Garden (Ou Garden) warisan UNESCO
Wuzhen Water Town — kota air abadi: kanal, jembatan batu, dan Xizha Scenic Area
Kuliner khas Jiangnan — Dongpo Pork, Squirrel-shaped Mandarin Fish, Wuxi Boat Cuisine',
  includes = 'Tiket pesawat PP + bagasi 25KG & kabin 7KG
Akomodasi hotel 4-5* (twin/triple share) 7 malam sesuai itinerary
Transportasi bus pariwisata ber-AC & berlisensi
Makan sesuai program (7x breakfast, 6x lunch, 6x dinner)
Tiket masuk objek wisata sesuai itinerary (1st gate)
Tour leader + guide lokal berbahasa Mandarin
Air mineral 1 botol/hari/pax + travel insurance',
  excludes = 'Tipping guide, driver & tour leader: Rp 850.000/pax/tour (wajib)
Single supplement (jika sekamar sendiri)
Pengeluaran pribadi: laundry, mini bar, telepon, kelebihan bagasi
Optional tour & biaya di luar program
Asuransi perjalanan usia >69 tahun (surcharge)
Hal-hal yang tidak disebutkan di INCLUDE',
  important_notes = 'Harga dapat berubah sewaktu-waktu mengikuti kurs & fuel surcharge
Deposit Rp 3.000.000/pax saat pendaftaran, pelunasan H-21
Jadwal flight dapat berubah mengikuti kebijakan maskapai
Hotel dapat disubstitusi dengan hotel setaraf sesuai kondisi'
WHERE id = 63;

DELETE FROM itineraries WHERE tour_id = 63;
INSERT INTO itineraries (tour_id, day_number, title, description, meals, accommodation) VALUES
(63, 1, 'Day 1 — Jakarta - Shanghai', 'Berkumpul di bandara 3 jam sebelum keberangkatan. Penerbangan menuju Shanghai. Tiba di Shanghai, proses imigrasi, dijemput guide lokal, transfer hotel dan beristirahat.', 'No Meal', 'Hotel 4-5* (Shanghai)'),
(63, 2, 'Day 2 — Shanghai City Tour', 'City tour Shanghai: The Bund dengan skyline Sungai Huangpu, Nanjing Road Pedestrian Street untuk belanja, dan malam hari menikmati Shanghai Skyline dari tepi laut.', 'B/L/D', 'Hotel 4-5* (Shanghai)'),
(63, 3, 'Day 3 — Shanghai - Hangzhou', 'Menuju Hangzhou. Mengunjungi West Lake (UNESCO), Qinghefang Ancient Street, Southern Song Imperial Street, dan Zhu Bingren Bronze House.', 'B/L/D', 'Hotel 4-5* (Hangzhou)'),
(63, 4, 'Day 4 — Hangzhou', 'Kunjungan ke Longjing Tea Plantation: pengalaman memetik teh dan kostum Hanfu. Mencicipi Hangzhou Dongpo Pork, Longjing Tea Garden Chicken, dan masakan Hangzhou Hu Qing Yan.', 'B/L/D', 'Hotel 4-5* (Hangzhou)'),
(63, 5, 'Day 5 — Hangzhou - Wuxi', 'Menuju Wuxi. Mengunjungi Huishan Ancient Town, Lihu Park yang tenang di tepi danau, dan Zisha Pottery Museum (tembikar tanah liat ungu). Sajian Taihu Three Delicacies Banquet & Wuxi Boat Cuisine.', 'B/L/D', 'Hotel 4-5* (Wuxi)'),
(63, 6, 'Day 6 — Wuxi - Suzhou', 'Menuju Suzhou. Mengunjungi Couple''s Retreat Garden (Ou Garden, UNESCO), Renheng Cangjie & Guixiangmeng, serta Jiangnan Silk Workshop. Mencicipi Suzhou-style Squirrel-shaped Mandarin Fish.', 'B/L/D', 'Hotel 4-5* (Suzhou)'),
(63, 7, 'Day 7 — Suzhou - Wuzhen', 'Menuju Wuzhen Water Town: kanal, jembatan batu, dan rumah tradisional. Jelajah Xizha Scenic Area dengan suasana kota kanal yang otentik.', 'B/L/D', 'Hotel 4-5* (Wuzhen)'),
(63, 8, 'Day 8 — Wuzhen - Shanghai - Jakarta', 'Kembali ke Shanghai, last-minute shopping bila waktu memungkinkan, transfer bandara dan penerbangan pulang ke Jakarta. Tour selesai.', 'B', '-');

INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots, price_adult, price_child, price_single, note) VALUES
(63, '2026-10-14', '2026-10-21', 30, 8990000, 7990000, 2200000, 'Low Season'),
(63, '2026-11-04', '2026-11-11', 30, 8990000, 7990000, 2200000, 'Low Season'),
(63, '2026-12-23', '2026-12-30', 30, 10590000, 9590000, 2500000, 'Liburan Natal'),
(63, '2027-02-16', '2027-02-23', 30, 11290000, 10290000, 2800000, 'Imlek'),
(63, '2027-03-10', '2027-03-17', 30, 11890000, 10890000, 2800000, 'Lebaran')
ON DUPLICATE KEY UPDATE price_adult=VALUES(price_adult), price_child=VALUES(price_child), price_single=VALUES(price_single), note=VALUES(note);
