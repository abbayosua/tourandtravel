<?php
/**
 * Loader hero slides per fokus website.
 * - Query hero_slides: aktif, (focus = $siteFocus ATAU focus = 'all'), urut sort_order.
 * - Kosong → fallback array bawaan (3 slide unsplash) agar homepage tak pernah polos.
 * - Return array ['image', 'alt', 'title', 'subtitle', 'cta_text', 'cta_link'].
 */
function getHeroSlides($siteFocus) {
    $default = [
        ['image' => 'https://images.unsplash.com/photo-1488085061387-422e29b40080?w=1920&q=80', 'alt' => 'Bali Beach', 'title' => null, 'subtitle' => null, 'cta_text' => null, 'cta_link' => null],
        ['image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1920&q=80', 'alt' => 'Beach Paradise', 'title' => null, 'subtitle' => null, 'cta_text' => null, 'cta_link' => null],
        ['image' => 'https://images.unsplash.com/photo-1530521954074-e64f6810b32d?w=1920&q=80', 'alt' => 'Travel Destination', 'title' => null, 'subtitle' => null, 'cta_text' => null, 'cta_link' => null],
    ];

    try {
        $stmt = db()->prepare("SELECT * FROM hero_slides WHERE is_active = 1 AND (focus = ? OR focus = 'all') ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$siteFocus]);
        $rows = $stmt->fetchAll();
        if (!count($rows)) return $default;
        return array_map(fn($s) => [
            'image'     => $s['image_url'],
            'alt'       => $s['title'] ?? '',
            'title'     => $s['title'] ?? null,
            'subtitle'  => $s['subtitle'] ?? null,
            'cta_text'  => $s['cta_text'] ?? null,
            'cta_link'  => $s['cta_link'] ?? null,
        ], $rows);
    } catch (Throwable $e) {
        return $default;
    }
}
