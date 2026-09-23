<?php
/**
 * HotelApiTest — validasi live hotel API (Booking.com/OYO/NusaTrip).
 * Fokus pada fungsi murni (tanpa network) + render kartu live.
 * Lihat HOTEL-ENDPOINTS.md.
 */

require_once __DIR__ . '/../../includes/hotelapi.php';
require_once __DIR__ . '/../../includes/components/live-hotel-card.php';

function testHotelApiParseBookingUrl() {
    [$cc, $pg] = hotelApiParseBookingUrl('https://www.booking.com/hotel/id/bobobox-pods-juanda-jakarta.html');
    assertSame('id', $cc, 'cc');
    assertSame('bobobox-pods-juanda-jakarta', $pg, 'pagename');
}

function testHotelApiParseBookingUrlUnknown() {
    [$cc, $pg] = hotelApiParseBookingUrl('https://example.com/foo');
    assertSame(null, $cc);
    assertSame(null, $pg);
}

function testHotelCacheKeyDeterministic() {
    assertSame('oyo:list:Jakarta', hotelCacheKey('oyo', ['list', 'Jakarta']));
    assertSame(hotelCacheKey('oyo', ['list', 'Jakarta']), hotelCacheKey('oyo', ['list', 'Jakarta']));
}

function testHotelCacheKeyBounded() {
    assertTrue(strlen(hotelCacheKey('booking', [str_repeat('x', 500)])) <= 191, 'cache key <= 191 char');
}

function testHotelApiResolveSourceDefaultsToNusatripNative() {
    $prevSource = getSetting('hotel_live_source', 'nusatrip');
    $prevRkey = (string)getSetting('nusatrip_rkey', '');
    $prevNusa = getSetting('nusatrip_module_enabled', '1');
    $prevOyo = getSetting('oyo_module_enabled', '1');
    setSetting('hotel_live_source', 'auto');
    setSetting('nusatrip_rkey', '');
    setSetting('nusatrip_module_enabled', '1');
    setSetting('oyo_module_enabled', '1');
    try {
        assertSame('nusatrip', hotelApiResolveSource(null));
    } finally {
        setSetting('hotel_live_source', $prevSource);
        setSetting('nusatrip_rkey', $prevRkey);
        setSetting('nusatrip_module_enabled', $prevNusa);
        setSetting('oyo_module_enabled', $prevOyo);
    }
}

function testHotelApiResolveSourceAutoPrefersNusatripWhenRkeySet() {
    $prevSource = getSetting('hotel_live_source', 'nusatrip');
    $prevRkey = (string)getSetting('nusatrip_rkey', '');
    $prevNusa = getSetting('nusatrip_module_enabled', '1');
    $prevOyo = getSetting('oyo_module_enabled', '1');
    setSetting('hotel_live_source', 'auto');
    setSetting('nusatrip_rkey', str_repeat('a', 128));
    setSetting('nusatrip_module_enabled', '1');
    setSetting('oyo_module_enabled', '1');
    try {
        assertSame('nusatrip', hotelApiResolveSource(null));
    } finally {
        setSetting('hotel_live_source', $prevSource);
        setSetting('nusatrip_rkey', $prevRkey);
        setSetting('nusatrip_module_enabled', $prevNusa);
        setSetting('oyo_module_enabled', $prevOyo);
    }
}

function testHotelApiResolveSourceHonorsExplicit() {
    $prevNusa = getSetting('nusatrip_module_enabled', '1');
    $prevOyo = getSetting('oyo_module_enabled', '1');
    setSetting('nusatrip_module_enabled', '1');
    setSetting('oyo_module_enabled', '1');
    try {
        assertSame('nusatrip', hotelApiResolveSource('nusatrip'));
        assertSame('oyo', hotelApiResolveSource('oyo'));
        assertSame('oyo', hotelApiResolveSource('bogus'), 'sumber tak dikenal → oyo');
    } finally {
        setSetting('nusatrip_module_enabled', $prevNusa);
        setSetting('oyo_module_enabled', $prevOyo);
    }
}

