<?php
/**
 * ItineraryTest — CRUD itinerary builder langsung via DB (logika yang dipakai
 * itinerary-ajax.php): create + day 1, add_day sequence, add_item, delete cascade,
 * ownership check, list agregat day_count/item_count.
 * User test: id=1 (FK users). Tour test: id=64.
 */

const IT_UID = 1;
const IT_TOUR = 64;

function itCleanup() {
    db()->prepare("DELETE FROM user_itineraries WHERE user_id = ? AND title LIKE 'IT-TEST%'")->execute([IT_UID]);
}

function itCreate(string $title): int {
    db()->prepare("INSERT INTO user_itineraries (user_id, title, start_date) VALUES (?, ?, NULL)")->execute([IT_UID, $title]);
    $id = (int)db()->lastInsertId();
    db()->prepare("INSERT INTO user_itinerary_days (itinerary_id, day_number) VALUES (?, 1)")->execute([$id]);
    return $id;
}

function testCreateItineraryAutoDayOne() {
    itCleanup();
    $id = itCreate('IT-TEST-create');
    $stmt = db()->prepare("SELECT COUNT(*) FROM user_itinerary_days WHERE itinerary_id = ? AND day_number = 1");
    $stmt->execute([$id]);
    assertEquals(1, $stmt->fetchColumn(), 'create → otomatis day 1');
    itCleanup();
}

function testAddDaySequence() {
    itCleanup();
    $id = itCreate('IT-TEST-days');
    db()->prepare("INSERT INTO user_itinerary_days (itinerary_id, day_number) VALUES (?, 2)")->execute([$id]);
    $stmt = db()->prepare("SELECT COALESCE(MAX(day_number), 0) + 1 FROM user_itinerary_days WHERE itinerary_id = ?");
    $stmt->execute([$id]);
    assertEquals(3, $stmt->fetchColumn(), 'next day number = 3');
    // duplicate day_number ditolak oleh unique key
    try {
        db()->prepare("INSERT INTO user_itinerary_days (itinerary_id, day_number) VALUES (?, 1)")->execute([$id]);
        assertTrue(false, 'duplikat day_number harus gagal');
    } catch (PDOException $e) {
        assertMatches('/23000|Duplicate/', $e->getMessage(), 'unique key uq_uitin_day');
    }
    itCleanup();
}

function testAddItemToDay() {
    itCleanup();
    $id = itCreate('IT-TEST-item');
    $stmt = db()->prepare("SELECT id FROM user_itinerary_days WHERE itinerary_id = ? AND day_number = 1");
    $stmt->execute([$id]);
    $dayId = (int)$stmt->fetchColumn();
    db()->prepare("INSERT INTO user_itinerary_items (day_id, item_type, tour_id, title, time_label) VALUES (?, 'tour', ?, 'Ujung Kulon', '07:00')")->execute([$dayId, IT_TOUR]);
    $stmt = db()->prepare("SELECT item_type, tour_id, title, time_label FROM user_itinerary_items WHERE day_id = ?");
    $stmt->execute([$dayId]);
    $item = $stmt->fetch();
    assertEquals('tour', $item['item_type']);
    assertEquals(IT_TOUR, (int)$item['tour_id']);
    assertEquals('07:00', $item['time_label']);
    itCleanup();
}

function testDeleteItineraryCascades() {
    itCleanup();
    $id = itCreate('IT-TEST-cascade');
    $stmt = db()->prepare("SELECT id FROM user_itinerary_days WHERE itinerary_id = ?");
    $stmt->execute([$id]);
    $dayIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    db()->prepare("INSERT INTO user_itinerary_items (day_id, title) VALUES (?, 'x')")->execute([$dayIds[0]]);
    db()->prepare("DELETE FROM user_itineraries WHERE id = ?")->execute([$id]);
    $stmt = db()->prepare("SELECT COUNT(*) FROM user_itinerary_days WHERE itinerary_id = ?");
    $stmt->execute([$id]);
    assertEquals(0, $stmt->fetchColumn(), 'days ikut terhapus (cascade)');
    $stmt = db()->prepare("SELECT COUNT(*) FROM user_itinerary_items WHERE day_id IN (" . implode(',', $dayIds) . ")");
    $stmt->execute();
    assertEquals(0, $stmt->fetchColumn(), 'items ikut terhapus (cascade)');
}

function testOwnershipQuery() {
    itCleanup();
    $id = itCreate('IT-TEST-own');
    $stmt = db()->prepare("SELECT COUNT(*) FROM user_itineraries WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, IT_UID]);
    assertEquals(1, $stmt->fetchColumn(), 'owner → 1');
    $stmt->execute([$id, 999999]);
    assertEquals(0, $stmt->fetchColumn(), 'bukan owner → 0');
    itCleanup();
}

function testListAggregateCounts() {
    itCleanup();
    $id = itCreate('IT-TEST-agg');
    db()->prepare("INSERT INTO user_itinerary_days (itinerary_id, day_number) VALUES (?, 2)")->execute([$id]);
    $stmt = db()->prepare("SELECT id FROM user_itinerary_days WHERE itinerary_id = ?");
    $stmt->execute([$id]);
    $dayIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    db()->prepare("INSERT INTO user_itinerary_items (day_id, title) VALUES (?, 'a')")->execute([$dayIds[0]]);
    db()->prepare("INSERT INTO user_itinerary_items (day_id, title) VALUES (?, 'b')")->execute([$dayIds[1]]);
    $stmt = db()->prepare("SELECT i.*, (SELECT COUNT(*) FROM user_itinerary_days d WHERE d.itinerary_id = i.id) AS day_count, (SELECT COUNT(*) FROM user_itinerary_days d JOIN user_itinerary_items it ON it.day_id = d.id WHERE d.itinerary_id = i.id) AS item_count FROM user_itineraries i WHERE i.user_id = ? AND i.id = ?");
    $stmt->execute([IT_UID, $id]);
    $row = $stmt->fetch();
    assertEquals(2, (int)$row['day_count'], 'day_count = 2');
    assertEquals(2, (int)$row['item_count'], 'item_count = 2');
    itCleanup();
}
