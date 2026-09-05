<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = t('Blog');
$metaDesc = t('Artikel, tips, dan panduan traveling dari TourAndTravel.');

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;
$category = trim($_GET['category'] ?? '');

$where = "status = 'published'";
$params = [];
if ($category !== '') { $where .= " AND category = ?"; $params[] = $category; }

$total = db()->prepare("SELECT COUNT(*) FROM posts WHERE $where");
$total->execute($params);
$total = (int)$total->fetchColumn();
$lastPage = max(1, (int)ceil($total / $perPage));
$page = min($page, $lastPage);

$rows = db()->prepare("SELECT * FROM posts WHERE $where ORDER BY published_at DESC, id DESC LIMIT $perPage OFFSET $offset");
$rows->execute($params);
$rows = $rows->fetchAll();

$categories = db()->query("SELECT DISTINCT category FROM posts WHERE status='published' AND category IS NOT NULL AND category != ''")->fetchAll(PDO::FETCH_COLUMN);

require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-1"><?= t('Blog') ?></h4>
        <p class="text-muted small"><?= t('Tips, panduan, dan cerita perjalanan') ?></p>

        <div class="d-flex flex-wrap gap-2 mb-4">
            <a href="blog.php" class="btn btn-sm <?= $category === '' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3"><?= t('Semua') ?></a>
            <?php foreach ($categories as $cat): ?>
                <a href="blog.php?category=<?= urlencode($cat) ?>" class="btn btn-sm <?= $category === $cat ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3"><?= e($cat) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (count($rows)): ?>
        <div class="row g-3">
            <?php foreach ($rows as $p): ?>
            <div class="col-md-4">
                <a href="blog-detail.php?slug=<?= e($p['slug']) ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 klook-hover-card overflow-hidden">
                        <img src="<?= e($p['cover_image'] ?: 'https://placehold.co/640x360?text=' . urlencode(e(tContent($p, 'title')))) ?>" class="w-100" style="height: 170px; object-fit: cover;" alt="<?= e(tContent($p, 'title')) ?>">
                        <div class="card-body p-3">
                            <?php if ($p['category']): ?><span class="badge bg-primary mb-2"><?= e($p['category']) ?></span><?php endif; ?>
                            <h6 class="fw-semibold text-dark"><?= e(tContent($p, 'title')) ?></h6>
                            <p class="text-muted small mb-2"><?= e(mb_substr((string)tContent($p, 'excerpt'), 0, 90)) ?>…</p>
                            <small class="text-muted"><?= $p['published_at'] ? date('d M Y', strtotime($p['published_at'])) : '' ?></small>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($lastPage > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?><?= $category !== '' ? '&category=' . urlencode($category) : '' ?>"><?= $i ?></a></li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-journal-text fs-1 text-muted"></i>
            <p class="mt-2 text-muted"><?= t('Belum ada artikel.') ?></p>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
