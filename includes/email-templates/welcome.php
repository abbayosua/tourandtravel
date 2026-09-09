<?php $lang = $lang ?? 'id';

$MESSAGES = [
    'id' => 'Selamat datang di ' . SITE_NAME . '!',
    'en' => 'Welcome to ' . SITE_NAME . '!',
    'zh' => '欢迎来到 ' . SITE_NAME . '！您的账户已就绪。',
];
?>
<p><?= $MESSAGES[$lang] ?? $MESSAGES['id'] ?></p>
