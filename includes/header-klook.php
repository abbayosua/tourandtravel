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
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/hero-uifactory.css?v=<?= filemtime(__DIR__ . '/../assets/css/hero-uifactory.css') ?>">
    <?php if (!empty($voyageDark)): ?><link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/voyage.css?v=<?= filemtime(__DIR__ . '/../assets/css/voyage.css') ?>"><?php endif; ?>
    <?= i18nJs() ?>
    <script defer src="<?= BASE_URL ?>/assets/js/klook.js?v=<?= filemtime(__DIR__ . '/../assets/js/klook.js') ?>"></script>
</head>
<body<?= !empty($voyageDark) ? ' class="voyage-page"' : '' ?>>

<a class="skip-link" href="#mainContent"><?= t('Skip to content') ?></a>
<script>
(function () {
    var t = localStorage.getItem('theme') || 'light';
    if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
})();
</script>

<?php if (!empty($voyageDark)): ?>
<?php $voyageTier = isset($_SESSION['user_id']) ? getTierBadgeInfo((int)$_SESSION['user_id']) : []; ?>
<?php if (isset($_SESSION['user_id'])) { require_once __DIR__ . '/notifications.php'; $voyageUnread = getUnreadCount((int)$_SESSION['user_id']); } else { $voyageUnread = 0; } ?>
<?php $voyageIsAdmin = false; try { if (isset($_SESSION['user_id']) && function_exists('getUserRole')) { $voyageIsAdmin = (getUserRole((int)$_SESSION['user_id']) === 'admin'); } } catch (Throwable $e) {} ?>
<?php $voyagePage = basename($_SERVER['PHP_SELF'] ?? ''); ?>
<!-- Navbar Voyage-style: floating glass pill (light) -->
<div class="voyage-nav-wrap">
<nav id="navbarNav" class="voyage-nav" aria-label="Main">
    <a class="voyage-brand" href="<?= BASE_URL ?>/">
        <span class="voyage-brand-dot"></span><?= e(SITE_NAME) ?>
    </a>
    <div class="voyage-tabs">
        <a href="<?= BASE_URL ?>/tours.php" class="<?= in_array($voyagePage, ['index.php', 'tours.php', 'tour-detail.php']) ? 'on' : '' ?>"><?= t('Tour') ?></a>
        <a href="<?= BASE_URL ?>/hotels.php" class="<?= strpos($voyagePage, 'hotel') !== false ? 'on' : '' ?>"><?= t('Hotel') ?></a>
        <a href="<?= BASE_URL ?>/flights.php" class="<?= strpos($voyagePage, 'flight') !== false ? 'on' : '' ?>"><?= t('Pesawat') ?></a>
    </div>
    <div class="voyage-actions">
        <div class="search-wrapper d-none d-xl-block" style="width: 200px;">
            <div class="input-group input-group-sm" style="border-radius: 999px; background: rgba(13,110,253,.07);">
                <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted small"></i></span>
                <input type="text" class="form-control bg-transparent border-0 shadow-none small" placeholder="<?= t('Cari destinasi...') ?>" id="navSearch" autocomplete="off" onkeypress="if(event.key==='Enter' && this.value.trim()) window.location='tours.php?search='+encodeURIComponent(this.value)">
            </div>
            <div class="search-dropdown" id="navSearchDropdown"></div>
        </div>
        <a class="voyage-icon" href="<?= BASE_URL ?>/wishlist.php" title="<?= t('Wishlist') ?>"><i class="bi bi-heart"></i></a>
        <?php if (isset($_SESSION['user_id'])): ?>
        <a class="voyage-icon position-relative" href="notifications.php" title="<?= t('Notifikasi') ?>"><i class="bi bi-bell"></i><?php if ($voyageUnread > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:9px;"><?= $voyageUnread > 9 ? '9+' : $voyageUnread ?></span><?php endif; ?></a>
        <?php endif; ?>
        <div class="dropdown voyage-drop">
            <a class="voyage-icon nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" id="currencyDropdown" aria-label="Currency"><i class="bi bi-currency-exchange"></i> <span id="currencyLabel">IDR</span></a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="currencyDropdown">
                <li><a class="dropdown-item currency-btn" href="#" data-currency="IDR">🇮🇩 IDR (Rp)</a></li>
                <li><a class="dropdown-item currency-btn" href="#" data-currency="SGD">🇸🇬 SGD (S$)</a></li>
                <li><a class="dropdown-item currency-btn" href="#" data-currency="USD">🇺🇸 USD ($)</a></li>
            </ul>
        </div>
        <div class="dropdown voyage-drop">
            <a class="voyage-icon nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-label="Language"><i class="bi bi-translate"></i> <?= strtoupper(getCurrentLang()) ?></a>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php $voyLangParams = $_GET; foreach (getSupportedLanguages() as $langCode => $langMeta): ?>
                <li><a class="dropdown-item <?= getCurrentLang() === $langCode ? 'active' : '' ?>" href="?<?= http_build_query(array_merge($voyLangParams, ['lang' => $langCode])) ?>"><?= $langMeta['flag'] ?> <?= e($langMeta['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <button id="themeToggle" class="voyage-icon" title="Theme" onclick="var t=document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark';document.documentElement.setAttribute('data-theme',t);localStorage.setItem('theme',t);this.innerHTML=t==='dark'?'☀️':'🌙';"><script>document.write(document.documentElement.getAttribute('data-theme')==='dark'?'☀️':'🌙')</script></button>
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="dropdown voyage-drop">
            <a class="voyage-user klook-user-dropdown dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-person-circle"></i><?= e($_SESSION['user_name'] ?? 'User') ?><?php if (!empty($voyageTier)): ?><span class="badge tier-badge ms-1" id="headerTierBadge" style="background: <?= e($voyageTier['color']) ?>; font-size: 10px;"><i class="bi <?= e($voyageTier['icon']) ?>"></i> <?= e($voyageTier['display_name']) ?></span><?php endif; ?></a>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php if (!empty($voyageTier)): ?>
                <li class="px-3 py-2 small"><span class="fw-semibold" style="color: <?= e($voyageTier['color']) ?>;"><i class="bi <?= e($voyageTier['icon']) ?> me-1"></i><?= e($voyageTier['display_name']) ?></span><span class="text-muted ms-1" id="headerPoints"><?= number_format($voyageTier['points']) ?> <?= t('poin') ?></span></li>
                <li><hr class="dropdown-divider"></li>
                <?php endif; ?>
                <li><a class="dropdown-item" href="my-points.php"><i class="bi bi-star me-2"></i><?= t('Poin Saya') ?></a></li>
                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-circle me-2"></i><?= t('Profil') ?></a></li>
                <li><a class="dropdown-item" href="my-bookings.php"><i class="bi bi-ticket-perforated me-2"></i><?= t('Booking Saya') ?></a></li>
                <li><a class="dropdown-item" href="wishlist.php"><i class="bi bi-heart me-2"></i><?= t('Wishlist') ?></a></li>
                <?php if (isLoggedIn() && isReseller((int)($_SESSION['user_id'] ?? 0))): ?>
                <li><a class="dropdown-item" href="reseller-topup.php"><i class="bi bi-wallet2 me-2"></i><?= t('Topup Saldo') ?></a></li>
                <?php endif; ?>
                <?php if (!empty($voyageIsAdmin)): ?>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-shield-lock me-2"></i><?= t('Admin') ?></a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i><?= t('Keluar') ?></a></li>
            </ul>
        </div>
        <?php else: ?>
        <a class="voyage-btn voyage-btn-ghost" href="register.php"><?= t('Daftar') ?></a>
        <a class="voyage-btn voyage-btn-solid" href="login.php"><?= t('Masuk') ?></a>
        <?php endif; ?>
        <button class="voyage-burger d-md-none" id="voyageBurger" aria-label="Menu">☰</button>
    </div>
</nav>
<div class="voyage-menu d-none" id="voyageMenu">
    <div class="search-wrapper mb-2">
        <div class="input-group input-group-sm" style="border-radius: 999px; background: rgba(13,110,253,.07);">
            <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted small"></i></span>
            <input type="text" class="form-control bg-transparent border-0 shadow-none small" placeholder="<?= t('Cari destinasi...') ?>" id="navSearchMobile" autocomplete="off" onkeypress="if(event.key==='Enter' && this.value.trim()) window.location='tours.php?search='+encodeURIComponent(this.value)">
        </div>
        <div class="search-dropdown" id="navSearchMobileDropdown"></div>
    </div>
    <a class="voyage-mitem" href="<?= BASE_URL ?>/"><i class="bi bi-house"></i><?= t('Beranda') ?></a>
    <a class="voyage-mitem" href="<?= BASE_URL ?>/tours.php"><i class="bi bi-map"></i><?= t('Tour') ?></a>
    <a class="voyage-mitem" href="<?= BASE_URL ?>/hotels.php"><i class="bi bi-building"></i><?= t('Hotel') ?></a>
    <a class="voyage-mitem" href="<?= BASE_URL ?>/flights.php"><i class="bi bi-airplane"></i><?= t('Pesawat') ?></a>
    <a class="voyage-mitem" href="<?= BASE_URL ?>/ferries.php"><i class="bi bi-ship"></i><?= t('Ferry') ?></a>
    <a class="voyage-mitem" href="<?= BASE_URL ?>/rental-cars.php"><i class="bi bi-car-front"></i><?= t('Rental') ?></a>
    <a class="voyage-mitem" href="<?= BASE_URL ?>/trains.php"><i class="bi bi-train-front"></i><?= t('Kereta') ?></a>
    <a class="voyage-mitem" href="<?= BASE_URL ?>/attractions.php"><i class="bi bi-signpost-2"></i><?= t('Atraksi') ?></a>
    <a class="voyage-mitem" href="<?= BASE_URL ?>/transfers.php"><i class="bi bi-arrow-left-right"></i><?= t('Transfer') ?></a>
    <a class="voyage-mitem" href="<?= BASE_URL ?>/esim.php"><i class="bi bi-sim"></i><?= t('eSIM') ?></a>
</div>
</div>
<script>document.addEventListener('DOMContentLoaded',function(){var b=document.getElementById('voyageBurger'),m=document.getElementById('voyageMenu');if(b&&m){b.addEventListener('click',function(){m.classList.toggle('d-none')})}});</script>
<?php else: ?>
<!-- Navbar Klook-style: 1 baris, putih bersih, sticky -->
<div class="sticky-top klook-navbar-wrap" style="z-index: 1020;">
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-2">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand fw-bold py-1 klook-logo" href="<?= BASE_URL ?>/" style="color: var(--primary);">
            <i class="bi bi-airplane-engines-fill me-1"></i><?= SITE_NAME ?>
        </a>

        <!-- Search pill (always visible desktop) -->
        <div class="mx-auto d-none d-lg-block" style="flex: 1; max-width: 360px;">
            <div class="search-wrapper">
                <div class="input-group input-group-sm klook-nav-search" style="border-radius: var(--radius-full); background: var(--bg-light);">
                    <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted small"></i></span>
                    <input type="text" class="form-control bg-transparent border-0 shadow-none small" 
                           placeholder="<?= t('Cari destinasi...') ?>" id="navSearch" autocomplete="off"
                           onkeypress="if(event.key==='Enter' && this.value.trim()) window.location='tours.php?search='+encodeURIComponent(this.value)">
                </div>
                <div class="search-dropdown" id="navSearchDropdown"></div>
            </div>
        </div>

        <!-- Toggler mobile -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
            <i class="bi bi-list fs-4"></i>
        </button>

        <!-- Nav items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php $tierBadge = isset($_SESSION['user_id']) ? getTierBadgeInfo((int)$_SESSION['user_id']) : []; ?>
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                <!-- Flat links (desktop: inline, mobile: stacked) -->
                <li class="nav-item d-lg-none">
                    <a class="nav-link py-2 <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active fw-semibold' : '' ?>" href="<?= BASE_URL ?>/">
                        <i class="bi bi-house me-2"></i><?= t('Beranda') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 klook-nav-link <?= strpos($_SERVER['PHP_SELF'], 'tours.php') !== false ? 'active fw-semibold' : '' ?>" href="<?= BASE_URL ?>/tours.php">
                        <i class="bi bi-map d-lg-none me-2"></i><?= t('Tour') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 klook-nav-link <?= strpos($_SERVER['PHP_SELF'], 'hotels') !== false ? 'active fw-semibold' : '' ?>" href="<?= BASE_URL ?>/hotels.php">
                        <i class="bi bi-building d-lg-none me-2"></i><?= t('Hotel') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 klook-nav-link <?= strpos($_SERVER['PHP_SELF'], 'flights') !== false ? 'active fw-semibold' : '' ?>" href="<?= BASE_URL ?>/flights.php">
                        <i class="bi bi-airplane d-lg-none me-2"></i><?= t('Pesawat') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 klook-nav-link <?= strpos($_SERVER['PHP_SELF'], 'ferries') !== false ? 'active fw-semibold' : '' ?>" href="<?= BASE_URL ?>/ferries.php">
                        <i class="bi bi-ship d-lg-none me-2"></i><?= t('Ferry') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 klook-nav-link <?= strpos($_SERVER['PHP_SELF'], 'rental-car') !== false ? 'active fw-semibold' : '' ?>" href="<?= BASE_URL ?>/rental-cars.php">
                        <i class="bi bi-car-front d-lg-none me-2"></i><?= t('Rental') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 klook-nav-link <?= strpos($_SERVER['PHP_SELF'], 'trains') !== false ? 'active fw-semibold' : '' ?>" href="<?= BASE_URL ?>/trains.php">
                        <i class="bi bi-train-front d-lg-none me-2"></i><?= t('Kereta') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 klook-nav-link <?= strpos($_SERVER['PHP_SELF'], 'attractions') !== false ? 'active fw-semibold' : '' ?>" href="<?= BASE_URL ?>/attractions.php">
                        <i class="bi bi-signpost-2 d-lg-none me-2"></i><?= t('Atraksi') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 klook-nav-link <?= strpos($_SERVER['PHP_SELF'], 'transfers') !== false ? 'active fw-semibold' : '' ?>" href="<?= BASE_URL ?>/transfers.php">
                        <i class="bi bi-arrow-left-right d-lg-none me-2"></i><?= t('Transfer') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 klook-nav-link <?= strpos($_SERVER['PHP_SELF'], 'esim') !== false ? 'active fw-semibold' : '' ?>" href="<?= BASE_URL ?>/esim.php">
                        <i class="bi bi-sim d-lg-none me-2"></i><?= t('eSIM') ?>
                    </a>
                </li>
                <li class="nav-item d-lg-none">
                    <hr class="my-1">
                </li>

                <!-- Notifications -->
                <?php if (isset($_SESSION['user_id'])): require_once 'includes/notifications.php'; $nUnread = getUnreadCount((int)$_SESSION['user_id']); ?>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="notifications.php" title="<?= t('Notifikasi') ?>">
                        <i class="bi bi-bell"></i>
                        <?php if ($nUnread > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:9px;"><?= $nUnread > 9 ? '9+' : $nUnread ?></span><?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Wishlist -->
                <li class="nav-item">
                    <a class="nav-link py-2 klook-nav-icon <?= basename($_SERVER['PHP_SELF']) === 'wishlist.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/wishlist.php" title="<?= t('Wishlist') ?>">
                        <i class="bi bi-heart"></i>
                    </a>
                </li>

                <!-- User menu -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle py-2 klook-user-dropdown" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i><?= e($_SESSION['user_name'] ?? 'User') ?>
                        <?php if (!empty($tierBadge)): ?>
                        <span class="badge tier-badge ms-1" id="headerTierBadge" style="background: <?= e($tierBadge['color']) ?>; font-size: 10px;" title="<?= e($tierBadge['display_name']) ?> — <?= number_format($tierBadge['points']) ?> <?= t('poin') ?>">
                            <i class="bi <?= e($tierBadge['icon']) ?>"></i> <?= e($tierBadge['display_name']) ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if (!empty($tierBadge)): ?>
                        <li class="px-3 py-2 small">
                            <span class="fw-semibold" style="color: <?= e($tierBadge['color']) ?>;"><i class="bi <?= e($tierBadge['icon']) ?> me-1"></i><?= e($tierBadge['display_name']) ?></span>
                            <span class="text-muted ms-1" id="headerPoints"><?= number_format($tierBadge['points']) ?> <?= t('poin') ?></span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="my-points.php"><i class="bi bi-star me-2"></i><?= t('Poin Saya') ?></a></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-circle me-2"></i><?= t('Profil') ?></a></li>
                        <li><a class="dropdown-item" href="my-bookings.php"><i class="bi bi-ticket-perforated me-2"></i><?= t('Booking Saya') ?></a></li>
                        <li><a class="dropdown-item" href="wishlist.php"><i class="bi bi-heart me-2"></i><?= t('Wishlist') ?></a></li>
                        <?php if (isLoggedIn() && isReseller((int)($_SESSION['user_id'] ?? 0))): ?>
                        <li><a class="dropdown-item" href="reseller-topup.php"><i class="bi bi-wallet2 me-2"></i><?= t('Topup Saldo') ?></a></li>
                        <?php endif; ?>
                        <?php if (!empty($isAdmin)): ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-shield-lock me-2"></i><?= t('Admin') ?></a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i><?= t('Keluar') ?></a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item d-flex align-items-center gap-2">
                    <a class="btn btn-sm btn-outline-primary rounded-pill px-3 klook-btn-signup" href="register.php"><?= t('Daftar') ?></a>
                    <a class="btn btn-sm btn-primary rounded-pill px-3 klook-btn-login" href="login.php"><?= t('Masuk') ?></a>
                </li>
                <?php endif; ?>

                <!-- Currency -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle py-2 small klook-currency-btn" href="#" data-bs-toggle="dropdown" id="currencyDropdown">
                        <i class="bi bi-currency-exchange"></i> <span id="currencyLabel">IDR</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="currencyDropdown">
                        <li><a class="dropdown-item currency-btn" href="#" data-currency="IDR">🇮🇩 IDR (Rp)</a></li>
                        <li><a class="dropdown-item currency-btn" href="#" data-currency="SGD">🇸🇬 SGD (S$)</a></li>
                        <li><a class="dropdown-item currency-btn" href="#" data-currency="USD">🇺🇸 USD ($)</a></li>
                    </ul>
                </li>

                <!-- Language -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle py-2 small klook-lang-btn" href="#" data-bs-toggle="dropdown" aria-label="Language">
                        <i class="bi bi-translate"></i> <?= strtoupper(getCurrentLang()) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php $langParams = $_GET; foreach (getSupportedLanguages() as $langCode => $langMeta): ?>
                        <li><a class="dropdown-item <?= getCurrentLang() === $langCode ? 'active' : '' ?>" href="?<?= http_build_query(array_merge($langParams, ['lang' => $langCode])) ?>"><?= $langMeta['flag'] ?> <?= e($langMeta['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>

                <!-- Mobile search -->
                <li class="nav-item d-lg-none mt-2">
                    <div class="search-wrapper">
                        <div class="input-group input-group-sm" style="border-radius: var(--radius-full); background: var(--bg-light);">
                            <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted small"></i></span>
                            <input type="text" class="form-control bg-transparent border-0 shadow-none small" 
                                   placeholder="<?= t('Cari destinasi...') ?>" id="navSearchMobile" autocomplete="off"
                                   onkeypress="if(event.key==='Enter' && this.value.trim()) window.location='tours.php?search='+encodeURIComponent(this.value)">
                        </div>
                        <div class="search-dropdown" id="navSearchMobileDropdown"></div>
                    </div>
                </li>
            <li class="nav-item">
                    <button id="themeToggle" class="btn btn-sm btn-outline-secondary rounded-pill px-2" title="Theme" onclick="var t=document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark';document.documentElement.setAttribute('data-theme',t);localStorage.setItem('theme',t);this.innerHTML=t==='dark'?'☀️':'🌙';"><script>document.write(document.documentElement.getAttribute('data-theme')==='dark'?'☀️':'🌙')</script></button>
                </li>
</ul>
        </div>
    </div>
</nav>
</div><!-- /.sticky-top -->
<?php endif; ?>