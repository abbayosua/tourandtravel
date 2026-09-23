<?php $lang = $lang ?? 'id';

$MESSAGES = [
    'id' => 'Selamat datang di ' . siteName() . '!',
    'en' => 'Welcome to ' . siteName() . '!',
    'zh' => '欢迎来到 ' . siteName() . '！您的账户已就绪。',
];
?>
<p><?= $MESSAGES[$lang] ?? $MESSAGES['id'] ?></p>
