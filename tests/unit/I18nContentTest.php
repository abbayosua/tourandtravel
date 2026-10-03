<?php
/**
 * I18nContentTest — regresi kelengkapan terjemahan KONTEN DB per-bahasa.
 *
 * Setiap item AKTIF di tabel konten (yang dirender via tContent/tContentLang)
 * harus punya nilai en DAN zh untuk field yang punya kolom {field}_en/{field}_zh;
 * kalau kosong, halaman akan jatuh ke bahasa sumber (bocor).
 *
 * Melengkapi I18nKeysTest (yang menguji key t()).
 */

/** Tabel konten: [tabel, kolom-filter-aktif (null = semua), [field...]]. */
function i18nContentConfigs(): array {
    return [
        ['tours', 'is_active = 1', ['title', 'description', 'category', 'location_city', 'highlights', 'includes', 'excludes', 'flight_info', 'meeting_point', 'important_notes', 'route_cities']],
        ['hotels', 'is_active = 1', ['name', 'description']],
        ['hotel_rooms', 'is_active = 1', ['name']],
        ['attractions', 'is_active = 1', ['name', 'description']],
        ['connectivity_products', 'is_active = 1', ['name', 'description']],
        ['transfers', 'is_active = 1', ['name', 'description']],
        ['posts', "status = 'published'", ['title', 'excerpt', 'body']],
    ];
}

function testActiveContentHasEnAndZh(): void {
    $bad = [];
    foreach (i18nContentConfigs() as [$table, $where, $fields]) {
        $rows = db()->query("SELECT * FROM `$table` WHERE $where")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            foreach ($fields as $f) {
                if (trim((string)($r[$f] ?? '')) === '') continue;
                foreach (['en', 'zh'] as $l) {
                    if (trim((string)($r[$f . '_' . $l] ?? '')) === '') {
                        $bad[] = "$table#{$r['id']}.{$f}_$l";
                    }
                }
            }
        }
    }
    sort($bad);
    assertSame([], $bad, 'Konten aktif tanpa terjemahan en/zh');
}

function testActiveTourItinerariesHaveEnAndZh(): void {
    // itineraries tidak punya is_active; aktif bila tour induknya aktif.
    $rows = db()->query("SELECT i.* FROM itineraries i JOIN tours t ON i.tour_id = t.id WHERE t.is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
    $bad = [];
    foreach ($rows as $r) {
        foreach (['title', 'description', 'meals', 'accommodation'] as $f) {
            if (trim((string)($r[$f] ?? '')) === '') continue;
            foreach (['en', 'zh'] as $l) {
                if (trim((string)($r[$f . '_' . $l] ?? '')) === '') $bad[] = "itineraries#{$r['id']}.{$f}_$l";
            }
        }
    }
    sort($bad);
    assertSame([], $bad, 'Itinerary tour aktif tanpa terjemahan en/zh');
}
