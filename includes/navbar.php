<?php
/**
 * includes/navbar.php — SATU navbar untuk semua halaman publik (reusable).
 * Dipakai via header-shared.php. Variabel masuk: $voyageDark (bool),
 * $voyageMenus (dari getNavMenus()), $voyagePage (basename PHP_SELF).
 * Menu dinamis via admin/nav-menus.php (tabel nav_menus).
 */
if (!function_exists('getNavMenus')) require_once __DIR__ . '/nav-menus.php';
$voyageDark = !empty($voyageDark);
$voyageMenus = $voyageMenus ?? getNavMenus();
$voyagePage = $voyagePage ?? basename($_SERVER['PHP_SELF'] ?? '');
$navTier = isset($_SESSION['user_id']) ? getTierBadgeInfo((int)$_SESSION['user_id']) : [];
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/notifications.php';
    $navUnread = getUnreadCount((int)$_SESSION['user_id']);
} else {
    $navUnread = 0;
}
$navIsAdmin = false;
try {
    if (isset($_SESSION['user_id']) && function_exists('getUserRole')) {
        $navIsAdmin = (getUserRole((int)$_SESSION['user_id']) === 'admin');
    }
} catch (Throwable $e) {}
$navLangParams = $_GET;
$navTabs = array_values(array_filter($voyageMenus, fn($m) => !empty($m['show_in_tabs'])));
$navMenuItems = array_values(array_filter($voyageMenus, fn($m) => !empty($m['show_in_menu'])));
?>
<?php if ($voyageDark): ?>
<div class="voyage-nav-wrap">
<nav id="navbarNav" class="voyage-nav" aria-label="Main">
    <a class="voyage-brand" href="<?= BASE_URL ?>/"><span class="voyage-brand-dot"></span><?= e(SITE_NAME) ?></a>
    <div class="voyage-tabs">
        <?php foreach ($navTabs as $nm): ?>
        <a href="<?= BASE_URL ?>/<?= e($nm['url']) ?>" class="<?= navMenuIsActive($nm, $voyagePage) ? 'on' : '' ?>"><?= t($nm['label']) ?></a>
        <?php endforeach; ?>
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
        <a class="voyage-icon position-relative" href="notifications.php" title="<?= t('Notifikasi') ?>"><i class="bi bi-bell"></i><?php if ($navUnread > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:9px;"><?= $navUnread > 9 ? '9+' : $navUnread ?></span><?php endif; ?></a>
        <?php endif; ?>
        <div class="dropdown voyage-drop">
            <a class="voyage-icon nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" id="currencyDropdown" aria-label="Currency"><i class="bi bi-currency-exchange"></i> <span id="currencyLabel">IDR</span></a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="currencyDropdown">
                <li><a class="dropdown-item currency-btn" href="#" data-currency="IDR">IDR (Rp)</a></li>
                <li><a class="dropdown-item currency-btn" href="#" data-currency="SGD">SGD (S$)</a></li>
                <li><a class="dropdown-item currency-btn" href="#" data-currency="USD">USD ($)</a></li>
            </ul>
        </div>
        <div class="dropdown voyage-drop">
            <a class="voyage-icon nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-label="Language"><i class="bi bi-translate"></i> <?= strtoupper(getCurrentLang()) ?></a>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php foreach (getSupportedLanguages() as $langCode => $langMeta): ?>
                <li><a class="dropdown-item <?= getCurrentLang() === $langCode ? 'active' : '' ?>" href="?<?= http_build_query(array_merge($navLangParams, ['lang' => $langCode])) ?>"><?= $langMeta['flag'] ?> <?= e($langMeta['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <button id="themeToggle" class="voyage-icon" title="Theme" onclick="var t=document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark';document.documentElement.setAttribute('data-theme',t);localStorage.setItem('theme',t);this.innerHTML=t==='dark'?'?':'?';"><script>document.write(document.documentElement.getAttribute('data-theme')==='dark'?'?':'?')</script></button>
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="dropdown voyage-drop">
            <a class="voyage-user klook-user-dropdown dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-person-circle"></i><?= e($_SESSION['user_name'] ?? 'User') ?><?php if (!empty($navTier)): ?><span class="badge tier-badge ms-1" id="headerTierBadge" style="background: <?= e($navTier['color']) ?>; font-size: 10px;"><i class="bi <?= e($navTier['icon']) ?>"></i> <?= e($navTier['display_name']) ?></span><?php endif; ?></a>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php if (!empty($navTier)): ?>
                <li class="px-3 py-2 small"><span class="fw-semibold" style="color: <?= e($navTier['color']) ?>;"><i class="bi <?= e($navTier['icon']) ?> me-1"></i><?= e($navTier['display_name']) ?></span><span class="text-muted ms-1" id="headerPoints"><?= number_format($navTier['points']) ?> <?= t('poin') ?></span></li>
                <li><hr class="dropdown-divider"></li>
                <?php endif; ?>
                <li><a class="dropdown-item" href="my-points.php"><i class="bi bi-star me-2"></i><?= t('Poin Saya') ?></a></li>
                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-circle me-2"></i><?= t('Profil') ?></a></li>
                <li><a class="dropdown-item" href="my-bookings.php"><i class="bi bi-ticket-perforated me-2"></i><?= t('Booking Saya') ?></a></li>
                <li><a class="dropdown-item" href="wishlist.php"><i class="bi bi-heart me-2"></i><?= t('Wishlist') ?></a></li>
                <?php if (isLoggedIn() && isReseller((int)($_SESSION['user_id'] ?? 0))): ?>
                <li><a class="dropdown-item" href="reseller-topup.php"><i class="bi bi-wallet2 me-2"></i><?= t('Topup Saldo') ?></a></li>
                <?php endif; ?>
                <?php if (!empty($navIsAdmin)): ?>
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
    <?php foreach ($navMenuItems as $nm): ?>
    <a class="voyage-mitem" href="<?= BASE_URL ?>/<?= e($nm['url']) ?>"><i class="bi <?= e($nm['icon'] ?: 'bi-circle') ?>"></i><?= t($nm['label']) ?></a>
    <?php endforeach; ?>
</div>
</div>
<script>document.addEventListener('DOMContentLoaded',function(){var b=document.getElementById('voyageBurger'),m=document.getElementById('voyageMenu');if(b&&m){b.addEventListener('click',function(){m.classList.toggle('d-none')})}});</script>
<?php else: ?>
<div class="sticky-top klook-navbar-wrap" style="z-index: 1020;">
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-2">
    <div class="container">
        <a class="navbar-brand fw-bold py-1 klook-logo" href="<?= BASE_URL ?>/" style="color: var(--primary);"><i class="bi bi-airplane-engines-fill me-1"></i><?= SITE_NAME ?></a>
        <div class="mx-auto d-none d-lg-block" style="flex: 1; max-width: 360px;">
            <div class="search-wrapper">
                <div class="input-group input-group-sm klook-nav-search" style="border-radius: var(--radius-full); background: var(--bg-light);">
                    <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted small"></i></span>
                    <input type="text" class="form-control bg-transparent border-0 shadow-none small" placeholder="<?= t('Cari destinasi...') ?>" id="navSearch" autocomplete="off" onkeypress="if(event.key==='Enter' && this.value.trim()) window.location='tours.php?search='+encodeURIComponent(this.value)">
                </div>
                <div class="search-dropdown" id="navSearchDropdown"></div>
            </div>
        </div>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation"><i class="bi bi-list fs-4"></i></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                <li class="nav-item d-lg-none"><a class="nav-link py-2 <?= $voyagePage === 'index.php' ? 'active fw-semibold' : '' ?>" href="<?= BASE_URL ?>/"><i class="bi bi-house me-2"></i><?= t('Beranda') ?></a></li>
                <?php foreach ($navMenuItems as $nm): ?>
                <li class="nav-item"><a class="nav-link py-2 klook-nav-link <?= navMenuIsActive($nm, $voyagePage) ? 'active fw-semibold' : '' ?>" href="<?= BASE_URL ?>/<?= e($nm['url']) ?>"><i class="bi <?= e($nm['icon'] ?: 'bi-circle') ?> d-lg-none me-2"></i><?= t($nm['label']) ?></a></li>
                <?php endforeach; ?>
                <li class="nav-item d-lg-none"><hr class="my-1"></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item"><a class="nav-link position-relative" href="notifications.php" title="<?= t('Notifikasi') ?>"><i class="bi bi-bell"></i><?php if ($navUnread > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:9px;"><?= $navUnread > 9 ? '9+' : $navUnread ?></span><?php endif; ?></a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link py-2 klook-nav-icon <?= $voyagePage === 'wishlist.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/wishlist.php" title="<?= t('Wishlist') ?>"><i class="bi bi-heart"></i></a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle py-2 klook-user-dropdown" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="bi bi-person-circle me-1"></i><?= e($_SESSION['user_name'] ?? 'User') ?><?php if (!empty($navTier)): ?><span class="badge tier-badge ms-1" id="headerTierBadge" style="background: <?= e($navTier['color']) ?>; font-size: 10px;" title="<?= e($navTier['display_name']) ?> — <?= number_format($navTier['points']) ?> <?= t('poin') ?>"><i class="bi <?= e($navTier['icon']) ?>"></i> <?= e($navTier['display_name']) ?></span><?php endif; ?></a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if (!empty($navTier)): ?>
                        <li class="px-3 py-2 small"><span class="fw-semibold" style="color: <?= e($navTier['color']) ?>;"><i class="bi <?= e($navTier['icon']) ?> me-1"></i><?= e($navTier['display_name']) ?></span><span class="text-muted ms-1" id="headerPoints"><?= number_format($navTier['points']) ?> <?= t('poin') ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="my-points.php"><i class="bi bi-star me-2"></i><?= t('Poin Saya') ?></a></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-circle me-2"></i><?= t('Profil') ?></a></li>
                        <li><a class="dropdown-item" href="my-bookings.php"><i class="bi bi-ticket-perforated me-2"></i><?= t('Booking Saya') ?></a></li>
                        <li><a class="dropdown-item" href="wishlist.php"><i class="bi bi-heart me-2"></i><?= t('Wishlist') ?></a></li>
                        <?php if (isLoggedIn() && isReseller((int)($_SESSION['user_id'] ?? 0))): ?>
                        <li><a class="dropdown-item" href="reseller-topup.php"><i class="bi bi-wallet2 me-2"></i><?= t('Topup Saldo') ?></a></li>
                        <?php endif; ?>
                        <?php if (!empty($navIsAdmin)): ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-shield-lock me-2"></i><?= t('Admin') ?></a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i><?= t('Keluar') ?></a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item d-flex align-items-center gap-2"><a class="btn btn-sm btn-outline-primary rounded-pill px-3 klook-btn-signup" href="register.php"><?= t('Daftar') ?></a><a class="btn btn-sm btn-primary rounded-pill px-3 klook-btn-login" href="login.php"><?= t('Masuk') ?></a></li>
                <?php endif; ?>
                <li class="nav-item dropdown"><a class="nav-link dropdown-toggle py-2 small klook-currency-btn" href="#" data-bs-toggle="dropdown" id="currencyDropdown"><i class="bi bi-currency-exchange"></i> <span id="currencyLabel">IDR</span></a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="currencyDropdown"><li><a class="dropdown-item currency-btn" href="#" data-currency="IDR">IDR (Rp)</a></li><li><a class="dropdown-item currency-btn" href="#" data-currency="SGD">SGD (S$)</a></li><li><a class="dropdown-item currency-btn" href="#" data-currency="USD">USD ($)</a></li></ul>
                </li>
                <li class="nav-item dropdown"><a class="nav-link dropdown-toggle py-2 small klook-lang-btn" href="#" data-bs-toggle="dropdown" aria-label="Language"><i class="bi bi-translate"></i> <?= strtoupper(getCurrentLang()) ?></a>
                    <ul class="dropdown-menu dropdown-menu-end"><?php foreach (getSupportedLanguages() as $langCode => $langMeta): ?><li><a class="dropdown-item <?= getCurrentLang() === $langCode ? 'active' : '' ?>" href="?<?= http_build_query(array_merge($navLangParams, ['lang' => $langCode])) ?>"><?= $langMeta['flag'] ?> <?= e($langMeta['label']) ?></a></li><?php endforeach; ?></ul>
                </li>
                <li class="nav-item d-lg-none mt-2"><div class="search-wrapper"><div class="input-group input-group-sm" style="border-radius: var(--radius-full); background: var(--bg-light);"><span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted small"></i></span><input type="text" class="form-control bg-transparent border-0 shadow-none small" placeholder="<?= t('Cari destinasi...') ?>" id="navSearchMobile" autocomplete="off" onkeypress="if(event.key==='Enter' && this.value.trim()) window.location='tours.php?search='+encodeURIComponent(this.value)"></div><div class="search-dropdown" id="navSearchMobileDropdown"></div></div></li>
                <li class="nav-item"><button id="themeToggle" class="btn btn-sm btn-outline-secondary rounded-pill px-2" title="Theme" onclick="var t=document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark';document.documentElement.setAttribute('data-theme',t);localStorage.setItem('theme',t);this.innerHTML=t==='dark'?'?':'?';"><script>document.write(document.documentElement.getAttribute('data-theme')==='dark'?'?':'?')</script></button></li>
            </ul>
        </div>
    </div>
</nav>
</div>
<?php endif; ?>
