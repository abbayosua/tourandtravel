<?php $lang = $lang ?? 'id';

$messages = [
    'id' => [
        'thank_you' => 'Terima kasih! Pemesanan Anda telah kami terima.',
        'booking_code' => 'Kode Booking',
        'tour' => 'Paket Tour',
        'departure' => 'Tanggal Keberangkatan',
        'participants' => 'Jumlah Peserta',
        'meeting_point' => 'Titik Kumpul',
        'total' => 'Total',
        'pay_now' => 'Bayar Sekarang',
        'track' => 'Lacak Booking',
        'includes' => 'Paket Termasuk',
        'notes' => 'Catatan',
    ],
    'en' => [
        'thank_you' => 'Thank you! Your booking has been received.',
        'booking_code' => 'Booking Code',
        'tour' => 'Tour Package',
        'departure' => 'Departure Date',
        'participants' => 'Participants',
        'meeting_point' => 'Meeting Point',
        'total' => 'Total',
        'pay_now' => 'Pay Now',
        'track' => 'Track Booking',
        'includes' => 'Package Includes',
        'notes' => 'Notes',
    ],
    'zh' => [
        'thank_you' => '谢谢！我们已收到您的预订。',
        'booking_code' => '预订编号',
        'tour' => '旅游套餐',
        'departure' => '出发日期',
        'participants' => '参与人数',
        'meeting_point' => '集合地点',
        'total' => '总计',
        'pay_now' => '立即支付',
        'track' => '追踪预订',
        'includes' => '套餐包含',
        'notes' => '备注',
    ],
];
$m = $messages[$lang] ?? $messages['id'];
?>
<p><?= $m['thank_you'] ?></p>
<table style="width:100%;border-collapse:collapse;margin:16px 0;">
    <tr>
        <td style="padding:8px 12px;background:#f8f9fa;border:1px solid #dee2e6;font-weight:bold;width:40%;"><?= $m['booking_code'] ?></td>
        <td style="padding:8px 12px;border:1px solid #dee2e6;"><?= htmlspecialchars($tplData['booking_code'] ?? '-') ?></td>
    </tr>
    <?php if (!empty($tplData['tour_title'])): ?>
    <tr>
        <td style="padding:8px 12px;background:#f8f9fa;border:1px solid #dee2e6;font-weight:bold;"><?= $m['tour'] ?></td>
        <td style="padding:8px 12px;border:1px solid #dee2e6;"><?= htmlspecialchars($tplData['tour_title']) ?></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($tplData['departure_date'])): ?>
    <tr>
        <td style="padding:8px 12px;background:#f8f9fa;border:1px solid #dee2e6;font-weight:bold;"><?= $m['departure'] ?></td>
        <td style="padding:8px 12px;border:1px solid #dee2e6;"><?= htmlspecialchars($tplData['departure_date']) ?></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($tplData['participants'])): ?>
    <tr>
        <td style="padding:8px 12px;background:#f8f9fa;border:1px solid #dee2e6;font-weight:bold;"><?= $m['participants'] ?></td>
        <td style="padding:8px 12px;border:1px solid #dee2e6;"><?= (int)$tplData['participants'] ?></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($tplData['meeting_point'])): ?>
    <tr>
        <td style="padding:8px 12px;background:#f8f9fa;border:1px solid #dee2e6;font-weight:bold;"><?= $m['meeting_point'] ?></td>
        <td style="padding:8px 12px;border:1px solid #dee2e6;"><?= nl2br(htmlspecialchars($tplData['meeting_point'])) ?></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($tplData['includes'])): ?>
    <tr>
        <td style="padding:8px 12px;background:#f8f9fa;border:1px solid #dee2e6;font-weight:bold;"><?= $m['includes'] ?></td>
        <td style="padding:8px 12px;border:1px solid #dee2e6;"><?= nl2br(htmlspecialchars($tplData['includes'])) ?></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($tplData['notes'])): ?>
    <tr>
        <td style="padding:8px 12px;background:#f8f9fa;border:1px solid #dee2e6;font-weight:bold;"><?= $m['notes'] ?></td>
        <td style="padding:8px 12px;border:1px solid #dee2e6;"><?= nl2br(htmlspecialchars($tplData['notes'])) ?></td>
    </tr>
    <?php endif; ?>
    <tr style="background:#e7f3ff;">
        <td style="padding:8px 12px;border:1px solid #dee2e6;font-weight:bold;"><?= $m['total'] ?></td>
        <td style="padding:8px 12px;border:1px solid #dee2e6;font-weight:bold;color:#0d6efd;"><?= htmlspecialchars($tplData['total'] ?? '-') ?></td>
    </tr>
</table>
<?php if (!empty($tplData['pay_link'])): ?>
<p><a href="<?= htmlspecialchars($tplData['pay_link']) ?>" style="background:#198754;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;display:inline-block;"><?= $m['pay_now'] ?></a></p>
<?php endif; ?>
<?php if (!empty($tplData['track_link'])): ?>
<p><a href="<?= htmlspecialchars($tplData['track_link']) ?>" style="background:#6c757d;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;display:inline-block;"><?= $m['track'] ?></a></p>
<?php endif; ?>
