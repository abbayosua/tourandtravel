<?php $lang = $lang ?? 'id';

$MESSAGES = [
    'id' => ['Pembayaran diterima — rincian berikut.', 'Pesanan', 'Jumlah', 'Termasuk Asuransi Perjalanan'],
    'en' => ['Payment received — invoice below.', 'Order', 'Amount', 'Travel Insurance included'],
    'zh' => ['已收到付款 — 账单如下。', '订单', '金额', '含旅行保险'],
];
$m = $MESSAGES[$lang] ?? $MESSAGES['id'];
$insurance = (float)($tplData['insurance_premi'] ?? 0);
?>
<p><?= $m[0] ?></p>
<p><strong><?= $m[1] ?>:</strong> <?= htmlspecialchars($tplData['order_id'] ?? '-') ?><br>
<strong><?= $m[2] ?>:</strong> <?= htmlspecialchars($tplData['amount'] ?? '-') ?></p>
<?php if ($insurance > 0): ?>
<p style="color:#198754;">🛡 <strong><?= $m[3] ?></strong>: <?= htmlspecialchars($tplData['insurance_amount'] ?? '') ?></p>
<?php endif; ?>
