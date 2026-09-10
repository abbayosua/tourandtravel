<!DOCTYPE html>
<html lang="<?= getCurrentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? t('Admin')) ?> - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
<style>
#adminSidebar {
    width: 250px;
    min-height: calc(100vh - 56px);
    transition: width 0.3s ease, padding 0.3s ease;
    overflow: hidden;
    flex-shrink: 0;
}
#adminSidebar.collapsed {
    width: 0;
    padding: 0;
}
/* Icon-only mode (desktop): 64px, labels hidden */
@media (min-width: 768px) {
    #adminSidebar.icon-only {
        width: 64px;
        padding: 1rem 0.5rem;
    }
    #adminSidebar.icon-only .nav-link {
        justify-content: center;
        padding: 10px 0;
    }
    #adminSidebar.icon-only .nav-link span.nav-label {
        display: none;
    }
    #adminSidebar.icon-only .nav-section-label {
        display: none;
    }
    #adminSidebar.icon-only .nav-link i {
        margin-right: 0;
        width: auto;
    }
    #adminSidebar.icon-only hr {
        margin: 0.5rem 0;
    }
}
#adminSidebar.collapsed .nav-link {
    white-space: nowrap;
}
#adminContent {
    min-height: calc(100vh - 56px);
    transition: margin-left 0.3s ease;
}
@media (max-width: 767.98px) {
    #adminSidebar {
        position: fixed;
        z-index: 1040;
        left: 0;
        top: 56px;
        height: calc(100vh - 56px);
    }
    #adminSidebar.collapsed {
        transform: translateX(-100%);
        width: 250px !important;
        padding: 1rem !important;
    }
    #adminSidebar:not(.collapsed) {
        box-shadow: 0 0 20px rgba(0,0,0,0.3);
    }
    #sidebarOverlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1035;
        background: rgba(0,0,0,0.4);
    }
    #sidebarOverlay.show {
        display: block;
    }
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand navbar-dark bg-primary sticky-top">
    <div class="container-fluid">
        <button class="btn btn-sm btn-outline-light me-2" id="sidebarToggle" title="Toggle Sidebar">
            <i class="bi bi-list"></i>
        </button>
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="bi bi-airplane-engines-fill"></i> <?= t('Admin Panel') ?>
        </a>
        <div class="d-flex align-items-center ms-auto">
            <span class="text-white me-3 small"><?= e($_SESSION['admin_username']) ?></span>
            <a href="logout.php" class="btn btn-sm btn-outline-light"><?= t('Logout') ?></a>
        </div>
    </div>
</nav>

