<?php $lang = $lang ?? 'id'; $st = $tplData['status'] ?? 'pending';

$MESSAGES = [
    'id' => ['Status pemesanan Anda telah diperbarui.', 'Kode Booking', 'Status', 'Lacak booking'],
    'en' => ['Your booking status has been updated.', 'Booking code', 'Status', 'Track booking'],
    'zh' => ['您的订单状态已更新。', '预订编号', '状态', '查询订单'],
];
$m = $MESSAGES[$lang] ?? $MESSAGES['id'];
?>
<p><?= $m[0] ?></p>
<p><strong><?= $m[1] ?>:</strong> <?= htmlspecialchars($tplData['booking_code'] ?? '-') ?></p>
<p><strong><?= $m[2] ?>:</strong> <?= htmlspecialchars($st) ?></p>
<p><a href="<?= htmlspecialchars($tplData['track_link'] ?? '') ?>" style="color:#0d6efd;"><?= $m[3] ?></a></p>
