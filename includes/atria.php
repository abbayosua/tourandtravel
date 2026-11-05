<?php
/**
 * includes/atria.php — wrapper LLM OpenAI-compatible untuk AI AUTO translate.
 *
 * Riwayat: dulu Atria (api.atria-asi.ai). Sejak kuota Atria habis, default
 * pindah ke Meta (api.meta.ai/v1). Nama file & fungsi dipertahankan agar
 * pemanggil (tour-translate-ai.php, i18nFillEmptyTranslated) tak berubah.
 *
 * Konfigurasi via env (lihat .env.example):
 *   AI_API_KEY    (wajib) — key Meta/OpenAI-compatible
 *   AI_BASE_URL   (default https://api.meta.ai/v1)
 *   AI_MODEL      (default muse-spark-1.3-contributor)
 * Alias lama ATRIA_* tetap dibaca sebagai fallback.
 */

if (!defined('AI_BASE_URL')) {
    define('AI_BASE_URL', getenv('AI_BASE_URL') ?: getenv('ATRIA_BASE_URL') ?: 'https://api.meta.ai/v1');
}
if (!defined('AI_MODEL')) {
    define('AI_MODEL', getenv('AI_MODEL') ?: getenv('ATRIA_MODEL') ?: 'muse-spark-1.3-contributor');
}
if (!defined('AI_API_KEY')) {
    define('AI_API_KEY', getenv('AI_API_KEY') ?: getenv('ATRIA_API_KEY') ?: '');
}
if (!defined('ATRIA_BASE_URL')) define('ATRIA_BASE_URL', AI_BASE_URL);
if (!defined('ATRIA_MODEL')) define('ATRIA_MODEL', AI_MODEL);
if (!defined('ATRIA_API_KEY')) define('ATRIA_API_KEY', AI_API_KEY);

/** Label bahasa manusia untuk prompt. */
function atriaLangName(string $code): string {
    return ['id' => 'Indonesian', 'en' => 'English', 'zh' => 'Simplified Mandarin Chinese (简体中文)'][$code] ?? $code;
}

/**
 * 1x chat completion ke Atria. Return isi pesan assistant.
 * @throws RuntimeException bila key kosong / HTTP / API error.
 */
function atriaChat(array $messages, array $opts = []): string {
    $key = defined('AI_API_KEY') ? AI_API_KEY : '';
    if ($key === '') {
        throw new RuntimeException('AI_API_KEY belum dikonfigurasi (isi .env).');
    }
    // Respons model bisa 20-60 dtk; jangan sampai dibunuh max_execution_time web (30 dtk).
    if (function_exists('set_time_limit')) @set_time_limit(150);
    $payload = [
        'model' => $opts['model'] ?? AI_MODEL,
        'messages' => $messages,
        'temperature' => $opts['temperature'] ?? 0.2,
    ];
    if (isset($opts['max_tokens'])) $payload['max_tokens'] = (int)$opts['max_tokens'];

    $ch = curl_init(rtrim(AI_BASE_URL, '/') . '/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => $opts['timeout'] ?? 60,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);
    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno !== 0 || $raw === false) {
        throw new RuntimeException(sprintf(t('AI API tidak terjangkau (curl errno %s).'), $errno));
    }
    $data = json_decode($raw, true);
    if ($http < 200 || $http >= 300) {
        $msg = is_array($data) ? ($data['error']['message'] ?? $data['message'] ?? $raw) : $raw;
        throw new RuntimeException('AI API error HTTP ' . $http . ': ' . mb_substr((string)$msg, 0, 300));
    }
    $content = $data['choices'][0]['message']['content'] ?? null;
    if (!is_string($content) || trim($content) === '') {
        throw new RuntimeException('AI API mengembalikan respons kosong.');
    }
    return $content;
}

/** Kupas ```json fence bila model membungkus output. */
function atriaStripCodeFence(string $s): string {
    $s = trim($s);
    if (preg_match('/^```(?:json)?\s*(.*)\s*```$/s', $s, $m)) return trim($m[1]);
    return $s;
}

/**
 * Terjemahkan batch field tour dari 1 bahasa sumber ke bahasa target.
 *
 * @param array $sourceFields ['title' => '...', 'description' => '...', ...] (kosong dilewati)
 * @param string $sourceLang 'id'|'en'|'zh'
 * @param array $targetLangs subset dari ['id','en','zh'] tanpa source
 * @return array [lang => [field => terjemahan]] — hanya field non-kosong
 * @throws RuntimeException bila API gagal / JSON tak valid
 */
function atriaTranslateTour(array $sourceFields, string $sourceLang, array $targetLangs): array {
    $sourceFields = array_filter(
        array_map(fn($v) => trim((string)$v), $sourceFields),
        fn($v) => $v !== ''
    );
    $targetLangs = array_values(array_unique(array_filter(
        $targetLangs,
        fn($l) => in_array($l, ['id', 'en', 'zh'], true) && $l !== $sourceLang
    )));
    if (!$sourceFields || !$targetLangs) return [];

    // Batasi total agar 1 request tetap aman (~hemat token & timeout)
    $total = array_sum(array_map('mb_strlen', $sourceFields));
    if ($total > 12000) {
        throw new RuntimeException(sprintf(t('Teks terlalu panjang untuk sekali translate AI (%s karakter, maks 12000).'), $total));
    }

    $targets = implode(', ', array_map('atriaLangName', $targetLangs));
    $system = 'You are a professional travel-content translator. Translate the given source texts '
        . 'written in ' . atriaLangName($sourceLang) . ' into these target languages: ' . $targets . '. '
        . 'Rules: return ONLY strict JSON (no markdown, no explanation) shaped as '
        . '{"<lang>": {"<field>": "<translation>"}} using language codes '
        . implode('/', $targetLangs) . ' and the exact same field names as input. '
        . 'Keep line breaks (one item per line stays one per line). Keep proper nouns '
        . '(hotel names, city names, airline codes) untranslated unless a standard '
        . 'translation exists. NEVER convert currencies, numbers, dates, times, phone '
        . 'numbers, URLs, emails, or codes — copy them verbatim (e.g. "Rp 15.000.000" '
        . 'stays as-is, "GA-123" stays, "+62 812-3456" stays). Tone: marketing-friendly '
        . 'tour brochure. For zh use Simplified Chinese (简体中文).';
    $user = 'Source language: ' . $sourceLang . "\n"
        . 'Target languages: ' . implode(',', $targetLangs) . "\n"
        . 'Translate this JSON: ' . json_encode($sourceFields, JSON_UNESCAPED_UNICODE);

    $raw = atriaChat(
        [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $user]],
        ['temperature' => 0.2, 'timeout' => 90]
    );
    $decoded = json_decode(atriaStripCodeFence($raw), true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Respons AI bukan JSON valid.');
    }
    $out = [];
    foreach ($targetLangs as $tl) {
        if (!isset($decoded[$tl]) || !is_array($decoded[$tl])) continue;
        foreach ($sourceFields as $field => $_) {
            $v = trim((string)($decoded[$tl][$field] ?? ''));
            // Model kadang escape newline JSON jadi teks literal "\n" — kembalikan.
            if ($v !== '' && str_contains($v, '\\n')) {
                $v = str_replace(["\r\\n", '\\r\\n', '\\n'], "\n", $v);
                $v = trim(preg_replace("/\n{3,}/", "\n\n", $v));
            }
            if ($v !== '') $out[$tl][$field] = $v;
        }
    }
    if (!$out) {
        throw new RuntimeException(t('AI tidak mengembalikan terjemahan.'));
    }
    return $out;
}
