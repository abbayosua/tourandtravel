<?php
/**
 * price-alert-checker.php — cron-like function to check price alerts.
 *
 * Compares current price vs target. If price ≤ target, sends notification + email.
 * Call via CLI: php includes/price-alert-checker.php
 * Or wire into existing cron/cron-like scheduler.
 *
 * @return array ['checked' => int, 'notified' => int]
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/email.php';

function checkPriceAlerts(): array {
    $checked = 0;
    $notified = 0;

    // Get all active alerts
    $stmt = db()->query("SELECT pa.*, t.slug AS tour_slug, h.slug AS hotel_slug FROM price_alerts pa LEFT JOIN tours t ON pa.item_type = 'tour' AND pa.item_id = t.id LEFT JOIN hotels h ON pa.item_type = 'hotel' AND pa.item_id = h.id WHERE pa.active = 1");
    $alerts = $stmt->fetchAll();

    $today = date('Y-m-d');

    foreach ($alerts as $alert) {
        $checked++;
        $itemType = $alert['item_type'];
        $itemId = (int)$alert['item_id'];
        $targetPrice = (float)$alert['target_price'];
        $currency = $alert['currency'] ?? 'IDR';

        // Get current price: try price_calendar first, then base price
        $currentPrice = null;
        $calPrice = getPriceForDate($itemType, $itemId, $today, null);
        if ($calPrice !== null) {
            $currentPrice = (float)$calPrice;
        } else {
            // Fallback to base price from table
            if ($itemType === 'tour') {
                $tour = getTourById($itemId);
                $currentPrice = $tour ? (float)$tour['price'] : null;
            } elseif ($itemType === 'hotel') {
                $hStmt = db()->prepare("SELECT price_per_night FROM hotels WHERE id = ?");
                $hStmt->execute([$itemId]);
                $currentPrice = $hStmt->fetchColumn() ? (float)$hStmt->fetchColumn() : null;
            }
        }

        if ($currentPrice === null) continue;

        // Check if price has dropped to or below target
        if ($currentPrice > $targetPrice) continue;

        // Skip if already notified today (use MySQL CURDATE() to avoid timezone mismatch)
        $nCheck = db()->prepare("SELECT 1 FROM price_alerts WHERE id = ? AND notified_at IS NOT NULL AND DATE(notified_at) = CURDATE()");
        $nCheck->execute([$alert['id']]);
        if ($nCheck->fetch()) continue;

        // Determine item title and link
        $title = '';
        $link = '';
        if ($itemType === 'tour') {
            $title = $alert['tour_title'] ?? 'Tour';
            $link = 'tour-detail.php?slug=' . urlencode($alert['tour_slug'] ?? '');
        } elseif ($itemType === 'hotel') {
            $title = $alert['hotel_title'] ?? 'Hotel';
            $link = 'hotel-detail.php?slug=' . urlencode($alert['hotel_slug'] ?? '');
        }

        // Send in-app notification
        $userId = (int)$alert['user_id'];
        $notifTitle = 'Harga Turun! 🎉';
        $notifBody = sprintf('%s sekarang %s (target: %s)', $title, formatRupiah($currentPrice, $currency), formatRupiah($targetPrice, $currency));
        addNotification($userId, 'price_alert', $notifTitle, $notifBody, $link);

        // Send email notification
        $email = null;
        $eStmt = db()->prepare("SELECT email FROM users WHERE id = ?");
        $eStmt->execute([$userId]);
        $email = $eStmt->fetchColumn();
        if ($email) {
            sendEmailTemplate($email, 'price-alert', [
                'item_title' => $title,
                'current_price' => formatRupiah($currentPrice, $currency),
                'target_price' => formatRupiah($targetPrice, $currency),
                'link' => BASE_URL . '/' . ltrim($link, '/'),
            ], null);
        }

        // Mark as notified
        db()->prepare("UPDATE price_alerts SET notified_at = NOW() WHERE id = ?")->execute([$alert['id']]);
        $notified++;
    }

    return ['checked' => $checked, 'notified' => $notified];
}

// CLI mode: run directly
if (php_sapi_name() === 'cli' && basename($argv[0] ?? '') === 'price-alert-checker.php') {
    $result = checkPriceAlerts();
    echo "Checked: {$result['checked']}, Notified: {$result['notified']}\n";
}
