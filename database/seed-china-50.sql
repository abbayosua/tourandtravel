-- Auto-generated: 50 China tours from Klook scraping
SET NAMES utf8mb4;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Beijing Qushui Lanting | Cabang Sihui", "beijing-qushui-lanting-cabang-sihui", "Beijing", "Beijing Qushui Lanting | Cabang Sihui

Tags: Pembatalan gratis; Konfirmasi instan", 2300327, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/x7frsonbnhxl3ktcxsof.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "beijing-qushui-lanting-cabang-sihui") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "beijing-qushui-lanting-cabang-sihui" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Beijing", "Day 1 di Beijing: Beijing Qushui Lanting | Cabang Sihui", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Beijing", "Day 2: Explore Beijing. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/x7frsonbnhxl3ktcxsof.jpg", "Beijing Qushui Lanting | Cabang Sihui", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/x7frsonbnhxl3ktcxsof.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 10, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Sehari Mutianyu Great Wall+Summer Palace/Yuanmingyuan Garden", "tur-sehari-mutianyu-great-wallsummer-palaceyuanmingyuan-garden", "Beijing", "Tur Sehari Mutianyu Great Wall+Summer Palace/Yuanmingyuan Garden

Tags: Pesan untuk besok; Private tour; Pembatalan gratis; Konfirmasi instan", 343702, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/do1gdf547tuw9kzv5mza.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-sehari-mutianyu-great-wallsummer-palaceyuanmingyuan-garden") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-sehari-mutianyu-great-wallsummer-palaceyuanmingyuan-garden" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Beijing", "Day 1 di Beijing: Tur Sehari Mutianyu Great Wall+Summer Palace/Yuanmingyuan Garden", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Beijing", "Day 2: Explore Beijing. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/do1gdf547tuw9kzv5mza.jpg", "Tur Sehari Mutianyu Great Wall+Summer Palace/Yuanmingyuan Garden", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/do1gdf547tuw9kzv5mza.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 20, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Sehari Beijing Mutianyu Great Wall & Forbidden City", "tur-sehari-beijing-mutianyu-great-wall-forbidden-city", "Beijing", "Tur Sehari Beijing Mutianyu Great Wall & Forbidden City

Tags: Pesan untuk besok; Private tour; Pembatalan gratis; Konfirmasi instan", 665421, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/sinapdw5wj2bogq3ykec.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-sehari-beijing-mutianyu-great-wall-forbidden-city") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-sehari-beijing-mutianyu-great-wall-forbidden-city" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Beijing", "Day 1 di Beijing: Tur Sehari Beijing Mutianyu Great Wall & Forbidden City", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Beijing", "Day 2: Explore Beijing. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/sinapdw5wj2bogq3ykec.jpg", "Tur Sehari Beijing Mutianyu Great Wall & Forbidden City", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/sinapdw5wj2bogq3ykec.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 18, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur 2 Hari Forbidden City & Great Wall Beijing dengan Sorotan Utama", "tur-2-hari-forbidden-city-great-wall-beijing-dengan-sorotan-utama", "Beijing", "Tur 2 Hari Forbidden City & Great Wall Beijing dengan Sorotan Utama

Tags: Pesan untuk besok; Pembatalan gratis; Konfirmasi instan", 2963046, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/vdf72k4ptgchakx6wucd.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-2-hari-forbidden-city-great-wall-beijing-dengan-sorotan-utama") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-2-hari-forbidden-city-great-wall-beijing-dengan-sorotan-utama" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Beijing", "Day 1 di Beijing: Tur 2 Hari Forbidden City & Great Wall Beijing dengan Sorotan Utama", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Beijing", "Day 2: Explore Beijing. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/vdf72k4ptgchakx6wucd.jpg", "Tur 2 Hari Forbidden City & Great Wall Beijing dengan Sorotan Utama", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/vdf72k4ptgchakx6wucd.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 20, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Pribadi Tembok Besar Mutianyu + Istana Musim Panas/Tembok Besar Huanghuacheng di Atas Air/Makam Ming", "tur-pribadi-tembok-besar-mutianyu-istana-musim-panastembok-besar-huanghuacheng-di-atas-airmakam-ming", "Beijing", "Tur Pribadi Tembok Besar Mutianyu + Istana Musim Panas/Tembok Besar Huanghuacheng di Atas Air/Makam Ming

Tags: Pesan untuk besok; Private tour; Grup pribadi; Pembatalan gratis; Konfirmasi instan", 2148369, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/in7mhc4wtz5q9kn4qhqa.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-pribadi-tembok-besar-mutianyu-istana-musim-panastembok-besar-huanghuacheng-di-atas-airmakam-ming") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-pribadi-tembok-besar-mutianyu-istana-musim-panastembok-besar-huanghuacheng-di-atas-airmakam-ming" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Beijing", "Day 1 di Beijing: Tur Pribadi Tembok Besar Mutianyu + Istana Musim Panas/Tembok Besar Huanghuacheng di Atas Air/Makam Ming", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Beijing", "Day 2: Explore Beijing. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/in7mhc4wtz5q9kn4qhqa.jpg", "Tur Pribadi Tembok Besar Mutianyu + Istana Musim Panas/Tembok Besar Huanghuacheng di Atas Air/Makam Ming", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/in7mhc4wtz5q9kn4qhqa.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 11, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Qushui Lanting | Toko Hangzhou Chengdong", "qushui-lanting-toko-hangzhou-chengdong", "Shanghai", "Qushui Lanting | Toko Hangzhou Chengdong

Tags: Pembatalan gratis; Konfirmasi instan", 2588522, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/tplh8xfcxpxdsaykl6eo.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "qushui-lanting-toko-hangzhou-chengdong") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "qushui-lanting-toko-hangzhou-chengdong" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Shanghai", "Day 1 di Shanghai: Qushui Lanting | Toko Hangzhou Chengdong", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Shanghai", "Day 2: Explore Shanghai. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/tplh8xfcxpxdsaykl6eo.jpg", "Qushui Lanting | Toko Hangzhou Chengdong", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/tplh8xfcxpxdsaykl6eo.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 17, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Mewah 6 Hari Shanghai + Nanjing + Wuxi + Suzhou + Hangzhou + Wuzhen (Pertunjukan Romansa Abadi Songcheng + Hotel Mewah Bintang 5 Sepanjang Perjalanan)", "tur-mewah-6-hari-shanghai-nanjing-wuxi-suzhou-hangzhou-wuzhen-pertunjukan-romansa-abadi-songcheng-hotel-mewah-bintang-5-sepanjang-perjalanan", "Shanghai", "Tur Mewah 6 Hari Shanghai + Nanjing + Wuxi + Suzhou + Hangzhou + Wuzhen (Pertunjukan Romansa Abadi Songcheng + Hotel Mewah Bintang 5 Sepanjang Perjalanan)

Tags: ", 9522304, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/uvawonxyhakfmmpjb2cs.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-mewah-6-hari-shanghai-nanjing-wuxi-suzhou-hangzhou-wuzhen-pertunjukan-romansa-abadi-songcheng-hotel-mewah-bintang-5-sepanjang-perjalanan") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-mewah-6-hari-shanghai-nanjing-wuxi-suzhou-hangzhou-wuzhen-pertunjukan-romansa-abadi-songcheng-hotel-mewah-bintang-5-sepanjang-perjalanan" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Shanghai", "Day 1 di Shanghai: Tur Mewah 6 Hari Shanghai + Nanjing + Wuxi + Suzhou + Hangzhou + Wuzhen (Pertunjukan Romansa Abadi Songcheng + Hotel Mewah Bintang 5 Sepanjang Perjalanan)", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Shanghai", "Day 2: Explore Shanghai. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/uvawonxyhakfmmpjb2cs.jpg", "Tur Mewah 6 Hari Shanghai + Nanjing + Wuxi + Suzhou + Hangzhou + Wuzhen (Pertunjukan Romansa Abadi Songcheng + Hotel Mewah Bintang 5 Sepanjang Perjalanan)", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/uvawonxyhakfmmpjb2cs.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 7, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Sehari Hangzhou West Lake & Lingyin Temple dengan Tiket", "tur-sehari-hangzhou-west-lake-lingyin-temple-dengan-tiket", "Shanghai", "Tur Sehari Hangzhou West Lake & Lingyin Temple dengan Tiket