<div class="container-fluid px-0">
    <div id="sidebarOverlay"></div>
    <div class="d-flex" id="adminWrapper">
        <!-- Sidebar -->
        <div class="bg-dark sidebar p-3" id="adminSidebar">
            <nav class="nav flex-column">
                <?php
                $currentPage = basename($_SERVER['PHP_SELF']);
                $sectionHdr = function (string $label) { ?>
                <div class="nav-section-label small text-secondary text-uppercase fw-bold px-2 pt-2 pb-1" style="letter-spacing:.08em; font-size:.68rem;"><?= $label ?></div>
                <?php };
                $navItem = function (string $href, string $icon, string $label, array $activePages, string $testid = '') use ($currentPage) { ?>
                <a class="nav-link <?= in_array($currentPage, $activePages, true) ? 'active' : '' ?>" href="<?= $href ?>"<?= $testid ? " data-testid=\"$testid\"" : '' ?>>
                    <i class="bi <?= $icon ?>"></i><span class="nav-label"> <?= $label ?></span>
                </a>
                <?php };

                // ===== OVERVIEW =====
                $sectionHdr(t('Overview'));
                $navItem('dashboard.php', 'bi-speedometer2', t('Dashboard'), ['dashboard.php']);
                $navItem('analytics.php', 'bi-graph-up-arrow', t('Analytics'), ['analytics.php']);

                // ===== INVENTORY =====
                $sectionHdr(t('Inventory'));
                $navItem('tours.php', 'bi-map', t('Kelola Tour'), ['tours.php', 'tour-edit.php', 'tour-add.php']);
                $navItem('hotels.php', 'bi-building', t('Kelola Hotel'), ['hotels.php', 'hotel-edit.php', 'hotel-rooms.php']);
                $navItem('flights.php', 'bi-airplane', t('Kelola Pesawat'), ['flights.php', 'flight-edit.php']);
                $navItem('ferries.php', 'bi-ship', t('Kelola Ferry'), ['ferries.php', 'ferry-edit.php']);
                $navItem('rental-cars.php', 'bi-car-front', t('Kelola Rental'), ['rental-cars.php', 'rental-car-edit.php']);
                $navItem('attractions.php', 'bi-signpost-2', t('Kelola Atraksi'), ['attractions.php', 'attraction-edit.php']);
                $navItem('transfers.php', 'bi-car-front', t('Kelola Transfer'), ['transfers.php', 'transfer-edit.php']);
                $navItem('trains.php', 'bi-train-front', t('Kelola Kereta'), ['trains.php', 'train-edit.php']);
                $navItem('esim.php', 'bi-sim', t('Kelola eSIM'), ['esim.php', 'esim-edit.php']);

                // ===== BOOKINGS =====
                $sectionHdr(t('Bookings'));
                $navItem('bookings.php', 'bi-ticket-perforated', t('Kelola Booking'), ['bookings.php']);
                $navItem('payments.php', 'bi-credit-card', t('Pembayaran'), ['payments.php']);

                // ===== MARKETING =====
                $sectionHdr(t('Marketing'));
                $navItem('flash-sales.php', 'bi-lightning-charge', t('Flash Sale'), ['flash-sales.php'], 'nav-flash-sales');
                $navItem('promo-codes.php', 'bi-tag', t('Kode Promo'), ['promo-codes.php']);
                $navItem('price-alerts.php', 'bi-bell', t('Price Alerts'), ['price-alerts.php']);
                $navItem('push-notifications.php', 'bi-bell-fill', t('Push Notifikasi'), ['push-notifications.php']);
                $navItem('corporate-rates.php', 'bi-building', t('Corporate Rates'), ['corporate-rates.php']);
                $navItem('collections.php', 'bi-collection', t('Koleksi'), ['collections.php']);

                // ===== FINANCE =====
                $sectionHdr(t('Finance'));
                $navItem('sales-report.php', 'bi-graph-up', t('Sales Report'), ['sales-report.php'], 'nav-sales-report');
                $navItem('accounting.php', 'bi-cash-stack', t('Accounting'), ['accounting.php'], 'nav-accounting');
                $navItem('loyalty-settings.php', 'bi-award', t('Loyalty Settings'), ['loyalty-settings.php']);

                // ===== RESELLER =====
                $sectionHdr(t('Reseller'));
                $navItem('resellers.php', 'bi-shop', t('Kelola Reseller'), ['resellers.php']);
                $navItem('reseller-topups.php', 'bi-wallet2', t('Topup Reseller'), ['reseller-topups.php']);
                $navItem('reseller-pricing.php', 'bi-tags', t('Harga Reseller'), ['reseller-pricing.php']);

                // ===== CONTENT =====
                $sectionHdr(t('Content'));
                $navItem('posts.php', 'bi-journal-richtext', t('Blog'), ['posts.php', 'post-edit.php']);
                $navItem('reviews.php', 'bi-chat-square-heart', t('Ulasan'), ['reviews.php']);
                $navItem('faq.php', 'bi-question-circle', t('Kelola FAQ'), ['faq.php', 'faq-edit.php', 'faq-category.php', 'faq-category-edit.php']);
                $navItem('appearance.php', 'bi-layout-text-window-reverse', t('Tampilan Homepage'), ['appearance.php']);

                // ===== SETTINGS =====
                $sectionHdr(t('Settings'));
                $navItem('wa-settings.php', 'bi-whatsapp', t('Pengaturan WA'), ['wa-settings.php']);
                $navItem('chat-settings.php', 'bi-chat-dots', t('Live Chat'), ['chat-settings.php'], 'nav-chat-settings');
                $navItem('email-log.php', 'bi-envelope-paper', t('Log Email'), ['email-log.php']);
                $navItem('currency-settings.php', 'bi-currency-exchange', t('Mata Uang'), ['currency-settings.php']);

                // ===== EXTERNAL =====
                $sectionHdr(t('Eksternal'));
                $navItem('../index.php', 'bi-globe', t('Lihat Website'), []);
                ?>
                <hr class="border-secondary">
                <!-- Language toggle -->
                <div class="nav-link d-flex align-items-center justify-content-between px-2">
                    <span class="nav-label small text-secondary"><i class="bi bi-translate me-1"></i><?= strtoupper(getCurrentLang()) ?></span>
                    <span class="d-flex gap-1">
                        <?php foreach (getSupportedLanguages() as $langCode => $langMeta): ?>
                        <a href="?<?= e(http_build_query(array_merge($_GET, ['lang' => $langCode]))) ?>" class="badge text-decoration-none <?= getCurrentLang() === $langCode ? 'bg-primary' : 'bg-secondary' ?>" title="<?= e($langMeta['label']) ?>"><?= $langMeta['flag'] ?></a>
                        <?php endforeach; ?>
                    </span>
                </div>
            </nav>
        </div>
        <!-- Content -->
        <div class="flex-grow-1 p-4" id="adminContent">
