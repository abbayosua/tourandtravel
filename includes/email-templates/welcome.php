<?php $lang = $lang ?? 'id'; ?>
<p><?= $lang === 'en' ? 'Welcome to ' : 'Selamat datang di ' . SITE_NAME . '!' ?></p>
<?php if ($lang === 'en'): ?><p>Welcome to <?= SITE_NAME ?>! Your account is ready.</p><?php endif; ?>
