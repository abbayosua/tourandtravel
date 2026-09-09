<?php
/**
 * profile-ajax.php — AJAX endpoint for passenger profiles.
 *
 * GET  → list user profiles (JSON)
 * POST action=save   → create or update profile
 * POST action=delete → remove profile
 * POST action=default → set default profile
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

// GET: list profiles
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = db()->prepare("SELECT * FROM passenger_profiles WHERE user_id = ? ORDER BY is_default DESC, created_at ASC");
    $stmt->execute([$userId]);
    echo json_encode(['profiles' => $stmt->fetchAll()]);
    exit;
}

// POST: save/delete/default
$action = $_POST['action'] ?? '';

if ($action === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $passportNo = trim($_POST['passport_no'] ?? '') ?: null;
    $nationality = trim($_POST['nationality'] ?? '') ?: null;
    $dob = $_POST['dob'] ?: null;
    $phone = trim($_POST['phone'] ?? '') ?: null;
    $isDefault = isset($_POST['is_default']) ? 1 : 0;

    if (!$fullName) {
        http_response_code(400);
        echo json_encode(['error' => 'full_name_required']);
        exit;
    }

    if ($isDefault) {
        db()->prepare("UPDATE passenger_profiles SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
    }

    if ($id > 0) {
        db()->prepare("UPDATE passenger_profiles SET full_name = ?, passport_no = ?, nationality = ?, dob = ?, phone = ?, is_default = ? WHERE id = ? AND user_id = ?")
            ->execute([$fullName, $passportNo, $nationality, $dob, $phone, $isDefault, $id, $userId]);
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        $hasAny = db()->prepare("SELECT COUNT(*) FROM passenger_profiles WHERE user_id = ?");
        $hasAny->execute([$userId]);
        if ((int)$hasAny->fetchColumn() === 0) $isDefault = 1;
        db()->prepare("INSERT INTO passenger_profiles (user_id, full_name, passport_no, nationality, dob, phone, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$userId, $fullName, $passportNo, $nationality, $dob, $phone, $isDefault]);
        echo json_encode(['success' => true, 'id' => (int)db()->lastInsertId()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_id']);
        exit;
    }
    db()->prepare("DELETE FROM passenger_profiles WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'default') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_id']);
        exit;
    }
    db()->prepare("UPDATE passenger_profiles SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
    db()->prepare("UPDATE passenger_profiles SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'unknown_action']);
