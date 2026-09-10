<?php $lang = $lang ?? 'id'; $amount = $tplData['amount'] ?? 'Rp 0'; $note = $tplData['admin_note'] ?? '';

$MESSAGES = [
    'id' => [
        'Topup Anda ditolak.',
        'Permintaan topup sebesar',
        'telah ditolak oleh admin.',
        'Alasan penolakan',
        'Ajukan Topup Baru',
    ],
    'en' => [
        'Your topup has been rejected.',
        'Topup request for',
        'has been rejected by admin.',
        'Rejection reason',
        'Submit New Topup',
    ],
    'zh' => [
        '您的充值已被拒绝。',
        '充值申请金额',
        '已被管理员拒绝。',
        '拒绝原因',
        '提交新充值',
    ],
];
$m = $MESSAGES[$lang] ?? $MESSAGES['id'];
?>
<p style="font-size:16px;font-weight:bold;color:#dc3545;"><?= $m[0] ?></p>
<p><?= $m[1] ?> <strong><?= htmlspecialchars($amount) ?></strong> <?= $m[2] ?></p>
<?php if ($note): ?>
<p><strong><?= $m[3] ?>:</strong> <?= htmlspecialchars($note) ?></p>
<?php endif; ?>
<p style="margin-top:16px;"><a href="<?= htmlspecialchars($tplData['track_link'] ?? BASE_URL . '/reseller-topup.php') ?>" style="display:inline-block;padding:10px 20px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:6px;"><?= $m[4] ?></a></p>
