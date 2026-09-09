<?php
/**
 * itinerary-ajax.php — CRUD itinerary builder via AJAX/POST.
 *
 * POST action=create_itinerary  title, start_date?        → buat itinerary + day 1
 * POST action=add_day           itinerary_id              → tambah hari
 * POST action=add_item          day_id, title, item_type?, tour_id?, hotel_id?, note?, time_label?
 * POST action=delete_item       item_id
 * POST action=delete_itinerary  itinerary_id
 * GET  action=list              → itinerary user + hari + item (JSON)
 * GET  action=get&itinerary_id= → detail satu itinerary (JSON)
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

function itinOwns(int $userId, int $itineraryId): bool {
    $stmt = db()->prepare("SELECT COUNT(*) FROM user_itineraries WHERE id = ? AND user_id = ?");
    $stmt->execute([$itineraryId, $userId]);
    return (bool)$stmt->fetchColumn();
}

function itinDayOwned(int $userId, int $dayId): int {
    $stmt = db()->prepare("SELECT d.itinerary_id FROM user_itinerary_days d JOIN user_itineraries i ON i.id = d.itinerary_id WHERE d.id = ? AND i.user_id = ?");
    $stmt->execute([$dayId, $userId]);
    return (int)($stmt->fetchColumn() ?: 0);
}

// GET: list / detail
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (($_GET['action'] ?? '') === 'get') {
        $itineraryId = (int)($_GET['itinerary_id'] ?? 0);
        if (!$itineraryId || !itinOwns($userId, $itineraryId)) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found']);
            exit;
        }
        $stmt = db()->prepare("SELECT * FROM user_itineraries WHERE id = ?");
        $stmt->execute([$itineraryId]);
        $itinerary = $stmt->fetch();
        $stmt = db()->prepare("SELECT d.id AS day_id, d.day_number, it.id AS item_id, it.item_type, it.tour_id, it.hotel_id, it.title, it.note, it.time_label, it.sort_order FROM user_itinerary_days d LEFT JOIN user_itinerary_items it ON it.day_id = d.id WHERE d.itinerary_id = ? ORDER BY d.day_number, it.sort_order, it.id");
        $stmt->execute([$itineraryId]);
        $itinerary['days'] = $stmt->fetchAll();
        echo json_encode(['itinerary' => $itinerary]);
        exit;
    }
    $stmt = db()->prepare("SELECT i.*, (SELECT COUNT(*) FROM user_itinerary_days d WHERE d.itinerary_id = i.id) AS day_count, (SELECT COUNT(*) FROM user_itinerary_days d JOIN user_itinerary_items it ON it.day_id = d.id WHERE d.itinerary_id = i.id) AS item_count FROM user_itineraries i WHERE i.user_id = ? ORDER BY i.updated_at DESC");
    $stmt->execute([$userId]);
    echo json_encode(['user_itineraries' => $stmt->fetchAll()]);
    exit;
}

// POST actions
$action = $_POST['action'] ?? '';

if ($action === 'create_itinerary') {
    $title = trim($_POST['title'] ?? '');
    if ($title === '') { http_response_code(422); echo json_encode(['error' => 'title_required']); exit; }
    $startDate = trim($_POST['start_date'] ?? '') ?: null;
    $stmt = db()->prepare("INSERT INTO user_itineraries (user_id, title, start_date) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $title, $startDate]);
    $itineraryId = (int)db()->lastInsertId();
    db()->prepare("INSERT INTO user_itinerary_days (itinerary_id, day_number) VALUES (?, 1)")->execute([$itineraryId]);
    echo json_encode(['ok' => true, 'itinerary_id' => $itineraryId]);
    exit;
}

if ($action === 'add_day') {
    $itineraryId = (int)($_POST['itinerary_id'] ?? 0);
    if (!$itineraryId || !itinOwns($userId, $itineraryId)) { http_response_code(404); echo json_encode(['error' => 'not_found']); exit; }
    $stmt = db()->prepare("SELECT COALESCE(MAX(day_number), 0) + 1 FROM user_itinerary_days WHERE itinerary_id = ?");
    $stmt->execute([$itineraryId]);
    $next = (int)$stmt->fetchColumn();
    db()->prepare("INSERT INTO user_itinerary_days (itinerary_id, day_number) VALUES (?, ?)")->execute([$itineraryId, $next]);
    echo json_encode(['ok' => true, 'day_id' => (int)db()->lastInsertId(), 'day_number' => $next]);
    exit;
}

if ($action === 'add_item') {
    $dayId = (int)($_POST['day_id'] ?? 0);
    $itineraryId = itinDayOwned($userId, $dayId);
    if (!$itineraryId) { http_response_code(404); echo json_encode(['error' => 'not_found']); exit; }
    $title = trim($_POST['title'] ?? '');
    if ($title === '') { http_response_code(422); echo json_encode(['error' => 'title_required']); exit; }
    $itemType = in_array($_POST['item_type'] ?? 'custom', ['tour', 'hotel', 'flight', 'custom'], true) ? $_POST['item_type'] : 'custom';
    $tourId = (int)($_POST['tour_id'] ?? 0) ?: null;
    $hotelId = (int)($_POST['hotel_id'] ?? 0) ?: null;
    $note = trim($_POST['note'] ?? '') ?: null;
    $timeLabel = trim($_POST['time_label'] ?? '') ?: null;
    $stmt = db()->prepare("INSERT INTO user_itinerary_items (day_id, item_type, tour_id, hotel_id, title, note, time_label) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$dayId, $itemType, $tourId, $hotelId, $title, $note, $timeLabel]);
    echo json_encode(['ok' => true, 'item_id' => (int)db()->lastInsertId(), 'itinerary_id' => $itineraryId]);
    exit;
}

if ($action === 'delete_item') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $stmt = db()->prepare("DELETE it FROM user_itinerary_items it JOIN user_itinerary_days d ON d.id = it.day_id JOIN user_itineraries i ON i.id = d.itinerary_id WHERE it.id = ? AND i.user_id = ?");
    $stmt->execute([$itemId, $userId]);
    echo json_encode(['ok' => $stmt->rowCount() > 0]);
    exit;
}

if ($action === 'delete_itinerary') {
    $itineraryId = (int)($_POST['itinerary_id'] ?? 0);
    if (!$itineraryId || !itinOwns($userId, $itineraryId)) { http_response_code(404); echo json_encode(['error' => 'not_found']); exit; }
    db()->prepare("DELETE FROM user_itineraries WHERE id = ? AND user_id = ?")->execute([$itineraryId, $userId]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'unknown_action']);