Tags: Pesan untuk besok; Pembatalan gratis; Konfirmasi instan", 570785, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/yhg8kpzesfmzi1qn0ucj.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-sehari-hangzhou-west-lake-lingyin-temple-dengan-tiket") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-sehari-hangzhou-west-lake-lingyin-temple-dengan-tiket" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Shanghai", "Day 1 di Shanghai: Tur Sehari Hangzhou West Lake & Lingyin Temple dengan Tiket", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Shanghai", "Day 2: Explore Shanghai. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/yhg8kpzesfmzi1qn0ucj.jpg", "Tur Sehari Hangzhou West Lake & Lingyin Temple dengan Tiket", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/yhg8kpzesfmzi1qn0ucj.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 5, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur kelompok Nanjing+Wuxi+Suzhou+Wuzhen+Hangzhou 4/6 hari", "tur-kelompok-nanjingwuxisuzhouwuzhenhangzhou-46-hari", "Shanghai", "Tur kelompok Nanjing+Wuxi+Suzhou+Wuzhen+Hangzhou 4/6 hari

Tags: Pesan untuk besok; Pembatalan gratis; Konfirmasi instan", 4675374, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/qv9iqhqnd6d6nsod9ahl.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-kelompok-nanjingwuxisuzhouwuzhenhangzhou-46-hari") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-kelompok-nanjingwuxisuzhouwuzhenhangzhou-46-hari" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Shanghai", "Day 1 di Shanghai: Tur kelompok Nanjing+Wuxi+Suzhou+Wuzhen+Hangzhou 4/6 hari", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Shanghai", "Day 2: Explore Shanghai. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/qv9iqhqnd6d6nsod9ahl.jpg", "Tur kelompok Nanjing+Wuxi+Suzhou+Wuzhen+Hangzhou 4/6 hari", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/qv9iqhqnd6d6nsod9ahl.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 13, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Sehari Penuh Kelompok Kecil Memetik Teh di Lahan Basah Xixi Hangzhou + Desa Longjing (Pilihan Setengah Hari Tersedia)", "tur-sehari-penuh-kelompok-kecil-memetik-teh-di-lahan-basah-xixi-hangzhou-desa-longjing-pilihan-setengah-hari-tersedia", "Shanghai", "Tur Sehari Penuh Kelompok Kecil Memetik Teh di Lahan Basah Xixi Hangzhou + Desa Longjing (Pilihan Setengah Hari Tersedia)

Tags: Pesan untuk besok; Pembatalan gratis; Konfirmasi instan", 728480, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/xigpk69bncw7ueuibcvh.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-sehari-penuh-kelompok-kecil-memetik-teh-di-lahan-basah-xixi-hangzhou-desa-longjing-pilihan-setengah-hari-tersedia") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-sehari-penuh-kelompok-kecil-memetik-teh-di-lahan-basah-xixi-hangzhou-desa-longjing-pilihan-setengah-hari-tersedia" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Shanghai", "Day 1 di Shanghai: Tur Sehari Penuh Kelompok Kecil Memetik Teh di Lahan Basah Xixi Hangzhou + Desa Longjing (Pilihan Setengah Hari Tersedia)", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Shanghai", "Day 2: Explore Shanghai. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/xigpk69bncw7ueuibcvh.jpg", "Tur Sehari Penuh Kelompok Kecil Memetik Teh di Lahan Basah Xixi Hangzhou + Desa Longjing (Pilihan Setengah Hari Tersedia)", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/xigpk69bncw7ueuibcvh.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 14, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Klook Outbound Custom Travel (Turkey)", "klook-outbound-custom-travel-turkey", "Guangzhou", "Klook Outbound Custom Travel (Turkey)

Tags: ", 37986827, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/eprsyy20b85ruxvvpxuv.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "klook-outbound-custom-travel-turkey") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "klook-outbound-custom-travel-turkey" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Guangzhou", "Day 1 di Guangzhou: Klook Outbound Custom Travel (Turkey)", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Guangzhou", "Day 2: Explore Guangzhou. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/eprsyy20b85ruxvvpxuv.jpg", "Klook Outbound Custom Travel (Turkey)", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/eprsyy20b85ruxvvpxuv.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 12, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Eksplorasi Sehari Penuh Shanghai Yu Garden & Zhujiajiao Water Town", "eksplorasi-sehari-penuh-shanghai-yu-garden-zhujiajiao-water-town", "Guangzhou", "Eksplorasi Sehari Penuh Shanghai Yu Garden & Zhujiajiao Water Town

Tags: Pesan untuk besok; Pembatalan gratis; Konfirmasi instan", 0, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/dbsvq5fwfrbzbrh43g0d.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "eksplorasi-sehari-penuh-shanghai-yu-garden-zhujiajiao-water-town") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "eksplorasi-sehari-penuh-shanghai-yu-garden-zhujiajiao-water-town" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Guangzhou", "Day 1 di Guangzhou: Eksplorasi Sehari Penuh Shanghai Yu Garden & Zhujiajiao Water Town", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Guangzhou", "Day 2: Explore Guangzhou. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/dbsvq5fwfrbzbrh43g0d.jpg", "Eksplorasi Sehari Penuh Shanghai Yu Garden & Zhujiajiao Water Town", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/dbsvq5fwfrbzbrh43g0d.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 7, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Pelayaran Spectrum of the Seas ke Korea Selatan dari Shanghai oleh Royal Caribbean International", "pelayaran-spectrum-of-the-seas-ke-korea-selatan-dari-shanghai-oleh-royal-caribbean-international", "Guangzhou", "Pelayaran Spectrum of the Seas ke Korea Selatan dari Shanghai oleh Royal Caribbean International

Tags: Konfirmasi instan", 0, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/zi3ya1vblywj2erlb8os.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "pelayaran-spectrum-of-the-seas-ke-korea-selatan-dari-shanghai-oleh-royal-caribbean-international") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "pelayaran-spectrum-of-the-seas-ke-korea-selatan-dari-shanghai-oleh-royal-caribbean-international" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Guangzhou", "Day 1 di Guangzhou: Pelayaran Spectrum of the Seas ke Korea Selatan dari Shanghai oleh Royal Caribbean International", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Guangzhou", "Day 2: Explore Guangzhou. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/zi3ya1vblywj2erlb8os.jpg", "Pelayaran Spectrum of the Seas ke Korea Selatan dari Shanghai oleh Royal Caribbean International", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/zi3ya1vblywj2erlb8os.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 14, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Malam Sungai Huangpu Shanghai (termasuk makan malam kepiting berbulu Michelin)", "tur-malam-sungai-huangpu-shanghai-termasuk-makan-malam-kepiting-berbulu-michelin", "Guangzhou", "Tur Malam Sungai Huangpu Shanghai (termasuk makan malam kepiting berbulu Michelin)

