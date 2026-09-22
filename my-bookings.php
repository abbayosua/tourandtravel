<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=my-bookings.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Fase 3: refund self-service — ajukan refund
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_refund') {
    require_once 'includes/refund.php';
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    if ($reason === '') $reason = t('Tidak disebutkan');
    [$ok, $msg] = requestRefund($bookingId, (int)$userId, $reason);
    header('Location: my-bookings.php?msg=' . ($ok ? 'refund_requested' : 'refund_fail') . '&rmsg=' . urlencode($msg));
    exit;
}

// Handle cancel request (self-service cancellation) - supports all booking types
if (isset($_GET['cancel']) && (int)$_GET['cancel'] > 0) {
    $cancelId = (int)$_GET['cancel'];
    $type = $_GET['type'] ?? 'tour';
    $tableMap = [
        'tour' => 'bookings',
        'attraction' => 'attraction_bookings',
        'transfer' => 'transfer_bookings',
        'train' => 'train_bookings',
        'esim' => 'connectivity_bookings',
    ];
    if (isset($tableMap[$type])) {
        $table = $tableMap[$type];
        // Refund wallet if paid with KlookCash (only refund the portion covered by wallet)
        $ref = db()->prepare("SELECT id, total_price FROM `$table` WHERE id = ? AND user_id = ? AND status IN ('pending','confirmed')");
        $ref->execute([$cancelId, $userId]);
        if ($brow = $ref->fetch()) {
            $walletPaid = db()->prepare("SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE user_id = ? AND reference_type = ? AND reference_id = ? AND amount < 0");
            $walletPaid->execute([$userId, $type . '_booking', $cancelId]);
            $paid = (float)$walletPaid->fetchColumn();
            if ($paid > 0) {
                require_once 'includes/wallet.php';
                refundWallet($userId, $paid, $type . '_booking', $cancelId);
            }
        }
        // Refund reseller balance if booking was made via reseller
        if ($type === 'tour') {
            $resCheck = db()->prepare("SELECT booking_source, total_price FROM bookings WHERE id = ? AND user_id = ? AND booking_source = 'reseller'");
            $resCheck->execute([$cancelId, $userId]);
            if ($resRow = $resCheck->fetch()) {
                topUpReseller($userId, (float)$resRow['total_price']);
            }
        }
        db()->prepare("UPDATE `$table` SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending','confirmed')")->execute([$cancelId, $userId]);
    }
    header('Location: my-bookings.php?msg=cancelled');
    exit;
}

// Combine all booking types
$all = [];

