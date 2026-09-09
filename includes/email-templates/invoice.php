<?php $lang = $lang ?? 'id';

$MESSAGES = [
    'id' => ['Pembayaran diterima — rincian berikut.', 'Pesanan', 'Jumlah'],
    'en' => ['Payment received — invoice below.', 'Order', 'Amount'],
    'zh' => ['已收到付款 — 账单如下。', '订单', '金额'],
];
$m = $MESSAGES[$lang] ?? $MESSAGES['id'];
?>
<p><?= $m[0] ?></p>
<p><strong><?= $m[1] ?>:</strong> <?= htmlspecialchars($tplData['order_id'] ?? '-') ?><br>
<strong><?= $m[2] ?>:</strong> <?= htmlspecialchars($tplData['amount'] ?? '-') ?></p>
