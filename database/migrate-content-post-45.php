<?php
/**
 * migrate-content-post-45.php
 *
 * Post 45 (slug panduan-beijing-zh, content_language=zh) terbit tetapi belum
 * punya title_en/excerpt_en/body_en (versi EN menampilkan teks Mandarin) dan
 * title_zh/excerpt_zh/body_zh (untuk konsistensi; base sudah zh).
 *
 * Idempotent.
 * Jalankan: php database/migrate-content-post-45.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$title_zh   = '北京旅行完全指南';
$excerpt_zh = '最佳旅行季节、交通、美食和必去景点的完整中文指南。';
$body_zh    = "北京四季分明，春季（4-5月）和秋季（9-10月）最为宜人。\n\n必去景点：故宫博物院、八达岭长城、颐和园、天坛。\n\n交通提示：地铁网络覆盖主要景点，建议办理一卡通。\n\n美食推荐：北京烤鸭、炸酱面、豆汁儿。";

$title_en   = 'The Complete Beijing Travel Guide';
$excerpt_en = 'A complete guide to the best seasons, transport, food and must-visit attractions in Beijing.';
$body_en    = "Beijing has four distinct seasons; spring (April–May) and autumn (September–October) are the most pleasant.\n\nMust-visit attractions: the Palace Museum, Badaling Great Wall, Summer Palace and Temple of Heaven.\n\nTransport tips: the subway network covers the main attractions; getting a Yikatong travel card is recommended.\n\nFood recommendations: Peking duck, zhajiangmian (noodles) and douzhir (fermented mung bean drink).";

$stmt = db()->prepare(
    "UPDATE posts SET
        title_en = COALESCE(NULLIF(title_en, ''), ?),
        excerpt_en = COALESCE(NULLIF(excerpt_en, ''), ?),
        body_en = COALESCE(NULLIF(body_en, ''), ?),
        title_zh = COALESCE(NULLIF(title_zh, ''), ?),
        excerpt_zh = COALESCE(NULLIF(excerpt_zh, ''), ?),
        body_zh = COALESCE(NULLIF(body_zh, ''), ?)
     WHERE id = 45"
);
$stmt->execute([$title_en, $excerpt_en, $body_en, $title_zh, $excerpt_zh, $body_zh]);
echo "Updated " . $stmt->rowCount() . " post row(s).\n";
