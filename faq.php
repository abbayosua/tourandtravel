<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = t('FAQ / Bantuan');

$categories = db()->query("SELECT * FROM faq_categories ORDER BY sort_order ASC, name ASC")->fetchAll();
$items = db()->query("SELECT fi.*, fc.name AS category_name FROM faq_items fi JOIN faq_categories fc ON fc.id = fi.category_id WHERE fi.is_active = 1 ORDER BY fc.sort_order ASC, fi.sort_order ASC, fi.id ASC")->fetchAll();

// Group items by category
$grouped = [];
foreach ($items as $item) {
    $grouped[$item['category_name']][] = $item;
}

require_once 'includes/components/breadcrumb.php';
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <?php renderBreadcrumb([['label' => t('FAQ / Bantuan'), 'url' => null]]); ?>

        <div class="text-center mb-4">
            <h2 class="fw-bold mb-1"><?= t('Pusat Bantuan') ?></h2>
            <p class="text-muted"><?= t('Temukan jawaban atas pertanyaan yang sering diajukan') ?></p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if (count($grouped) > 0): ?>
                    <div class="accordion" id="faqAccordion">
                        <?php $itemIndex = 0; ?>
                        <?php foreach ($grouped as $catName => $catItems): ?>
                        <h5 class="fw-bold mt-4 mb-2 d-flex align-items-center">
                            <i class="bi bi-tag text-primary me-2"></i><?= e($catName) ?>
                        </h5>
                        <?php foreach ($catItems as $it): ?>
                            <?php $itemIndex++; ?>
                            <div class="accordion-item border-0 shadow-sm mb-2 rounded-3 overflow-hidden">
                                <h2 class="accordion-header" id="faqHead<?= $itemIndex ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqBody<?= $itemIndex ?>" aria-expanded="false" aria-controls="faqBody<?= $itemIndex ?>">
                                        <?= e($it['question']) ?>
                                    </button>
                                </h2>
                                <div id="faqBody<?= $itemIndex ?>" class="accordion-collapse collapse" aria-labelledby="faqHead<?= $itemIndex ?>" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body small text-muted"><?= nl2br(e($it['answer'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-question-circle fs-1 text-muted"></i>
                        <p class="mt-2 text-muted"><?= t('Belum ada pertanyaan.') ?></p>
                    </div>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <p class="text-muted small"><?= t('Masih butuh bantuan?') ?></p>
                    <a href="#" class="btn btn-outline-primary rounded-pill px-4" onclick="return false;"><i class="bi bi-chat-dots me-1"></i><?= t('Hubungi Kami') ?></a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>