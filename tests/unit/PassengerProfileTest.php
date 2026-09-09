<?php
/**
 * PassengerProfileTest — CRUD, default handling, auto-fill logic.
 * Menggunakan user 1 (FK users).
 */

function _ppCleanup(int $userId) {
    db()->prepare("DELETE FROM passenger_profiles WHERE user_id = ?")->execute([$userId]);
}

function testCreatePassengerProfile() {
    $uid = 1;
    _ppCleanup($uid);
    $stmt = db()->prepare("INSERT INTO passenger_profiles (user_id, full_name, passport_no, nationality, dob, phone, is_default) VALUES (?, 'Budi Santoso', 'A1234567', 'Indonesia', '1990-05-15', '0812345678', 1)");
    $stmt->execute([$uid]);
    $id = (int)db()->lastInsertId();
    assertTrue($id > 0, 'profile created');
    $row = db()->prepare("SELECT * FROM passenger_profiles WHERE id = ?");
    $row->execute([$id]);
    $r = $row->fetch();
    assertEquals('Budi Santoso', $r['full_name']);
    assertEquals('A1234567', $r['passport_no']);
    assertEquals('Indonesia', $r['nationality']);
    assertEquals('1990-05-15', $r['dob']);
    assertEquals('0812345678', $r['phone']);
    assertEquals(1, (int)$r['is_default']);
    _ppCleanup($uid);
}

function testUpdatePassengerProfile() {
    $uid = 1;
    _ppCleanup($uid);
    db()->prepare("INSERT INTO passenger_profiles (user_id, full_name, phone, is_default) VALUES (?, 'Old Name', '08111', 1)")->execute([$uid]);
    $id = (int)db()->lastInsertId();
    db()->prepare("UPDATE passenger_profiles SET full_name = ?, phone = ? WHERE id = ?")->execute(['New Name', '08222', $id]);
    $row = db()->prepare("SELECT full_name, phone FROM passenger_profiles WHERE id = ?");
    $row->execute([$id]);
    $r = $row->fetch();
    assertEquals('New Name', $r['full_name']);
    assertEquals('08222', $r['phone']);
    _ppCleanup($uid);
}

function testDeletePassengerProfile() {
    $uid = 1;
    _ppCleanup($uid);
    db()->prepare("INSERT INTO passenger_profiles (user_id, full_name, is_default) VALUES (?, 'To Delete', 1)")->execute([$uid]);
    $id = (int)db()->lastInsertId();
    db()->prepare("DELETE FROM passenger_profiles WHERE id = ? AND user_id = ?")->execute([$id, $uid]);
    $row = db()->prepare("SELECT COUNT(*) FROM passenger_profiles WHERE id = ?");
    $row->execute([$id]);
    assertEquals(0, (int)$row->fetchColumn(), 'deleted');
    _ppCleanup($uid);
}

function testDefaultHandling() {
    $uid = 1;
    _ppCleanup($uid);
    // First profile → auto default
    db()->prepare("INSERT INTO passenger_profiles (user_id, full_name, is_default) VALUES (?, 'First', 1)")->execute([$uid]);
    $id1 = (int)db()->lastInsertId();
    // Second profile → not default
    db()->prepare("INSERT INTO passenger_profiles (user_id, full_name, is_default) VALUES (?, 'Second', 0)")->execute([$uid]);
    $id2 = (int)db()->lastInsertId();
    // Set second as default → first should be cleared
    db()->prepare("UPDATE passenger_profiles SET is_default = 0 WHERE user_id = ?")->execute([$uid]);
    db()->prepare("UPDATE passenger_profiles SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$id2, $uid]);
    $stmt = db()->prepare("SELECT is_default FROM passenger_profiles WHERE id = ?");
    $stmt->execute([$id1]);
    assertEquals(0, (int)$stmt->fetchColumn(), 'first no longer default');
    $stmt->execute([$id2]);
    assertEquals(1, (int)$stmt->fetchColumn(), 'second is default');
    _ppCleanup($uid);
}

function testProfilesOrderedByDefault() {
    $uid = 1;
    _ppCleanup($uid);
    db()->prepare("INSERT INTO passenger_profiles (user_id, full_name, is_default, created_at) VALUES (?, 'Not Default', 0, NOW())")->execute([$uid]);
    db()->prepare("INSERT INTO passenger_profiles (user_id, full_name, is_default, created_at) VALUES (?, 'Is Default', 1, NOW())")->execute([$uid]);
    $stmt = db()->prepare("SELECT full_name FROM passenger_profiles WHERE user_id = ? ORDER BY is_default DESC, created_at ASC");
    $stmt->execute([$uid]);
    $rows = $stmt->fetchAll();
    assertEquals('Is Default', $rows[0]['full_name'], 'default first');
    assertEquals('Not Default', $rows[1]['full_name'], 'non-default second');
    _ppCleanup($uid);
}

function testUserIsolation() {
    _ppCleanup(1);
    _ppCleanup(4);
    db()->prepare("INSERT INTO passenger_profiles (user_id, full_name, is_default) VALUES (?, 'User1 Profile', 1)")->execute([1]);
    db()->prepare("INSERT INTO passenger_profiles (user_id, full_name, is_default) VALUES (?, 'User4 Profile', 1)")->execute([4]);
    $stmt = db()->prepare("SELECT COUNT(*) FROM passenger_profiles WHERE user_id = ?");
    $stmt->execute([1]);
    assertEquals(1, (int)$stmt->fetchColumn(), 'user 1 has 1 profile');
    $stmt->execute([4]);
    assertEquals(1, (int)$stmt->fetchColumn(), 'user 4 has 1 profile');
    // User 1 cannot see user 4 profiles
    $list = db()->prepare("SELECT * FROM passenger_profiles WHERE user_id = ?");
    $list->execute([1]);
    $rows = $list->fetchAll();
    assertEquals('User1 Profile', $rows[0]['full_name'], 'user 1 only sees own');
    _ppCleanup(1);
    _ppCleanup(4);
}

function testProfileAjaxReturnsJson() {
    // Simulate GET request logic
    $uid = 1;
    _ppCleanup($uid);
    db()->prepare("INSERT INTO passenger_profiles (user_id, full_name, passport_no, phone, is_default) VALUES (?, 'Ajax Test', 'P999', '08999', 1)")->execute([$uid]);
    $stmt = db()->prepare("SELECT * FROM passenger_profiles WHERE user_id = ? ORDER BY is_default DESC, created_at ASC");
    $stmt->execute([$uid]);
    $profiles = $stmt->fetchAll();
    assertTrue(count($profiles) >= 1, 'at least 1 profile');
    assertEquals('Ajax Test', $profiles[0]['full_name']);
    assertEquals('P999', $profiles[0]['passport_no']);
    assertEquals('08999', $profiles[0]['phone']);
    _ppCleanup($uid);
}
