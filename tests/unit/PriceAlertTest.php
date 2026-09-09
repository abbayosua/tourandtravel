<?php
/**
 * PriceAlertTest — create alert, price drop detection, notification trigger.
 * Menggunakan tour 61 (ada di seed price_calendar), user 1 (FK).
 */

require_once __DIR__ . '/../../includes/price-alert-checker.php';

function _paCleanup(int $userId) {
    db()->prepare("DELETE FROM price_alerts WHERE user_id = ?")->execute([$userId]);
    db()->prepare("DELETE FROM notifications WHERE user_id = ? AND type = 'price_alert'")->execute([$userId]);
}

function testCreatePriceAlert() {
    $uid = 1;
    _paCleanup($uid);
    $stmt = db()->prepare("INSERT INTO price_alerts (user_id, item_type, item_id, target_price, currency, active) VALUES (?, 'tour', 61, 100000, 'IDR', 1)");
    $stmt->execute([$uid]);
    $id = (int)db()->lastInsertId();
    assertTrue($id > 0, 'alert created');
    $row = db()->prepare("SELECT * FROM price_alerts WHERE id = ?");
    $row->execute([$id]);
    $r = $row->fetch();
    assertEquals('tour', $r['item_type']);
    assertEquals(61, (int)$r['item_id']);
    assertEquals(100000.0, (float)$r['target_price']);
    assertEquals(1, (int)$r['active']);
    _paCleanup($uid);
}

function testCreateAlertIdempotent() {
    $uid = 1;
    _paCleanup($uid);
    // Insert twice same user+item → ON DUPLICATE KEY UPDATE (upsert)
    db()->prepare("INSERT INTO price_alerts (user_id, item_type, item_id, target_price, currency, active) VALUES (?, 'tour', 61, 200000, 'IDR', 1)")->execute([$uid]);
    db()->prepare("INSERT INTO price_alerts (user_id, item_type, item_id, target_price, currency, active) VALUES (?, 'tour', 61, 300000, 'IDR', 1) ON DUPLICATE KEY UPDATE target_price = VALUES(target_price)")->execute([$uid]);
    $stmt = db()->prepare("SELECT COUNT(*) FROM price_alerts WHERE user_id = ? AND item_type = 'tour' AND item_id = 61");
    $stmt->execute([$uid]);
    assertEquals(1, (int)$stmt->fetchColumn(), 'only 1 alert after upsert');
    _paCleanup($uid);
}

function testPriceDropDetectionHighTarget() {
    $uid = 1;
    _paCleanup($uid);
    // Set target very high (99999999) so current price is below it
    db()->prepare("INSERT INTO price_alerts (user_id, item_type, item_id, target_price, currency, active) VALUES (?, 'tour', 61, 99999999, 'IDR', 1)")->execute([$uid]);
    $result = checkPriceAlerts();
    assertTrue($result['checked'] >= 1, 'at least 1 alert checked');
    assertTrue($result['notified'] >= 1, 'notification triggered (price < target)');
    // Verify notification created
    $n = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND type = 'price_alert'");
    $n->execute([$uid]);
    assertTrue((int)$n->fetchColumn() >= 1, 'notification in DB');
    // Verify notified_at set
    $na = db()->prepare("SELECT notified_at FROM price_alerts WHERE user_id = ? AND item_type = 'tour' AND item_id = 61");
    $na->execute([$uid]);
    $row = $na->fetch();
    assertTrue($row['notified_at'] !== null, 'notified_at is set');
    _paCleanup($uid);
}

function testPriceNotDroppedLowTarget() {
    $uid = 1;
    _paCleanup($uid);
    // Set target very low (1) so current price is above it
    db()->prepare("INSERT INTO price_alerts (user_id, item_type, item_id, target_price, currency, active) VALUES (?, 'tour', 61, 1, 'IDR', 1)")->execute([$uid]);
    $before = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND type = 'price_alert'");
    $before->execute([$uid]);
    $cntBefore = (int)$before->fetchColumn();
    $result = checkPriceAlerts();
    $after = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND type = 'price_alert'");
    $after->execute([$uid]);
    $cntAfter = (int)$after->fetchColumn();
    assertEquals($cntBefore, $cntAfter, 'no new notification when price > target');
    _paCleanup($uid);
}

function testInactiveAlertSkipped() {
    $uid = 1;
    _paCleanup($uid);
    db()->prepare("INSERT INTO price_alerts (user_id, item_type, item_id, target_price, currency, active) VALUES (?, 'tour', 61, 99999999, 'IDR', 0)")->execute([$uid]);
    $result = checkPriceAlerts();
    // Should check 0 active alerts
    assertEquals(0, $result['notified'], 'inactive alert not checked');
    _paCleanup($uid);
}

function testNotificationNotDuplicatedSameDay() {
    $uid = 1;
    _paCleanup($uid);
    // First trigger → should notify
    db()->prepare("INSERT INTO price_alerts (user_id, item_type, item_id, target_price, currency, active) VALUES (?, 'tour', 61, 99999999, 'IDR', 1)")->execute([$uid]);
    $result1 = checkPriceAlerts();
    assertTrue($result1['notified'] >= 1, 'first run notifies');
    $afterFirst = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND type = 'price_alert'");
    $afterFirst->execute([$uid]);
    $cntAfterFirst = (int)$afterFirst->fetchColumn();
    // Second run → should NOT notify again (notified_at already set)
    $result2 = checkPriceAlerts();
    $afterSecond = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND type = 'price_alert'");
    $afterSecond->execute([$uid]);
    $cntAfterSecond = (int)$afterSecond->fetchColumn();
    assertEquals($cntAfterFirst, $cntAfterSecond, 'no new notification on second run');
    _paCleanup($uid);
}
