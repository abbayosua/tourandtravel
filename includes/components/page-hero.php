<?php
/**
 * includes/components/page-hero.php — Voyage page-hero reusable untuk SEMUA halaman.
 * Samakan latar dengan landing: bg image + gradasi terang + glow, pill navbar floating di atasnya.
 * Dipakai via renderPageHero($title, $sub, $crumbs) — panggil sekali setelah header-shared.
 */
function pageHeroImage(string $page): string {
    $map = [
        'tours.php' => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=2070&q=80',
        'tour-detail.php' => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=2070&q=80',
        'hotels.php' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=2070&q=80',
        'hotel-detail.php' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=2070&q=80',
        'flights.php' => 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=2070&q=80',
        'flight-detail.php' => 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=2070&q=80',
        'ferries.php' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=2070&q=80',
        'trains.php' => 'https://images.unsplash.com/photo-1474487548417-781cb71495f3?auto=format&fit=crop&w=2070&q=80',
        'rental-cars.php' => 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?auto=format&fit=crop&w=2070&q=80',
        'attractions.php' => 'https://images.unsplash.com/photo-1533929736458-ca588d08c8be?auto=format&fit=crop&w=2070&q=80',
        'transfers.php' => 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?auto=format&fit=crop&w=2070&q=80',
        'esim.php' => 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?auto=format&fit=crop&w=2070&q=80',
        'destinasi.php' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=2070&q=80',
        'blog.php' => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=2070&q=80',
    ];
    return $map[$page] ?? 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=2070&q=80';
}

function renderPageHero(string $title, string $sub = '', array $crumbs = []) {
    $page = basename($_SERVER['PHP_SELF'] ?? '');
    $img = pageHeroImage($page);
    $crumbs = array_values(array_filter($crumbs, fn($c) => trim($c['label'] ?? '') !== ''));
    if (count($crumbs) === 1 && empty($crumbs[0]['url'])) $crumbs = [];
    ?>
    <section class="voyage-pagehero">
        <div class="voyage-bg">
            <div class="voyage-bg-img" style="background-image:url('<?= e($img) ?>')"></div>
            <div class="voyage-bg-grad"></div>
            <div class="voyage-bg-glow"></div>
        </div>
        <div class="voyage-inner">
            <?php if (!empty($crumbs)): ?>
            <nav class="voyage-crumbs" aria-label="breadcrumb">
                <a href="<?= BASE_URL ?>/">Home</a>
                <?php foreach ($crumbs as $c): ?>
                <span class="voyage-crumb-sep">/</span>
                <?php if (!empty($c['url'])): ?><a href="<?= e($c['url']) ?>"><?= e($c['label']) ?></a><?php else: ?><span class="on"><?= e($c['label']) ?></span><?php endif; ?>
                <?php endforeach; ?>
            </nav>
            <?php endif; ?>
            <h1 class="voyage-page-title serif"><?= e($title) ?></h1>
            <?php if ($sub !== ''): ?><p class="voyage-page-sub"><?= e($sub) ?></p><?php endif; ?>
        </div>
    </section>
    <?php
}
