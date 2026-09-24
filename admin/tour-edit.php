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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['add_itinerary']) && !isset($_POST['add_date']) && !isset($_POST['add_gallery'])) {
    $ti = [];
    foreach (['title', 'description', 'category', 'route_cities', 'highlights', 'includes', 'excludes', 'flight_info', 'meeting_point', 'important_notes'] as $f) $ti[$f] = i18nPost($f);
    $title = $ti['title']['id'];
    $category = $ti['category']['id'];
    $description = $ti['description']['id'];
    $durationDays = (int)($_POST['duration_days'] ?? 0) ?: null;
    $durationNights = (int)($_POST['duration_nights'] ?? 0) ?: null;
    $routeCities = $ti['route_cities']['id'];
    $highlights = $ti['highlights']['id'];
    $includes = $ti['includes']['id'];
    $excludes = $ti['excludes']['id'];
    $flightInfo = $ti['flight_info']['id'];
    $meetingPoint = $ti['meeting_point']['id'];
    $importantNotes = $ti['important_notes']['id'];
    $price = (float)($_POST['price'] ?? 0);
    $priceCurrency = in_array($_POST['price_currency'] ?? '', ['IDR', 'SGD', 'USD']) ? $_POST['price_currency'] : 'IDR';
    $maxParticipants = (int)($_POST['max_participants'] ?? 1);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $contentLanguage = isValidLang($_POST['content_language'] ?? '') ? $_POST['content_language'] : 'id';

    if (!$title) $error = t('Judul tour harus diisi');
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

        $stmt = db()->prepare("UPDATE tours SET title=?, slug=?, category=?, description=?, highlights=?, includes=?, excludes=?, flight_info=?, meeting_point=?, important_notes=?, route_cities=?, duration_days=?, duration_nights=?, price=?, price_currency=?, content_language=?, max_participants=?, cover_image=?, is_active=? WHERE id=?");
        $stmt->execute([$title, $slug, $category, $description, $highlights, $includes, $excludes, $flightInfo, $meetingPoint, $importantNotes, $routeCities, $durationDays, $durationNights, $price, $priceCurrency, $contentLanguage, $maxParticipants, $coverImage, $isActive, $id]);

        i18nSaveRow('tours', 'id', $id, [
            'title' => $ti['title'], 'description' => $ti['description'], 'category' => $ti['category'],
            'route_cities' => $ti['route_cities'], 'highlights' => $ti['highlights'], 'includes' => $ti['includes'],
            'excludes' => $ti['excludes'], 'flight_info' => $ti['flight_info'], 'meeting_point' => $ti['meeting_point'],
            'important_notes' => $ti['important_notes'],
        ]);

        header('Location: tours.php?msg=updated');
        exit;
    }
}

$tourDates = getTourDates($id);
$itineraries = getItineraries($id);