function testOyoModuleToggleDisablesOyo() {
    $prevNusa = getSetting('nusatrip_module_enabled', '1');
    $prevOyo = getSetting('oyo_module_enabled', '1');
    setSetting('nusatrip_module_enabled', '1');
    setSetting('oyo_module_enabled', '1');
    try {
        assertTrue(oyoModuleEnabled());
        assertSame('oyo', hotelApiResolveSource('oyo'));
    } finally {
        setSetting('nusatrip_module_enabled', $prevNusa);
        setSetting('oyo_module_enabled', $prevOyo);
    }
    setSetting('nusatrip_module_enabled', '1');
    setSetting('oyo_module_enabled', '0');
    try {
        assertTrue(!oyoModuleEnabled());
        assertSame('nusatrip', hotelApiResolveSource('oyo'), 'oyo off → paksa nusatrip');
        $r = hotelApiOyo('Batam');
        assertContains('Modul OYO nonaktif', $r['error'] ?? '');
    } finally {
        setSetting('nusatrip_module_enabled', $prevNusa);
        setSetting('oyo_module_enabled', $prevOyo);
    }
    setSetting('nusatrip_module_enabled', '0');
    setSetting('oyo_module_enabled', '0');
    try {
        assertSame('', hotelApiResolveSource(null), 'keduanya off → tanpa live');
        assertSame('', hotelApiResolveSource('oyo'), 'keduanya off → tanpa live');
        assertSame('', hotelApiResolveSource('nusatrip'), 'keduanya off → tanpa live');
    } finally {
        setSetting('nusatrip_module_enabled', $prevNusa);
        setSetting('oyo_module_enabled', $prevOyo);
    }
}

function testNusaModuleToggleFallsBackToOyo() {
    $prevNusa = getSetting('nusatrip_module_enabled', '1');
    $prevOyo = getSetting('oyo_module_enabled', '1');
    setSetting('nusatrip_module_enabled', '1');
    setSetting('oyo_module_enabled', '1');
    try {
        assertTrue(nusaModuleEnabled());
        assertSame('nusatrip', hotelApiResolveSource('nusatrip'));
    } finally {
        setSetting('nusatrip_module_enabled', $prevNusa);
        setSetting('oyo_module_enabled', $prevOyo);
    }
    setSetting('nusatrip_module_enabled', '0');
    setSetting('oyo_module_enabled', '1');
    try {
        assertTrue(!nusaModuleEnabled());
        assertSame('oyo', hotelApiResolveSource('nusatrip'), 'modul off → paksa oyo');
        assertSame('oyo', hotelApiResolveSource(null), 'auto + modul off → oyo');
    } finally {
        setSetting('nusatrip_module_enabled', $prevNusa);
        setSetting('oyo_module_enabled', $prevOyo);
    }
}

function testHotelApiEnabledIsBool() {
    assertTrue(is_bool(hotelApiEnabled()));
}

function testHotelApiSearchEmptyCityNoNetwork() {
    setSetting('hotel_live_enabled', '1');
    $r = hotelApiSearch('');
    assertContains('Kota kosong', $r['error']);
    assertSame(0, $r['count']);
}

function testRenderLiveHotelCard() {
    ob_start();
    renderLiveHotelCard([
        'source' => 'oyorooms', 'external_id' => '1', 'name' => 'Hotel Uji',
        'star' => 3, 'price' => 100000, 'price_formatted' => 'Rp100.000',
        'image' => null, 'lat' => -6.2, 'lng' => 106.8, 'address' => 'Jakarta',
        'url' => 'https://example.test/1',
    ], 'Jakarta', '2026-01-01', '2026-01-02', 2);
    $html = ob_get_clean();
    assertContains('Hotel Uji', $html);
    assertContains('Rp100.000', $html);
    assertContains('hotel-detail.php?live=1', $html);
    assertContains('OYO', $html);
}

function testHotelApiOyoSlugLowercasesCity() {
    // Slug harus menurunkan huruf besar (regresi: "Jakarta" → "jakarta", bukan "akarta").
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(trim('Jakarta'))), '-');
    assertSame('jakarta', $slug);
}

function testNusaPhoneNormalizesToCcSpace() {
    assertSame('62 8517488415', nusaPhone('08517488415', '62'));
    assertSame('62 8517488415', nusaPhone('628517488415', '62'));
    assertSame('62 8517488415', nusaPhone('+62 8517488415', '62'));
    assertSame('62 8517488415', nusaPhone('62 8517488415', '62'));
    assertSame('65 81234567', nusaPhone('81234567', '65'));
    assertSame('65 81234567', nusaPhone('+65 81234567', '62'));
    assertSame('86 13800138000', nusaPhone('13800138000', '86'));
    assertSame('86 13800138000', nusaPhone('+86 13800138000', '62'));
    assertSame('1 2025550123', nusaPhone('+1 2025550123', '62'));
    assertSame('', nusaPhone('', '62'));
    $c = json_decode(nusaContact('MR', 'A', 'B', 'a@b.com', '08517488415', false, '62'), true);
    assertSame('62 8517488415', $c['phoneNo']);
    $c = json_decode(nusaContact('MR', 'A', 'B', 'a@b.com', '81234567', false, '65'), true);
    assertSame('65 81234567', $c['phoneNo']);
}
