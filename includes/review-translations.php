<?php
/**
 * includes/review-translations.php — Backlog #9: review multi-bahasa.
 * Fallback chain: review asli (lang user) → terjemahan (review_translations) → teks asli.
 * Auto-translate: kamus frasa umum (offline, deterministik) — tanpa API eksternal.
 */

const RT_LANGS = ['id', 'en', 'zh'];

/** Kamus translate frasa umum review (deterministik, tanpa network). */
function rtDictionary(): array {
    return [
        'id' => [
            '/\bsangat bagus\b/i' => ['en' => 'very good', 'zh' => '非常好'],
            '/\bbagus sekali\b/i' => ['en' => 'excellent', 'zh' => '很棒'],
            '/\bbagus\b/i' => ['en' => 'good', 'zh' => '不错'],
            '/\bluar biasa\b/i' => ['en' => 'amazing', 'zh' => '太棒了'],
            '/\bseru\b/i' => ['en' => 'fun', 'zh' => '有趣'],
            '/\bmenarik\b/i' => ['en' => 'interesting', 'zh' => '有意思'],
            '/\bmantap\b/i' => ['en' => 'great', 'zh' => '赞'],
            '/\bpuas\b/i' => ['en' => 'satisfied', 'zh' => '满意'],
            '/\b Recommended\b/i' => ['en' => 'Recommended', 'zh' => '推荐'],
            '/\brecommended\b/i' => ['en' => 'recommended', 'zh' => '推荐'],
            '/\bpemandu\b/i' => ['en' => 'guide', 'zh' => '导游'],
            '/\bhasil \b/i' => ['en' => 'guide ', 'zh' => '导游 '],
            '/\btempat\b/i' => ['en' => 'place', 'zh' => '地方'],
            '/\bmakanan\b/i' => ['en' => 'food', 'zh' => '食物'],
            '/\bperjalanan\b/i' => ['en' => 'trip', 'zh' => '旅程'],
            '/\bharga\b/i' => ['en' => 'price', 'zh' => '价格'],
            '/\bpelayanan\b/i' => ['en' => 'service', 'zh' => '服务'],
            '/\bhotel\b/i' => ['en' => 'hotel', 'zh' => '酒店'],
            '/\bkamar\b/i' => ['en' => 'room', 'zh' => '房间'],
            '/\bbersih\b/i' => ['en' => 'clean', 'zh' => '干净'],
            '/\bnyaman\b/i' => ['en' => 'comfortable', 'zh' => '舒适'],
            '/\bterima kasih\b/i' => ['en' => 'thank you', 'zh' => '谢谢'],
        ],
        'en' => [
            '/\bvery good\b/i' => ['id' => 'sangat bagus', 'zh' => '非常好'],
            '/\bexcellent\b/i' => ['id' => 'luar biasa', 'zh' => '优秀'],
            '/\bgood\b/i' => ['id' => 'bagus', 'zh' => '不错'],
            '/\bamazing\b/i' => ['id' => 'luar biasa', 'zh' => '令人惊叹'],
            '/\bfun\b/i' => ['id' => 'seru', 'zh' => '有趣'],
            '/\binteresting\b/i' => ['id' => 'menarik', 'zh' => '有意思'],
            '/\bgreat\b/i' => ['id' => 'mantap', 'zh' => '很棒'],
            '/\bsatisfied\b/i' => ['id' => 'puas', 'zh' => '满意'],
            '/\bguide\b/i' => ['id' => 'pemandu', 'zh' => '导游'],
            '/\bplace\b/i' => ['id' => 'tempat', 'zh' => '地方'],
            '/\bfood\b/i' => ['id' => 'makanan', 'zh' => '食物'],
            '/\btrip\b/i' => ['id' => 'perjalanan', 'zh' => '旅程'],
            '/\bprice\b/i' => ['id' => 'harga', 'zh' => '价格'],
            '/\bservice\b/i' => ['id' => 'pelayanan', 'zh' => '服务'],
            '/\bhotel\b/i' => ['id' => 'hotel', 'zh' => '酒店'],
            '/\broom\b/i' => ['id' => 'kamar', 'zh' => '房间'],
            '/\bclean\b/i' => ['id' => 'bersih', 'zh' => '干净'],
            '/\bcomfortable\b/i' => ['id' => 'nyaman', 'zh' => '舒适'],
            '/\bthank you\b/i' => ['id' => 'terima kasih', 'zh' => '谢谢'],
        ],
        'zh' => [
            '/非常好/' => ['id' => 'sangat bagus', 'en' => 'very good'],
            '/很棒/' => ['id' => 'bagus sekali', 'en' => 'excellent'],
            '/不错/' => ['id' => 'bagus', 'en' => 'good'],
            '/有趣/' => ['id' => 'seru', 'en' => 'fun'],
            '/导游/' => ['id' => 'pemandu', 'en' => 'guide'],
            '/干净/' => ['id' => 'bersih', 'en' => 'clean'],
            '/舒适/' => ['id' => 'nyaman', 'en' => 'comfortable'],
            '/谢谢/' => ['id' => 'terima kasih', 'en' => 'thank you'],
        ],
    ];
}