Tags: Pesan untuk besok; Pembatalan gratis", 3248753, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/yy7cyct3vlxoygloptxq.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-malam-sungai-huangpu-shanghai-termasuk-makan-malam-kepiting-berbulu-michelin") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-malam-sungai-huangpu-shanghai-termasuk-makan-malam-kepiting-berbulu-michelin" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Guangzhou", "Day 1 di Guangzhou: Tur Malam Sungai Huangpu Shanghai (termasuk makan malam kepiting berbulu Michelin)", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Guangzhou", "Day 2: Explore Guangzhou. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/yy7cyct3vlxoygloptxq.jpg", "Tur Malam Sungai Huangpu Shanghai (termasuk makan malam kepiting berbulu Michelin)", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/yy7cyct3vlxoygloptxq.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 13, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Kota Kuno Setengah Hari Shanghai Zhujiajiao dengan Transfer", "tur-kota-kuno-setengah-hari-shanghai-zhujiajiao-dengan-transfer", "Guangzhou", "Tur Kota Kuno Setengah Hari Shanghai Zhujiajiao dengan Transfer

Tags: Pesan untuk besok; Private tour; Pembatalan gratis; Konfirmasi instan", 4806399, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/bkgt6bcjiwfs0wdviujm.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-kota-kuno-setengah-hari-shanghai-zhujiajiao-dengan-transfer") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-kota-kuno-setengah-hari-shanghai-zhujiajiao-dengan-transfer" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Guangzhou", "Day 1 di Guangzhou: Tur Kota Kuno Setengah Hari Shanghai Zhujiajiao dengan Transfer", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Guangzhou", "Day 2: Explore Guangzhou. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/bkgt6bcjiwfs0wdviujm.jpg", "Tur Kota Kuno Setengah Hari Shanghai Zhujiajiao dengan Transfer", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/bkgt6bcjiwfs0wdviujm.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 8, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Klook yang Disesuaikan di Xi\'an, Shaanxi (Terracotta Army/Pagoda Angsa Liar/Istana Huaqing/Gunung Hua/Air Terjun Hukou)", "tur-klook-yang-disesuaikan-di-xian-shaanxi-terracotta-armypagoda-angsa-liaristana-huaqinggunung-huaair-terjun-hukou", "Shenzhen", "Tur Klook yang Disesuaikan di Xi\'an, Shaanxi (Terracotta Army/Pagoda Angsa Liar/Istana Huaqing/Gunung Hua/Air Terjun Hukou)

Tags: Private tour; Grup pribadi; Pembatalan gratis", 0, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/xwqvmelu4tjsfgkmfeh0.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-klook-yang-disesuaikan-di-xian-shaanxi-terracotta-armypagoda-angsa-liaristana-huaqinggunung-huaair-terjun-hukou") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-klook-yang-disesuaikan-di-xian-shaanxi-terracotta-armypagoda-angsa-liaristana-huaqinggunung-huaair-terjun-hukou" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Shenzhen", "Day 1 di Shenzhen: Tur Klook yang Disesuaikan di Xi\'an, Shaanxi (Terracotta Army/Pagoda Angsa Liar/Istana Huaqing/Gunung Hua/Air Terjun Hukou)", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Shenzhen", "Day 2: Explore Shenzhen. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/xwqvmelu4tjsfgkmfeh0.jpg", "Tur Klook yang Disesuaikan di Xi\'an, Shaanxi (Terracotta Army/Pagoda Angsa Liar/Istana Huaqing/Gunung Hua/Air Terjun Hukou)", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/xwqvmelu4tjsfgkmfeh0.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 18, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Setengah Hari Pasukan Terakota Xi\'an dengan Makan Siang Prasmanan", "tur-setengah-hari-pasukan-terakota-xian-dengan-makan-siang-prasmanan", "Shenzhen", "Tur Setengah Hari Pasukan Terakota Xi\'an dengan Makan Siang Prasmanan

Tags: Pesan untuk besok; Pembatalan gratis; Konfirmasi instan", 871137, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/xdyyyzzginrhpzxhnrl1.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-setengah-hari-pasukan-terakota-xian-dengan-makan-siang-prasmanan") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-setengah-hari-pasukan-terakota-xian-dengan-makan-siang-prasmanan" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Shenzhen", "Day 1 di Shenzhen: Tur Setengah Hari Pasukan Terakota Xi\'an dengan Makan Siang Prasmanan", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Shenzhen", "Day 2: Explore Shenzhen. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/xdyyyzzginrhpzxhnrl1.jpg", "Tur Setengah Hari Pasukan Terakota Xi\'an dengan Makan Siang Prasmanan", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/xdyyyzzginrhpzxhnrl1.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 12, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Grup Kecil Setengah Hari Xi\'an Terracotta Army", "tur-grup-kecil-setengah-hari-xian-terracotta-army", "Shenzhen", "Tur Grup Kecil Setengah Hari Xi\'an Terracotta Army

Tags: Pesan untuk besok; Private tour; Pembatalan gratis; Konfirmasi instan", 748549, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/jsgatma0okln9qe3btao.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-grup-kecil-setengah-hari-xian-terracotta-army") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-grup-kecil-setengah-hari-xian-terracotta-army" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Shenzhen", "Day 1 di Shenzhen: Tur Grup Kecil Setengah Hari Xi\'an Terracotta Army", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Shenzhen", "Day 2: Explore Shenzhen. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/jsgatma0okln9qe3btao.jpg", "Tur Grup Kecil Setengah Hari Xi\'an Terracotta Army", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/jsgatma0okln9qe3btao.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 18, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur 5 Hari Esensial Prajurit Terakota Xi\'an + Mausoleum Kaisar Kuning + Air Terjun Hukou", "tur-5-hari-esensial-prajurit-terakota-xian-mausoleum-kaisar-kuning-air-terjun-hukou", "Shenzhen", "Tur 5 Hari Esensial Prajurit Terakota Xi\'an + Mausoleum Kaisar Kuning + Air Terjun Hukou

Tags: Pembatalan gratis; Konfirmasi instan", 5626683, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/klnknwx9lgjnvn39reoo.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-5-hari-esensial-prajurit-terakota-xian-mausoleum-kaisar-kuning-air-terjun-hukou") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-5-hari-esensial-prajurit-terakota-xian-mausoleum-kaisar-kuning-air-terjun-hukou" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Shenzhen", "Day 1 di Shenzhen: Tur 5 Hari Esensial Prajurit Terakota Xi\'an + Mausoleum Kaisar Kuning + Air Terjun Hukou", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Shenzhen", "Day 2: Explore Shenzhen. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/klnknwx9lgjnvn39reoo.jpg", "Tur 5 Hari Esensial Prajurit Terakota Xi\'an + Mausoleum Kaisar Kuning + Air Terjun Hukou", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/klnknwx9lgjnvn39reoo.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 14, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Perjamuan Istana Daming Xi\'an", "perjamuan-istana-daming-xian", "Shenzhen", "Perjamuan Istana Daming Xi\'an

Tags: Pembatalan gratis", 407561, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/ec7wdzcaflhf82sxfsk9.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "perjamuan-istana-daming-xian") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "perjamuan-istana-daming-xian" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Shenzhen", "Day 1 di Shenzhen: Perjamuan Istana Daming Xi\'an", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Shenzhen", "Day 2: Explore Shenzhen. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/ec7wdzcaflhf82sxfsk9.jpg", "Perjamuan Istana Daming Xi\'an", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/ec7wdzcaflhf82sxfsk9.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 16, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Pilihan KLOOK|Tur 3 Hari Murni Jiuzhaigou Huanglong dengan Kereta Cepat Berkualitas Tinggi (Berangkat dari Chengdu・Grup Berbahasa Mandarin)", "pilihan-klooktur-3-hari-murni-jiuzhaigou-huanglong-dengan-kereta-cepat-berkualitas-tinggi-berangkat-dari-chengdugrup-berbahasa-mandarin", "Chengdu", "Pilihan KLOOK|Tur 3 Hari Murni Jiuzhaigou Huanglong dengan Kereta Cepat Berkualitas Tinggi (Berangkat dari Chengdu・Grup Berbahasa Mandarin)

