<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$id = (int)($_GET['id'] ?? 0);
$tour = getTourById($id);

if (!$tour) {
    header('Location: tours.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $priceCurrency = in_array($_POST['price_currency'] ?? '', ['IDR', 'SGD', 'USD']) ? $_POST['price_currency'] : 'IDR';
    $maxParticipants = (int)($_POST['max_participants'] ?? 1);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $contentLanguage = in_array($_POST['content_language'] ?? '', ['id', 'en']) ? $_POST['content_language'] : 'id';

    if (!$title) $error = 'Judul tour harus diisi';
    elseif (!$category) $error = t('Kategori harus diisi');
    elseif ($price <= 0) $error = t('Harga harus diisi');

    $coverImage = $tour['cover_image'];
    if (empty($error) && isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = uploadGambar($_FILES['cover_image'], __DIR__ . '/../uploads');
        if ($upload['success']) {
            // Hapus gambar lama
            if ($tour['cover_image'] && file_exists(__DIR__ . '/../uploads/' . $tour['cover_image'])) {
                unlink(__DIR__ . '/../uploads/' . $tour['cover_image']);
            }
            $coverImage = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }

    if (empty($error)) {
        $slug = buatSlug($title);
        // Cek slug unik (kecuali dirinya sendiri)
        $stmt = db()->prepare("SELECT COUNT(*) FROM tours WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id]);
        if ($stmt->fetchColumn() > 0) {
            $slug .= '-' . time();
        }

        $stmt = db()->prepare("UPDATE tours SET title=?, slug=?, category=?, description=?, price=?, price_currency=?, content_language=?, max_participants=?, cover_image=?, is_active=? WHERE id=?");
        $stmt->execute([$title, $slug, $category, $description, $price, $priceCurrency, $contentLanguage, $maxParticipants, $coverImage, $isActive, $id]);

        // Simpan konten tour untuk kedua bahasa (tanpa API — fallback konten asli)
        $targetLang = $contentLanguage === 'id' ? 'en' : 'id';
        saveTranslation($title, $targetLang, $title);
        if (strlen($description) > 10) {
            saveTranslation($description, $targetLang, $description);
        }

        header('Location: tours.php?msg=updated');
        exit;
    }
}

$tourDates = getTourDates($id);
$itineraries = getItineraries($id);

// Handle tambah itinerary
if (isset($_POST['add_itinerary'])) {
    $day = (int)$_POST['day'];
    $itTitle = trim($_POST['it_title'] ?? '');
    $itDesc = trim($_POST['it_desc'] ?? '');
    $meals = trim($_POST['meals'] ?? '');
    $accommodation = trim($_POST['accommodation'] ?? '');

    if ($day > 0 && $itTitle) {
        $stmt = db()->prepare("INSERT INTO itineraries (tour_id, day_number, title, description, meals, accommodation) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $day, $itTitle, $itDesc, $meals, $accommodation]);
        header("Location: tour-edit.php?id=$id&msg=itinerary_added");
        exit;
    }
}

// Handle hapus itinerary
if (isset($_GET['delete_itinerary'])) {
    $itId = (int)$_GET['delete_itinerary'];
    db()->prepare("DELETE FROM itineraries WHERE id = ? AND tour_id = ?")->execute([$itId, $id]);
    header("Location: tour-edit.php?id=$id&msg=itinerary_deleted");
    exit;
}

// Handle tambah tanggal
if (isset($_POST['add_date'])) {
    $departure = $_POST['departure_date'] ?? '';
    $return = $_POST['return_date'] ?? '';
    $slots = (int)($_POST['slots'] ?? 0);

    if ($departure && $return && $slots > 0) {
        $stmt = db()->prepare("INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $departure, $return, $slots]);
        header("Location: tour-edit.php?id=$id&msg=date_added");
        exit;
    }
}

// Handle hapus tanggal
if (isset($_GET['delete_date'])) {
    $tdId = (int)$_GET['delete_date'];
    db()->prepare("DELETE FROM tour_dates WHERE id = ? AND tour_id = ?")->execute([$tdId, $id]);
    header("Location: tour-edit.php?id=$id&msg=date_deleted");
    exit;
}

// Handle hapus gambar galeri
if (isset($_GET['delete_gallery'])) {
    $gid = (int)$_GET['delete_gallery'];
    $row = db()->prepare("SELECT * FROM tour_images WHERE id=? AND tour_id=?");
    $row->execute([$gid, $id]);
    $img = $row->fetch();
    if ($img) {
        if (!empty($img['image_path']) && file_exists(__DIR__ . '/../uploads/' . $img['image_path'])) {
            @unlink(__DIR__ . '/../uploads/' . $img['image_path']);
        }
        db()->prepare("DELETE FROM tour_images WHERE id=? AND tour_id=?")->execute([$gid, $id]);
    }
    header("Location: tour-edit.php?id=$id&msg=gallery_deleted"); exit;
}

// Handle upload galeri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_gallery'])) {
    if (!empty($_FILES['gallery_images']['name'][0])) {
        $maxOrder = db()->prepare("SELECT COALESCE(MAX(sort_order),0) FROM tour_images WHERE tour_id=?");
        $maxOrder->execute([$id]);
        $nextOrder = (int)$maxOrder->fetchColumn() + 1;
        foreach ($_FILES['gallery_images']['tmp_name'] as $idx => $tmp) {
            if ($_FILES['gallery_images']['error'][$idx] !== UPLOAD_ERR_OK) continue;
            $file = ['name'=>$_FILES['gallery_images']['name'][$idx],'type'=>$_FILES['gallery_images']['type'][$idx],'tmp_name'=>$tmp,'error'=>$_FILES['gallery_images']['error'][$idx],'size'=>$_FILES['gallery_images']['size'][$idx]];
            $up = uploadGambar($file, __DIR__ . '/../uploads');
            if ($up['success']) {
                db()->prepare("INSERT INTO tour_images (tour_id, image_path, sort_order) VALUES (?, ?, ?)")->execute([$id, $up['filename'], $nextOrder++]);
            }
        }
        header("Location: tour-edit.php?id=$id&msg=gallery_added"); exit;
    }
}

$msg = '';
if (isset($_GET['msg'])) {
    $msgs = [
        'itinerary_added' => t('Itinerary berhasil ditambahkan'),
        'itinerary_deleted' => t('Itinerary berhasil dihapus'),
        'date_added' => t('Tanggal keberangkatan berhasil ditambahkan'),
        'date_deleted' => t('Tanggal keberangkatan berhasil dihapus'),
    ];
    $msg = $msgs[$_GET['msg']] ?? '';
}

$pageTitle = t('Edit Tour');
require_once 'includes/admin-header.php';
?>

<h4 class="fw-bold mb-3"><?= t('Edit Tour:') ?><?= e($tour['title']) ?></h4>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible py-2"><?= $msg ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= $error ?></div>
<?php endif; ?>

<!-- Form Edit Tour -->
<form method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Judul Tour') ?></label>
                        <input type="text" name="title" class="form-control" value="<?= e($tour['title']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Deskripsi') ?></label>
                        <textarea name="description" class="form-control" rows="5"><?= e($tour['description']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Gambar Cover') ?></label>
                        <?php if ($tour['cover_image']): ?>
                            <div class="mb-2">
                                <img src="../uploads/<?= e($tour['cover_image']) ?>" style="max-height: 100px; border-radius: 8px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="cover_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text"><?= t('Kosongkan jika tidak ingin mengubah gambar') ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Kategori') ?></label>
                        <input type="text" name="category" class="form-control" value="<?= e($tour['category']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Harga') ?></label>
                        <div class="input-group">
                            <select name="price_currency" class="form-select" style="max-width: 100px;">
                                <?php foreach (['IDR' => t('Rp (IDR)'), 'SGD' => 'S$ (SGD)', 'USD' => '$ (USD)'] as $code => $label): ?>
                                    <option value="<?= $code ?>" <?= ($tour['price_currency'] ?? 'IDR') === $code ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="price" class="form-control" min="0" value="<?= $tour['price'] ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Bahasa Konten') ?></label>
                        <select name="content_language" class="form-select">
                            <option value="id" <?= ($tour['content_language'] ?? 'id') === 'id' ? 'selected' : '' ?>><?= t('🇮🇩 Indonesia (asli)') ?></option>
                            <option value="en" <?= ($tour['content_language'] ?? 'id') === 'en' ? 'selected' : '' ?>><?= t('🇬🇧 English (asli)') ?></option>
                        </select>
                        <div class="form-text"><?= t('Konten akan otomatis diterjemahkan ke bahasa lain') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Max Peserta') ?></label>
                        <label class="form-label fw-semibold"><?= t('Max Peserta') ?></label>
                        <input type="number" name="max_participants" class="form-control" min="1" value="<?= $tour['max_participants'] ?>">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= $tour['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isActive"><?= t('Aktif') ?></label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100"><?= t('Update Tour') ?></button>
            <a href="tours.php" class="btn btn-outline-secondary w-100 mt-2"><?= t('Kembali') ?></a>
        </div>
    </div>
</form>

<!-- Jadwal Keberangkatan -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><?= t('Jadwal Keberangkatan') ?></h6>
        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addDateForm">+ Tambah</button>
    </div>
    <div class="card-body">
        <div class="collapse mb-3" id="addDateForm">
            <form method="POST" class="row g-2 bg-light p-3 rounded">
                <div class="col-md-4">
                    <label class="form-label small"><?= t('Tanggal Berangkat') ?></label>
                    <input type="date" name="departure_date" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small"><?= t('Tanggal Kembali') ?></label>
                    <input type="date" name="return_date" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small"><?= t('Slot') ?></label>
                    <input type="number" name="slots" class="form-control form-control-sm" min="1" value="20" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="add_date" class="btn btn-sm btn-primary w-100"><?= t('Simpan') ?></button>
                </div>
            </form>
        </div>

        <?php if (count($tourDates) > 0): ?>
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th><?= t('Berangkat') ?></th>
                    <th><?= t('Kembali') ?></th>
                    <th><?= t('Slot') ?></th>
                    <th><?= t('Aksi') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tourDates as $td): ?>
                <tr>
                    <td><?= tglIndonesia($td['departure_date']) ?></td>
                    <td><?= tglIndonesia($td['return_date']) ?></td>
                    <td><?= $td['available_slots'] ?></td>
                    <td>
                        <a href="tour-edit.php?id=<?= $id ?>&delete_date=<?= $td['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus tanggal ini?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="text-muted small mb-0"><?= t('Belum ada jadwal keberangkatan.') ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Itinerary -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><?= t('Itinerary') ?></h6>
        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addItineraryForm">+ Tambah</button>
    </div>
    <div class="card-body">
        <div class="collapse mb-3" id="addItineraryForm">
            <form method="POST" class="row g-2 bg-light p-3 rounded">
                <div class="col-md-1">
                    <label class="form-label small"><?= t('Hari') ?></label>
                    <input type="number" name="day" class="form-control form-control-sm" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small"><?= t('Judul') ?></label>
                    <input type="text" name="it_title" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small"><?= t('Deskripsi') ?></label>
                    <textarea name="it_desc" class="form-control form-control-sm" rows="1"></textarea>
                </div>
                <div class="col-md-2">
                    <label class="form-label small"><?= t('Makan') ?></label>
                    <input type="text" name="meals" class="form-control form-control-sm" placeholder="Sarapan, makan siang">
                </div>
                <div class="col-md-1">
                    <label class="form-label small"><?= t('Akomodasi') ?></label>
                    <input type="text" name="accommodation" class="form-control form-control-sm" placeholder="Hotel">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" name="add_itinerary" class="btn btn-sm btn-primary">+</button>
                </div>
            </form>
        </div>

        <?php if (count($itineraries) > 0): ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th><?= t('Hari') ?></th>
                        <th><?= t('Judul') ?></th>
                        <th><?= t('Deskripsi') ?></th>
                        <th><?= t('Makan') ?></th>
                        <th><?= t('Akomodasi') ?></th>
                        <th><?= t('Aksi') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itineraries as $it): ?>
                    <tr>
                        <td><?= $it['day_number'] ?></td>
                        <td><?= e($it['title']) ?></td>
                        <td><small><?= e(substr($it['description'], 0, 50)) ?></small></td>
                        <td><small><?= e($it['meals']) ?></small></td>
                        <td><small><?= e($it['accommodation']) ?></small></td>
                        <td>
                            <a href="tour-edit.php?id=<?= $id ?>&delete_itinerary=<?= $it['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus itinerary ini?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted small mb-0"><?= t('Belum ada itinerary.') ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Galeri Foto -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-images me-2"></i><?= t('Galeri Foto') ?></h6>
        <small class="text-muted"><?= t('Upload beberapa gambar (JPG/PNG/WebP, max 2MB)') ?></small>
    </div>
    <div class="card-body">
        <?php
        try { $galleryItems = db()->prepare("SELECT * FROM tour_images WHERE tour_id=? ORDER BY sort_order ASC, id ASC"); $galleryItems->execute([$id]); $galleryItems = $galleryItems->fetchAll(); } catch(Throwable $e){ $galleryItems=[]; }
        ?>
        <form method="POST" enctype="multipart/form-data" class="mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label small"><?= t('Pilih Gambar (bisa banyak)') ?></label>
                    <input type="file" name="gallery_images[]" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp" multiple required>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" name="add_gallery" class="btn btn-sm btn-primary"><?= t('Upload Galeri') ?></button>
                </div>
            </div>
        </form>
        <?php if (count($galleryItems)>0): ?>
        <div class="row g-2">
            <?php foreach ($galleryItems as $g): ?>
            <div class="col-4 col-md-2">
                <div class="position-relative">
                    <img src="../uploads/<?= e($g['image_path']) ?>" class="w-100 rounded-3 border" style="height: 110px; object-fit: cover;">
                    <a href="tour-edit.php?id=<?= $id ?>&delete_gallery=<?= $g['id'] ?>" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 py-0 px-1" onclick="return confirm('Hapus gambar ini?')" title="Hapus" style="font-size: 11px;"><i class="bi bi-trash"></i></a>
                    <small class="d-block text-truncate text-muted" style="font-size: 10px;"><?= e($g['image_path']) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted small mb-0"><?= t('Belum ada foto galeri. Upload untuk mengganti galeri auto (loremflickr).') ?></p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
