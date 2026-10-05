<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once 'includes/admin-access.php';
requireSuperadmin();

$pageTitle = t('Kelola Admin');
$me = (int)$_SESSION['admin_id'];
$action = $_GET['action'] ?? '';
$editId = (int)($_GET['id'] ?? 0);
$formError = '';

function superadminCount(): int {
    try {
        return (int)db()->query("SELECT COUNT(*) FROM admins WHERE role = 'superadmin'")->fetchColumn();
    } catch (Throwable $e) {
        return 1;
    }
}

/* ---------- POST: simpan ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $isNew = $id === 0;
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $role = ($_POST['role'] ?? 'staff') === 'superadmin' ? 'superadmin' : 'staff';
    $grants = array_values(array_filter(array_map('strval', (array)($_POST['grants'] ?? []))));

    if ($username === '' || !preg_match('/^[A-Za-z0-9_.@-]{3,50}$/', $username)) {
        $formError = t('Username 3-50 karakter (huruf, angka, _ . @ -).');
    } elseif ($id === 0 && strlen($password) < 6) {
        $formError = t('Password minimal 6 karakter.');
    } elseif ($password !== '' && strlen($password) < 6) {
        $formError = t('Password minimal 6 karakter.');
    } elseif ($role === 'staff' && count($grants) === 0) {
        $formError = t('Pilih minimal satu halaman.');
    } else {
        $dup = db()->prepare("SELECT id FROM admins WHERE username = ? AND id <> ? LIMIT 1");
        $dup->execute([$username, $id]);
        if ($dup->fetch()) {
            $formError = t('Username sudah dipakai.');
        } else {
            if ($id === 0) {
                $ins = db()->prepare("INSERT INTO admins (username, password_hash, role) VALUES (?, ?, ?)");
                $ins->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role]);
                $id = (int)db()->lastInsertId();
            } else {
                if ($id === $me && $role !== 'superadmin') {
                    $formError = t('Tidak dapat menurunkan peran akun sendiri.');
                } elseif ($role !== 'superadmin') {
                    $cur = db()->prepare("SELECT role FROM admins WHERE id = ?");
                    $cur->execute([$id]);
                    if ($cur->fetchColumn() === 'superadmin' && superadminCount() <= 1) {
                        $formError = t('Tidak dapat menurunkan superadmin terakhir.');
                    }
                }
                if ($formError === '') {
                    if ($password !== '') {
                        $upd = db()->prepare("UPDATE admins SET username = ?, password_hash = ?, role = ? WHERE id = ?");
                        $upd->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role, $id]);
                    } else {
                        $upd = db()->prepare("UPDATE admins SET username = ?, role = ? WHERE id = ?");
                        $upd->execute([$username, $role, $id]);
                    }
                }
            }
            if ($formError === '') {
                setAdminPermissions($id, $role === 'superadmin' ? [] : $grants);
                $_SESSION['admin_role'] = $me === $id ? $role : ($_SESSION['admin_role'] ?? 'superadmin');
                adminFlash($isNew ? t('Admin berhasil ditambahkan.') : t('Admin berhasil diperbarui.'));
                header('Location: admins.php');
                exit;
            }
        }
    }
    $action = $id > 0 ? 'edit' : 'create';
    $editId = $id;
}

/* ---------- POST: hapus ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id === $me) {
        adminFlash(t('Tidak dapat menghapus akun sendiri.'), 'danger');
    } else {
        $cur = db()->prepare("SELECT role FROM admins WHERE id = ?");
        $cur->execute([$id]);
        $curRole = $cur->fetchColumn();
        if ($curRole === 'superadmin' && superadminCount() <= 1) {
            adminFlash(t('Tidak dapat menghapus superadmin terakhir.'), 'danger');
        } else {
            db()->prepare("DELETE FROM admins WHERE id = ?")->execute([$id]);
            adminFlash(t('Admin berhasil dihapus.'));
        }
    }
    header('Location: admins.php');
    exit;
}

$rows = db()->query("SELECT id, username, role, created_at FROM admins ORDER BY id ASC")->fetchAll();
$grantMap = [];
$gstmt = db()->query("SELECT admin_id, page_key FROM admin_permissions");
foreach ($gstmt->fetchAll() as $g) {
    $grantMap[(int)$g['admin_id']][] = $g['page_key'];
}

$editRow = null;
$editGrants = [];
if ($action === 'edit' && $editId > 0) {
    $s = db()->prepare("SELECT id, username, role FROM admins WHERE id = ?");
    $s->execute([$editId]);
    $editRow = $s->fetch();
    if (!$editRow) {
        $action = '';
    } else {
        $editGrants = $grantMap[$editId] ?? [];
    }
}

/* Daftar key kanonis untuk form (tanpa 'admins', tanpa dashboard yg otomatis). */
$formKeys = [];
foreach (adminPages() as $k => $p) {
    if (adminCanonicalKey($k) !== $k || $k === 'admins' || $k === 'dashboard') continue;
    $formKeys[$p['section']][] = $k;
}

require_once 'includes/admin-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><?= t('Kelola Admin') ?></h4>
    <?php if ($action === ''): ?>
    <a href="admins.php?action=create" class="btn btn-primary btn-sm" data-testid="admin-add-btn"><i class="bi bi-plus-lg"></i> <?= t('Tambah Admin') ?></a>
    <?php endif; ?>
</div>

