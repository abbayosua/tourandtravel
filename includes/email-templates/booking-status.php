<?php $lang = $lang ?? 'id'; $st = $tplData['status'] ?? 'pending'; ?>
<p><?= $lang === 'en' ? 'Your booking status has been updated.' : 'Status pemesanan Anda telah diperbarui.' ?></p>
<p><strong><?= $lang === 'en' ? 'Booking code' : 'Kode Booking' ?>:</strong> <?= htmlspecialchars($tplData['booking_code'] ?? '-') ?></p>
<p><strong><?= $lang === 'en' ? 'Status' : 'Status' ?>:</strong> <?= htmlspecialchars($st) ?></p>
<p><a href="<?= htmlspecialchars($tplData['track_link'] ?? '') ?>" style="color:#0d6efd;"><?= $lang === 'en' ? 'Track booking' : 'Lacak booking' ?></a></p>
