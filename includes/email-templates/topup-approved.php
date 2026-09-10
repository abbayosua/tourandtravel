<?php $lang = $lang ?? 'id'; $amount = $tplData['amount'] ?? 'Rp 0'; $note = $tplData['admin_note'] ?? '';

$MESSAGES = [
    'id' => [
        'Topup Anda telah disetujui!',
        'Saldo reseller Anda telah ditambahkan sebesar',
        'Catatan admin',
        'Lihat Dashboard Reseller',
    ],
    'en' => [
        'Your topup has been approved!',
        'Your reseller balance has been credited',
        'Admin note',
        'View Reseller Dashboard',
    ],
    'zh' => [
        '您的充值已获批准！',
        '您的经销商余额已增加',
        '管理员备注',
        '查看经销商仪表板',
    ],
];
$m = $MESSAGES[$lang] ?? $MESSAGES['id'];
?>
<p style="font-size:16px;font-weight:bold;color:#198754;"><?= $m[0] ?></p>
<p><?= $m[1] ?>: <strong><?= htmlspecialchars($amount) ?></strong></p>
<?php if ($note): ?>
<p><small style="color:#6c757d;"><?= $m[2] ?>: <?= htmlspecialchars($note) ?></small></p>
<?php endif; ?>
<p style="margin-top:16px;"><a href="<?= htmlspecialchars($tplData['track_link'] ?? BASE_URL . '/reseller-dashboard.php') ?>" style="display:inline-block;padding:10px 20px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:6px;"><?= $m[3] ?></a></p>
