<!DOCTYPE html>
<html lang="id">
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
            <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-0">
    <div id="sidebarOverlay"></div>
    <div class="d-flex" id="adminWrapper">
        <!-- Sidebar -->
        <div class="bg-dark sidebar p-3" id="adminSidebar">
            <nav class="nav flex-column">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i><span class="nav-label"> <?= t('Dashboard') ?></span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'tours.php' ? 'active' : '' ?>" href="tours.php">
                    <i class="bi bi-map"></i><span class="nav-label"> <?= t('Kelola Tour') ?></span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'hotels.php' || basename($_SERVER['PHP_SELF']) === 'hotel-edit.php' ? 'active' : '' ?>" href="hotels.php">
                    <i class="bi bi-building"></i><span class="nav-label"> <?= t('Kelola Hotel') ?></span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'flights.php' || basename($_SERVER['PHP_SELF']) === 'flight-edit.php' ? 'active' : '' ?>" href="flights.php">
                    <i class="bi bi-airplane"></i><span class="nav-label"> <?= t('Kelola Pesawat') ?></span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'ferries.php' || basename($_SERVER['PHP_SELF']) === 'ferry-edit.php' ? 'active' : '' ?>" href="ferries.php">
                    <i class="bi bi-ship"></i><span class="nav-label"> <?= t('Kelola Ferry') ?></span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'rental-cars.php' || basename($_SERVER['PHP_SELF']) === 'rental-car-edit.php' ? 'active' : '' ?>" href="rental-cars.php">
                    <i class="bi bi-car-front"></i><span class="nav-label"> <?= t('Kelola Rental') ?></span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'active' : '' ?>" href="bookings.php">
                    <i class="bi bi-ticket-perforated"></i><span class="nav-label"> <?= t('Kelola Booking') ?></span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'attractions.php' || basename($_SERVER['PHP_SELF']) === 'attraction-edit.php' ? 'active' : '' ?>" href="attractions.php">
                    <i class="bi bi-signpost-2"></i><span class="nav-label"> <?= t('Kelola Atraksi') ?></span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'transfers.php' || basename($_SERVER['PHP_SELF']) === 'transfer-edit.php' ? 'active' : '' ?>" href="transfers.php">
                    <i class="bi bi-car-front"></i><span class="nav-label"> <?= t('Kelola Transfer') ?></span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'trains.php' || basename($_SERVER['PHP_SELF']) === 'train-edit.php' ? 'active' : '' ?>" href="trains.php">
                    <i class="bi bi-train-front"></i><span class="nav-label"> <?= t('Kelola Kereta') ?></span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'esim.php' || basename($_SERVER['PHP_SELF']) === 'esim-edit.php' ? 'active' : '' ?>" href="esim.php">
                    <i class="bi bi-sim"></i><span class="nav-label"> <?= t('Kelola eSIM') ?></span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'promo-codes.php' ? 'active' : '' ?>" href="promo-codes.php">
                    <i class="bi bi-tag"></i><span class="nav-label"> <?= t('Kode Promo') ?></span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'collections.php' ? 'active' : '' ?>" href="collections.php">
                    <i class="bi bi-collection"></i><span class="nav-label"> <?= t('Koleksi') ?></span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'faq.php' || basename($_SERVER['PHP_SELF']) === 'faq-edit.php' || basename($_SERVER['PHP_SELF']) === 'faq-category.php' || basename($_SERVER['PHP_SELF']) === 'faq-category-edit.php' ? 'active' : '' ?>" href="faq.php">
                    <i class="bi bi-question-circle"></i><span class="nav-label"> <?= t('Kelola FAQ') ?></span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'loyalty-settings.php' ? 'active' : '' ?>" href="loyalty-settings.php">
                    <i class="bi bi-award"></i><span class="nav-label"> <?= t('Loyalty Settings') ?></span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'wa-settings.php' ? 'active' : '' ?>" href="wa-settings.php">
                    <i class="bi bi-whatsapp text-success"></i><span class="nav-label"> <?= t('Pengaturan WA') ?></span>
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'currency-settings.php' ? 'active' : '' ?>" href="currency-settings.php">
                    <i class="bi bi-currency-exchange text-warning"></i><span class="nav-label"> <?= t('Mata Uang') ?></span>
                </a>
                <hr class="border-secondary">
                <a class="nav-link" href="../index.php" target="_blank">
                    <i class="bi bi-globe"></i><span class="nav-label"> <?= t('Lihat Website') ?></span>
                </a>
                <hr class="border-secondary">
                <!-- Language toggle -->
                <div class="nav-link d-flex align-items-center justify-content-between px-2">
                    <span class="nav-label small text-secondary"><i class="bi bi-translate me-1"></i><?= strtoupper(getCurrentLang()) ?></span>
                    <span class="d-flex gap-1">
                        <a href="?<?= e(http_build_query(array_merge($_GET, ['lang' => 'id']))) ?>" class="badge text-decoration-none <?= getCurrentLang() === 'id' ? 'bg-primary' : 'bg-secondary' ?>">🇮🇩</a>
                        <a href="?<?= e(http_build_query(array_merge($_GET, ['lang' => 'en']))) ?>" class="badge text-decoration-none <?= getCurrentLang() === 'en' ? 'bg-primary' : 'bg-secondary' ?>">🇬🇧</a>
                    </span>
                </div>
            </nav>
        </div>
        <!-- Content -->
        <div class="flex-grow-1 p-4" id="adminContent">
