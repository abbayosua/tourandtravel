<?php
/**
 * renderHeroSearch — Klook-style hero search bar
 * Renders the full search panel with input, autocomplete dropdown, and quick category pills.
 */
function renderHeroSearch($categories = []) {
    ?>
    <div class="bg-white rounded-4 p-2 shadow-lg" style="max-width: 720px;">
        <div class="search-wrapper">
            <div class="input-group input-group-lg klook-hero-search">
                <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-0 shadow-none" 
                       placeholder="<?= t('Cari destinasi atau aktivitas...') ?>" 
                       id="heroSearch" autocomplete="off"
                       onkeypress="if(event.key==='Enter') window.location='tours.php?search='+encodeURIComponent(this.value)">
                <button class="btn btn-primary px-4 rounded-3 m-1 klook-search-btn" 
                        onclick="window.location='tours.php?search='+encodeURIComponent(document.getElementById('heroSearch').value)">
                    <?= t('Cari') ?>
                </button>
            </div>
            <div class="search-dropdown" id="heroSearchDropdown"></div>
        </div>
    </div>
    <?php if (!empty($categories)): ?>
    <div class="d-flex flex-wrap gap-2 mt-3">
        <a href="tours.php" class="btn btn-sm btn-light rounded-pill px-3 fw-semibold"><i class="bi bi-grid-fill me-1"></i><?= t('Semua') ?></a>
        <?php foreach ($categories as $cat): ?>
            <a href="tours.php?category=<?= e($cat) ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><?= e($cat) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif;
}