// Handle tambah itinerary
if (isset($_POST['add_itinerary'])) {
    $day = (int)$_POST['day'];
    $itTitle = i18nPost('it_title');
    $itDesc = i18nPost('it_desc');
    $meals = i18nPost('meals');
    $accommodation = i18nPost('accommodation');

    if ($day > 0 && $itTitle['id'] !== '') {
        $stmt = db()->prepare("INSERT INTO itineraries (tour_id, day_number, title, description, meals, accommodation) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $day, $itTitle['id'], $itDesc['id'] ?: null, $meals['id'] ?: null, $accommodation['id'] ?: null]);
        $newIt = (int)db()->lastInsertId();
        i18nSaveRow('itineraries', 'id', $newIt, ['title' => $itTitle, 'description' => $itDesc, 'meals' => $meals, 'accommodation' => $accommodation]);
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
    $priceAdult = (float)($_POST['price_adult'] ?? 0) ?: null;
    $priceChild = (float)($_POST['price_child'] ?? 0) ?: null;
    $priceSingle = (float)($_POST['price_single'] ?? 0) ?: null;
    $priceTwin = (float)($_POST['price_twin'] ?? 0) ?: null;
    $priceTriple = (float)($_POST['price_triple'] ?? 0) ?: null;
    $dateNote = i18nPost('date_note');

    if ($departure && $return && $slots > 0) {
        $stmt = db()->prepare("INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots, price_adult, price_child, price_single, price_twin, price_triple, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $departure, $return, $slots, $priceAdult, $priceChild, $priceSingle, $priceTwin, $priceTriple, $dateNote['id'] ?: null]);
        i18nSaveRow('tour_dates', 'id', (int)db()->lastInsertId(), ['note' => $dateNote]);
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
                    <?= i18nInputs(t('Judul Tour'), 'title', $tour) ?>
                    <?= i18nInputs(t('Deskripsi'), 'description', $tour, 'textarea', 5) ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold"><?= t('Durasi (hari)') ?></label>
                            <input type="number" name="duration_days" class="form-control" min="0" value="<?= e($tour['duration_days'] ?? '') ?>" placeholder="8">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold"><?= t('Durasi (malam)') ?></label>
                            <input type="number" name="duration_nights" class="form-control" min="0" value="<?= e($tour['duration_nights'] ?? '') ?>" placeholder="7">
                        </div>
                    </div>
                    <?= i18nInputs(t('Rute Kota (untuk brosur PDF)'), 'route_cities', $tour) ?>
                    <?= i18nInputs(t('Highlights (satu per baris — tampil di brosur PDF)'), 'highlights', $tour, 'textarea', 4) ?>
                    <?= i18nInputs(t('Jadwal Penerbangan (satu per baris)'), 'flight_info', $tour, 'textarea', 2) ?>
                    <?= i18nInputs(t('Titik Kumpul'), 'meeting_point', $tour) ?>
                    <?= i18nInputs(t('Paket Termasuk / Include (satu per baris)'), 'includes', $tour, 'textarea', 4) ?>
                    <?= i18nInputs(t('Paket Belum Termasuk / Exclude (satu per baris)'), 'excludes', $tour, 'textarea', 4) ?>
                    <?= i18nInputs(t('Catatan Penting (satu per baris)'), 'important_notes', $tour, 'textarea', 3) ?>
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
                    <?= i18nInputs(t('Kategori'), 'category', $tour) ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Harga') ?></label>
                        <div class="input-group">
                            <select name="price_currency" class="form-select" style="max-width: 100px;">
                                <?php foreach (['IDR' => t('Rp (IDR)'), 'SGD' => t('S$ (SGD)'), 'USD' => t('$ (USD)')] as $code => $label): ?>
                                    <option value="<?= $code ?>" <?= ($tour['price_currency'] ?? 'IDR') === $code ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="price" class="form-control" min="0" value="<?= $tour['price'] ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= t('Bahasa Konten') ?></label>
                        <select name="content_language" class="form-select">
                            <?php foreach (getSupportedLanguages() as $langCode => $langMeta): ?>
                            <option value="<?= e($langCode) ?>" <?= ($tour['content_language'] ?? 'id') === $langCode ? 'selected' : '' ?>><?= $langMeta['flag'] ?> <?= e($langMeta['label']) ?><?= $langCode === 'id' ? ' (' . t('asli') . ')' : '' ?></option>
                            <?php endforeach; ?>
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
        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addDateForm"><?= t('+ Tambah') ?></button>
    </div>
    <div class="card-body">
        <div class="collapse mb-3" id="addDateForm">
            <form method="POST" class="row g-2 bg-light p-3 rounded">
                <div class="col-md-3">
                    <label class="form-label small"><?= t('Tanggal Berangkat') ?></label>
                    <input type="date" name="departure_date" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small"><?= t('Tanggal Kembali') ?></label>
                    <input type="date" name="return_date" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small"><?= t('Slot') ?></label>
                    <input type="number" name="slots" class="form-control form-control-sm" min="1" value="20" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small"><?= t('Catatan (mis: Low Season)') ?></label>
                    <input type="text" name="date_note" class="form-control form-control-sm" placeholder="<?= t('Low Season') ?>">
                    <input type="text" name="date_note_en" class="form-control form-control-sm mt-1" placeholder="Note (EN)">
                    <input type="text" name="date_note_zh" class="form-control form-control-sm mt-1" placeholder="备注 (中文)">
                </div>
                <div class="col-md-4">
                    <label class="form-label small"><?= t('Harga Dewasa') ?></label>
                    <input type="number" name="price_adult" class="form-control form-control-sm" min="0" step="0.01">
                </div>
                <div class="col-md-4">
                    <label class="form-label small"><?= t('Harga Anak') ?></label>
                    <input type="number" name="price_child" class="form-control form-control-sm" min="0" step="0.01">
                </div>
                <div class="col-md-4">
                    <label class="form-label small"><?= t('Single Supp.') ?></label>
                    <input type="number" name="price_single" class="form-control form-control-sm" min="0" step="0.01">
                </div>
                <div class="col-md-4">
                    <label class="form-label small"><?= t('Twin (1 kamar 2 org)') ?></label>
                    <input type="number" name="price_twin" class="form-control form-control-sm" min="0" step="0.01">
                </div>
                <div class="col-md-4">
                    <label class="form-label small"><?= t('Triple (1 kamar 3 org)') ?></label>
                    <input type="number" name="price_triple" class="form-control form-control-sm" min="0" step="0.01">
                </div>
                <div class="col-12 d-flex align-items-end">
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
                    <th><?= t('Dewasa') ?></th>
                    <th><?= t('Anak') ?></th>
                    <th><?= t('Single') ?></th>
                    <th><?= t('Catatan') ?></th>
                    <th><?= t('Aksi') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tourDates as $td): ?>
                <tr>
                    <td><?= tglIndonesia($td['departure_date']) ?></td>
                    <td><?= tglIndonesia($td['return_date']) ?></td>
                    <td><?= $td['available_slots'] ?></td>
                    <td><?= !empty($td['price_adult']) ? number_format((float)$td['price_adult'], 0, ',', '.') : '-' ?></td>
                    <td><?= !empty($td['price_child']) ? number_format((float)$td['price_child'], 0, ',', '.') : '-' ?></td>
                    <td><?= !empty($td['price_single']) ? number_format((float)$td['price_single'], 0, ',', '.') : '-' ?></td>
                    <td><small><?= e($td['note'] ?? '') ?></small></td>
                    <td>
                        <a href="tour-edit.php?id=<?= $id ?>&delete_date=<?= $td['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= t('Hapus tanggal ini?') ?>')"><i class="bi bi-trash"></i></a>
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
        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addItineraryForm"><?= t('+ Tambah') ?></button>
    </div>
    <div class="card-body">
        <div class="collapse mb-3" id="addItineraryForm">
            <form method="POST" class="row g-2 bg-light p-3 rounded">
                <div class="col-md-1">
                    <label class="form-label small"><?= t('Hari') ?></label>
                    <input type="number" name="day" class="form-control form-control-sm" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small"><?= t('Judul') ?> (ID) *</label>
                    <input type="text" name="it_title" class="form-control form-control-sm" required>
                    <input type="text" name="it_title_en" class="form-control form-control-sm mt-1" placeholder="Title (EN)">
                    <input type="text" name="it_title_zh" class="form-control form-control-sm mt-1" placeholder="标题 (中文)">
                </div>
                <div class="col-md-4">
                    <label class="form-label small"><?= t('Deskripsi') ?> (ID)</label>
                    <textarea name="it_desc" class="form-control form-control-sm" rows="1"></textarea>
                    <textarea name="it_desc_en" class="form-control form-control-sm mt-1" rows="1" placeholder="Description (EN)"></textarea>
                    <textarea name="it_desc_zh" class="form-control form-control-sm mt-1" rows="1" placeholder="描述 (中文)"></textarea>
                </div>
                <div class="col-md-2">
                    <label class="form-label small"><?= t('Makan') ?> (ID)</label>
                    <input type="text" name="meals" class="form-control form-control-sm" placeholder="Sarapan, makan siang">
                    <input type="text" name="meals_en" class="form-control form-control-sm mt-1" placeholder="Meals (EN)">
                    <input type="text" name="meals_zh" class="form-control form-control-sm mt-1" placeholder="餐饮 (中文)">
                </div>
                <div class="col-md-1">
                    <label class="form-label small"><?= t('Akomodasi') ?> (ID)</label>
                    <input type="text" name="accommodation" class="form-control form-control-sm" placeholder="Hotel">
                    <input type="text" name="accommodation_en" class="form-control form-control-sm mt-1" placeholder="Hotel (EN)">
                    <input type="text" name="accommodation_zh" class="form-control form-control-sm mt-1" placeholder="酒店 (中文)">
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
                            <a href="tour-edit.php?id=<?= $id ?>&delete_itinerary=<?= $it['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= t('Hapus itinerary ini?') ?>')"><i class="bi bi-trash"></i></a>
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
                    <a href="tour-edit.php?id=<?= $id ?>&delete_gallery=<?= $g['id'] ?>" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 py-0 px-1" onclick="return confirm('<?= t('Hapus gambar ini?') ?>')" title="<?= t('Hapus') ?>" style="font-size: 11px;"><i class="bi bi-trash"></i></a>
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
