<?php
/**
 * seed-content-zh.php — isi konten zh contoh (step 17): 1 tour + 1 post + 1 hotel + 1 kamar
 * dengan content_language='zh' + kolom _zh pada konten bilingual.
 * Idempotent: cek dulu sebelum insert/update.
 *
 * Jalankan: php database/seed-content-zh.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// 1) Tour berbahasa zh (content_language='zh')
$exists = db()->prepare("SELECT id FROM tours WHERE slug = ? LIMIT 1");
$exists->execute(['beijing-classic-zh']);
$tourId = $exists->fetchColumn();
if (!$tourId) {
    $st = db()->prepare("INSERT INTO tours (title, slug, category, description, price, content_language, max_participants, cover_image, is_active)
                         VALUES (?, ?, ?, ?, ?, 'zh', 20, NULL, 1)");
    $st->execute([
        '北京经典之旅 5天4晚',
        'beijing-classic-zh',
        'Best Seller',
        '探索北京的历史与文化：故宫、长城、颐和园，以及现代都市魅力。全程中文导游，含酒店住宿、每日早餐和机场接送。',
        4500000,
    ]);
    $tourId = (int)db()->lastInsertId();
    echo "OK: tour zh dibuat (id={$tourId})\n";
} else {
    echo "SKIP: tour zh sudah ada (id={$tourId})\n";
}

// 2) Post berbahasa zh
$exists = db()->prepare("SELECT id FROM posts WHERE slug = ? LIMIT 1");
$exists->execute(['panduan-beijing-zh']);
$postId = $exists->fetchColumn();
if (!$postId) {
    $st = db()->prepare("INSERT INTO posts (title, slug, excerpt, body, cover_image, category, status, content_language)
                         VALUES (?, ?, ?, ?, NULL, 'Tips', 'published', 'zh')");
    $st->execute([
        '北京旅行完全指南',
        'panduan-beijing-zh',
        '最佳旅行季节、交通、美食和必去景点的完整中文指南。',
        "北京四季分明，春季（4-5月）和秋季（9-10月）最为宜人。\n\n必去景点：故宫博物院、八达岭长城、颐和园、天坛。\n\n交通提示：地铁网络覆盖主要景点，建议办理一卡通。\n\n美食推荐：北京烤鸭、炸酱面、豆汁儿。",
    ]);
    $postId = (int)db()->lastInsertId();
    echo "OK: post zh dibuat (id={$postId})\n";
} else {
    echo "SKIP: post zh sudah ada (id={$postId})\n";
}

// 3) Kolom _zh pada hotel pertama (bilingual)
$upd = db()->prepare("UPDATE hotels SET name_zh = ?, description_zh = ? WHERE id = 1 AND (name_zh IS NULL OR name_zh = '')");
$upd->execute(['巴厘岛君悦大酒店', '位于努沙杜瓦的豪华海滨度假村，拥有泻湖泳池和直达海滩通道。']);
echo $upd->rowCount() ? "OK: hotel id=1 kolom _zh diisi\n" : "SKIP: hotel id=1 _zh sudah terisi\n";

// 4) Kolom _zh pada post pertama (bilingual)
$upd = db()->prepare("UPDATE posts SET title_zh = ?, excerpt_zh = ? WHERE id = 1 AND (title_zh IS NULL OR title_zh = '')");
$upd->execute(['2026年亚洲最受欢迎目的地', '从东京到巴厘岛 — 探索明年最值得前往的亚洲目的地。']);
echo $upd->rowCount() ? "OK: post id=1 kolom _zh diisi\n" : "SKIP: post id=1 _zh sudah terisi\n";

// Ringkasan
$cnt = db()->query("SELECT COUNT(*) c FROM tours WHERE content_language = 'zh'")->fetch()['c'] ?? 0;
$cntP = db()->query("SELECT COUNT(*) c FROM posts WHERE content_language = 'zh'")->fetch()['c'] ?? 0;
$cntH = db()->query("SELECT COUNT(*) c FROM hotels WHERE name_zh IS NOT NULL AND name_zh != ''")->fetch()['c'] ?? 0;
echo "SUMMARY: tours zh={$cnt}, posts zh={$cntP}, hotels ber-_zh={$cntH}\n";