Tags: Pembatalan gratis", 0, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/aepg7fmovnrthmhvuxiq.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "pilihan-klooktur-3-hari-murni-jiuzhaigou-huanglong-dengan-kereta-cepat-berkualitas-tinggi-berangkat-dari-chengdugrup-berbahasa-mandarin") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "pilihan-klooktur-3-hari-murni-jiuzhaigou-huanglong-dengan-kereta-cepat-berkualitas-tinggi-berangkat-dari-chengdugrup-berbahasa-mandarin" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Chengdu", "Day 1 di Chengdu: Pilihan KLOOK|Tur 3 Hari Murni Jiuzhaigou Huanglong dengan Kereta Cepat Berkualitas Tinggi (Berangkat dari Chengdu・Grup Berbahasa Mandarin)", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Chengdu", "Day 2: Explore Chengdu. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/aepg7fmovnrthmhvuxiq.jpg", "Pilihan KLOOK|Tur 3 Hari Murni Jiuzhaigou Huanglong dengan Kereta Cepat Berkualitas Tinggi (Berangkat dari Chengdu・Grup Berbahasa Mandarin)", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/aepg7fmovnrthmhvuxiq.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 12, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur 2 Hari Butik Gunung Siguniang Lembah Bipenggou Sichuan ", "tur-2-hari-butik-gunung-siguniang-lembah-bipenggou-sichuan", "Chengdu", "Tur 2 Hari Butik Gunung Siguniang Lembah Bipenggou Sichuan 

Tags: Pesan untuk besok; Private tour; Pembatalan gratis; Konfirmasi instan", 1828733, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/oqbikwuos6amke3o5n9t.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-2-hari-butik-gunung-siguniang-lembah-bipenggou-sichuan") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-2-hari-butik-gunung-siguniang-lembah-bipenggou-sichuan" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Chengdu", "Day 1 di Chengdu: Tur 2 Hari Butik Gunung Siguniang Lembah Bipenggou Sichuan ", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Chengdu", "Day 2: Explore Chengdu. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/oqbikwuos6amke3o5n9t.jpg", "Tur 2 Hari Butik Gunung Siguniang Lembah Bipenggou Sichuan ", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/oqbikwuos6amke3o5n9t.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 20, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Perjamuan Istana Shu · Pertunjukan Makan Malam Imersif Budaya Shu | Jalan Chunxi Chengdu", "perjamuan-istana-shu-pertunjukan-makan-malam-imersif-budaya-shu-jalan-chunxi-chengdu", "Chengdu", "Perjamuan Istana Shu · Pertunjukan Makan Malam Imersif Budaya Shu | Jalan Chunxi Chengdu

Tags: Pesan untuk besok; Hingga 3 jam; Pembatalan gratis", 1042745, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/sufsrngzzzfbqvakfrjj.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "perjamuan-istana-shu-pertunjukan-makan-malam-imersif-budaya-shu-jalan-chunxi-chengdu") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "perjamuan-istana-shu-pertunjukan-makan-malam-imersif-budaya-shu-jalan-chunxi-chengdu" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Chengdu", "Day 1 di Chengdu: Perjamuan Istana Shu · Pertunjukan Makan Malam Imersif Budaya Shu | Jalan Chunxi Chengdu", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Chengdu", "Day 2: Explore Chengdu. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/sufsrngzzzfbqvakfrjj.jpg", "Perjamuan Istana Shu · Pertunjukan Makan Malam Imersif Budaya Shu | Jalan Chunxi Chengdu", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/sufsrngzzzfbqvakfrjj.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 14, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Berpemandu Sehari Penuh Panda Base & Leshan Giant Buddha", "tur-berpemandu-sehari-penuh-panda-base-leshan-giant-buddha", "Chengdu", "Tur Berpemandu Sehari Penuh Panda Base & Leshan Giant Buddha

Tags: Pesan untuk besok; Pembatalan gratis; Konfirmasi instan", 528053, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/lo6r6jtwz47iphv3y8yu.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-berpemandu-sehari-penuh-panda-base-leshan-giant-buddha") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-berpemandu-sehari-penuh-panda-base-leshan-giant-buddha" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Chengdu", "Day 1 di Chengdu: Tur Berpemandu Sehari Penuh Panda Base & Leshan Giant Buddha", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Chengdu", "Day 2: Explore Chengdu. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/lo6r6jtwz47iphv3y8yu.jpg", "Tur Berpemandu Sehari Penuh Panda Base & Leshan Giant Buddha", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/lo6r6jtwz47iphv3y8yu.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 13, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "【Pilihan Klook】Jiuzhaigou Premium + Pangkalan Panda/Huanglong | Tersedia Berbagai Paket", "pilihan-klookjiuzhaigou-premium-pangkalan-pandahuanglong-tersedia-berbagai-paket", "Chengdu", "【Pilihan Klook】Jiuzhaigou Premium + Pangkalan Panda/Huanglong | Tersedia Berbagai Paket

Tags: Pembatalan gratis; Konfirmasi instan", 0, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/dhewatbdbxftgrl0k8lw.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "pilihan-klookjiuzhaigou-premium-pangkalan-pandahuanglong-tersedia-berbagai-paket") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "pilihan-klookjiuzhaigou-premium-pangkalan-pandahuanglong-tersedia-berbagai-paket" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Chengdu", "Day 1 di Chengdu: 【Pilihan Klook】Jiuzhaigou Premium + Pangkalan Panda/Huanglong | Tersedia Berbagai Paket", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Chengdu", "Day 2: Explore Chengdu. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/dhewatbdbxftgrl0k8lw.jpg", "【Pilihan Klook】Jiuzhaigou Premium + Pangkalan Panda/Huanglong | Tersedia Berbagai Paket", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/dhewatbdbxftgrl0k8lw.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 8, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Klook Perjalanan yang Disesuaikan Secara Pribadi di Guangxi, Tiongkok (Nanning/Guilin/Beihai/Chongzuo/Liuzhou/Baise)", "klook-perjalanan-yang-disesuaikan-secara-pribadi-di-guangxi-tiongkok-nanningguilinbeihaichongzuoliuzhoubaise", "Xi\'an", "Klook Perjalanan yang Disesuaikan Secara Pribadi di Guangxi, Tiongkok (Nanning/Guilin/Beihai/Chongzuo/Liuzhou/Baise)

Tags: Private tour; Grup pribadi; Pembatalan gratis", 0, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/dw8sxgiuotqe3ma3fdsy.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "klook-perjalanan-yang-disesuaikan-secara-pribadi-di-guangxi-tiongkok-nanningguilinbeihaichongzuoliuzhoubaise") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "klook-perjalanan-yang-disesuaikan-secara-pribadi-di-guangxi-tiongkok-nanningguilinbeihaichongzuoliuzhoubaise" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Xi'an", "Day 1 di Xi\'an: Klook Perjalanan yang Disesuaikan Secara Pribadi di Guangxi, Tiongkok (Nanning/Guilin/Beihai/Chongzuo/Liuzhou/Baise)", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Xi'an", "Day 2: Explore Xi\'an. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/dw8sxgiuotqe3ma3fdsy.jpg", "Klook Perjalanan yang Disesuaikan Secara Pribadi di Guangxi, Tiongkok (Nanning/Guilin/Beihai/Chongzuo/Liuzhou/Baise)", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/dw8sxgiuotqe3ma3fdsy.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 5, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur pribadi 5 hari di Guilin, Sungai Li, Sawah Terasering Longji, dan Yangshuo", "tur-pribadi-5-hari-di-guilin-sungai-li-sawah-terasering-longji-dan-yangshuo", "Xi\'an", "Tur pribadi 5 hari di Guilin, Sungai Li, Sawah Terasering Longji, dan Yangshuo

