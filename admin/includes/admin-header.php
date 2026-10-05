<!DOCTYPE html>
<html lang="<?= getCurrentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? t('Admin')) ?> - <?= siteName() ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-voyage.css">
    <?php require_once __DIR__ . '/../../includes/components/date-picker.php'; dpAssets(); ?>
<style>
#adminSidebar {
    width: 250px;
    height: calc(100vh - 56px);
    position: sticky;
    top: 56px;
    overflow-y: auto;
    overflow-x: hidden;
    overscroll-behavior-y: contain;
    transition: width 0.3s ease, padding 0.3s ease;
    flex-shrink: 0;
    align-self: flex-start;
    scrollbar-width: thin;
    scrollbar-color: rgba(13,110,253,.35) transparent;
}
#adminSidebar::-webkit-scrollbar {
    width: 6px;
}
#adminSidebar::-webkit-scrollbar-track {
    background: transparent;
}
#adminSidebar::-webkit-scrollbar-thumb {
    background: rgba(13,110,253,.3);
    border-radius: 999px;
}
[data-theme="dark"] #adminSidebar {
    scrollbar-color: rgba(255,255,255,.25) transparent;
}
[data-theme="dark"] #adminSidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,.22);
}
#adminSidebar.collapsed {
    width: 0;
    padding: 0;
    overflow: hidden;
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
        white-space: nowrap;
        overflow: hidden;
    }
    #adminSidebar.icon-only .nav-link span.nav-label {
        display: none;
    }
    #adminSidebar.icon-only .nav-section-label {
        display: none;
    }
    #adminSidebar.icon-only .nav-link i {
        margin-right: 0;
    }
    #adminSidebar.icon-only hr {
        margin: 0.5rem 0;
    }
    /* Baris bahasa: hanya badge aktif yang tampil, di tengah */
    #adminSidebar.icon-only hr + .nav-link {
        justify-content: center !important;
    }
    #adminSidebar.icon-only .nav-link .badge:not(.bg-primary) {
        display: none;
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
<script>
(function () {
    var t = localStorage.getItem('theme') || 'light';
    if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
})();
</script>
<div class="admin-bg" aria-hidden="true"><div class="admin-bg-img"></div><div class="admin-bg-grad"></div><div class="admin-bg-glow"></div></div>
<!-- Navbar -->
<nav class="navbar navbar-expand sticky-top" id="adminTopbar">
    <div class="container-fluid">
        <button class="voyage-icon-btn me-2" id="sidebarToggle" title="<?= t("Toggle Sidebar") ?>">
            <i class="bi bi-list"></i>
        </button>
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="bi bi-airplane-engines-fill"></i> <?= t('Admin Panel') ?>
        </a>
        <div class="d-flex align-items-center ms-auto">
            <button id="adminThemeToggle" class="voyage-icon-btn me-2" title="<?= e(t('Tema')) ?>"><i class="bi bi-moon-stars"></i></button>
            <span class="admin-user me-3 small"><?= e($_SESSION['admin_username']) ?> <span class="badge <?= isSuperadmin() ? 'bg-danger' : 'bg-info' ?>"><?= e(isSuperadmin() ? t('Superadmin') : t('Staff')) ?></span></span>
            <a href="logout.php" class="btn-voyage-ghost"><?= t('Logout') ?></a>
        </div>
    </div>
</nav>

<div class="container-fluid px-0">
    <div id="sidebarOverlay"></div>
    <div class="d-flex" id="adminWrapper">
        <!-- Sidebar -->
        <div class="sidebar p-3" id="adminSidebar">
            <nav class="nav flex-column">
                <?php
                require_once __DIR__ . '/admin-access.php';
                $currentPage = basename($_SERVER['PHP_SELF']);
                $sectionHdr = function (string $label) { ?>
                <div class="nav-section-label small text-secondary text-uppercase fw-bold px-2 pt-2 pb-1" style="letter-spacing:.08em; font-size:.68rem;"><?= $label ?></div>
                <?php };
                $navItem = function (string $href, string $icon, string $label, array $activePages, string $testid = '') use ($currentPage) { ?>
                <a class="nav-link <?= in_array($currentPage, $activePages, true) ? 'active' : '' ?>" href="<?= $href ?>"<?= $testid ? " data-testid=\"$testid\"" : '' ?>>
                    <i class="bi <?= $icon ?>"></i><span class="nav-label"> <?= $label ?></span>
                </a>
                <?php };

                // testid lama dipertahankan untuk E2E
                $navTestids = [
                    'flash-sales' => 'nav-flash-sales',
                    'sales-report' => 'nav-sales-report',
                    'accounting' => 'nav-accounting',
                    'brand-settings' => 'nav-brand-settings',
                    'chat-settings' => 'nav-chat-settings',
                    'hotel-api-settings' => 'nav-hotel-api-settings',
                ];
                $shownNav = [];
                foreach (adminPages() as $pKey => $p) {
                    if (empty($p['show'])) continue;
                    if (!canAccessPage($pKey)) continue;
                    $shownNav[$p['section']][] = $pKey;
                }
                $allPages = adminPages();
                foreach (adminSections() as $section) {
                    if (empty($shownNav[$section])) continue;
                    $sectionHdr(t($section));
                    foreach ($shownNav[$section] as $pKey) {
                        $p = $allPages[$pKey];
                        $navItem($p['file'], $p['icon'], t($p['label']), adminFilesForKey($pKey), $navTestids[$pKey] ?? '');
                    }
                }

                // ===== EXTERNAL (selalu tampil) =====
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
            <?= adminFlashHtml() ?>
