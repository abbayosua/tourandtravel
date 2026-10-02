<?php
// Seed terjemahan key multi-peserta (idempotent, INSERT IGNORE).
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$rows = [
    'wajib diisi' => ['is required', '必填'],
    'Foto paspor peserta' => ['Passport photo for participant', '参与者护照照片'],
    'Saya ikut tour ini' => ['I am joining this tour', '我参加此行程'],
    'Nama Anda otomatis jadi peserta pertama.' => ['Your name becomes the first participant automatically.', '您的姓名将自动成为第一位参与者。'],
    'Isi Data Peserta & Paspor' => ['Enter Participant Data & Passports', '填写参与者信息与护照'],
    'Lengkapi nama & foto paspor setiap peserta.' => ['Complete each participant name & passport photo.', '请填写每位参与者的姓名与护照照片。'],
    'Data Peserta & Paspor' => ['Participant Data & Passports', '参与者信息与护照'],
    'Data Peserta' => ['Participant Data', '参与者信息'],
    'Isi nama lengkap (sesuai paspor) dan unggah foto paspor untuk setiap peserta.' => ['Enter full name (as printed on passport) and upload a passport photo for every participant.', '请填写每位参与者的全名（与护照一致）并上传护照照片。'],
    'Selesai' => ['Done', '完成'],
    'Pemesan' => ['Booker', '预订人'],
    'Nama lengkap sesuai paspor' => ['Full name as printed on passport', '与护照一致的全名'],
    'Mengunggah...' => ['Uploading...', '上传中...'],
    'Terunggah' => ['Uploaded', '已上传'],
    'Gagal mengunggah' => ['Upload failed', '上传失败'],
];

$stmt = db()->prepare("INSERT IGNORE INTO translations (`key`, lang, value) VALUES (?, ?, ?)");
$n = 0;
foreach ($rows as $key => [$en, $zh]) {
    $stmt->execute([$key, 'en', $en]); $n += $stmt->rowCount();
    $stmt->execute([$key, 'zh', $zh]); $n += $stmt->rowCount();
}
echo "Seeded $n translation rows.\n";