Tags: Private tour; Pembatalan gratis", 8109411, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/e3p26aktwexnze3e6v6j.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-pribadi-5-hari-di-guilin-sungai-li-sawah-terasering-longji-dan-yangshuo") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-pribadi-5-hari-di-guilin-sungai-li-sawah-terasering-longji-dan-yangshuo" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Xi'an", "Day 1 di Xi\'an: Tur pribadi 5 hari di Guilin, Sungai Li, Sawah Terasering Longji, dan Yangshuo", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Xi'an", "Day 2: Explore Xi\'an. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/e3p26aktwexnze3e6v6j.jpg", "Tur pribadi 5 hari di Guilin, Sungai Li, Sawah Terasering Longji, dan Yangshuo", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/e3p26aktwexnze3e6v6j.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 13, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Perjalanan Sehari Kapal Bintang Empat Sungai Li Guilin & Rakit Bambu Sungai Yulong", "perjalanan-sehari-kapal-bintang-empat-sungai-li-guilin-rakit-bambu-sungai-yulong", "Xi\'an", "Perjalanan Sehari Kapal Bintang Empat Sungai Li Guilin & Rakit Bambu Sungai Yulong

Tags: Pembatalan gratis; Konfirmasi instan", 750855, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/nlscdiybzngansdyzu8q.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "perjalanan-sehari-kapal-bintang-empat-sungai-li-guilin-rakit-bambu-sungai-yulong") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "perjalanan-sehari-kapal-bintang-empat-sungai-li-guilin-rakit-bambu-sungai-yulong" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Xi'an", "Day 1 di Xi\'an: Perjalanan Sehari Kapal Bintang Empat Sungai Li Guilin & Rakit Bambu Sungai Yulong", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Xi'an", "Day 2: Explore Xi\'an. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/nlscdiybzngansdyzu8q.jpg", "Perjalanan Sehari Kapal Bintang Empat Sungai Li Guilin & Rakit Bambu Sungai Yulong", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/nlscdiybzngansdyzu8q.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 17, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Arung Jeram Bambu Sungai Yulong & Tur Sehari Gunung Xiangong", "arung-jeram-bambu-sungai-yulong-tur-sehari-gunung-xiangong", "Xi\'an", "Arung Jeram Bambu Sungai Yulong & Tur Sehari Gunung Xiangong

Tags: Pesan untuk besok; Pembatalan gratis; Konfirmasi instan", 1372362, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/t1usimh1qzjoscembtcw.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "arung-jeram-bambu-sungai-yulong-tur-sehari-gunung-xiangong") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "arung-jeram-bambu-sungai-yulong-tur-sehari-gunung-xiangong" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Xi'an", "Day 1 di Xi\'an: Arung Jeram Bambu Sungai Yulong & Tur Sehari Gunung Xiangong", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Xi'an", "Day 2: Explore Xi\'an. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/t1usimh1qzjoscembtcw.jpg", "Arung Jeram Bambu Sungai Yulong & Tur Sehari Gunung Xiangong", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/t1usimh1qzjoscembtcw.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 5, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Perjalanan 1 Hari dari Guilin ke Terasering Longji, Desa Zhuang Ping\'an, Desa Changfa, Terasering Jinkeng", "perjalanan-1-hari-dari-guilin-ke-terasering-longji-desa-zhuang-pingan-desa-changfa-terasering-jinkeng", "Xi\'an", "Perjalanan 1 Hari dari Guilin ke Terasering Longji, Desa Zhuang Ping\'an, Desa Changfa, Terasering Jinkeng

Tags: Pesan untuk besok; Private tour; Pembatalan gratis; Konfirmasi instan", 839017, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/wh3ncu5vls93mg4xefoa.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "perjalanan-1-hari-dari-guilin-ke-terasering-longji-desa-zhuang-pingan-desa-changfa-terasering-jinkeng") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "perjalanan-1-hari-dari-guilin-ke-terasering-longji-desa-zhuang-pingan-desa-changfa-terasering-jinkeng" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Xi'an", "Day 1 di Xi\'an: Perjalanan 1 Hari dari Guilin ke Terasering Longji, Desa Zhuang Ping\'an, Desa Changfa, Terasering Jinkeng", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Xi'an", "Day 2: Explore Xi\'an. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/wh3ncu5vls93mg4xefoa.jpg", "Perjalanan 1 Hari dari Guilin ke Terasering Longji, Desa Zhuang Ping\'an, Desa Changfa, Terasering Jinkeng", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/wh3ncu5vls93mg4xefoa.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 5, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur VIP 5 Hari Zhangjiajie + Gunung Tianmen (Pilihan Menginap di Homestay Mewah di Puncak Gunung)", "tur-vip-5-hari-zhangjiajie-gunung-tianmen-pilihan-menginap-di-homestay-mewah-di-puncak-gunung", "Hangzhou", "Tur VIP 5 Hari Zhangjiajie + Gunung Tianmen (Pilihan Menginap di Homestay Mewah di Puncak Gunung)

Tags: Pesan untuk besok; Pembatalan gratis; Konfirmasi instan", 6858693, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/ggxre5zgsfzn4xfv8xu7.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-vip-5-hari-zhangjiajie-gunung-tianmen-pilihan-menginap-di-homestay-mewah-di-puncak-gunung") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-vip-5-hari-zhangjiajie-gunung-tianmen-pilihan-menginap-di-homestay-mewah-di-puncak-gunung" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Hangzhou", "Day 1 di Hangzhou: Tur VIP 5 Hari Zhangjiajie + Gunung Tianmen (Pilihan Menginap di Homestay Mewah di Puncak Gunung)", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Hangzhou", "Day 2: Explore Hangzhou. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/ggxre5zgsfzn4xfv8xu7.jpg", "Tur VIP 5 Hari Zhangjiajie + Gunung Tianmen (Pilihan Menginap di Homestay Mewah di Puncak Gunung)", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/ggxre5zgsfzn4xfv8xu7.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 14, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Zhangjiajie 1 Hari: Kota Furong + Avatar, Tianmen ATAU Jembatan Kaca", "zhangjiajie-1-hari-kota-furong-avatar-tianmen-atau-jembatan-kaca", "Hangzhou", "Zhangjiajie 1 Hari: Kota Furong + Avatar, Tianmen ATAU Jembatan Kaca

Tags: Pesan untuk besok; Pembatalan gratis; Konfirmasi instan", 1309981, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/m2njj4dgqgn9bouib65a.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "zhangjiajie-1-hari-kota-furong-avatar-tianmen-atau-jembatan-kaca") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "zhangjiajie-1-hari-kota-furong-avatar-tianmen-atau-jembatan-kaca" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Hangzhou", "Day 1 di Hangzhou: Zhangjiajie 1 Hari: Kota Furong + Avatar, Tianmen ATAU Jembatan Kaca", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Hangzhou", "Day 2: Explore Hangzhou. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/m2njj4dgqgn9bouib65a.jpg", "Zhangjiajie 1 Hari: Kota Furong + Avatar, Tianmen ATAU Jembatan Kaca", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/m2njj4dgqgn9bouib65a.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 7, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur 2 Hari Taman Nasional & Gunung Tianmen Zhangjiajie", "tur-2-hari-taman-nasional-gunung-tianmen-zhangjiajie", "Hangzhou", "Tur 2 Hari Taman Nasional & Gunung Tianmen Zhangjiajie

