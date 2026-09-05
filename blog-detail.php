<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
$stmt = db()->prepare("SELECT * FROM posts WHERE slug = ? AND status = 'published' LIMIT 1");
$stmt->execute([$slug]);
$post = $stmt->fetch();
if (!$post) { http_response_code(404); $pageTitle = t('Artikel tidak ditemukan'); require_once 'includes/header-klook.php'; echo '<div class="container py-5 text-center"><h3>' . t('Artikel tidak ditemukan') . '</h3><a href="blog.php" class="btn btn-primary mt-3">' . t('Kembali') . '</a></div>'; require_once 'includes/footer-klook.php'; exit; }

$pageTitle = tContent($post, 'title');
$metaDesc = mb_substr(trim(strip_tags((string)tContent($post, 'excerpt'))), 0, 160);
$jsonLd = ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $pageTitle, 'datePublished' => $post['published_at']];

$related = db()->query("SELECT * FROM tours WHERE is_active = 1 AND best_seller = 1 LIMIT 4")->fetchAll();

require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($post['cover_image']): ?>
                    <img src="<?= e($post['cover_image']) ?>" class="w-100 rounded-4 mb-4" style="max-height: 380px; object-fit: cover;" alt="<?= e($pageTitle) ?>">
                <?php endif; ?>
                <h1 class="fw-bold mb-3"><?= e($pageTitle) ?></h1>
                <p class="text-muted small"><?= $post['published_at'] ? date('d M Y', strtotime($post['published_at'])) : '' ?> · <?= e($post['category'] ?? '') ?></p>
                <div class="article-body mb-5"><?= nl2br(e(tContent($post, 'body'))) ?></div>

                <?php if (count($related)): ?>
                <hr class="my-5">
                <h5 class="fw-bold mb-3"><?= t('Paket Rekomendasi') ?></h5>
                <div class="row g-3">
                    <?php foreach ($related as $t): ?>
                    <div class="col-md-3 col-6">
                        <a href="tour-detail.php?slug=<?= e($t['slug']) ?>" class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100 klook-hover-card">
                                <img src="<?= getTourImage($t, 'small') ?>" class="w-100" style="height: 110px; object-fit: cover;" alt="<?= e(t($t['title'])) ?>">
                                <div class="card-body p-2">
                                    <h6 class="fw-semibold small text-dark mb-1"><?= e(t($t['title'], null, $t['content_language'] ?? 'id')) ?></h6>
                                    <span class="fw-bold text-primary small"><?= formatRupiah($t['price']) ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
