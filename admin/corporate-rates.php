<?php
/**
 * admin/corporate-rates.php — CRUD corporate companies (rate diskon %).
 */
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $pct = min(100.0, max(0.0, (float)($_POST['discount_percent'] ?? 0)));
        if ($name !== '') {
            db()->prepare("INSERT INTO corporate_companies (name, discount_percent, is_active) VALUES (?, ?, 1)")->execute([$name, $pct]);
            $message = 'Perusahaan ditambahkan.';
        }
    } elseif ($action === 'toggle') {
        db()->prepare("UPDATE corporate_companies SET is_active = 1 - is_active WHERE id = ?")->execute([(int)$_POST['id']]);
    } elseif ($action === 'update_pct') {
        db()->prepare("UPDATE corporate_companies SET discount_percent = ? WHERE id = ?")->execute([min(100.0, max(0.0, (float)$_POST['discount_percent'])), (int)$_POST['id']]);
    } elseif ($action === 'assign_user') {
        $email = trim($_POST['email'] ?? '');
        $companyId = (int)($_POST['company_id'] ?? 0);
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $uid = $stmt->fetchColumn();
        if ($uid) {
            db()->prepare("UPDATE users SET corporate_company_id = ? WHERE id = ?")->execute([$companyId ?: null, $uid]);
            $message = $companyId ? "User $email ditautkan." : "User $email dilepas.";
        } else {
            $message = "User $email tidak ditemukan.";
        }
    }
}

$companies = db()->query("SELECT * FROM corporate_companies ORDER BY id DESC")->fetchAll();

$pageTitle = 'Corporate Rates';
require_once 'includes/admin-header.php';
?>
<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4"><i class="bi bi-building me-2"></i>Corporate Rates</h4>
    <?php if ($message): ?><div class="alert alert-success py-2"><?= e($message) ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <h6 class="fw-semibold mb-3">Tambah Perusahaan</h6>
                    <form method="POST">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-2"><input type="text" name="name" class="form-control" placeholder="Nama perusahaan" required></div>
                        <div class="mb-3"><input type="number" name="discount_percent" class="form-control" min="0" max="100" step="0.5" placeholder="Diskon % (mis. 10)" required></div>
                        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                    </form>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <h6 class="fw-semibold mb-3">Tautkan User</h6>
                    <form method="POST">
                        <input type="hidden" name="action" value="assign_user">
                        <div class="mb-2"><input type="email" name="email" class="form-control" placeholder="Email user" required></div>
                        <select name="company_id" class="form-select mb-3" required>
                            <option value="">— Lepas afiliasi —</option>
                            <?php foreach ($companies as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?> (<?= (float)$c['discount_percent'] ?>%)</option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Tautkan</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <h6 class="fw-semibold mb-3">Daftar Perusahaan</h6>
                    <?php if (empty($companies)): ?>
                    <p class="text-muted small mb-0">Belum ada perusahaan.</p>
                    <?php else: ?>
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Nama</th><th style="width:130px;">Diskon %</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($companies as $c): ?>
                        <tr>
                            <td><?= e($c['name']) ?></td>
                            <td>
                                <form method="POST" class="d-flex gap-1">
                                    <input type="hidden" name="action" value="update_pct">
                                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                    <input type="number" name="discount_percent" class="form-control form-control-sm" min="0" max="100" step="0.5" value="<?= (float)$c['discount_percent'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">✓</button>
                                </form>
                            </td>
                            <td><span class="badge <?= $c['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $c['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary"><?= $c['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/admin-footer.php'; ?>
