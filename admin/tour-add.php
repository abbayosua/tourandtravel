<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once 'includes/admin-access.php';
requireAdminPage();

$error = '';
$success = '';

// Mode input: manual (seperti sekarang) | auto (tulis 1 bahasa, AI terjemahkan sisanya)
$inputMode = ($_POST['input_mode'] ?? 'manual') === 'auto' ? 'auto' : 'manual';
// Nilai form agar tidak hilang saat validasi/AI gagal
$formVals = [];

// Semua kolom teks i18n tours yang bisa diisi sekali-tulis (itinerary/tanggal/galeri tetap di Edit)
$addFields = ['title', 'category', 'description', 'route_cities', 'highlights', 'includes', 'excludes', 'flight_info', 'meeting_point', 'important_notes'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tiAdd = [];
    foreach ($addFields as $f) $tiAdd[$f] = i18nPost($f);
    $contentLanguage = isValidLang($_POST['content_language'] ?? '') ? $_POST['content_language'] : 'id';

    // AI AUTO: sumber = 1 bahasa (sesuai Bahasa Konten). Simpan cepat pakai nilai
    // sumber/pratinjau, slot kosong diisi AI via background setelah redirect
    // (respons Atria 20-60+ dtk > batas proxy). $needAi = [field => teks sumber].
    $needAi = [];
    $aiSrc = $contentLanguage;
    if ($inputMode === 'auto') {
        $src = $contentLanguage;
        foreach ($addFields as $f) {
            // Pakai hasil pratinjau bila admin sudah isi target manual; yang kosong diisi AI
            foreach (['id', 'en', 'zh'] as $l) {
                if ($l !== $src && $tiAdd[$f][$l] === '' && $tiAdd[$f][$src] !== '') {
                    $needAi[$f] = $tiAdd[$f][$src];
                    break;
                }
            }
        }
        // Kolom dasar (id) jangan kosong bila sumber terisi (fallback = sumber)
        if ($src !== 'id') {
            foreach (['title', 'category'] as $f) {
                if ($tiAdd[$f][$src] !== '' && $tiAdd[$f]['id'] === '' && !isset($needAi[$f])) {
                    $needAi[$f] = $tiAdd[$f][$src];
                }
            }
        }
        foreach ($addFields as $f) {
            if ($tiAdd[$f]['id'] === '') $tiAdd[$f]['id'] = $tiAdd[$f][$src];
        }
        $title = $tiAdd['title']['id'];
        $category = $tiAdd['category']['id'];
        $description = $tiAdd['description']['id'];
    } else {
        $title = $tiAdd['title']['id'];
        $category = $tiAdd['category']['id'];
        $description = $tiAdd['description']['id'];
    }
    $durationDays = (int)($_POST['duration_days'] ?? 0) ?: null;
    $durationNights = (int)($_POST['duration_nights'] ?? 0) ?: null;
    $price = (float)($_POST['price'] ?? 0);
    $priceCurrency = in_array($_POST['price_currency'] ?? '', ['IDR', 'SGD', 'USD']) ? $_POST['price_currency'] : 'IDR';
    $maxParticipants = (int)($_POST['max_participants'] ?? 1);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Validasi
    if (!$title) $error = t('Judul tour harus diisi');
    elseif (!$category) $error = t('Kategori harus diisi');
    elseif ($price <= 0) $error = t('Harga harus diisi');
    elseif ($maxParticipants < 1) $error = t('Max peserta minimal 1');

    // Upload gambar
    $coverImage = '';
    if (empty($error) && isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = uploadGambar($_FILES['cover_image'], __DIR__ . '/../uploads');
        if ($upload['success']) {
            $coverImage = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }

    if (empty($error)) {
        $slug = buatSlug($title);
        // Cek slug unik
        $stmt = db()->prepare("SELECT COUNT(*) FROM tours WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() > 0) {
            $slug .= '-' . time();
        }

        $stmt = db()->prepare("INSERT INTO tours (title, slug, category, description, highlights, includes, excludes, flight_info, meeting_point, important_notes, route_cities, duration_days, duration_nights, price, price_currency, content_language, max_participants, cover_image, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $category, $description, $tiAdd['highlights']['id'] ?: null, $tiAdd['includes']['id'] ?: null, $tiAdd['excludes']['id'] ?: null, $tiAdd['flight_info']['id'] ?: null, $tiAdd['meeting_point']['id'] ?: null, $tiAdd['important_notes']['id'] ?: null, $tiAdd['route_cities']['id'] ?: null, $durationDays, $durationNights, $price, $priceCurrency, $contentLanguage, $maxParticipants, $coverImage ?: null, $isActive]);

        $tourId = db()->lastInsertId();
        $saveVals = [];
        foreach ($addFields as $f) $saveVals[$f] = $tiAdd[$f];
        i18nSaveRow('tours', 'id', (int)$tourId, $saveVals);

        $afterUrl = 'tour-edit.php?id=' . (int)$tourId . '&msg=added';
        if ($needAi) {
            // AI jalan background agar redirect tetap cepat
            $jobId = (int)$tourId;
            $jobNeed = $needAi;
            $jobSrc = $aiSrc;
            redirectThenBackground($afterUrl . '&ai=1', function () use ($jobId, $jobNeed, $jobSrc) {
                i18nFillEmptyTranslated('tours', 'id', $jobId, $jobNeed, $jobSrc);
            });
        }
        header('Location: ' . $afterUrl);
        exit;
    }

    // Agar input tidak hilang saat validasi/AI gagal — petakan ke format row i18nInputs
    if (!empty($error) && !empty($tiAdd)) {
        foreach ($tiAdd as $f => $langs) {
            $formVals[$f] = $langs['id'] ?? '';
            foreach (['en', 'zh'] as $l) $formVals[$f . '_' . $l] = $langs[$l] ?? '';
        }
        $formVals['duration_days'] = $_POST['duration_days'] ?? '';
        $formVals['duration_nights'] = $_POST['duration_nights'] ?? '';
    }
}

$pageTitle = t('Tambah Tour');
require_once 'includes/admin-header.php';
?>

<h4 class="fw-bold mb-3"><?= t('Tambah Tour Baru') ?></h4>

<?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= $error ?></div>
<?php endif; ?>

<form method="POST" data-submit-once enctype="multipart/form-data" id="tourAddForm">
    <!-- Mode input: Manual vs AI AUTO -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="d-flex align-items-center gap-4 flex-wrap">
                <strong class="small"><?= t('Mode Input:') ?></strong>
                <div class="form-check">
                    <input type="radio" name="input_mode" value="manual" id="modeManual" class="form-check-input" <?= $inputMode !== 'auto' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="modeManual">📝 <?= t('Manual (isi 3 bahasa sendiri)') ?></label>
                </div>
                <div class="form-check">
                    <input type="radio" name="input_mode" value="auto" id="modeAuto" class="form-check-input" <?= $inputMode === 'auto' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="modeAuto">✨ <?= t('AI AUTO (tulis 1 bahasa, auto translate)') ?></label>
                </div>
                <button type="button" id="btnAiPreview" class="btn btn-sm btn-outline-primary d-none">✨ <?= t('Terjemahkan Otomatis (pratinjau)') ?></button>
                <span id="aiStatus" class="small text-muted"></span>
            </div>
            <div id="aiHint" class="form-text mt-2 d-none"><?= t('Tulis semua kolom dalam 1 bahasa (lihat Bahasa Konten), klik pratinjau untuk cek hasil, lalu Simpan — kolom yang masih kosong otomatis diterjemahkan AI saat disimpan. Itinerary, jadwal & galeri dilengkapi di halaman Edit.') ?></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body" id="tourI18nLeft">
                    <?= i18nInputs(t('Judul Tour'), 'title', $formVals) ?>
                    <?= i18nInputs(t('Deskripsi'), 'description', $formVals, 'textarea', 5) ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold"><?= t('Durasi (hari)') ?></label>
                            <input type="number" name="duration_days" class="form-control" min="0" value="<?= e($formVals['duration_days'] ?? '') ?>" placeholder="8">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold"><?= t('Durasi (malam)') ?></label>
                            <input type="number" name="duration_nights" class="form-control" min="0" value="<?= e($formVals['duration_nights'] ?? '') ?>" placeholder="7">
                        </div>
                    </div>
                    <?= i18nInputs(t('Rute Kota (untuk brosur PDF)'), 'route_cities', $formVals) ?>
                    <?= i18nInputs(t('Highlights (satu per baris — tampil di brosur PDF)'), 'highlights', $formVals, 'textarea', 4) ?>
                    <?= i18nInputs(t('Jadwal Penerbangan (satu per baris)'), 'flight_info', $formVals, 'textarea', 2) ?>
                    <?= i18nInputs(t('Titik Kumpul'), 'meeting_point', $formVals) ?>
                    <?= i18nInputs(t('Paket Termasuk / Include (satu per baris)'), 'includes', $formVals, 'textarea', 4) ?>
                    <?= i18nInputs(t('Paket Belum Termasuk / Exclude (satu per baris)'), 'excludes', $formVals, 'textarea', 4) ?>
                    <?= i18nInputs(t('Catatan Penting (satu per baris)'), 'important_notes', $formVals, 'textarea', 3) ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Gambar Cover') ?></label>
                        <input type="file" name="cover_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text"><?= t('Max 2MB. Format: JPG, PNG, WebP') ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <?= i18nInputs(t('Kategori'), 'category', $formVals) ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Harga') ?></label>
                        <div class="input-group">
                            <select name="price_currency" class="form-select" style="max-width: 100px;">
                                <option value="IDR"><?= t('Rp (IDR)') ?></option>
                                <option value="SGD"><?= t('S$ (SGD)') ?></option>
                                <option value="USD">$ (USD)</option>
                            </select>
                            <input type="number" name="price" class="form-control" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" id="contentLangLabel"><?= t('Bahasa Konten') ?></label>
                        <select name="content_language" id="contentLangSelect" class="form-select">
                            <?php foreach (getSupportedLanguages() as $langCode => $langMeta): ?>
                            <option value="<?= e($langCode) ?>"><?= $langMeta['flag'] ?> <?= e($langMeta['label']) ?><?= $langCode === 'id' ? ' (' . t('asli') . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text" id="contentLangHelp"><?= t('Konten akan otomatis diterjemahkan ke bahasa lain') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Max Peserta') ?></label>
                        <label class="form-label fw-semibold"><?= t('Max Peserta') ?></label>
                        <input type="number" name="max_participants" class="form-control" min="1" value="20">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="isActive" checked>
                        <label class="form-check-label" for="isActive"><?= t('Aktif') ?></label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100"><?= t('Simpan Tour') ?></button>
            <a href="tours.php" class="btn btn-outline-secondary w-100 mt-2"><?= t('Batal') ?></a>
        </div>
    </div>
</form>

<script>
// Mode Manual vs AI AUTO — AI AUTO: hanya tampilkan input bahasa sumber
(function() {
    const form = document.getElementById('tourAddForm');
    const btnPreview = document.getElementById('btnAiPreview');
    const aiHint = document.getElementById('aiHint');
    const aiStatus = document.getElementById('aiStatus');
    const langSelect = document.getElementById('contentLangSelect');
    const FIELDS = ['title', 'category', 'description', 'route_cities', 'highlights', 'includes', 'excludes', 'flight_info', 'meeting_point', 'important_notes'];

    const isAuto = () => form.querySelector('input[name="input_mode"]:checked')?.value === 'auto';
    const srcLang = () => langSelect.value || 'id';
    const suffixFor = (l) => l === 'id' ? '' : '_' + l;

    function applyMode() {
        const auto = isAuto(), src = srcLang();
        btnPreview.classList.toggle('d-none', !auto);
        aiHint.classList.toggle('d-none', !auto);
        document.getElementById('contentLangLabel').textContent =
            auto ? '<?= t('Bahasa sumber (yang kamu tulis)') ?>' : '<?= t('Bahasa Konten') ?>';
        // Tampilkan hanya input bahasa sumber saat auto; tampilkan semua saat manual
        FIELDS.forEach(f => {
            ['id', 'en', 'zh'].forEach(l => {
                const input = form.querySelector(`[name="${f}${suffixFor(l)}"]`);
                const wrap = input?.closest('.mb-3');
                if (wrap) wrap.style.display = (!auto || l === src) ? '' : 'none';
            });
        });
        if (!auto) aiStatus.textContent = '';
    }

    function collectSource() {
        const src = srcLang(), out = {};
        FIELDS.forEach(f => {
            const input = form.querySelector(`[name="${f}${suffixFor(src)}"]`);
            if (input && input.value.trim() !== '') out[f] = input.value.trim();
        });
        return out;
    }

    btnPreview.addEventListener('click', async () => {
        const fields = collectSource();
        if (!Object.keys(fields).length) {
            aiStatus.textContent = '<?= t('Isi dulu minimal 1 kolom dalam bahasa sumber.') ?>';
            aiStatus.className = 'small text-danger';
            return;
        }
        btnPreview.disabled = true;
        aiStatus.textContent = '⏳ ' + <?= json_encode(t('Menerjemahkan via AI...')) ?>;
        aiStatus.className = 'small text-muted';
        try {
            const res = await fetch('ajax/tour-translate-ai.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({source_lang: srcLang(), fields})
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'AI error');
            let n = 0;
            Object.entries(data.translations || {}).forEach(([lang, vals]) => {
                Object.entries(vals || {}).forEach(([f, v]) => {
                    const input = form.querySelector(`[name="${f}${suffixFor(lang)}"]`);
                    if (input) { input.value = v; n++; }
                });
            });
            aiStatus.textContent = '✅ ' + n + ' ' + <?= json_encode(t('kolom terisi otomatis — cek dengan pindah ke mode Manual.')) ?>;
            aiStatus.className = 'small text-success';
        } catch (e) {
            aiStatus.textContent = '❌ ' + (e.message || e);
            aiStatus.className = 'small text-danger';
        } finally {
            btnPreview.disabled = false;
        }
    });

    form.querySelectorAll('input[name="input_mode"]').forEach(r => r.addEventListener('change', applyMode));
    langSelect.addEventListener('change', applyMode);
    applyMode();
})();
</script>

<?php require_once 'includes/admin-footer.php'; ?>
