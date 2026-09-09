<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

function getAuthUserId(): ?int {
    return isLoggedIn() ? (int)$_SESSION['user_id'] : null;
}
