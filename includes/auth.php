<?php
/**
 * Cek apakah admin sudah login
 */
function cekLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Login user by ID: set session + regenerate id.
 * Dipakai login form lama dan OAuth (api/oauth-google.php).
 */
function loginUserById($id) {
    $stmt = db()->prepare("SELECT id, name FROM users WHERE id = ?");
    $stmt->execute([(int)$id]);
    $user = $stmt->fetch();
    if (!$user) return false;
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    return true;
}
?>
