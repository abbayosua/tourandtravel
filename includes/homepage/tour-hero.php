<section class="hero-klook klook-hero d-flex align-items-center position-relative overflow-hidden" style="min-height: 65vh;">
    <div id="heroCarousel" class="carousel slide carousel-fade w-100 h-100 position-absolute" data-bs-ride="carousel" data-bs-interval="5000" style="inset: 0;">
        <div class="carousel-inner h-100">
            <?php foreach ($heroSlides as $i => $slide): ?>
            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?> h-100">
                <img src="<?= $slide['image'] ?>" class="d-block w-100 h-100" style="object-fit: cover;" alt="<?= e($slide['alt']) ?>">
            </div>
            <?php endforeach; ?>
        </div>
        <div class="hero-overlay"></div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden"><?= t('Previous') ?></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden"><?= t('Next') ?></span>
        </button>
    </div>

    <div class="container position-relative" style="z-index: 2;">
        <div class="row">
            <div class="col-lg-7 text-white">
                <?php $firstSlide = $heroSlides[0] ?? []; ?>
                <h1 class="display-4 fw-bold mb-2 lh-1"><?= !empty($firstSlide['title']) ? e($firstSlide['title']) : $heroHeadline ?></h1>
                <p class="lead mb-4 text-white-50"><?= !empty($firstSlide['subtitle']) ? e($firstSlide['subtitle']) : $heroSub ?></p>
                <?php renderHeroSearch($categories); ?>
                <?php if (!empty($firstSlide['cta_text']) && !empty($firstSlide['cta_link'])): ?>
                    <a href="<?= e($firstSlide['cta_link']) ?>" class="btn btn-light rounded-pill px-4 fw-semibold mt-2"><?= e($firstSlide['cta_text']) ?> <i class="bi bi-arrow-right ms-1"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

