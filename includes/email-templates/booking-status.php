<?php $lang = $lang ?? 'id'; $st = $tplData['status'] ?? 'pending';

$MESSAGES = [
    'id' => ['Status pemesanan Anda telah diperbarui.', 'Kode Booking', 'Status', 'Lacak booking'],
    'en' => ['Your booking status has been updated.', 'Booking code', 'Status', 'Track booking'],
    'zh' => ['您的订单状态已更新。', '预订编号', '状态', '查询订单'],
];
$m = $MESSAGES[$lang] ?? $MESSAGES['id'];

$STATUS = [
    'id' => ['pending' => 'Menunggu Pembayaran', 'confirmed' => 'Dikonfirmasi', 'cancelled' => 'Dibatalkan', 'paid' => 'Dibayar', 'refunded' => 'Dana Dikembalikan', 'expired' => 'Kedaluwarsa', 'completed' => 'Selesai', 'rejected' => 'Ditolak'],
    'en' => ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled', 'paid' => 'Paid', 'refunded' => 'Refunded', 'expired' => 'Expired', 'completed' => 'Completed', 'rejected' => 'Rejected'],
    'zh' => ['pending' => '待付款', 'confirmed' => '已确认', 'cancelled' => '已取消', 'paid' => '已付款', 'refunded' => '已退款', 'expired' => '已过期', 'completed' => '已完成', 'rejected' => '已拒绝'],
];
$stLabel = $STATUS[$lang][$st] ?? $st;
?>
<p><?= $m[0] ?></p>
<p><strong><?= $m[1] ?>:</strong> <?= htmlspecialchars($tplData['booking_code'] ?? '-') ?></p>
<p><strong><?= $m[2] ?>:</strong> <?= htmlspecialchars($stLabel) ?></p>
<p><a href="<?= htmlspecialchars($tplData['track_link'] ?? '') ?>" style="color:#0d6efd;"><?= $m[3] ?></a></p>