<?php if ($action === 'create' || $action === 'edit'): ?>
<?php
$fUsername = $formError !== '' ? ($_POST['username'] ?? '') : ($editRow['username'] ?? '');
$fRole = $formError !== '' ? (($_POST['role'] ?? 'staff') === 'superadmin' ? 'superadmin' : 'staff') : ($editRow['role'] ?? 'staff');
$fGrants = $formError !== '' ? array_values((array)($_POST['grants'] ?? [])) : $editGrants;
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><?= $action === 'create' ? t('Tambah Admin') : t('Edit Admin') ?></h6>
        <?php if ($formError): ?><div class="alert alert-danger py-2 small"><?= e($formError) ?></div><?php endif; ?>
        <form method="POST" id="adminForm">
            <input type="hidden" name="form" value="save">
            <input type="hidden" name="id" value="<?= (int)($editRow['id'] ?? 0) ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold"><?= t('Username') ?></label>
                    <input type="text" name="username" class="form-control" value="<?= e($fUsername) ?>" required maxlength="50">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold"><?= t('Password') ?></label>
                    <input type="password" name="password" class="form-control" minlength="6" <?= $action === 'create' ? 'required' : '' ?> placeholder="<?= $action === 'edit' ? e(t('Biarkan kosong jika tidak diubah')) : '' ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold"><?= t('Peran') ?></label>
                    <select name="role" id="roleSelect" class="form-select">
                        <option value="staff" <?= $fRole === 'staff' ? 'selected' : '' ?>><?= t('Staff') ?> — <?= t('hak akses dipilih') ?></option>
                        <option value="superadmin" <?= $fRole === 'superadmin' ? 'selected' : '' ?>><?= t('Superadmin') ?> — <?= t('semua akses') ?></option>
                    </select>
                </div>
            </div>
            <div id="grantsBox" class="mt-3">
                <label class="form-label small fw-semibold"><?= t('Hak Akses') ?> — <?= t('Preset') ?>:</label>
                <div class="d-flex flex-wrap gap-2 mb-2" id="presetRow">
                    <?php foreach (adminPresets() as $pk => $pr): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-preset="<?= e($pk) ?>"><?= e(t($pr['label'])) ?></button>
                    <?php endforeach; ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-preset="__clear"><?= t('Bersihkan') ?></button>
                </div>
                <div class="row g-3">
                    <?php foreach ($formKeys as $section => $keys): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded p-2 h-100">
                            <div class="small fw-bold text-secondary text-uppercase mb-1"><?= e(t($section)) ?></div>
                            <?php foreach ($keys as $k): $pg = adminPages()[$k]; ?>
                            <div class="form-check">
                                <input class="form-check-input grant-cb" type="checkbox" name="grants[]" value="<?= e($k) ?>" id="g-<?= e($k) ?>" <?= in_array($k, $fGrants, true) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="g-<?= e($k) ?>"><?= e(t($pg['label'])) ?><?= empty($pg['show']) ? ' <span class="text-muted">(' . e(t('tanpa menu')) . ')</span>' : '' ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-4"><?= t('Simpan') ?></button>
                <a href="admins.php" class="btn btn-outline-secondary btn-sm"><?= t('Batal') ?></a>
            </div>
        </form>
    </div>
</div>
<script>
(function() {
    var presets = <?= json_encode(array_map(fn($p) => $p['pages'], adminPresets()), JSON_UNESCAPED_UNICODE) ?>;
    var roleSel = document.getElementById('roleSelect');
    var box = document.getElementById('grantsBox');
    function syncRole() { box.style.display = roleSel.value === 'superadmin' ? 'none' : ''; }
    roleSel.addEventListener('change', syncRole);
    syncRole();
    document.getElementById('presetRow').addEventListener('click', function(e) {
        var b = e.target.closest('button[data-preset]');
        if (!b) return;
        var pages = b.getAttribute('data-preset') === '__clear' ? [] : (presets[b.getAttribute('data-preset')] || []);
        document.querySelectorAll('.grant-cb').forEach(function(cb) { cb.checked = pages.indexOf(cb.value) !== -1; });
        if (roleSel.value === 'superadmin' && pages.length) { roleSel.value = 'staff'; syncRole(); }
    });
})();
</script>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= t('Username') ?></th>
                        <th><?= t('Peran') ?></th>
                        <th><?= t('Hak Akses') ?></th>
                        <th><?= t('Dibuat') ?></th>
                        <th class="text-end"><?= t('Aksi') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): $g = $grantMap[(int)$r['id']] ?? []; ?>
                    <tr>
                        <td class="fw-semibold"><?= e($r['username']) ?><?= (int)$r['id'] === $me ? ' <span class="badge bg-secondary">' . e(t('Anda')) . '</span>' : '' ?></td>
                        <td><span class="badge <?= $r['role'] === 'superadmin' ? 'bg-danger' : 'bg-info' ?>"><?= e($r['role'] === 'superadmin' ? t('Superadmin') : t('Staff')) ?></span></td>
                        <td class="small text-muted"><?= $r['role'] === 'superadmin' ? e(t('Semua akses')) : (count($g) ? e(implode(', ', array_map(fn($k) => t(adminPages()[$k]['label'] ?? $k), $g))) : '—') ?></td>
                        <td class="small text-muted"><?= e($r['created_at'] ?? '') ?></td>
                        <td class="text-end text-nowrap">
                            <a href="admins.php?action=edit&id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary"><?= t('Edit') ?></a>
                            <?php if ((int)$r['id'] !== $me): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm(<?= e(json_encode(t('Yakin ingin menghapus akun ini?'))) ?>)">
                                <input type="hidden" name="form" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><?= t('Hapus') ?></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once 'includes/admin-footer.php'; ?>
