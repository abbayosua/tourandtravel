<?php $lang = $lang ?? 'id'; ?>
<p><?= $lang === 'en' ? 'Click the button below to set a new password (valid 1 hour).' : 'Klik tombol di bawah untuk mengatur password baru (berlaku 1 jam).' ?></p>
<p><a href="<?= htmlspecialchars($tplData['reset_link'] ?? '#') ?>" style="background:#0d6efd;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;display:inline-block;"><?= $lang === 'en' ? 'Set New Password' : 'Atur Password Baru' ?></a></p>
<p style="color:#9ca3af;font-size:12px;"><?= $lang === 'en' ? 'If you did not request this, ignore this email.' : 'Bila Anda tidak meminta ini, abaikan email ini.' ?></p>
