<?php
/**
 * WishlistTest — toggle polimorfik + backward-compat tour.
 * Memakai user id=1 (FK user_id → users).
 */
const WL_UID = 1;

function wlCleanup() {
    db()->prepare("DELETE FROM wishlists WHERE user_id = ? AND item_id IN (61, 1, 2, 3, 4)")->execute([WL_UID]);
}

function testToggleTourBackwardCompat() {
    wlCleanup();
    assertEquals('added', toggleWishlistItem(WL_UID, 'tour', 61));
    assertTrue(isWishlisted(WL_UID, 61), 'wrapper tour → true');
    assertTrue(isWishlistedItem(WL_UID, 'tour', 61), 'item tour → true');
    $row = db()->query("SELECT tour_id, item_type, item_id FROM wishlists WHERE user_id = " . WL_UID . " AND item_type='tour' AND item_id=61")->fetch();
    assertEquals(61, (int)$row['tour_id']);
    assertEquals('tour', $row['item_type']);
    $ids = getWishlistIds(WL_UID);
    assertContains(61, $ids);
    assertEquals('removed', toggleWishlistItem(WL_UID, 'tour', 61));
    assertTrue(!isWishlisted(WL_UID, 61));
    wlCleanup();
}

function testToggleHotelAndOtherTypes() {
    wlCleanup();
    assertEquals('added', toggleWishlistItem(WL_UID, 'hotel', 1));
    assertEquals('removed', toggleWishlistItem(WL_UID, 'hotel', 1));
    assertEquals('added', toggleWishlistItem(WL_UID, 'attraction', 4));
    assertEquals('added', toggleWishlistItem(WL_UID, 'esim', 2));
    assertTrue(isWishlistedItem(WL_UID, 'attraction', 4));
    assertEquals('added', toggleWishlistItem(WL_UID, 'tour', 61));
    assertTrue(isWishlisted(WL_UID, 61));
    $items = getUserWishlistItems(WL_UID);
    assertContains(4, $items['attraction']);
    assertContains(2, $items['esim']);
    assertContains(61, $items['tour']);
    assertEquals(0, count($items['hotel']), 'hotel sudah diremove');
    wlCleanup();
}

function testToggleIdempotentInsertIgnore() {
    wlCleanup();
    assertEquals('added', toggleWishlistItem(WL_UID, 'hotel', 2));
    db()->prepare("INSERT IGNORE INTO wishlists (user_id, item_type, item_id, tour_id) VALUES (?, 'hotel', 2, 0)")->execute([WL_UID]);
    assertEquals(1, (int)db()->query("SELECT COUNT(*) FROM wishlists WHERE user_id = " . WL_UID . " AND item_type = 'hotel' AND item_id = 2")->fetchColumn());
    wlCleanup();
}

function testUserAOnlySeesOwnItems() {
    wlCleanup();
    db()->prepare("DELETE FROM wishlists WHERE user_id = 4 AND item_id = 3")->execute();
    toggleWishlistItem(WL_UID, 'hotel', 3);
    assertTrue(!isWishlistedItem(4, 'hotel', 3), 'user lain tidak melihat');
    assertTrue(isWishlistedItem(WL_UID, 'hotel', 3));
    wlCleanup();
    db()->prepare("DELETE FROM wishlists WHERE user_id = 4 AND item_id = 3")->execute();
}
