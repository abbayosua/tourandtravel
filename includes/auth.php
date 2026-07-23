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
?>
