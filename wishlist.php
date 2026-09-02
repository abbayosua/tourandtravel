<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=wishlist.php');
    exit;
}

$userId = $_SESSION['user_id'];
$wishlists = getUserWishlists($userId);

$wishlistIds = [];
foreach ($wishlists as $w) { $wishlistIds[] = $w['id']; }

$pageTitle = 'Wishlist Saya';
require_once 'includes/components/tour-card.php';
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-3"><i class="bi bi-heart-fill text-danger me-2"></i>Wishlist Saya</h4>

        <?php if (count($wishlists) > 0): ?>
        <div class="row g-3">
            <?php foreach ($wishlists as $tour): ?>
                <?php renderTourCard($tour, $wishlistIds); ?>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5 klook-empty-state">
            <div class="display-1 text-muted mb-3"><i class="bi bi-heart"></i></div>
            <h5 class="fw-bold text-dark mb-2">Belum ada tour yang disimpan</h5>
            <p class="text-muted small mb-4">Simpan tour favorit Anda dengan menekan ikon hati <i class="bi bi-heart-fill text-danger"></i> di halaman katalog.</p>
            <a href="tours.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-compass me-1"></i>Jelajahi Tour</a>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>