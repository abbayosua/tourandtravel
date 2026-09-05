<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$pageTitle = t('Tampilan Homepage');

$error = '';

$focusOptions = [
    'tour'   => ['label' => t('Paket Tour (Klook-style)'), 'desc' => t('Homepage menonjolkan paket tour: flash deals, destinasi populer, rekomendasi tur.')],
    'hotel'  => ['label' => t('Hotel (Agoda-style)'),      'desc' => t('Homepage menonjolkan hotel: pencarian menginap dominan, deal hotel terbaik, hotel per kota.')],
    'flight' => ['label' => t('Tiket Pesawat (Tiket.com-style)'), 'desc' => t('Homepage menonjolkan penerbangan: form cari tiket dominan, promo & rute populer.')],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $focus = $_POST['site_focus'] ?? 'tour';
    if (!array_key_exists($focus, $focusOptions)) {
        $error = t('Fokus tidak valid');
    } else {
        setSetting('site_focus', $focus);
        header('Location: appearance.php?msg=updated');
        exit;
    }
}

$siteFocus = getSetting('site_focus', 'tour');
if (!array_key_exists($siteFocus, $focusOptions)) $siteFocus = 'tour';

require_once __DIR__ . '/includes/admin-header.php';
?>

<h4 class="fw-bold mb-3"><?= t('Tampilan Homepage') ?></h4>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
    <div class="alert alert-success py-2 small"><?= t('Fokus website berhasil disimpan.') ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h6 class="fw-semibold mb-1"><?= t('Fokus Website') ?></h6>
        <p class="text-muted small mb-3"><?= t('Pilih produk utama yang dijual website ini. Halaman utama akan tersusun otomatis mengikuti pilihan.') ?></p>
        <form method="POST" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label small fw-semibold"><?= t('Fokus') ?></label>
                <select name="site_focus" class="form-select" id="siteFocusSelect">
                    <?php foreach ($focusOptions as $code => $opt): ?>
                        <option value="<?= e($code) ?>" <?= $siteFocus === $code ? 'selected' : '' ?>><?= $opt['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary"><?= t('Simpan') ?></button>
            </div>
            <div class="col-12">
                <div class="form-text" id="focusDesc"></div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <h6 class="fw-semibold mb-3"><?= t('Yang berubah saat fokus diganti') ?></h6>
        <ul class="small text-muted mb-0">
            <li><?= t('Hero utama + slide hero sesuai fokus (dikelola di menu Hero Slides).') ?></li>
            <li><?= t('Urutan & jenis section homepage (produk utama di atas, lainnya sebagai pendukung).') ?></li>
            <li><?= t('Halaman lain (tour/hotel/flight detail) tidak berubah.') ?></li>
        </ul>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var descs = <?= json_encode(array_map(fn($o) => $o['desc'], $focusOptions), JSON_UNESCAPED_UNICODE) ?>;
    var sel = document.getElementById('siteFocusSelect');
    var target = document.getElementById('focusDesc');
    function update() {
        var key = sel.value;
        if (target && descs[key]) target.textContent = descs[key];
    }
    sel.addEventListener('change', update);
    update();
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
