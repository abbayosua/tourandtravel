<?php $lang = $lang ?? 'id';
$MESSAGES = [
    'id' => ['Notifikasi dari ' . siteName(), 'Buka'],
    'en' => ['Notification from ' . siteName(), 'Open'],
    'zh' => ['来自 ' . siteName() . ' 的通知', '打开'],
];
$m = $MESSAGES[$lang] ?? $MESSAGES['id'];
?>
<p><?= htmlspecialchars($tplData['message'] ?? $m[0]) ?></p>
<?php if (!empty($tplData['cta_link'])): ?>
<p><a href="<?= htmlspecialchars($tplData['cta_link']) ?>" style="background:#0d6efd;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;display:inline-block;"><?= htmlspecialchars($tplData['cta_text'] ?? $m[1]) ?></a></p>
<?php endif; ?>
