<p><?= htmlspecialchars($tplData['message'] ?? 'Notifikasi dari ' . SITE_NAME) ?></p>
<?php if (!empty($tplData['cta_link'])): ?>
<p><a href="<?= htmlspecialchars($tplData['cta_link']) ?>" style="background:#0d6efd;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;display:inline-block;"><?= htmlspecialchars($tplData['cta_text'] ?? 'Buka') ?></a></p>
<?php endif; ?>
