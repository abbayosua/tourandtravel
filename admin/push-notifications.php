<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];

$langs = ['id', 'en', 'zh'];

// Log table (idempotent)
try {
    db()->exec("CREATE TABLE IF NOT EXISTS push_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NULL,
        target VARCHAR(50) NOT NULL DEFAULT 'all',
        title VARCHAR(200) NOT NULL,
        sent INT NOT NULL DEFAULT 0,
        failed INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $history = db()->query("SELECT pl.*, u.name AS admin_name FROM push_log pl LEFT JOIN users u ON pl.admin_id = u.id ORDER BY pl.created_at DESC LIMIT 20")->fetchAll();
} catch (Throwable $e) {
    $history = [];
}

$pageTitle = t('Push Notifikasi');
require_once 'includes/admin-header.php';
?>
<h4 class="fw-bold mb-3"><i class="bi bi-bell-fill text-primary me-2"></i><?= t('Push Notifikasi') ?></h4>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form id="pushForm">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold"><?= t('Target') ?></label>
                    <select name="target" id="target" class="form-select">
                        <option value="all"><?= t('Semua pengguna') ?></option>
                        <option value="lang"><?= t('Per bahasa (sesuai token)') ?></option>
                        <option value="user"><?= t('User ID tertentu') ?></option>
                    </select>
                </div>
                <div class="col-md-4" id="langWrap" style="display:none">
                    <label class="form-label fw-semibold"><?= t('Bahasa') ?></label>
                    <select name="lang" id="lang" class="form-select">
                        <option value="id">Indonesia</option>
                        <option value="en">English</option>
                        <option value="zh">中文</option>
                    </select>
                </div>
                <div class="col-md-4" id="userIdWrap" style="display:none">
                    <label class="form-label fw-semibold">User ID</label>
                    <input type="number" min="1" name="user_id" id="user_id" class="form-control" placeholder="123">
                </div>
            </div>

            <ul class="nav nav-tabs mt-3" role="tablist">
                <?php foreach ($langs as $i => $lg): ?>
                <li class="nav-item">
                    <button class="nav-link <?= $i === 0 ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-<?= $lg ?>" type="button">
                        <?= $lg === 'id' ? 'Indonesia' : ($lg === 'en' ? 'English' : '中文') ?>
                        <?php if ($lg !== 'id'): ?><span class="text-muted small">(<?= t('opsional') ?>)</span><?php endif; ?>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
            <div class="tab-content border border-top-0 p-3">
                <?php foreach ($langs as $i => $lg): ?>
                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="tab-<?= $lg ?>">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold"><?= t('Judul') ?> (<?= strtoupper($lg) ?>)</label>
                        <input type="text" name="title_<?= $lg ?>" class="form-control" maxlength="150" placeholder="<?= t('Judul notifikasi') ?> <?= strtoupper($lg) ?>">
                    </div>
                    <div>
                        <label class="form-label small fw-semibold"><?= t('Isi Pesan') ?> (<?= strtoupper($lg) ?>)</label>
                        <textarea name="body_<?= $lg ?>" class="form-control" rows="3" maxlength="500" placeholder="<?= t('Isi pesan notifikasi') ?> <?= strtoupper($lg) ?>"></textarea>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex align-items-center gap-2 mt-3">
                <button type="submit" id="sendBtn" class="btn btn-primary">
                    <i class="bi bi-send me-1"></i><?= t('Kirim Notifikasi') ?>
                </button>
                <span id="pushStatus" class="small text-muted"></span>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-2"></i><?= t('Riwayat Pengiriman') ?></div>
    <div class="card-body p-0">
        <?php if (!count($history)): ?>
            <div class="text-center py-4 text-muted"><?= t('Belum ada pengiriman.') ?></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Waktu</th>
                        <th><?= t('Judul') ?></th>
                        <th><?= t('Target') ?></th>
                        <th><?= t('Terkirim') ?></th>
                        <th><?= t('Gagal') ?></th>
                        <th><?= t('Oleh') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td class="small text-muted"><?= date('d M Y H:i', strtotime($h['created_at'])) ?></td>
                        <td class="small fw-semibold"><?= e($h['title']) ?></td>
                        <td><span class="badge bg-secondary"><?= e($h['target']) ?></span></td>
                        <td><span class="badge bg-success"><?= (int)$h['sent'] ?></span></td>
                        <td><span class="badge bg-danger"><?= (int)$h['failed'] ?></span></td>
                        <td class="small"><?= e($h['admin_name'] ?? '#' . $h['admin_id']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('pushForm');
    var target = document.getElementById('target');
    var langWrap = document.getElementById('langWrap');
    var userIdWrap = document.getElementById('userIdWrap');
    var statusEl = document.getElementById('pushStatus');
    var sendBtn = document.getElementById('sendBtn');

    function syncTarget() {
        langWrap.style.display = target.value === 'lang' ? '' : 'none';
        userIdWrap.style.display = target.value === 'user' ? '' : 'none';
    }
    target.addEventListener('change', syncTarget);
    syncTarget();

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        var fd = new FormData(form);
        var payload = {};
        fd.forEach(function (v, k) { payload[k] = v; });

        statusEl.textContent = '...';
        statusEl.className = 'small text-muted';
        sendBtn.disabled = true;

        fetch('ajax/send-push.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': payload.csrf_token || ''
            },
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json().catch(function () { throw new Error('HTTP ' + r.status); }); })
        .then(function (res) {
            if (res.ok) {
                statusEl.textContent = res.sent + ' terkirim, ' + res.failed + ' gagal';
                statusEl.className = 'small text-success fw-semibold';
                setTimeout(function () { window.location.reload(); }, 1200);
            } else {
                throw new Error(res.error || 'Gagal');
            }
        })
        .catch(function (err) {
            statusEl.textContent = err.message || 'Error';
            statusEl.className = 'small text-danger fw-semibold';
        })
        .finally(function () { sendBtn.disabled = false; });
    });
})();
</script>
<?php require_once 'includes/admin-footer.php'; ?>
