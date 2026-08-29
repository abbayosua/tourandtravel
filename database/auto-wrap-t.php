<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$files = [
    __DIR__ . '/../index.php',
    __DIR__ . '/../tour-detail.php',
    __DIR__ . '/../tours.php',
    __DIR__ . '/../includes/header.php',
    __DIR__ . '/../includes/footer.php',
];

$stmt = db()->prepare("SELECT `key` FROM translations WHERE lang = 'en'");
$stmt->execute();
$keys = $stmt->fetchAll(PDO::FETCH_COLUMN);
usort($keys, fn($a, $b) => strlen($b) - strlen($a));

$replacements = [
    'Cari destinasi atau aktivitas...' => 'placeholder',
    'Cari destinasi...' => 'placeholder',
    'Cari...' => 'placeholder',
    'Bagikan pengalaman Anda...' => 'placeholder',
    '-- Pilih Tanggal --' => 'option_default',
    'Cari' => 'button',
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $original = $content;

    foreach ($keys as $key) {
        if (strlen($key) < 2) continue;

        $escaped = preg_quote($key, '/');

        $content = preg_replace_callback(
            '/(?<=\>)(\s*)' . $escaped . '(\s*)(?=<\/)/',
            function ($m) use ($key) {
                return $m[1] . '<?= t(\'' . addslashes($key) . '\') ?>' . $m[2];
            },
            $content
        );

        $content = preg_replace(
            '/placeholder="' . $escaped . '"/',
            'placeholder="<?= t(\'' . addslashes($key) . '\') ?>"',
            $content
        );

        $content = preg_replace(
            '/(?<=>)' . $escaped . '(?=<\/(?:button|a|span))/',
            '<?= t(\'' . addslashes($key) . '\') ?>',
            $content
        );
    }

    $content = str_replace("<?= t('Beranda') ?>", "<?= t('Beranda') ?>", $content);

    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "✓ Updated: " . basename($file) . "\n";
    } else {
        echo "- No changes: " . basename($file) . "\n";
    }
}

echo "\nDone! Review changes with git diff\n";
