<?php
/**
 * Wallet / KlookCash system
 * Tabel: wallet_transactions (user_id, amount, type, description, reference_type, reference_id)
 * Balance = SUM(amount) WHERE user_id = ?
 */

require_once __DIR__ . '/db.php';

/**
 * Get user wallet balance (total KlookCash)
 */
function getWalletBalance($userId) {
    $userId = (int)$userId;
    $stmt = db()->prepare("SELECT COALESCE(SUM(amount), 0) FROM wallet_transactions WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (float)$stmt->fetchColumn();
}

/**
 * Add wallet transaction
 * type: earn | spend | refund | bonus
 * amount: positif untuk earn/refund/bonus, negatif untuk spend
 */
function addWalletTransaction($userId, $amount, $type, $description = '', $referenceType = null, $referenceId = null) {
    $userId = (int)$userId;
    $amount = (float)$amount;

    $validTypes = ['earn', 'spend', 'refund', 'bonus'];
    if (!in_array($type, $validTypes)) $type = 'earn';

    // Spend harus negatif
    if ($type === 'spend' && $amount > 0) $amount = -$amount;
    // Earn/refund/bonus harus positif
    if (in_array($type, ['earn', 'refund', 'bonus']) && $amount < 0) $amount = -$amount;

    $stmt = db()->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description, reference_type, reference_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $amount, $type, $description, $referenceType, $referenceId]);
    return (int)db()->lastInsertId();
}

/**
 * Spend wallet balance (pembayaran dengan KlookCash)
 * Returns [success => bool, message => string, new_balance => float]
 */
function spendWallet($userId, $amount, $referenceType = null, $referenceId = null) {
    $userId = (int)$userId;
    $amount = (float)$amount;
    $balance = getWalletBalance($userId);

    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Jumlah tidak valid'];
    }
    if ($balance < $amount) {
        return ['success' => false, 'message' => 'Saldo KlookCash tidak mencukupi'];
    }

    addWalletTransaction($userId, -$amount, 'spend', 'Pembayaran menggunakan KlookCash', $referenceType, $referenceId);
    return ['success' => true, 'message' => 'Pembayaran KlookCash berhasil', 'new_balance' => getWalletBalance($userId)];
}

/**
 * Refund wallet balance (pembatalan booking)
 */
function refundWallet($userId, $amount, $referenceType = null, $referenceId = null) {
    $amount = (float)$amount;
    addWalletTransaction($userId, $amount, 'refund', 'Refund pembatalan booking', $referenceType, $referenceId);
    return ['success' => true, 'new_balance' => getWalletBalance($userId)];
}

/**
 * Get wallet transaction history
 */
function getWalletTransactions($userId, $limit = 50) {
    $userId = (int)$userId;
    $limit = (int)$limit;
    $stmt = db()->prepare("SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}