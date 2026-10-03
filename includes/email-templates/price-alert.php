<?php $lang = $lang ?? 'id';
/**
 * price-alert.php — email notifikasi harga turun (trilingual).
 * Data: item_title, current_price, target_price, link.
 */
$MESSAGES = [
    'id' => [
        'title' => 'Harga Turun! 🎉',
        'body' => '%s sekarang %s (target Anda: %s).',
        'cta' => 'Lihat Sekarang',
    ],
    'en' => [
        'title' => 'Price Dropped! 🎉',
        'body' => '%s is now %s (your target: %s).',
        'cta' => 'View Now',
    ],
    'zh' => [
        'title' => '价格下降！🎉',
        'body' => '%s 现为 %s（您的目标价：%s）。',
        'cta' => '立即查看',
    ],
];
$m = $MESSAGES[$lang] ?? $MESSAGES['id'];
$itemTitle = $tplData['item_title'] ?? '';
$current = $tplData['current_price'] ?? '';
$target = $tplData['target_price'] ?? '';
?>
<p style="font-size:16px;font-weight:bold;color:#198754;"><?= htmlspecialchars($m['title']) ?></p>
<p><?= htmlspecialchars(sprintf($m['body'], $itemTitle, $current, $target)) ?></p>
<?php if (!empty($tplData['link'])): ?>
<p style="margin-top:16px;"><a href="<?= htmlspecialchars($tplData['link']) ?>" style="display:inline-block;padding:10px 20px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:6px;"><?= htmlspecialchars($m['cta']) ?></a></p>
<?php endif; ?>
