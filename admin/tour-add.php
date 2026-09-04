<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $priceCurrency = in_array($_POST['price_currency'] ?? '', ['IDR', 'SGD', 'USD']) ? $_POST['price_currency'] : 'IDR';
    $maxParticipants = (int)($_POST['max_participants'] ?? 1);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $contentLanguage = in_array($_POST['content_language'] ?? '', ['id', 'en']) ? $_POST['content_language'] : 'id';

    // Validasi
    if (!$title) $error = 'Judul tour harus diisi';
    elseif (!$category) $error = t('Kategori harus diisi');
    elseif ($price <= 0) $error = t('Harga harus diisi');
    elseif ($maxParticipants < 1) $error = 'Max peserta minimal 1';

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

        $stmt = db()->prepare("INSERT INTO tours (title, slug, category, description, price, price_currency, content_language, max_participants, cover_image, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $category, $description, $price, $priceCurrency, $contentLanguage, $maxParticipants, $coverImage ?: null, $isActive]);

        // Simpan konten tour untuk kedua bahasa (tanpa API — fallback konten asli)
        $tourId = db()->lastInsertId();
        $targetLang = $contentLanguage === 'id' ? 'en' : 'id';
        saveTranslation($title, $targetLang, $title);
        if (strlen($description) > 10) {
            saveTranslation($description, $targetLang, $description);
        }

        header('Location: tours.php?msg=added');
        exit;
    }
}

$pageTitle = t('Tambah Tour');
require_once 'includes/admin-header.php';
?>

<h4 class="fw-bold mb-3"><?= t('Tambah Tour Baru') ?></h4>

<?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= $error ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Judul Tour') ?></label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Deskripsi') ?></label>
                        <textarea name="description" class="form-control" rows="5"></textarea>
                    </div>
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
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Kategori') ?></label>
                        <input type="text" name="category" class="form-control" placeholder="Domestik / Internasional" required>
                    </div>
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
                        <label class="form-label fw-semibold"><?= t('Bahasa Konten') ?></label>
                        <select name="content_language" class="form-select">
                            <option value="id"><?= t('🇮🇩 Indonesia (asli)') ?></option>
                            <option value="en"><?= t('🇬🇧 English (asli)') ?></option>
                        </select>
                        <div class="form-text"><?= t('Konten akan otomatis diterjemahkan ke bahasa lain') ?></div>
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

<?php require_once 'includes/admin-footer.php'; ?>