$tourBookings = db()->prepare("
    SELECT b.*, t.title as item_title, t.slug as item_slug, t.cover_image, td.departure_date, 'tour' AS btype,
           b.participants AS qty_num, 'peserta' AS qty_unit, b.total_price,
           (SELECT amount FROM booking_addons ba WHERE ba.booking_type='tour' AND ba.booking_id = b.id AND ba.type='insurance') AS insurance_premi
    FROM bookings b
    JOIN tours t ON b.tour_id = t.id
    JOIN tour_dates td ON b.tour_date_id = td.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$tourBookings->execute([$userId]);
foreach ($tourBookings->fetchAll() as $b) { $b['img'] = getTourImage($b, 'small'); $all[] = $b; }

$attrBookings = db()->prepare("
    SELECT ab.*, a.name as item_title, a.slug as item_slug, 'attraction' AS btype,
           ab.quantity AS qty_num, 'tiket' AS qty_unit, ab.total_price, ab.visit_date AS date_label
    FROM attraction_bookings ab
    JOIN attractions a ON ab.attraction_id = a.id
    WHERE ab.user_id = ?
    ORDER BY ab.created_at DESC
");
$attrBookings->execute([$userId]);
foreach ($attrBookings->fetchAll() as $b) { $b['img'] = 'https://placehold.co/300x200?text=Atraksi'; $all[] = $b; }

$transferBookings = db()->prepare("
    SELECT tb.*, tr.name as item_title, tr.slug as item_slug, 'transfer' AS btype,
           tb.passengers AS qty_num, 'pax' AS qty_unit, tb.total_price, tb.pickup_date AS date_label
    FROM transfer_bookings tb
    JOIN transfers tr ON tb.transfer_id = tr.id
    WHERE tb.user_id = ?
    ORDER BY tb.created_at DESC
");
$transferBookings->execute([$userId]);
foreach ($transferBookings->fetchAll() as $b) { $b['img'] = 'https://placehold.co/300x200?text=Transfer'; $all[] = $b; }

$trainBookings = db()->prepare("
    SELECT tb.*, tr.name as item_title, tr.slug as item_slug, 'train' AS btype,
           tb.seats AS qty_num, 'kursi' AS qty_unit, tb.total_price, tb.travel_date AS date_label
    FROM train_bookings tb
    JOIN trains tr ON tb.train_id = tr.id
    WHERE tb.user_id = ?
    ORDER BY tb.created_at DESC
");
$trainBookings->execute([$userId]);
foreach ($trainBookings->fetchAll() as $b) { $b['img'] = 'https://placehold.co/300x200?text=Kereta'; $all[] = $b; }

$esimBookings = db()->prepare("
    SELECT cb.*, cp.name as item_title, cp.slug as item_slug, 'esim' AS btype,
           cb.quantity AS qty_num, 'pcs' AS qty_unit, cb.total_price
    FROM connectivity_bookings cb
    JOIN connectivity_products cp ON cb.product_id = cp.id
    WHERE cb.user_id = ?
    ORDER BY cb.created_at DESC
");
$esimBookings->execute([$userId]);
foreach ($esimBookings->fetchAll() as $b) { $b['img'] = 'https://placehold.co/300x200?text=eSIM'; $b['date_label'] = null; $all[] = $b; }

// Sort combined by created_at desc
usort($all, function ($a, $b) { return strtotime($b['created_at']) - strtotime($a['created_at']); });

$typeIcon = ['tour' => 'map', 'attraction' => 'signpost-2', 'transfer' => 'arrow-left-right', 'train' => 'train-front', 'esim' => 'sim'];
$typeName = ['tour' => t('Tour'), 'attraction' => t('Atraksi'), 'transfer' => t('Transfer'), 'train' => t('Kereta'), 'esim' => t('eSIM')];
$typeLink = ['tour' => 'tour-detail.php', 'attraction' => 'attraction-detail.php', 'transfer' => 'transfer-detail.php', 'train' => 'train-detail.php', 'esim' => 'esim-detail.php'];

$pageTitle = t('Riwayat Booking');
require_once 'includes/header-klook.php';
?>
<section class="py-4">
    <div class="container">
        <h4 class="fw-bold mb-3"><i class="bi bi-ticket-perforated me-2"></i><?= t('Riwayat Booking') ?></h4>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled'): ?>
            <div class="alert alert-success py-2 small"><?= t('Booking berhasil dibatalkan. KlookCash yang digunakan telah dikembalikan.') ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'refund_requested'): ?>
            <div class="alert alert-success py-2 small" data-testid="refund-ok"><i class="bi bi-check-circle me-1"></i><?= e($_GET['rmsg'] ?? t('Pengajuan refund diterima')) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'refund_fail'): ?>
            <div class="alert alert-danger py-2 small" data-testid="refund-fail"><i class="bi bi-x-circle me-1"></i><?= e($_GET['rmsg'] ?? 'Pengajuan refund gagal') ?></div>
        <?php endif; ?>

        <?php if (count($all) > 0): ?>
        <div class="row g-3">
            <?php foreach ($all as $b): ?>
            <?php $btype = $b['btype']; ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="row g-0">
                        <!-- Foto mini -->
                        <div class="col-4 col-md-4">
                            <img src="<?= $b['img'] ?>" onerror="this.onerror=null;this.src='https://placehold.co/300x200?text=<?= $typeName[$btype] ?>'" class="w-100 h-100" style="object-fit: cover; min-height: 130px;" alt="">
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="fw-semibold mb-0"><?= e($b['item_title']) ?></h6>
                                        <small class="text-muted"><i class="bi bi-<?= $typeIcon[$btype] ?> me-1"></i><?= $typeName[$btype] ?> · #<?= e($b['booking_code'] ?? $b['id']) ?></small>
                                    </div>
                                    <span class="badge bg-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'pending' ? 'warning text-dark' : 'danger') ?>">
                                        <?= ucfirst($b['status']) ?>
                                    </span>
                                </div>
                                <div class="row small text-muted g-2">
                                    <?php if (!empty($b['date_label'])): ?>
                                    <div class="col-6">
                                        <i class="bi bi-calendar me-1"></i><?= tglIndonesia($b['date_label']) ?>
                                    </div>
                                    <?php elseif (!empty($b['departure_date'])): ?>
                                    <div class="col-6">
                                        <i class="bi bi-calendar me-1"></i><?= tglIndonesia($b['departure_date']) ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-6">
                                        <i class="bi bi-people me-1"></i><?= $b['qty_num'] . ' ' . t($b['qty_unit']) ?>
                                    </div>
                                    <div class="col-6">
                                        <i class="bi bi-cash me-1"></i><?= formatRupiah($b['total_price']) ?>
                                        <?php if (!empty($b['insurance_premi'])): ?>
                                        <span class="badge bg-success-subtle text-success ms-1" data-testid="insurance-badge-<?= $b['id'] ?>" title="<?= t('Termasuk asuransi perjalanan') ?>"><i class="bi bi-shield-check"></i> +<?= formatRupiah((float)$b['insurance_premi']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-6">
                                        <i class="bi bi-clock me-1"></i><?= date('d/m/Y', strtotime($b['created_at'])) ?>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 mt-2 flex-wrap">
                                    <a href="<?= $typeLink[$btype] ?>?slug=<?= urlencode($b['item_slug']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3"><i class="bi bi-eye me-1"></i><?= t('Detail') ?></a>
                                    <?php if ($b['status'] === 'pending' || $b['status'] === 'confirmed'): ?>
                                    <a href="my-bookings.php?cancel=<?= $b['id'] ?>&type=<?= $btype ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('<?= t('Batalkan booking ini?') ?>')"><i class="bi bi-x-circle me-1"></i><?= t('Batalkan') ?></a>
                                    <?php endif; ?>
                                    <?php if ($btype === 'tour' && $b['status'] === 'confirmed' && ($b['refund_status'] ?? 'none') === 'none'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#refundModal<?= $b['id'] ?>" data-testid="refund-btn-<?= $b['id'] ?>"><i class="bi bi-cash-coin me-1"></i><?= t('Minta Refund') ?></button>
                                    <?php endif; ?>
                                </div>
                                <?php if ($btype === 'tour' && ($b['refund_status'] ?? 'none') !== 'none'): ?>
                                <?php
                                    $rs = $b['refund_status'];
                                    $timeline = [
                                        'requested' => ['bg-warning text-dark', 'Menunggu persetujuan admin'],
                                        'approved'  => ['bg-success', 'Disetujui — refund ' . formatRupiah((float)($b['refund_amount'] ?? 0)) . ' ke KlookCash'],
                                        'rejected'  => ['bg-danger', 'Ditolak admin'],
                                    ];
                                ?>
                                <div class="mt-2 p-2 rounded bg-light" data-testid="refund-timeline-<?= $b['id'] ?>">
                                    <div class="small fw-semibold mb-1"><i class="bi bi-arrow-repeat me-1"></i><?= t('Status Refund') ?></div>
                                    <div class="d-flex align-items-center gap-1 small">
                                        <span class="badge <?= $rs === 'requested' ? 'bg-warning text-dark' : 'bg-secondary' ?>">Diajukan</span>
                                        <i class="bi bi-arrow-right text-muted"></i>
                                        <span class="badge <?= $timeline[$rs][0] ?>"><?= $timeline[$rs][1] ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-ticket fs-1 text-muted"></i>
            <p class="mt-2 text-muted"><?= t('Belum ada pemesanan.') ?></p>
            <a href="tours.php" class="btn btn-primary rounded-pill px-4"><?= t('Booking Sekarang') ?></a>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer-klook.php'; ?>

<?php $rfModalsRendered = $rfModalsRendered ?? []; ?>
<?php foreach ($all as $b): if ($b['btype'] !== 'tour' || ($b['refund_status'] ?? 'none') !== 'none' || $b['status'] !== 'confirmed') continue; if (isset($rfModalsRendered[$b['id']])) continue; $rfModalsRendered[$b['id']] = true; ?>
<div class="modal fade" id="refundModal<?= $b['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="my-bookings.php">
                <input type="hidden" name="action" value="request_refund">
                <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-cash-coin me-2"></i><?= t('Minta Refund') ?> — #<?= e($b['booking_code'] ?? $b['id']) ?></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= t('Tutup') ?>"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2"><?= t('Refund dihitung otomatis: 100% (≥H-8), 50% (H-4–H-7), 0% (<H-3) sesuai kebijakan produk.') ?></p>
                    <label class="form-label small fw-semibold"><?= t('Alasan Refund') ?></label>
                    <textarea name="reason" class="form-control form-control-sm" rows="3" required placeholder="<?= t('Contoh: Perubahan jadwal perjalanan') ?>"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?= t('Batal') ?></button>
                    <button type="submit" class="btn btn-sm btn-warning" data-testid="refund-submit"><?= t('Ajukan Refund') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>