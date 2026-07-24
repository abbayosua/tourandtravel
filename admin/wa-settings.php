<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Simpan settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminPhone = trim($_POST['admin_phone'] ?? '');
    $token = trim($_POST['token'] ?? '');
    $serverUrl = trim($_POST['server_url'] ?? '');

    if (!$adminPhone) {
        $error = 'Nomor WA admin harus diisi';
    } else {
        // Bersihkan nomor (ambil angka saja)
        $adminPhone = preg_replace('/[^0-9]/', '', $adminPhone);
        if (!preg_match('/^62[0-9]{8,15}$/', $adminPhone)) {
            $error = 'Nomor WA harus diawali 62 (contoh: 6285174488415)';
        } else {
            $configFile = __DIR__ . '/../includes/wa-config.json';
            $current = [];
            if (file_exists($configFile)) {
                $current = json_decode(file_get_contents($configFile), true) ?: [];
            }
            $current['admin_phone'] = $adminPhone;
            if ($token) $current['token'] = $token;
            if ($serverUrl) $current['server_url'] = rtrim($serverUrl, '/');

            if (file_put_contents($configFile, json_encode($current, JSON_PRETTY_PRINT), LOCK_EX)) {
                $message = 'Pengaturan WhatsApp berhasil disimpan';
            } else {
                $error = 'Gagal menyimpan file config';
            }
        }
    }
}

// Load current settings
$wa_config_file = __DIR__ . '/../includes/wa-config.json';
$wa_defaults = [
    'admin_phone' => '6285174488415',
    'token'       => 'abbayosua',
    'server_url'  => 'http://45.158.126.130:48499',
];
$wa_settings = $wa_defaults;
if (file_exists($wa_config_file)) {
    $loaded = json_decode(file_get_contents($wa_config_file), true);
    if (is_array($loaded)) {
        $wa_settings = array_merge($wa_defaults, $loaded);
    }
}

// Test connection
$waStatus = null;
$lastLog = null;
if (function_exists('curl_init')) {
    $ch = curl_init($wa_settings['server_url'] . '/health');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 200) {
        $health = json_decode($res, true);
        $waStatus = $health['status'] ?? 'error';
    } else {
        $waStatus = 'disconnected';
    }
}

// Baca log webhook (last 5 lines)
$logFile = __DIR__ . '/../logs/wa-webhook.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLog = array_slice($lines, -5);
}

$pageTitle = 'Pengaturan WhatsApp';
require_once 'includes/admin-header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h4 class="fw-bold mb-4"><i class="bi bi-whatsapp me-2 text-success"></i>Pengaturan WhatsApp</h4>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success py-2"><?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= $error ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Settings Form -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="bi bi-gear me-2"></i>Konfigurasi WA</h6>

                    <div class="mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-<?= $waStatus === 'ok' ? 'success' : 'danger' ?>">
                                <?= $waStatus === 'ok' ? 'Terhubung' : 'Tidak Terhubung' ?>
                            </span>
                            <small class="text-muted">WUZAPI Server</small>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nomor WA Admin/Supplier <span class="text-danger">*</span></label>
                            <input type="text" name="admin_phone" class="form-control" value="<?= e($wa_settings['admin_phone']) ?>" placeholder="6285174488415" required>
                            <div class="form-text">Nomor tujuan notifikasi booking baru. Diawali 62, tanpa + atau spasi.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Token WUZAPI</label>
                            <input type="text" name="token" class="form-control" value="<?= e($wa_settings['token']) ?>" placeholder="abbayosua">
                            <div class="form-text">Kosongkan jika tidak diubah.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Server URL</label>
                            <input type="text" name="server_url" class="form-control" value="<?= e($wa_settings['server_url']) ?>" placeholder="http://45.158.126.130:48499">
                            <div class="form-text">Kosongkan jika tidak diubah.</div>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check2 me-1"></i> Simpan Pengaturan
                        </button>
                    </form>
                </div>
            </div>

            <!-- Test Send -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="bi bi-send me-2"></i>Test Kirim WA</h6>
                    <form method="POST" action="wa-test.php">
                        <div class="mb-2">
                            <label class="form-label small">Nomor Tujuan</label>
                            <input type="text" name="test_phone" class="form-control form-control-sm" value="<?= e($wa_settings['admin_phone']) ?>">
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-whatsapp me-1"></i> Kirim Test
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info & Logs -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="bi bi-info-circle me-2"></i>Informasi Notifikasi</h6>
                    <p class="small text-muted mb-2">Saat ada booking baru, notifikasi otomatis dikirim ke nomor WA Admin/Supplier dengan informasi:</p>
                    <ul class="small">
                        <li>Kode Booking</li>
                        <li>Nama Tour</li>
                        <li>Tanggal Keberangkatan</li>
                        <li>Nama & No. WA Pemesan</li>
                        <li>Jumlah Peserta</li>
                        <li>Total Harga</li>
                        <li>Link Tracking</li>
                    </ul>
                    <hr>
                    <h6 class="fw-semibold mb-2"><i class="bi bi-terminal me-2"></i>Log Webhook</h6>
                    <?php if ($lastLog): ?>
                        <pre class="bg-dark text-light p-2 rounded small" style="max-height: 200px; overflow-y: auto; font-size: 11px;"><?php foreach ($lastLog as $l): ?><?= e($l) ?><?php endforeach; ?></pre>
                    <?php else: ?>
                        <p class="text-muted small mb-0">Belum ada aktivitas webhook.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
