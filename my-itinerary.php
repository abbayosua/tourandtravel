<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=my-itinerary.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

$stmt = db()->prepare("SELECT i.*, (SELECT COUNT(*) FROM user_itinerary_days d WHERE d.itinerary_id = i.id) AS day_count, (SELECT COUNT(*) FROM user_itinerary_days d JOIN user_itinerary_items it ON it.day_id = d.id WHERE d.itinerary_id = i.id) AS item_count FROM user_itineraries i WHERE i.user_id = ? ORDER BY i.updated_at DESC");
$stmt->execute([$userId]);
$itineraries = $stmt->fetchAll();

$pageTitle = t('Itinerary Saya');
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-1"><i class="bi bi-calendar-week me-2"></i><?= t('Itinerary Saya') ?></h4>
        <p class="text-muted small mb-4"><?= t('Rencana perjalanan yang kamu simpan.') ?></p>

        <?php if (empty($itineraries)): ?>
        <div class="text-center py-5">
            <i class="bi bi-calendar-x display-4 text-muted"></i>
            <p class="text-muted mt-3"><?= t('Belum ada itinerary.') ?> <a href="tours.php"><?= t('Jelajahi tour') ?></a></p>
        </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($itineraries as $it): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 itin-card" data-id="<?= (int)$it['id'] ?>">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="fw-semibold mb-0"><?= e($it['title']) ?></h6>
                            <button class="btn btn-sm btn-link text-danger p-0 itin-del-btn" data-id="<?= (int)$it['id'] ?>" title="<?= t('Hapus') ?>"><i class="bi bi-trash"></i></button>
                        </div>
                        <p class="small text-muted mb-2">
                            <i class="bi bi-calendar3 me-1"></i><?= $it['start_date'] ? date('d M Y', strtotime($it['start_date'])) : t('Tanggal belum diset') ?>
                        </p>
                        <span class="badge bg-light text-dark border me-1"><i class="bi bi-sun me-1"></i><?= (int)$it['day_count'] ?> <?= t('hari') ?></span>
                        <span class="badge bg-light text-dark border"><i class="bi bi-list-check me-1"></i><?= (int)$it['item_count'] ?> <?= t('item') ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.querySelectorAll('.itin-del-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        if (!confirm('<?= t('Hapus itinerary ini?') ?>')) return;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'itinerary-ajax.php');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            try { if (JSON.parse(xhr.responseText).ok) location.reload(); } catch (e) {}
        };
        xhr.send(new URLSearchParams({ action: 'delete_itinerary', itinerary_id: btn.getAttribute('data-id') }));
    });
});
</script>
<?php require_once 'includes/footer-klook.php'; ?>
