<?php $lang = $lang ?? 'id'; ?>
<p><?= $lang === 'en' ? 'Payment received — invoice below.' : 'Pembayaran diterima — rincian berikut.' ?></p>
<p><strong><?= $lang === 'en' ? 'Order' : 'Pesanan' ?>:</strong> <?= htmlspecialchars($tplData['order_id'] ?? '-') ?><br>
<strong><?= $lang === 'en' ? 'Amount' : 'Jumlah' ?>:</strong> <?= htmlspecialchars($tplData['amount'] ?? '-') ?></p>
