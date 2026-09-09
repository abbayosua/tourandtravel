<?php
/**
 * Bottom Navigation Bar — mobile-only fixed bottom nav with 4 tabs.
 * Include before closing </body> tag.
 * Active state based on current page.
 */
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$bottomNavItems = [
    ['icon' => 'bi-house', 'label' => 'Beranda', 'url' => 'index.php', 'pages' => ['index.php']],
    ['icon' => 'bi-search', 'label' => 'Cari', 'url' => 'tours.php', 'pages' => ['tours.php', 'hotels.php', 'flights.php', 'ferries.php', 'rental-cars.php', 'transfers.php', 'trains.php', 'esim.php', 'attractions.php', 'destinasi.php', 'collection.php']],
    ['icon' => 'bi-ticket-perforated', 'label' => 'Booking', 'url' => 'my-bookings.php', 'pages' => ['my-bookings.php', 'track.php', 'booking-success.php']],
    ['icon' => 'bi-person', 'label' => 'Akun', 'url' => isLoggedIn() ? 'profile.php' : 'login.php', 'pages' => ['profile.php', 'login.php', 'register.php', 'forgot-password.php', 'reset-password.php', 'my-points.php', 'my-coupons.php', 'my-alerts.php', 'my-profiles.php', 'wishlist.php', 'notifications.php', 'referral.php']],
];
?>
<nav class="bottom-nav" aria-label="Bottom Navigation">
    <?php foreach ($bottomNavItems as $item): ?>
    <a href="<?= e($item['url']) ?>" class="<?= in_array($currentPage, $item['pages']) ? 'active' : '' ?>">
        <i class="bi <?= $item['icon'] ?>"></i>
        <span><?= t($item['label']) ?></span>
    </a>
    <?php endforeach; ?>
</nav>
