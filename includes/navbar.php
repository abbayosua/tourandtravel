<?php
/**
 * includes/navbar.php — SATU navbar Voyage untuk SEMUA halaman publik (reusable).
 * Dipakai via header-shared.php. Variabel masuk: $voyagePageOnly (bool),
 * $voyageMenus (dari getNavMenus()), $voyagePage (basename PHP_SELF).
 * Menu dinamis via admin/nav-menus.php (tabel nav_menus).
 */
if (!function_exists('getNavMenus')) require_once __DIR__ . '/nav-menus.php';
$voyagePageOnly = ($voyagePageOnly ?? true) ? true : false;
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
<div class="voyage-nav-wrap">
<nav id="navbarNav" class="voyage-nav" aria-label="Main">
    <?php $navLogo = function_exists('siteLogoUrl') ? siteLogoUrl() : ''; ?>
    <a class="voyage-brand" href="<?= BASE_URL ?>/" data-testid="brand-nav"><?php if ($navLogo): ?><img src="<?= e($navLogo) ?>" alt="<?= e(siteName()) ?>" style="height:28px;width:auto" data-testid="brand-logo"><?php else: ?><span class="voyage-brand-dot"></span><?php endif; ?><?= e(siteName()) ?></a>
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
