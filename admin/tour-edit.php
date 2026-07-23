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
    $maxParticipants = (int)($_POST['max_participants'] ?? 1);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (!$title) $error = 'Judul tour harus diisi';
    elseif (!$category) $error = 'Kategori harus diisi';
    elseif ($price <= 0) $error = 'Harga harus diisi';

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

        $stmt = db()->prepare("UPDATE tours SET title=?, slug=?, category=?, description=?, price=?, max_participants=?, cover_image=?, is_active=? WHERE id=?");
        $stmt->execute([$title, $slug, $category, $description, $price, $maxParticipants, $coverImage, $isActive, $id]);

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

$msg = '';
if (isset($_GET['msg'])) {
    $msgs = [
        'itinerary_added' => 'Itinerary berhasil ditambahkan',
        'itinerary_deleted' => 'Itinerary berhasil dihapus',
        'date_added' => 'Tanggal keberangkatan berhasil ditambahkan',
        'date_deleted' => 'Tanggal keberangkatan berhasil dihapus',
    ];
    $msg = $msgs[$_GET['msg']] ?? '';
}

$pageTitle = 'Edit Tour';
require_once 'includes/admin-header.php';
?>

<h4 class="fw-bold mb-3">Edit Tour: <?= e($tour['title']) ?></h4>

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
                        <label class="form-label fw-semibold">Judul Tour</label>
                        <input type="text" name="title" class="form-control" value="<?= e($tour['title']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="5"><?= e($tour['description']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Gambar Cover</label>
                        <?php if ($tour['cover_image']): ?>
                            <div class="mb-2">
                                <img src="../uploads/<?= e($tour['cover_image']) ?>" style="max-height: 100px; border-radius: 8px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="cover_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">Kosongkan jika tidak ingin mengubah gambar</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori</label>
                        <input type="text" name="category" class="form-control" value="<?= e($tour['category']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga (Rp)</label>
                        <input type="number" name="price" class="form-control" min="0" value="<?= $tour['price'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Max Peserta</label>
                        <input type="number" name="max_participants" class="form-control" min="1" value="<?= $tour['max_participants'] ?>">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= $tour['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isActive">Aktif</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Update Tour</button>
            <a href="tours.php" class="btn btn-outline-secondary w-100 mt-2">Kembali</a>
        </div>
    </div>
</form>

<!-- Jadwal Keberangkatan -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Jadwal Keberangkatan</h6>
        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addDateForm">+ Tambah</button>
    </div>
    <div class="card-body">
        <div class="collapse mb-3" id="addDateForm">
            <form method="POST" class="row g-2 bg-light p-3 rounded">
                <div class="col-md-4">
                    <label class="form-label small">Tanggal Berangkat</label>
                    <input type="date" name="departure_date" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Tanggal Kembali</label>
                    <input type="date" name="return_date" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Slot</label>
                    <input type="number" name="slots" class="form-control form-control-sm" min="1" value="20" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="add_date" class="btn btn-sm btn-primary w-100">Simpan</button>
                </div>
            </form>
        </div>

        <?php if (count($tourDates) > 0): ?>
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Berangkat</th>
                    <th>Kembali</th>
                    <th>Slot</th>
                    <th>Aksi</th>
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
        <p class="text-muted small mb-0">Belum ada jadwal keberangkatan.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Itinerary -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Itinerary</h6>
        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addItineraryForm">+ Tambah</button>
    </div>
    <div class="card-body">
        <div class="collapse mb-3" id="addItineraryForm">
            <form method="POST" class="row g-2 bg-light p-3 rounded">
                <div class="col-md-1">
                    <label class="form-label small">Hari</label>
                    <input type="number" name="day" class="form-control form-control-sm" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Judul</label>
                    <input type="text" name="it_title" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Deskripsi</label>
                    <textarea name="it_desc" class="form-control form-control-sm" rows="1"></textarea>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Makan</label>
                    <input type="text" name="meals" class="form-control form-control-sm" placeholder="Sarapan, makan siang">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Akomodasi</label>
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
                        <th>Hari</th>
                        <th>Judul</th>
                        <th>Deskripsi</th>
                        <th>Makan</th>
                        <th>Akomodasi</th>
                        <th>Aksi</th>
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
        <p class="text-muted small mb-0">Belum ada itinerary.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