Tags: Pesan untuk besok; Pembatalan gratis; Konfirmasi instan", 1278803, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/bfj29f8k2uuwzy7qaxfu.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-2-hari-taman-nasional-gunung-tianmen-zhangjiajie") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-2-hari-taman-nasional-gunung-tianmen-zhangjiajie" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Hangzhou", "Day 1 di Hangzhou: Tur 2 Hari Taman Nasional & Gunung Tianmen Zhangjiajie", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Hangzhou", "Day 2: Explore Hangzhou. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/bfj29f8k2uuwzy7qaxfu.jpg", "Tur 2 Hari Taman Nasional & Gunung Tianmen Zhangjiajie", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/bfj29f8k2uuwzy7qaxfu.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 19, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur 5 Hari Zhangjiajie Hunan yang Nyaman (Saluran VIP Opsional + Kelompok Kecil 6 Orang + Taman Hutan + Jembatan Kaca)", "tur-5-hari-zhangjiajie-hunan-yang-nyaman-saluran-vip-opsional-kelompok-kecil-6-orang-taman-hutan-jembatan-kaca", "Hangzhou", "Tur 5 Hari Zhangjiajie Hunan yang Nyaman (Saluran VIP Opsional + Kelompok Kecil 6 Orang + Taman Hutan + Jembatan Kaca)

Tags: ", 0, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/dif62a9l70ci17wrpnru.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-5-hari-zhangjiajie-hunan-yang-nyaman-saluran-vip-opsional-kelompok-kecil-6-orang-taman-hutan-jembatan-kaca") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-5-hari-zhangjiajie-hunan-yang-nyaman-saluran-vip-opsional-kelompok-kecil-6-orang-taman-hutan-jembatan-kaca" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Hangzhou", "Day 1 di Hangzhou: Tur 5 Hari Zhangjiajie Hunan yang Nyaman (Saluran VIP Opsional + Kelompok Kecil 6 Orang + Taman Hutan + Jembatan Kaca)", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Hangzhou", "Day 2: Explore Hangzhou. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/dif62a9l70ci17wrpnru.jpg", "Tur 5 Hari Zhangjiajie Hunan yang Nyaman (Saluran VIP Opsional + Kelompok Kecil 6 Orang + Taman Hutan + Jembatan Kaca)", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/dif62a9l70ci17wrpnru.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 19, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Qixing Mountain & Via Ferrata Full-Day Private Tour", "qixing-mountain-via-ferrata-full-day-private-tour", "Hangzhou", "Qixing Mountain & Via Ferrata Full-Day Private Tour

Tags: Pesan untuk besok; Pembatalan gratis; Konfirmasi instan", 1437783, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/mgz3lrnquvnudnuhw3jh.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "qixing-mountain-via-ferrata-full-day-private-tour") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "qixing-mountain-via-ferrata-full-day-private-tour" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Hangzhou", "Day 1 di Hangzhou: Qixing Mountain & Via Ferrata Full-Day Private Tour", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Hangzhou", "Day 2: Explore Hangzhou. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/mgz3lrnquvnudnuhw3jh.jpg", "Qixing Mountain & Via Ferrata Full-Day Private Tour", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/mgz3lrnquvnudnuhw3jh.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 12, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "【Tur Pribadi】Liburan 6 Hari di Yunnan Dali Lijiang Shangri-La", "tur-pribadiliburan-6-hari-di-yunnan-dali-lijiang-shangri-la", "Zhangjiajie", "【Tur Pribadi】Liburan 6 Hari di Yunnan Dali Lijiang Shangri-La

Tags: Pesan untuk besok; Penjemputan hotel; Private tour; Grup pribadi; Konfirmasi instan", 15103320, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/zcslbcsvve5xhhnk84fm.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-pribadiliburan-6-hari-di-yunnan-dali-lijiang-shangri-la") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-pribadiliburan-6-hari-di-yunnan-dali-lijiang-shangri-la" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Zhangjiajie", "Day 1 di Zhangjiajie: 【Tur Pribadi】Liburan 6 Hari di Yunnan Dali Lijiang Shangri-La", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Zhangjiajie", "Day 2: Explore Zhangjiajie. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/zcslbcsvve5xhhnk84fm.jpg", "【Tur Pribadi】Liburan 6 Hari di Yunnan Dali Lijiang Shangri-La", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/zcslbcsvve5xhhnk84fm.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 19, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Pribadi 8 Hari Yunnan Kunming Dali Lijiang Lugu Lake Shangri-La", "tur-pribadi-8-hari-yunnan-kunming-dali-lijiang-lugu-lake-shangri-la", "Zhangjiajie", "Tur Pribadi 8 Hari Yunnan Kunming Dali Lijiang Lugu Lake Shangri-La

Tags: Private tour; Grup pribadi; Pembatalan gratis", 26692434, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/hruiatpvammaxzyzl6z5.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-pribadi-8-hari-yunnan-kunming-dali-lijiang-lugu-lake-shangri-la") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-pribadi-8-hari-yunnan-kunming-dali-lijiang-lugu-lake-shangri-la" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Zhangjiajie", "Day 1 di Zhangjiajie: Tur Pribadi 8 Hari Yunnan Kunming Dali Lijiang Lugu Lake Shangri-La", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Zhangjiajie", "Day 2: Explore Zhangjiajie. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/hruiatpvammaxzyzl6z5.jpg", "Tur Pribadi 8 Hari Yunnan Kunming Dali Lijiang Lugu Lake Shangri-La", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/hruiatpvammaxzyzl6z5.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 8, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur 10 Hari Yunnan Kunming, Dali, Lijiang, Shangri-La, Lugu Lake", "tur-10-hari-yunnan-kunming-dali-lijiang-shangri-la-lugu-lake", "Zhangjiajie", "Tur 10 Hari Yunnan Kunming, Dali, Lijiang, Shangri-La, Lugu Lake

Tags: Pembatalan gratis", 19088282, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/mumxmngwaueqaqpuhegl.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-10-hari-yunnan-kunming-dali-lijiang-shangri-la-lugu-lake") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-10-hari-yunnan-kunming-dali-lijiang-shangri-la-lugu-lake" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Zhangjiajie", "Day 1 di Zhangjiajie: Tur 10 Hari Yunnan Kunming, Dali, Lijiang, Shangri-La, Lugu Lake", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Zhangjiajie", "Day 2: Explore Zhangjiajie. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/mumxmngwaueqaqpuhegl.jpg", "Tur 10 Hari Yunnan Kunming, Dali, Lijiang, Shangri-La, Lugu Lake", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/mumxmngwaueqaqpuhegl.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 19, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Sehari Penuh Lijiang Jade Dragon Snow Mountain & Blue Moon Valley dengan Wahana Kereta Gantung", "tur-sehari-penuh-lijiang-jade-dragon-snow-mountain-blue-moon-valley-dengan-wahana-kereta-gantung", "Zhangjiajie", "Tur Sehari Penuh Lijiang Jade Dragon Snow Mountain & Blue Moon Valley dengan Wahana Kereta Gantung

