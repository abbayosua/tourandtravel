<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php';

header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['unread' => 0, 'items' => []]); exit; }

$uid = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

if ($action === 'mark_read' && isset($_POST['id'])) {
    db()->prepare("UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?")->execute([(int)$_POST['id'], $uid]);
}
echo json_encode(['unread' => getUnreadCount($uid)]);
