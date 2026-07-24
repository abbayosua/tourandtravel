<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

session_start();
cekLogin();

$message = '';
$error = '';

// Simpan settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $adminPhone = trim($_POST['admin_phone'] ?? '');
    $token = trim($_POST['token'] ?? '');
    $serverUrl = trim($_POST['server_url'] ?? '');

    if (!$adminPhone) {
        $error = 'Nomor WA admin harus diisi';
    } else {
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

// Test koneksi server WUZAPI
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

// Baca log webhook
$logFile = __DIR__ . '/../logs/wa-webhook.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLog = array_slice($lines, -5);
}

$pageTitle = 'Pengaturan WhatsApp';
require_once 'includes/admin-header.php';
?>

<style>
#qrContainer img { max-width: 280px; height: auto; }
.qr-refresh { animation: spin 1s linear infinite; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

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
    <div id="alertArea"></div>

    <div class="row g-4">
        <!-- Koneksi Pengirim -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="bi bi-phone me-2"></i>Koneksi Pengirim WA</h6>
                    <p class="small text-muted mb-2">Nomor WhatsApp yang digunakan untuk mengirim notifikasi ke supplier.</p>

                    <div id="connectionStatus" class="mb-3">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                            <span class="text-muted small">Memuat status...</span>
                        </div>
                    </div>

                    <div id="qrContainer" class="text-center mb-3" style="display:none;">
                        <div class="bg-light rounded p-3 d-inline-block border">
                            <p class="small fw-semibold mb-2">Scan QR ini dengan WhatsApp Anda</p>
                            <img id="qrImage" src="" alt="QR Code">
                            <p class="text-muted small mt-2 mb-0">Buka WhatsApp > Menu > Perangkat Tertaut > Gabung Perangkat</p>
                        </div>
                        <div class="mt-2">
                            <button class="btn btn-sm btn-outline-secondary" onclick="refreshQR()">
                                <i class="bi bi-arrow-clockwise"></i> Refresh QR
                            </button>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button id="btnConnect" class="btn btn-sm btn-success" onclick="connectWA()" style="display:none;">
                            <i class="bi bi-qr-code me-1"></i> Hubungkan Nomor Baru
                        </button>
                        <button id="btnDisconnect" class="btn btn-sm btn-danger" onclick="disconnectWA()" style="display:none;">
                            <i class="bi bi-plug me-1"></i> Putuskan Koneksi
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="loadStatus()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>

            <!-- Konfigurasi -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="bi bi-gear me-2"></i>Konfigurasi</h6>

                    <div class="mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-<?= $waStatus === 'ok' ? 'success' : 'danger' ?>">
                                <?= $waStatus === 'ok' ? 'Server Terhubung' : 'Server Tidak Terhubung' ?>
                            </span>
                            <small class="text-muted">WUZAPI Server</small>
                        </div>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="save_settings" value="1">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nomor WA Admin/Supplier <span class="text-danger">*</span></label>
                            <input type="text" name="admin_phone" class="form-control" value="<?= e($wa_settings['admin_phone']) ?>" placeholder="6285174488415" required>
                            <div class="form-text">Nomor tujuan notifikasi booking baru. Diawali 62, tanpa + atau spasi.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Token Akun WUZAPI</label>
                            <input type="text" name="token" class="form-control" value="<?= e($wa_settings['token']) ?>" placeholder="abbayosua">
                            <div class="form-text">Token akun pengirim WA. Kosongkan jika tidak diubah.</div>
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
                    <p class="small text-muted mb-2">Saat ada booking baru, notifikasi otomatis dikirim ke nomor WA Admin/Supplier:</p>
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

<script>
const WA_AJAX = 'wa-ajax.php';
let qrPollInterval = null;

function loadStatus() {
    const el = document.getElementById('connectionStatus');
    el.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary me-2"></div><span class="text-muted small">Memuat status...</span></div>';

    fetch(WA_AJAX + '?action=status')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                const s = data.data;
                const connected = s.connected && s.loggedIn;
                const phone = s.jid ? s.jid.split(':')[0] : '-';
                const name = s.name || '-';

                el.innerHTML = `
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="badge bg-${connected ? 'success' : 'secondary'} fs-6 px-3 py-2">
                            <i class="bi bi-${connected ? 'check-circle' : 'x-circle'} me-1"></i>
                            ${connected ? 'Terhubung' : 'Putus'}
                        </span>
                        ${connected ? `
                        <div>
                            <strong><i class="bi bi-whatsapp text-success"></i> ${phone}</strong><br>
                            <small class="text-muted">Akun: ${name}</small>
                        </div>` : ''}
                    </div>
                    ${connected ? `
                    <div class="small text-muted">
                        <i class="bi bi-info-circle me-1"></i> Siap mengirim notifikasi ke nomor supplier.
                    </div>` : `
                    <div class="small text-warning">
                        <i class="bi bi-exclamation-triangle me-1"></i> Belum terhubung. Klik "Hubungkan Nomor Baru" untuk scan QR.
                    </div>`}
                `;

                document.getElementById('btnConnect').style.display = connected ? 'none' : 'inline-block';
                document.getElementById('btnDisconnect').style.display = connected ? 'inline-block' : 'none';
                if (connected) {
                    document.getElementById('qrContainer').style.display = 'none';
                    if (qrPollInterval) clearInterval(qrPollInterval);
                }
            } else {
                el.innerHTML = `<div class="alert alert-danger py-2 mb-0">Gagal memuat status: ${data.error || 'Unknown'}</div>`;
            }
        })
        .catch(e => {
            el.innerHTML = `<div class="alert alert-danger py-2 mb-0">Error: ${e.message}</div>`;
        });
}

