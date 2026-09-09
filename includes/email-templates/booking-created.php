<?php $lang = $lang ?? 'id';

$MESSAGES = [
    'id' => ['Terima kasih! Pemesanan Anda telah kami terima.', 'Kode Booking', 'Total', 'Bayar Sekarang'],
    'en' => ['Thank you! Your booking has been received.', 'Booking code', 'Total', 'Pay Now'],
    'zh' => ['谢谢！我们已收到您的预订。', '预订编号', '总计', '立即支付'],
];
$m = $MESSAGES[$lang] ?? $MESSAGES['id'];
?>
<p><?= $m[0] ?></p>
<p><strong><?= $m[1] ?>:</strong> <?= htmlspecialchars($tplData['booking_code'] ?? '-') ?></p>
<p><strong><?= $m[2] ?>:</strong> <?= htmlspecialchars($tplData['total'] ?? '-') ?></p>
<?php if (!empty($tplData['pay_link'])): ?>
<p><a href="<?= htmlspecialchars($tplData['pay_link']) ?>" style="background:#198754;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;display:inline-block;"><?= $m[3] ?></a></p>
<?php endif; ?>
