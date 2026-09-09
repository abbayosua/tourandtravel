<?php
/**
 * TierBadgeTest — getTierBadgeInfo: struktur, join user_tiers, cache per session,
 * bust saat points berubah, sad path user invalid, render markup header.
 * User test: id=1 (FK users). Selalu kembalikan tier ke explorer.
 */

const TB_UID = 1;

function tbResetCache() {
    unset($_SESSION['tier_badge']);
}

function tbSetTier(string $tier) {
    db()->prepare("UPDATE users SET tier = ? WHERE id = ?")->execute([$tier, TB_UID]);
}

function testBadgeStructureForUser() {
    tbResetCache();
    $info = getTierBadgeInfo(TB_UID);
    foreach (['tier', 'display_name', 'icon', 'color', 'points'] as $k) {
        assertTrue(array_key_exists($k, $info), "key $k harus ada");
    }
    assertTrue(in_array($info['tier'], ['explorer', 'silver', 'gold', 'platinum']), 'tier valid');
    assertTrue(is_int($info['points']), 'points integer');
    assertTrue($info['points'] >= 0, 'points >= 0');
}

function testBadgeReflectsTierFromUserTiers() {
    tbResetCache();
    tbSetTier('gold');
    $info = getTierBadgeInfo(TB_UID);
    assertEquals('gold', $info['tier']);
    assertEquals('Gold', $info['display_name'], 'display_name dari user_tiers');
    assertEquals('bi-trophy', $info['icon']);
    assertEquals('#ffc107', $info['color']);
    tbSetTier('explorer');
    tbResetCache();
    $info = getTierBadgeInfo(TB_UID);
    assertEquals('Explorer', $info['display_name']);
    assertEquals('#6c757d', $info['color']);
}

function testBadgeSessionCacheHit() {
    tbResetCache();
    $info1 = getTierBadgeInfo(TB_UID);
    $info2 = getTierBadgeInfo(TB_UID);
    assertSame($info1, $info2, 'panggilan kedua = cache persis sama (same instance array)');
}

function testBadgeCacheBustOnRecordPoints() {
    tbResetCache();
    db()->prepare("DELETE FROM points_ledger WHERE booking_code = 'TB-BUST'")->execute();
    $before = getTierBadgeInfo(TB_UID);
    recordPoints(TB_UID, 7, 'test', null, null, 'TB-BUST');
    $after = getTierBadgeInfo(TB_UID);
    assertEquals($before['points'] + 7, $after['points'], 'cache bust → saldo baru terlihat');
    db()->prepare("DELETE FROM points_ledger WHERE user_id = ? AND booking_code = 'TB-BUST'")->execute([TB_UID]);
    tbResetCache();
    $guest = getTierBadgeInfo(99999999);
    assertTrue(empty($guest), 'invalid user tetap kosong');
}

function testHeaderRendersBadgeMarkup() {
    tbResetCache();
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['user_id'] = TB_UID;
    $_SESSION['user_name'] = 'Tester';
    ob_start();
    require __DIR__ . '/../../includes/header-klook.php';
    $html = ob_get_clean();
    assertMatches('/headerTierBadge/', $html, 'badge tier ada di header');
    assertMatches('/headerPoints/', $html, 'saldo poin ada di header');
    assertMatches('/Poin Saya/', $html, 'link my-points ada');
    assertMatches('/tier-badge/', $html, 'class tier-badge ada');
    // Guest → tanpa badge
    unset($_SESSION['user_id']);
    ob_start();
    require __DIR__ . '/../../includes/header-klook.php';
    $html = ob_get_clean();
    assertTrue(strpos($html, 'headerTierBadge') === false, 'guest → tidak ada badge');
}