Tags: Konfirmasi instan", 1405845, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/qwo2tqnqmi4frwcdicax.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-sehari-penuh-lijiang-jade-dragon-snow-mountain-blue-moon-valley-dengan-wahana-kereta-gantung") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-sehari-penuh-lijiang-jade-dragon-snow-mountain-blue-moon-valley-dengan-wahana-kereta-gantung" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Zhangjiajie", "Day 1 di Zhangjiajie: Tur Sehari Penuh Lijiang Jade Dragon Snow Mountain & Blue Moon Valley dengan Wahana Kereta Gantung", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Zhangjiajie", "Day 2: Explore Zhangjiajie. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/qwo2tqnqmi4frwcdicax.jpg", "Tur Sehari Penuh Lijiang Jade Dragon Snow Mountain & Blue Moon Valley dengan Wahana Kereta Gantung", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/qwo2tqnqmi4frwcdicax.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 6, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Pengalaman Studi Warisan Budaya Takbenda Memetik dan Membuat Teh di Gunung Cangshan, Dali", "pengalaman-studi-warisan-budaya-takbenda-memetik-dan-membuat-teh-di-gunung-cangshan-dali", "Zhangjiajie", "Pengalaman Studi Warisan Budaya Takbenda Memetik dan Membuat Teh di Gunung Cangshan, Dali

Tags: Pesan untuk besok; Keberangkatan pagi; Pembatalan gratis; Konfirmasi instan", 492553, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/ihuzsgpjvlplywdm6cbd.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "pengalaman-studi-warisan-budaya-takbenda-memetik-dan-membuat-teh-di-gunung-cangshan-dali") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "pengalaman-studi-warisan-budaya-takbenda-memetik-dan-membuat-teh-di-gunung-cangshan-dali" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Zhangjiajie", "Day 1 di Zhangjiajie: Pengalaman Studi Warisan Budaya Takbenda Memetik dan Membuat Teh di Gunung Cangshan, Dali", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Zhangjiajie", "Day 2: Explore Zhangjiajie. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/ihuzsgpjvlplywdm6cbd.jpg", "Pengalaman Studi Warisan Budaya Takbenda Memetik dan Membuat Teh di Gunung Cangshan, Dali", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/ihuzsgpjvlplywdm6cbd.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 14, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Fantasi Es dan Salju A | Tur 7 Hari Harbin, Changbai Mountain, Xuexiang, Yanji di Timur Laut", "fantasi-es-dan-salju-a-tur-7-hari-harbin-changbai-mountain-xuexiang-yanji-di-timur-laut", "Chongqing", "Fantasi Es dan Salju A | Tur 7 Hari Harbin, Changbai Mountain, Xuexiang, Yanji di Timur Laut

Tags: Private tour", 0, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/vxalmmttvdxel0euhwjb.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "fantasi-es-dan-salju-a-tur-7-hari-harbin-changbai-mountain-xuexiang-yanji-di-timur-laut") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "fantasi-es-dan-salju-a-tur-7-hari-harbin-changbai-mountain-xuexiang-yanji-di-timur-laut" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Chongqing", "Day 1 di Chongqing: Fantasi Es dan Salju A | Tur 7 Hari Harbin, Changbai Mountain, Xuexiang, Yanji di Timur Laut", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Chongqing", "Day 2: Explore Chongqing. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/vxalmmttvdxel0euhwjb.jpg", "Fantasi Es dan Salju A | Tur 7 Hari Harbin, Changbai Mountain, Xuexiang, Yanji di Timur Laut", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/vxalmmttvdxel0euhwjb.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 16, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Pribadi 5 Hari Harbin, Desa Salju, Yabuli, Hengdaohezi", "tur-pribadi-5-hari-harbin-desa-salju-yabuli-hengdaohezi", "Chongqing", "Tur Pribadi 5 Hari Harbin, Desa Salju, Yabuli, Hengdaohezi

Tags: Private tour; Grup pribadi", 10601492, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/y8izeaq1fpyalym9fk6z.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-pribadi-5-hari-harbin-desa-salju-yabuli-hengdaohezi") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-pribadi-5-hari-harbin-desa-salju-yabuli-hengdaohezi" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Chongqing", "Day 1 di Chongqing: Tur Pribadi 5 Hari Harbin, Desa Salju, Yabuli, Hengdaohezi", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Chongqing", "Day 2: Explore Chongqing. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/y8izeaq1fpyalym9fk6z.jpg", "Tur Pribadi 5 Hari Harbin, Desa Salju, Yabuli, Hengdaohezi", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/y8izeaq1fpyalym9fk6z.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 19, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Pribadi 6 Hari Harbin Snow Town Changbai Mountain Yanji (termasuk hotel mata air panas)", "tur-pribadi-6-hari-harbin-snow-town-changbai-mountain-yanji-termasuk-hotel-mata-air-panas", "Chongqing", "Tur Pribadi 6 Hari Harbin Snow Town Changbai Mountain Yanji (termasuk hotel mata air panas)

Tags: Private tour; Grup pribadi", 16218822, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/yubdwhhoak6qlfci5kgf.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-pribadi-6-hari-harbin-snow-town-changbai-mountain-yanji-termasuk-hotel-mata-air-panas") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-pribadi-6-hari-harbin-snow-town-changbai-mountain-yanji-termasuk-hotel-mata-air-panas" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Chongqing", "Day 1 di Chongqing: Tur Pribadi 6 Hari Harbin Snow Town Changbai Mountain Yanji (termasuk hotel mata air panas)", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Chongqing", "Day 2: Explore Chongqing. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/yubdwhhoak6qlfci5kgf.jpg", "Tur Pribadi 6 Hari Harbin Snow Town Changbai Mountain Yanji (termasuk hotel mata air panas)", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/yubdwhhoak6qlfci5kgf.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 17, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur 1 hari ke Yabuli Ski & Desa Salju", "tur-1-hari-ke-yabuli-ski-desa-salju", "Chongqing", "Tur 1 hari ke Yabuli Ski & Desa Salju

Tags: Grup kecil; Pembatalan gratis; Konfirmasi instan", 1980953, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/w2kcpo0resakjradn8kz.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-1-hari-ke-yabuli-ski-desa-salju") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-1-hari-ke-yabuli-ski-desa-salju" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Chongqing", "Day 1 di Chongqing: Tur 1 hari ke Yabuli Ski & Desa Salju", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Chongqing", "Day 2: Explore Chongqing. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/w2kcpo0resakjradn8kz.jpg", "Tur 1 hari ke Yabuli Ski & Desa Salju", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/w2kcpo0resakjradn8kz.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 7, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Tur Ski Sehari Harbin-Yabuli | Termasuk Bus Langsung + Peralatan Ski + Ski Sepanjang Hari", "tur-ski-sehari-harbin-yabuli-termasuk-bus-langsung-peralatan-ski-ski-sepanjang-hari", "Chongqing", "Tur Ski Sehari Harbin-Yabuli | Termasuk Bus Langsung + Peralatan Ski + Ski Sepanjang Hari

Tags: Pembatalan gratis; Konfirmasi instan", 1501684, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/cp1tefy5dw8721sxghvg.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "tur-ski-sehari-harbin-yabuli-termasuk-bus-langsung-peralatan-ski-ski-sepanjang-hari") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "tur-ski-sehari-harbin-yabuli-termasuk-bus-langsung-peralatan-ski-ski-sepanjang-hari" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Chongqing", "Day 1 di Chongqing: Tur Ski Sehari Harbin-Yabuli | Termasuk Bus Langsung + Peralatan Ski + Ski Sepanjang Hari", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Chongqing", "Day 2: Explore Chongqing. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/cp1tefy5dw8721sxghvg.jpg", "Tur Ski Sehari Harbin-Yabuli | Termasuk Bus Langsung + Peralatan Ski + Ski Sepanjang Hari", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/cp1tefy5dw8721sxghvg.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 9, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Chongqing Wulong Tiansheng Sanqiao + Longshui Gorge Ground Fissure/Xiannyu Mountain 2+1 Tur Bus Pengasuh (Pemandu Wisata Mandarin/Inggris/Thailand)", "chongqing-wulong-tiansheng-sanqiao-longshui-gorge-ground-fissurexiannyu-mountain-21-tur-bus-pengasuh-pemandu-wisata-mandarininggristhailand", "Guilin", "Chongqing Wulong Tiansheng Sanqiao + Longshui Gorge Ground Fissure/Xiannyu Mountain 2+1 Tur Bus Pengasuh (Pemandu Wisata Mandarin/Inggris/Thailand)

