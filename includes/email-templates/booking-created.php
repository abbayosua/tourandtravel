<?php $lang = $lang ?? 'id'; ?>
<p><?= $lang === 'en' ? 'Thank you! Your booking has been received.' : 'Terima kasih! Pemesanan Anda telah kami terima.' ?></p>
<p><strong><?= $lang === 'en' ? 'Booking code' : 'Kode Booking' ?>:</strong> <?= htmlspecialchars($tplData['booking_code'] ?? '-') ?></p>
<p><strong><?= $lang === 'en' ? 'Total' : 'Total' ?>:</strong> <?= htmlspecialchars($tplData['total'] ?? '-') ?></p>
<?php if (!empty($tplData['pay_link'])): ?>
<p><a href="<?= htmlspecialchars($tplData['pay_link']) ?>" style="background:#198754;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;display:inline-block;"><?= $lang === 'en' ? 'Pay Now' : 'Bayar Sekarang' ?></a></p>
<?php endif; ?>
