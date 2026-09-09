<?php $lang = $lang ?? 'id';

$MESSAGES = [
    'id' => [
        'Klik tombol di bawah untuk mengatur password baru (berlaku 1 jam).',
        'Atur Password Baru',
        'Bila Anda tidak meminta ini, abaikan email ini.',
    ],
    'en' => [
        'Click the button below to set a new password (valid 1 hour).',
        'Set New Password',
        'If you did not request this, ignore this email.',
    ],
    'zh' => [
        '点击下方按钮设置新密码（1 小时内有效）。',
        '设置新密码',
        '若非您本人操作，请忽略此邮件。',
    ],
];
$m = $MESSAGES[$lang] ?? $MESSAGES['id'];
?>
<p><?= $m[0] ?></p>
<p><a href="<?= htmlspecialchars($tplData['reset_link'] ?? '#') ?>" style="background:#0d6efd;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;display:inline-block;"><?= $m[1] ?></a></p>
<p style="color:#9ca3af;font-size:12px;"><?= $m[2] ?></p>