Tags: Pesan untuk besok; Pembatalan gratis; Konfirmasi instan", 926943, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/rvduwy2hy6t858a6yes2.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "chongqing-wulong-tiansheng-sanqiao-longshui-gorge-ground-fissurexiannyu-mountain-21-tur-bus-pengasuh-pemandu-wisata-mandarininggristhailand") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "chongqing-wulong-tiansheng-sanqiao-longshui-gorge-ground-fissurexiannyu-mountain-21-tur-bus-pengasuh-pemandu-wisata-mandarininggristhailand" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Guilin", "Day 1 di Guilin: Chongqing Wulong Tiansheng Sanqiao + Longshui Gorge Ground Fissure/Xiannyu Mountain 2+1 Tur Bus Pengasuh (Pemandu Wisata Mandarin/Inggris/Thailand)", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Guilin", "Day 2: Explore Guilin. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/rvduwy2hy6t858a6yes2.jpg", "Chongqing Wulong Tiansheng Sanqiao + Longshui Gorge Ground Fissure/Xiannyu Mountain 2+1 Tur Bus Pengasuh (Pemandu Wisata Mandarin/Inggris/Thailand)", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/rvduwy2hy6t858a6yes2.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 7, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Chongqing Tiansheng Three Bridges & Fairy Mountain Day Tour", "chongqing-tiansheng-three-bridges-fairy-mountain-day-tour", "Guilin", "Chongqing Tiansheng Three Bridges & Fairy Mountain Day Tour

Tags: Pesan untuk besok; Pembatalan gratis; Konfirmasi instan", 757169, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/kwzku2vvmjml7dexkrug.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "chongqing-tiansheng-three-bridges-fairy-mountain-day-tour") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "chongqing-tiansheng-three-bridges-fairy-mountain-day-tour" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Guilin", "Day 1 di Guilin: Chongqing Tiansheng Three Bridges & Fairy Mountain Day Tour", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Guilin", "Day 2: Explore Guilin. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/kwzku2vvmjml7dexkrug.jpg", "Chongqing Tiansheng Three Bridges & Fairy Mountain Day Tour", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/kwzku2vvmjml7dexkrug.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 17, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Chongqing Tiansheng Three Bridges & Xiannyushan Small Group Tour", "chongqing-tiansheng-three-bridges-xiannyushan-small-group-tour", "Guilin", "Chongqing Tiansheng Three Bridges & Xiannyushan Small Group Tour

Tags: Pesan untuk besok; Pembatalan gratis; Konfirmasi instan", 898280, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/h7ge1zivjjlt5imnkc90.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "chongqing-tiansheng-three-bridges-xiannyushan-small-group-tour") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "chongqing-tiansheng-three-bridges-xiannyushan-small-group-tour" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Guilin", "Day 1 di Guilin: Chongqing Tiansheng Three Bridges & Xiannyushan Small Group Tour", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Guilin", "Day 2: Explore Guilin. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/h7ge1zivjjlt5imnkc90.jpg", "Chongqing Tiansheng Three Bridges & Xiannyushan Small Group Tour", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/h7ge1zivjjlt5imnkc90.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 10, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Century Cruises|Kapal Pesiar Mewah Tiga Ngarai Yangtze 4 Hari 3 Malam/5 Hari 4 Malam (Berangkat dari Chongqing/Yichang)", "century-cruiseskapal-pesiar-mewah-tiga-ngarai-yangtze-4-hari-3-malam5-hari-4-malam-berangkat-dari-chongqingyichang", "Guilin", "Century Cruises|Kapal Pesiar Mewah Tiga Ngarai Yangtze 4 Hari 3 Malam/5 Hari 4 Malam (Berangkat dari Chongqing/Yichang)

Tags: Keberangkatan sore/malam", 0, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/nmw9vxssjk8miaoijncj.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "century-cruiseskapal-pesiar-mewah-tiga-ngarai-yangtze-4-hari-3-malam5-hari-4-malam-berangkat-dari-chongqingyichang") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "century-cruiseskapal-pesiar-mewah-tiga-ngarai-yangtze-4-hari-3-malam5-hari-4-malam-berangkat-dari-chongqingyichang" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Guilin", "Day 1 di Guilin: Century Cruises|Kapal Pesiar Mewah Tiga Ngarai Yangtze 4 Hari 3 Malam/5 Hari 4 Malam (Berangkat dari Chongqing/Yichang)", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Guilin", "Day 2: Explore Guilin. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/nmw9vxssjk8miaoijncj.jpg", "Century Cruises|Kapal Pesiar Mewah Tiga Ngarai Yangtze 4 Hari 3 Malam/5 Hari 4 Malam (Berangkat dari Chongqing/Yichang)", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/nmw9vxssjk8miaoijncj.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 7, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

INSERT IGNORE INTO tours (title, slug, category, description, price, price_currency, original_price, max_participants, rating, total_reviews, cover_image, is_active)
SELECT "Chongqing Wulong Karst National Park Day Tour", "chongqing-wulong-karst-national-park-day-tour", "Guilin", "Chongqing Wulong Karst National Park Day Tour

Tags: Pesan untuk besok; Pembatalan gratis; Konfirmasi instan", 795578, "IDR", NULL, 20, 5, 0, "https://res.klook.com/image/upload/activities/o5c0dbflrrljd2xs96hs.jpg", 1
WHERE NOT EXISTS (SELECT 1 FROM tours WHERE slug = "chongqing-wulong-karst-national-park-day-tour") LIMIT 1;
SET @tid = (SELECT id FROM tours WHERE slug = "chongqing-wulong-karst-national-park-day-tour" LIMIT 1);

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 1, "Day 1 — Guilin", "Day 1 di Guilin: Chongqing Wulong Karst National Park Day Tour", NULL, NULL
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 1) LIMIT 1;

INSERT IGNORE INTO itineraries (tour_id, day_number, title, description, meals, accommodation)
SELECT @tid, 2, "Day 2 — Explore Guilin", "Day 2: Explore Guilin. Free day untuk jalan-jalan, kuliner, dan belanja.", "Breakfast", "Hotel"
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM itineraries WHERE tour_id = @tid AND day_number = 2) LIMIT 1;

INSERT IGNORE INTO tour_images (tour_id, image_path, caption, sort_order)
SELECT @tid, "https://res.klook.com/image/upload/activities/o5c0dbflrrljd2xs96hs.jpg", "Chongqing Wulong Karst National Park Day Tour", 0
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_images WHERE tour_id = @tid AND image_path = "https://res.klook.com/image/upload/activities/o5c0dbflrrljd2xs96hs.jpg") LIMIT 1;

INSERT IGNORE INTO tour_dates (tour_id, departure_date, return_date, available_slots, is_active)
SELECT @tid, "2026-10-09", "2026-10-16", 16, 1
WHERE @tid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_dates WHERE tour_id = @tid AND departure_date = "2026-10-09") LIMIT 1;

