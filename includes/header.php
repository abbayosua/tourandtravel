<!DOCTYPE html>
<html lang="<?= getCurrentLang() ?>">
<head>
    <meta charset="UTF-8">
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title><?= e($pageTitle ?? SITE_NAME) ?> - <?= SITE_NAME ?></title>
    <?php require_once __DIR__ . '/seo.php'; seoHead($metaDesc ?? null, $ogImage ?? null, $jsonLd ?? null); ?>
    <!-- Google Fonts: Inter + Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <!-- Design Tokens (CSS variables) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/design-tokens.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <?= i18nJs() ?>
    <script defer src="<?= BASE_URL ?>/assets/js/klook.js?v=<?= filemtime(__DIR__ . '/../assets/js/klook.js') ?>"></script>
</head>
<body>

<a class="skip-link" href="#mainContent">Skip to content</a>
<script>
(function () {
    var t = localStorage.getItem('theme') || 'light';
    if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
})();
</script>

<!-- Navbar Line 1: Logo + User -->
<div class="sticky-top" style="z-index: 1020;">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary pb-0 position-relative" style="z-index: 1;">
    <div class="container">
        <a class="navbar-brand fw-bold py-2" href="<?= BASE_URL ?>/">
            <i class="bi bi-airplane-engines-fill"></i> <?= SITE_NAME ?>
        </a>
        <!-- Search bar muncul pas scroll -->
        <div class="mx-auto d-none d-lg-block" style="flex: 1; max-width: 400px;">
            <div class="nav-search-wrapper search-wrapper">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted small"></i></span>
                    <input type="text" class="form-control border-0 shadow-none small" placeholder="<?= t('Cari destinasi...') ?>" id="navSearch" autocomplete="off" onkeypress="if(event.key==='Enter' && this.value.trim()) window.location='tours.php?search='+encodeURIComponent(this.value)">
                </div>
                <div class="search-dropdown" id="navSearchDropdown"></div>
            </div>
        </div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item dropdown">
                    <a class="nav-link py-2 dropdown-toggle" href="#" data-bs-toggle="dropdown"><?= t('Layanan') ?></a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="tours.php"><i class="bi bi-map me-2"></i><?= t('Paket Tour') ?></a></li>
                        <li><a class="dropdown-item" href="flights.php"><i class="bi bi-airplane me-2"></i><?= t('Pesawat') ?></a></li>
                        <li><a class="dropdown-item" href="ferries.php"><i class="bi bi-ship me-2"></i><?= t('Ferry') ?></a></li>
                        <li><a class="dropdown-item" href="rental-cars.php"><i class="bi bi-car-front me-2"></i><?= t('Rental Mobil') ?></a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 <?= basename($_SERVER['PHP_SELF']) === 'wishlist.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/wishlist.php">
                        <i class="bi bi-heart"></i>
                    </a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle py-2" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i><?= e($_SESSION['user_name'] ?? 'User') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-circle me-2"></i><?= t('Profil') ?></a></li>
                        <li><a class="dropdown-item" href="my-bookings.php"><i class="bi bi-ticket-perforated me-2"></i><?= t('Booking Saya') ?></a></li>
                        <li><a class="dropdown-item" href="wishlist.php"><i class="bi bi-heart me-2"></i><?= t('Wishlist') ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i><?= t('Keluar') ?></a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link py-2" href="login.php"><i class="bi bi-person me-1"></i><?= t('Masuk') ?></a>
                </li>
                <?php endif; ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle py-2 small" href="#" data-bs-toggle="dropdown" id="currencyDropdown">
                        <i class="bi bi-currency-exchange"></i> <span id="currencyLabel">IDR</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="currencyDropdown">
                        <li><a class="dropdown-item currency-btn" href="#" data-currency="IDR">🇮🇩 IDR (Rp)</a></li>
                        <li><a class="dropdown-item currency-btn" href="#" data-currency="SGD">🇸🇬 SGD (S$)</a></li>
                        <li><a class="dropdown-item currency-btn" href="#" data-currency="USD">🇺🇸 USD ($)</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle py-2 small" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-translate"></i> <?= strtoupper(getCurrentLang()) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php $langParams = $_GET; foreach (getSupportedLanguages() as $langCode => $langMeta): ?>
                        <li><a class="dropdown-item <?= getCurrentLang() === $langCode ? 'active' : '' ?>" href="?<?= http_build_query(array_merge($langParams, ['lang' => $langCode])) ?>"><?= $langMeta['flag'] ?> <?= e($langMeta['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
        </div>
    </div>
</nav>

<!-- Navbar Line 2: Service links -->
<nav class="navbar navbar-expand navbar-dark bg-primary pt-0 border-top border-white border-opacity-10" style="margin-top: -1px;">
    <div class="container">
        <ul class="navbar-nav flex-row gap-1 overflow-auto py-1 w-100 kategori-scroll">
            <li class="nav-item">
                <a class="nav-link text-white fw-semibold small py-1 px-3 rounded-3 <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active bg-white bg-opacity-25' : '' ?>" href="<?= BASE_URL ?>/"><?= t('Beranda') ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white-50 small py-1 px-3 rounded-3 <?= strpos($_SERVER['PHP_SELF'], 'tours.php') !== false ? 'active bg-white bg-opacity-25 text-white' : '' ?>" href="<?= BASE_URL ?>/tours.php"><?= t('Tour') ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white-50 small py-1 px-3 rounded-3 <?= strpos($_SERVER['PHP_SELF'], 'flights.php') !== false ? 'active bg-white bg-opacity-25 text-white' : '' ?>" href="<?= BASE_URL ?>/flights.php"><?= t('Pesawat') ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white-50 small py-1 px-3 rounded-3 <?= strpos($_SERVER['PHP_SELF'], 'ferries.php') !== false ? 'active bg-white bg-opacity-25 text-white' : '' ?>" href="<?= BASE_URL ?>/ferries.php"><?= t('Ferry') ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white-50 small py-1 px-3 rounded-3 <?= strpos($_SERVER['PHP_SELF'], 'rental-cars') !== false || strpos($_SERVER['PHP_SELF'], 'rental-car-detail') !== false ? 'active bg-white bg-opacity-25 text-white' : '' ?>" href="<?= BASE_URL ?>/rental-cars.php"><?= t('Rental') ?></a>
            </li>
        </ul>
    </div>
</nav>
</div><!-- /.sticky-top -->