function connectWA() {
    const btn = document.getElementById('btnConnect');
    const el = document.getElementById('connectionStatus');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menghubungkan...';

    el.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary me-2"></div><span class="text-muted small">Inisialisasi koneksi...</span></div>';

    const formData = new FormData();
    formData.append('action', 'connect');

    fetch(WA_AJAX, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-qr-code me-1"></i> Hubungkan Nomor Baru';

            if (data.success && data.qrcode) {
                document.getElementById('qrImage').src = data.qrcode;
                document.getElementById('qrContainer').style.display = 'block';
                el.innerHTML = '<div class="alert alert-info py-2 mb-0"><i class="bi bi-qr-code me-1"></i> Scan QR code dengan WhatsApp Anda.</div>';
                document.getElementById('btnDisconnect').style.display = 'none';
                document.getElementById('btnConnect').style.display = 'inline-block';
                btn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> QR Baru';

                // Poll status
                if (qrPollInterval) clearInterval(qrPollInterval);
                qrPollInterval = setInterval(() => {
                    fetch(WA_AJAX + '?action=get_qr')
                        .then(r => r.json())
                        .then(d => {
                            if (d.success && !d.qrcode) {
                                // QR gone = connected
                                clearInterval(qrPollInterval);
                                qrPollInterval = null;
                                loadStatus();
                            }
                        })
                        .catch(() => {});
                }, 3000);
            } else if (data.qrcode === '') {
                // Maybe connecting
                el.innerHTML = '<div class="alert alert-warning py-2 mb-0"><i class="bi bi-hourglass me-1"></i> Menunggu QR code...</div>';
                setTimeout(() => refreshQR(), 2000);
            } else {
                el.innerHTML = `<div class="alert alert-danger py-2 mb-0">Gagal: ${data.error || 'Unknown'}</div>`;
            }
        })
        .catch(e => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-qr-code me-1"></i> Hubungkan Nomor Baru';
            el.innerHTML = `<div class="alert alert-danger py-2 mb-0">Error: ${e.message}</div>`;
        });
}

function refreshQR() {
    fetch(WA_AJAX + '?action=get_qr')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.qrcode) {
                document.getElementById('qrImage').src = data.qrcode;
            } else if (data.success && !data.qrcode) {
                // Already connected
                if (qrPollInterval) clearInterval(qrPollInterval);
                qrPollInterval = null;
                document.getElementById('qrContainer').style.display = 'none';
                loadStatus();
            }
        });
}

function disconnectWA() {
    if (!confirm('Yakin ingin memutuskan koneksi WhatsApp?')) return;

    const btn = document.getElementById('btnDisconnect');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memutuskan...';

    const formData = new FormData();
    formData.append('action', 'disconnect');

    fetch(WA_AJAX, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plug me-1"></i> Putuskan Koneksi';
            if (data.success) {
                showAlert('Koneksi WhatsApp berhasil diputuskan.', 'success');
                loadStatus();
            } else {
                showAlert('Gagal memutuskan koneksi.', 'danger');
            }
        })
        .catch(e => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plug me-1"></i> Putuskan Koneksi';
            showAlert('Error: ' + e.message, 'danger');
        });
}

function showAlert(msg, type) {
    const area = document.getElementById('alertArea');
    area.innerHTML = `<div class="alert alert-${type} py-2 alert-dismissible fade show">${msg}<button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button></div>`;
    setTimeout(() => { area.innerHTML = ''; }, 5000);
}

document.addEventListener('DOMContentLoaded', loadStatus);
</script>

<?php require_once 'includes/admin-footer.php'; ?>