/** Auto-translate komentar dari $fromLang ke $toLang (kamus; kata tak dikenal dibiarkan). */
function rtAutoTranslate(string $comment, string $fromLang, string $toLang): string {
    if ($fromLang === $toLang) return $comment;
    $dict = rtDictionary();
    $out = $comment;
    foreach ($dict[$fromLang] ?? [] as $pattern => $targets) {
        if (isset($targets[$toLang])) {
            $out = preg_replace($pattern, $targets[$toLang], $out);
        }
    }
    return $out;
}

/** Simpan (upsert) terjemahan review. Return id baris terjemahan. */
function rtSaveTranslation(int $reviewId, string $lang, string $comment, string $source = 'auto'): int {
    $stmt = db()->prepare("INSERT INTO review_translations (review_id, lang, comment, source)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE comment = VALUES(comment), source = VALUES(source)");
    $stmt->execute([$reviewId, $lang, $comment, $source]);
    return (int)db()->lastInsertId();
}

/** Ambil terjemahan (atau null). */
function rtGetTranslation(int $reviewId, string $lang): ?string {
    $stmt = db()->prepare("SELECT comment FROM review_translations WHERE review_id = ? AND lang = ?");
    $stmt->execute([$reviewId, $lang]);
    $v = $stmt->fetchColumn();
    return $v !== false ? (string)$v : null;
}

/**
 * Generate terjemahan otomatis untuk semua bahasa lain (idempotent per review).
 * Return jumlah terjemahan dibuat.
 */
function rtGenerateForReview(int $reviewId, string $comment, string $sourceLang): int {
    $count = 0;
    foreach (RT_LANGS as $lang) {
        if ($lang === $sourceLang) continue;
        if (rtGetTranslation($reviewId, $lang) !== null) continue; // sudah ada
        rtSaveTranslation($reviewId, $lang, rtAutoTranslate($comment, $sourceLang, $lang), 'auto');
        $count++;
    }
    return $count;
}

/**
 * Teks review untuk user dalam bahasa $lang dengan fallback:
 * review asli (jika lang cocok) → terjemahan → comment asli.
 * Return ['text', 'lang', 'translated'].
 */
function rtDisplayText(array $review, string $lang): array {
    if (($review['lang'] ?? '') === $lang) {
        return ['text' => $review['comment'], 'lang' => $lang, 'translated' => false];
    }
    $tr = rtGetTranslation((int)$review['id'], $lang);
    if ($tr !== null) {
        return ['text' => $tr, 'lang' => $lang, 'translated' => true];
    }
    return ['text' => $review['comment'], 'lang' => $review['lang'] ?? '', 'translated' => false];
}
