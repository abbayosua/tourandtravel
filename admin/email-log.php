<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/email.php';
require_once '../includes/auth.php';
cekLogin();

$pageTitle = t('Log Email');

$error = '';

// Resend manual: kirim ulang subject + template terakhir berdasarkan event & email
// (body tidak disimpan penuh — resend memakai generic dengan subject sama).
if (isset($_GET['resend'])) {
    $id = (int)$_GET['resend'];
    $st = db()->prepare("SELECT * FROM email_log WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch();
    if ($row) {
        $r = sendEmail($row['to_email'], $row['subject'], '<p>' . t('Resend dari log oleh admin.') . '</p>', $row['event']);
        header('Location: email-log.php?msg=' . ($r['ok'] ? 'resent' : 'failed'));
    } else {
        header('Location: email-log.php?msg=failed');
    }
    exit;
}

$filterStatus = $_GET['status'] ?? '';
$filterEvent = $_GET['event'] ?? '';
$validStatus = ['sent', 'failed'];

$sql = "SELECT * FROM email_log WHERE 1=1";
$params = [];
if (in_array($filterStatus, $validStatus, true)) { $sql .= " AND status = ?"; $params[] = $filterStatus; }
if ($filterEvent !== '') { $sql .= " AND event = ?"; $params[] = $filterEvent; }
$sql .= " ORDER BY id DESC LIMIT 200";
$rows = db()->prepare($sql);
$rows->execute($params);
$rows = $rows->fetchAll();

$events = db()->query("SELECT DISTINCT event FROM email_log WHERE event IS NOT NULL AND event != '' ORDER BY event")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/includes/admin-header.php';
?>

<h4 class="fw-bold mb-3"><?= t('Log Email') ?></h4>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-<?= $_GET['msg'] === 'resent' ? 'success' : 'warning' ?> py-2 small">
        <?= $_GET['msg'] === 'resent' ? t('Email berhasil dikirim ulang.') : t('Resend gagal — lihat log terbaru.') ?>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold"><?= t('Status') ?></label>
                <select name="status" class="form-select form-select-sm">
                    <option value=""><?= t('Semua') ?></option>
                    <?php foreach ($validStatus as $vs): ?>
                        <option value="<?= $vs ?>" <?= $filterStatus === $vs ? 'selected' : '' ?>><?= ucfirst(t($vs)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold"><?= t('Event') ?></label>
                <select name="event" class="form-select form-select-sm">
                    <option value=""><?= t('Semua') ?></option>
                    <?php foreach ($events as $ev): ?>
                        <option value="<?= e($ev) ?>" <?= $filterEvent === $ev ? 'selected' : '' ?>><?= e($ev) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-sm btn-primary"><?= t('Filter') ?></button>
                <a href="email-log.php" class="btn btn-sm btn-outline-secondary"><?= t('Reset') ?></a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th><?= t('Kepada') ?></th>
                        <th><?= t('Subjek') ?></th>
                        <th><?= t('Event') ?></th>
                        <th><?= t('Driver') ?></th>
                        <th><?= t('Status') ?></th>
                        <th><?= t('Tanggal') ?></th>
                        <th class="text-end"><?= t('Aksi') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!count($rows)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4"><?= t('Belum ada log email.') ?></td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td class="small"><?= e($r['to_email']) ?></td>
                        <td class="small"><?= e($r['subject']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e($r['event'] ?? '-') ?></span></td>
                        <td><small><?= e($r['driver']) ?></small></td>
                        <td>
                            <span class="badge <?= $r['status'] === 'sent' ? 'bg-success' : 'bg-danger' ?>"><?= ucfirst(t($r['status'])) ?></span>
                            <?php if ($r['error']): ?><div class="text-danger" style="font-size:10px; max-width:180px;"><?= e($r['error']) ?></div><?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?= date('d/m H:i', strtotime($r['created_at'])) ?></small></td>
                        <td class="text-end">
                            <a href="email-log.php?resend=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary"><?= t('Resend') ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
