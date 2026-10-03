<?php
/**
 * Bottom Navigation Bar — mobile-only fixed bottom nav with 4 tabs.
 * Include before closing </body> tag.
 * Active state based on current page.
 */
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$wishCount = 0;
try {
    if (function_exists('isLoggedIn') && isLoggedIn() && function_exists('getWishlistIds')) {
        $wishCount = count(getWishlistIds((int)($_SESSION['user_id'] ?? 0)));
    }
} catch (Throwable $e) { $wishCount = 0; }
$bottomNavItems = [
    ['icon' => 'bi-house', 'label' => 'Beranda', 'url' => 'index.php', 'pages' => ['index.php']],
    ['icon' => 'bi-search', 'label' => 'Cari', 'url' => 'tours.php', 'pages' => ['tours.php', 'hotels.php', 'flights.php', 'ferries.php', 'rental-cars.php', 'transfers.php', 'trains.php', 'esim.php', 'attractions.php', 'destinasi.php', 'collection.php', 'tour-detail.php', 'hotel-detail.php', 'flight-detail.php', 'attraction-detail.php', 'esim-detail.php', 'transfer-detail.php', 'train-detail.php', 'rental-car-detail.php', 'blog.php', 'blog-detail.php', 'faq.php']],
    ['icon' => 'bi-heart', 'label' => 'Wishlist', 'url' => 'wishlist.php', 'pages' => ['wishlist.php'], 'badge' => $wishCount],
    ['icon' => 'bi-ticket-perforated', 'label' => 'Booking', 'url' => 'my-bookings.php', 'pages' => ['my-bookings.php', 'track.php', 'booking-success.php']],
    ['icon' => 'bi-person', 'label' => 'Akun', 'url' => isLoggedIn() ? 'profile.php' : 'login.php', 'pages' => ['profile.php', 'login.php', 'register.php', 'forgot-password.php', 'reset-password.php', 'my-points.php', 'my-coupons.php', 'my-alerts.php', 'my-profiles.php', 'notifications.php', 'referral.php', 'wallet.php', 'my-itinerary.php']],
];
?>
<nav class="bottom-nav" aria-label="<?= e(t('Navigasi Bawah')) ?>">
    <?php foreach ($bottomNavItems as $item): ?>
    <a href="<?= e($item['url']) ?>" class="<?= in_array($currentPage, $item['pages']) ? 'active' : '' ?>">
        <i class="bi <?= $item['icon'] ?>"></i>
        <span><?= t($item['label']) ?></span>
        <?php if (!empty($item['badge'])): ?><span class="bn-badge"><?= $item['badge'] > 9 ? '9+' : (int)$item['badge'] ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
</nav>
