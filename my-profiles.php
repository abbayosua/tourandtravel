<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=my-profiles.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$msg = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $passportNo = trim($_POST['passport_no'] ?? '') ?: null;
        $nationality = trim($_POST['nationality'] ?? '') ?: null;
        $dob = $_POST['dob'] ?: null;
        $phone = trim($_POST['phone'] ?? '') ?: null;
        $isDefault = isset($_POST['is_default']) ? 1 : 0;

        if (!$fullName) {
            $msg = 'error:Nama wajib diisi';
        } else {
            if ($isDefault) {
                db()->prepare("UPDATE passenger_profiles SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
            }
            if ($id > 0) {
                db()->prepare("UPDATE passenger_profiles SET full_name = ?, passport_no = ?, nationality = ?, dob = ?, phone = ?, is_default = ? WHERE id = ? AND user_id = ?")
                    ->execute([$fullName, $passportNo, $nationality, $dob, $phone, $isDefault, $id, $userId]);
            } else {
                $hasAny = db()->prepare("SELECT COUNT(*) FROM passenger_profiles WHERE user_id = ?");
                $hasAny->execute([$userId]);
                if ((int)$hasAny->fetchColumn() === 0) $isDefault = 1;
                db()->prepare("INSERT INTO passenger_profiles (user_id, full_name, passport_no, nationality, dob, phone, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$userId, $fullName, $passportNo, $nationality, $dob, $phone, $isDefault]);
            }
            header('Location: my-profiles.php?msg=saved');
            exit;
        }
    } elseif ($action === 'delete') {
        $deleteId = (int)($_POST['id'] ?? 0);
        db()->prepare("DELETE FROM passenger_profiles WHERE id = ? AND user_id = ?")->execute([$deleteId, $userId]);
        header('Location: my-profiles.php?msg=deleted');
        exit;
    } elseif ($action === 'default') {
        $defaultId = (int)($_POST['id'] ?? 0);
        db()->prepare("UPDATE passenger_profiles SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
        db()->prepare("UPDATE passenger_profiles SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$defaultId, $userId]);
        header('Location: my-profiles.php?msg=default');
        exit;
    }
}

// Fetch profiles
$stmt = db()->prepare("SELECT * FROM passenger_profiles WHERE user_id = ? ORDER BY is_default DESC, created_at ASC");
$stmt->execute([$userId]);
$profiles = $stmt->fetchAll();

// Edit mode
$editProfile = null;
if (!empty($_GET['edit'])) {
    $eStmt = db()->prepare("SELECT * FROM passenger_profiles WHERE id = ? AND user_id = ?");
    $eStmt->execute([(int)$_GET['edit'], $userId]);
    $editProfile = $eStmt->fetch() ?: null;
}

$pageTitle = t('Profil Penumpang');
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-3"><i class="bi bi-person-badge me-2 text-primary"></i><?= t('Profil Penumpang') ?></h4>
        <p class="text-muted small mb-4"><?= t('Simpan data penumpang untuk checkout lebih cepat.') ?></p>

        <?php if ($msg): ?>
            <?php [$type, $text] = explode(':', $msg, 2); ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : 'success' ?> alert-dismissible py-2"><?= e($text) ?>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible py-2">
                <?= $_GET['msg'] === 'saved' ? t('Profil tersimpan') : ($_GET['msg'] === 'deleted' ? t('Profil dihapus') : t('Default diperbarui')) ?>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Form -->
            <div class="col-md-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><?= $editProfile ? t('Edit Profil') : t('Tambah Profil Baru') ?></h6>
                        <form method="POST">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id" value="<?= $editProfile['id'] ?? 0 ?>">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold"><?= t('Nama Lengkap') ?> *</label>
                                <input type="text" name="full_name" class="form-control" required value="<?= e($editProfile['full_name'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold"><?= t('Nomor Paspor') ?></label>
                                <input type="text" name="passport_no" class="form-control" value="<?= e($editProfile['passport_no'] ?? '') ?>">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold"><?= t('Kewarganegaraan') ?></label>
                                    <input type="text" name="nationality" class="form-control" value="<?= e($editProfile['nationality'] ?? '') ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold"><?= t('Tanggal Lahir') ?></label>
                                    <input type="date" name="dob" class="form-control" value="<?= e($editProfile['dob'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold"><?= t('No. Telepon') ?></label>
                                <input type="text" name="phone" class="form-control" value="<?= e($editProfile['phone'] ?? '') ?>">
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="isDefault" <?= (!empty($editProfile['is_default']) || empty($profiles)) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="isDefault"><?= t('Jadikan default') ?></label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><?= $editProfile ? t('Update') : t('Simpan') ?></button>
                            <?php if ($editProfile): ?>
                                <a href="my-profiles.php" class="btn btn-outline-secondary w-100 mt-2"><?= t('Batal') ?></a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- List -->
            <div class="col-md-7">
                <?php if (count($profiles) > 0): ?>
                <div class="row g-3">
                <?php foreach ($profiles as $p): ?>
                    <div class="col-12">
                        <div class="card border-0 shadow-sm <?= $p['is_default'] ? 'border-primary' : '' ?>">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="fw-semibold"><?= e($p['full_name']) ?></span>
                                            <?php if ($p['is_default']): ?>
                                                <span class="badge bg-primary"><?= t('Default') ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?php if ($p['passport_no']): ?><span><i class="bi bi-passport me-1"></i><?= e($p['passport_no']) ?></span> · <?php endif; ?>
                                            <?php if ($p['nationality']): ?><span><?= e($p['nationality']) ?></span> · <?php endif; ?>
                                            <?php if ($p['dob']): ?><span><?= date('d M Y', strtotime($p['dob'])) ?></span> · <?php endif; ?>
                                            <?php if ($p['phone']): ?><span><?= e($p['phone']) ?></span><?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <?php if (!$p['is_default']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="default">
                                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="<?= t('Jadikan default') ?>"><i class="bi bi-star"></i></button>
                                        </form>
                                        <?php endif; ?>
                                        <a href="my-profiles.php?edit=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-secondary" title="<?= t('Edit') ?>"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('<?= t('Hapus profil ini?') ?>')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= t('Hapus') ?>"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-person-plus fs-1 text-muted"></i>
                    <p class="mt-2 text-muted"><?= t('Belum ada profil penumpang.') ?></p>
                    <p class="small text-muted"><?= t('Tambahkan profil di sisi kiri untuk checkout lebih cepat.') ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>
