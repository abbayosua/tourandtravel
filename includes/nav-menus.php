<?php
/**
 * includes/nav-menus.php — menu header dinamis (satu sumber, dipakai semua header).
 * Disimpan di tabel nav_menus (migration database/migrate-nav-menus.sql).
 * Fallback array bila tabel belum ada / DB error — tampilan tak pernah kosong.
 */
function navMenuDefaults() {
    return [
        ['label' => 'Tour', 'url' => 'tours.php', 'icon' => 'bi-map', 'match_key' => 'tour', 'show_in_tabs' => 1, 'show_in_menu' => 1],
        ['label' => 'Hotel', 'url' => 'hotels.php', 'icon' => 'bi-building', 'match_key' => 'hotel', 'show_in_tabs' => 1, 'show_in_menu' => 1],
        ['label' => 'Pesawat', 'url' => 'flights.php', 'icon' => 'bi-airplane', 'match_key' => 'flight', 'show_in_tabs' => 1, 'show_in_menu' => 1],
        ['label' => 'Ferry', 'url' => 'ferries.php', 'icon' => 'bi-ship', 'match_key' => 'ferri', 'show_in_tabs' => 0, 'show_in_menu' => 1],
        ['label' => 'Rental', 'url' => 'rental-cars.php', 'icon' => 'bi-car-front', 'match_key' => 'rental-car', 'show_in_tabs' => 0, 'show_in_menu' => 1],
        ['label' => 'Kereta', 'url' => 'trains.php', 'icon' => 'bi-train-front', 'match_key' => 'train', 'show_in_tabs' => 0, 'show_in_menu' => 1],
        ['label' => 'Atraksi', 'url' => 'attractions.php', 'icon' => 'bi-signpost-2', 'match_key' => 'attraction', 'show_in_tabs' => 0, 'show_in_menu' => 1],
        ['label' => 'Transfer', 'url' => 'transfers.php', 'icon' => 'bi-arrow-left-right', 'match_key' => 'transfer', 'show_in_tabs' => 0, 'show_in_menu' => 1],
        ['label' => 'eSIM', 'url' => 'esim.php', 'icon' => 'bi-sim', 'match_key' => 'esim', 'show_in_tabs' => 0, 'show_in_menu' => 1],
    ];
}

function getNavMenus() {
    try {
        $rows = db()->query("SELECT label, url, icon, match_key, show_in_tabs, show_in_menu FROM nav_menus WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
        if (is_array($rows) && count($rows)) return $rows;
    } catch (Throwable $e) {}
    return navMenuDefaults();
}

function navMenuIsActive(array $item, string $page): bool {
    $mk = trim($item['match_key'] ?? '');
    if ($mk !== '' && stripos($page, $mk) !== false) return true;
    if ($page === 'index.php' && ($item['url'] ?? '') === 'tours.php') return true;
    return false;
}
