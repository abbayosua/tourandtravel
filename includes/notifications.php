<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/** Tambah notifikasi untuk user (tidak pernah throw). */
function addNotification(int $userId, string $type, string $title, string $body = '', ?string $link = null): void {
    try {
        db()->prepare("INSERT INTO notifications (user_id, type, title, body, link) VALUES (?, ?, ?, ?, ?)")
            ->execute([$userId, $type, $title, $body, $link]);
    } catch (Throwable $e) {}
}

function getUnreadCount(int $userId): int {
    $st = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL");
    $st->execute([$userId]);
    return (int)$st->fetchColumn();
}

function getNotifications(int $userId, int $limit = 30): array {
    $st = db()->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT " . (int)$limit);
    $st->execute([$userId]);
    return $st->fetchAll();
}
