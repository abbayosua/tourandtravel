<?php
/**
 * includes/header-shared.php — SATU header Voyage untuk SEMUA halaman publik (reusable).
 * Variabel opsional sebelum include: $pageTitle, $voyagePageOnly (bool, default true).
 * - $voyagePageOnly=true  (halaman biasa): navbar Voyage floating, body normal + spacer.
 * - $voyagePageOnly=false (landing tour): body voyage-page, hero full-bleed.
 * Menu tab + mobile dinamis via getNavMenus() → admin/nav-menus.php.
 */
if (!function_exists('getNavMenus')) require_once __DIR__ . '/nav-menus.php';
$voyagePageOnly = ($voyagePageOnly ?? true) ? true : false;
$voyageDark = !$voyagePageOnly;
$voyageMenus = getNavMenus();
$voyagePage = basename($_SERVER['PHP_SELF'] ?? '');
$voyageHeroPages = ['index.php','tours.php','hotels.php','flights.php','ferries.php'];
$voyageNoSpacer = !empty($voyageNoSpacer) || in_array($voyagePage, $voyageHeroPages, true);
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLang() ?>">
<head>
    <meta charset="UTF-8">
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.php">
    <meta name="theme-color" content="#0d6efd">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title><?= e($pageTitle ?? siteName()) ?> - <?= siteName() ?></title>
    <?php require_once __DIR__ . '/seo.php'; seoHead($metaDesc ?? null, $ogImage ?? null, $jsonLd ?? null); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/design-tokens.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/hero-uifactory.css?v=<?= filemtime(__DIR__ . '/../assets/css/hero-uifactory.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/voyage.css?v=<?= filemtime(__DIR__ . '/../assets/css/voyage.css') ?>">
    <?= i18nJs() ?>
    <script defer src="<?= BASE_URL ?>/assets/js/klook.js?v=<?= filemtime(__DIR__ . '/../assets/js/klook.js') ?>"></script>
</head>
<body<?= $voyageDark ? ' class="voyage-page"' : '' ?>>

<a class="skip-link" href="#mainContent"><?= t('Skip to content') ?></a>
<script>
(function () {
    var t = localStorage.getItem('theme') || 'light';
    if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
})();
</script>

<?php require __DIR__ . '/navbar.php'; ?>
<?php if (empty($voyageNoSpacer)): ?><div class="voyage-spreader"></div><?php endif; ?>
