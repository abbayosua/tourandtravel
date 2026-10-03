<?php
/**
 * migrate-translations-i18n-gaps.php
 *
 * Menutup celah terjemahan yang ditemukan oleh audit + regresi e2e
 * tests/e2e/i18n-language-toggle.spec.ts:
 *   - key t() yang belum punya baris en/zh  -> fallback tampil bahasa Indonesia
 *   - key yang nilainya identity (value === key) -> tampil bahasa Indonesia
 *   - konten FAQ (question/answer) belum punya kolom & terjemahan per-bahasa
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE hanya untuk key yang terdaftar.
 * Jalankan: php database/migrate-translations-i18n-gaps.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$en = [
    // Homepage / hero
    'Cari Tour' => 'Search Tours',
    'Total peserta' => 'Total participants',
    'Harga terbaik' => 'Best Price',
    'Harga Terbaik' => 'Best Price',
    'Tour paling populer di kalangan pelanggan' => 'The most popular tours among our customers',
    // Transport search
    'Multi-Kota' => 'Multi-City',
    'trains' => 'trains',
    'Stasiun asal' => 'Origin Station',
    'Stasiun tujuan' => 'Destination Station',
    'Pilih Stasiun' => 'Select Station',
    'Stasiun tidak ditemukan. Coba kata kunci lain.' => 'Station not found. Try another keyword.',
    'Pesan tiket KAI — booking instan, harga terbaik.' => 'Book KAI train tickets — instant booking, best prices.',
    'jadwal ditemukan' => 'schedules found',
    'Segera' => 'Soon',
    'Tidak ada jadwal ditemukan.' => 'No schedules found.',
    'Dari' => 'From',
    'Reset Filter' => 'Reset Filter',
    'Filter' => 'Filter',
    'Best Seller' => 'Best Seller',
    'Simpan ke wishlist' => 'Save to wishlist',
    // Auth
    '— atau —' => '— or —',
    'atau' => 'or',
    'Email' => 'Email',
    'Password' => 'Password',
    'Daftar sebagai Reseller' => 'Register as a Reseller',
    'Beli paket wisata dengan harga reseller dan kelola booking dari satu dashboard.' => 'Buy tour packages at reseller prices and manage bookings from a single dashboard.',
    // FAQ categories
    'Umum' => 'General',
    'Pembatalan & Refund' => 'Cancellation & Refund',
    // Attractions
    'Peta harga atraksi' => 'Attraction price map',
    'Tiket Tempat Wisata' => 'Attraction Tickets',
    // Blog
    'Blog' => 'Blog',
    'Tips' => 'Tips',
    'Panduan' => 'Guides',
    'Tips, panduan, dan cerita perjalanan' => 'Tips, guides, and travel stories',
    'Artikel, tips, dan panduan traveling dari TourAndTravel.' => 'Articles, tips, and travel guides from TourAndTravel.',
    // Refund policy
    'Refund' => 'Refund',
    '0% (non-refundable)' => '0% (non-refundable)',
    // City / category labels (filter dropdowns & cards)
    'Pelabuhan Merak' => 'Merak Port',
    'Juanda (SUB)' => 'Juanda (SUB)',
    'Surabaya Pusat' => 'Central Surabaya',
    'YIA (Yogyakarta)' => 'YIA (Yogyakarta)',
    'Ngurah Rai (DPS)' => 'Ngurah Rai (DPS)',
    'Taman & Hiburan' => 'Parks & Entertainment',
    'Landmark' => 'Landmarks',
    'Taman Air' => 'Water Parks',
    'Aquarium' => 'Aquariums',
    // Missing t() keys reported by scripts/audit-translations.php
    'Berhasil join waitlist! Kami akan memberi tahu Anda jika ada slot tersedia.' => 'Successfully joined the waitlist! We will let you know if a slot becomes available.',
    'Booking berhasil diubah.' => 'Booking updated successfully.',
    'Booking tidak dapat diubah' => 'Booking cannot be changed',
    'Cari stasiun / kota / kode…' => 'Search station / city / code…',
    'Diskon Grup' => 'Group Discount',
    'Email Anda sudah terdaftar di waitlist.' => 'Your email is already on the waitlist.',
    'Gagal mengubah booking' => 'Failed to update booking',
    'Jika jumlah peserta berubah, Anda perlu mengisi ulang data peserta.' => 'If the number of participants changes, you need to re-enter the participant data.',
    'Join Waitlist' => 'Join Waitlist',
    'Jumlah peserta tidak valid' => 'Invalid number of participants',
    'KAI' => 'KAI',
    'KAI Lainnya' => 'Other KAI',
    'KAI Tidak Ditemukan' => 'KAI Not Found',
    'KAI tidak ditemukan' => 'KAI not found',
    'Kalender Ketersediaan' => 'Availability Calendar',
    'Kami akan memberi tahu Anda jika ada slot yang tersedia.' => 'We will let you know if a slot becomes available.',
    'Kartu Kredit' => 'Credit Card',
    'Lihat Tour Lain' => 'See Other Tours',
    'Maks' => 'Max',
    'Nama minimal 3 karakter' => 'Name must be at least 3 characters',
    'Nama tidak boleh mengandung angka' => 'Name must not contain numbers',
    'No. WhatsApp tidak valid (format: 08xxxxxxxxxx)' => 'Invalid WhatsApp number (format: 08xxxxxxxxxx)',
    'Nomor Virtual Account' => 'Virtual Account Number',
    'Pilih tanggal di kalender' => 'Select a date on the calendar',
    'Semua jadwal keberangkatan sudah penuh.' => 'All departure schedules are full.',
    'Slot tidak cukup' => 'Not enough slots',
    'Stasiun keberangkatan' => 'Departure Station',
    'Tanggal keberangkatan sudah lewat, silakan pilih tanggal lain' => 'The departure date has passed, please choose another date',
    'Tanggal tidak valid' => 'Invalid date',
    'Tanpa paspor' => 'No passport',
    'Ubah' => 'Change',
    'Ubah Booking' => 'Modify Booking',
    'Visa / Mastercard / Amex. 3DS bila perlu.' => 'Visa / Mastercard / Amex. 3DS when required.',
    'wajib diupload' => 'must be uploaded',
    'Kota atau bandara' => 'City or airport',
    'Kota atau terminal' => 'City or terminal',
    'Tutup' => 'Close',
];

$zh = [
    // Homepage / hero
    'Cari Tour' => '搜索旅游',
    'Total peserta' => '总人数',
    'Harga terbaik' => '最优惠价格',
    'Harga Terbaik' => '最优惠价格',
    'Tour paling populer di kalangan pelanggan' => '最受客户欢迎的旅游线路',
    // Transport search
    'Multi-Kota' => '多城市',
    'trains' => '火车',
    'Stasiun asal' => '出发站',
    'Stasiun tujuan' => '到达站',
    'Pilih Stasiun' => '选择车站',
    'Stasiun tidak ditemukan. Coba kata kunci lain.' => '未找到车站。请尝试其他关键词。',
    'Pesan tiket KAI — booking instan, harga terbaik.' => '预订 KAI 火车票 — 即时预订，最优价格。',
    'jadwal ditemukan' => '个班次',
    'Segera' => '即将推出',
    'Tidak ada jadwal ditemukan.' => '未找到班次。',
    'Dari' => '从',
    'Reset Filter' => '重置筛选',
    'Filter' => '筛选',
    'Best Seller' => '热销',
    'Simpan ke wishlist' => '加入心愿单',
    // Auth
    '— atau —' => '— 或 —',
    'atau' => '或',
    'Email' => '电子邮件',
    'Password' => '密码',
    'Daftar sebagai Reseller' => '注册成为分销商',
    'Beli paket wisata dengan harga reseller dan kelola booking dari satu dashboard.' => '以分销商价格购买旅游套餐，并在一个后台管理订单。',
    // FAQ categories
    'Umum' => '通用',
    'Pembatalan & Refund' => '取消与退款',
    // Attractions
    'Peta harga atraksi' => '景点价格地图',
    'Tiket Tempat Wisata' => '景点门票',
    // Blog
    'Blog' => '博客',
    'Tips' => '贴士',
    'Panduan' => '攻略',
    'Tips, panduan, dan cerita perjalanan' => '旅行贴士、攻略和故事',
    'Artikel, tips, dan panduan traveling dari TourAndTravel.' => '来自 TourAndTravel 的文章、贴士和旅行指南。',
    // Refund policy
    'Refund' => '退款',
    '0% (non-refundable)' => '0%（不可退款）',
    // City / category labels (filter dropdowns & cards)
    'Pelabuhan Merak' => 'Merak 港',
    'Juanda (SUB)' => 'Juanda (SUB)',
    'Surabaya Pusat' => '泗水市中心',
    'YIA (Yogyakarta)' => 'YIA（日惹）',
    'Ngurah Rai (DPS)' => 'Ngurah Rai (DPS)',
    'Taman & Hiburan' => '公园与娱乐',
    'Landmark' => '地标',
    'Taman Air' => '水上乐园',
    'Aquarium' => '水族馆',
    // Missing t() keys reported by scripts/audit-translations.php
    'Berhasil join waitlist! Kami akan memberi tahu Anda jika ada slot tersedia.' => '已成功加入候补名单！如有空位我们会通知您。',
    'Booking berhasil diubah.' => '预订修改成功。',
    'Booking tidak dapat diubah' => '预订无法修改',
    'Cari stasiun / kota / kode…' => '搜索车站 / 城市 / 代码…',
    'Diskon Grup' => '团体折扣',
    'Email Anda sudah terdaftar di waitlist.' => '您的邮箱已在候补名单中。',
    'Gagal mengubah booking' => '修改预订失败',
    'Jika jumlah peserta berubah, Anda perlu mengisi ulang data peserta.' => '如果参加人数发生变化，您需要重新填写参与者信息。',
    'Join Waitlist' => '加入候补名单',
    'Jumlah peserta tidak valid' => '参加人数无效',
    'KAI' => 'KAI',
    'KAI Lainnya' => '其他 KAI',
    'KAI Tidak Ditemukan' => '未找到 KAI',
    'KAI tidak ditemukan' => '未找到 KAI',
    'Kalender Ketersediaan' => '可用性日历',
    'Kami akan memberi tahu Anda jika ada slot yang tersedia.' => '如有空位我们会通知您。',
    'Kartu Kredit' => '信用卡',
    'Lihat Tour Lain' => '查看其他旅游',
    'Maks' => '最多',
    'Nama minimal 3 karakter' => '姓名至少 3 个字符',
    'Nama tidak boleh mengandung angka' => '姓名不能包含数字',
    'No. WhatsApp tidak valid (format: 08xxxxxxxxxx)' => 'WhatsApp 号码无效（格式：08xxxxxxxxxx）',
    'Nomor Virtual Account' => '虚拟账户号码',
    'Pilih tanggal di kalender' => '在日历上选择日期',
    'Semua jadwal keberangkatan sudah penuh.' => '所有出发班次已满。',
    'Slot tidak cukup' => '名额不足',
    'Stasiun keberangkatan' => '出发站',
    'Tanggal keberangkatan sudah lewat, silakan pilih tanggal lain' => '出发日期已过，请选择其他日期',
    'Tanggal tidak valid' => '日期无效',
    'Tanpa paspor' => '无需护照',
    'Ubah' => '修改',
    'Ubah Booking' => '修改预订',
    'Visa / Mastercard / Amex. 3DS bila perlu.' => 'Visa / Mastercard / Amex，必要时使用 3DS。',
    'wajib diupload' => '必须上传',
    'Kota atau bandara' => '城市或机场',
    'Kota atau terminal' => '城市或码头',
    'Tutup' => '关闭',
    'Hotel' => '酒店',
    // About / terms / privacy / refund content
    ':brand adalah platform pemesanan perjalanan online yang menyediakan paket tour domestik & internasional, hotel, tiket pesawat, ferry, kereta, transfer, atraksi, eSIM, dan rental mobil dalam satu tempat.' =>
        ':brand 是一个在线旅行预订平台，提供国内外旅游套餐、酒店、机票、渡轮、火车、接送、景点门票、eSIM 和租车服务，一站式搞定。',
    'Kami bekerja sama dengan penyedia layanan terpercaya untuk memberikan harga transparan, proses booking yang mudah, dan pembayaran yang aman.' =>
        '我们与值得信赖的服务提供商合作，为您提供透明的价格、便捷的预订流程和安全的支付。',
    'Harga transparan tanpa biaya tersembunyi.' => '价格透明，无隐藏费用。',
    'Didukung payment gateway resmi & terenkripsi.' => '由官方加密支付网关支持。',
    'Tim support siap membantu booking Anda.' => '客服团队随时协助您的预订。',
    ':brand adalah platform pemesanan perjalanan online untuk paket tour, hotel, tiket pesawat, ferry, kereta, transfer, atraksi, eSIM, dan rental mobil. Dengan menggunakan situs ini, Anda menyetujui seluruh ketentuan di halaman ini.' =>
        ':brand 是一个在线旅行预订平台，提供旅游套餐、酒店、机票、渡轮、火车、接送、景点门票、eSIM 和租车服务。使用本网站即表示您同意本页面的全部条款。',
    'Harga yang tertera adalah harga final dalam Rupiah, kecuali dinyatakan lain. Pemesanan bersifat confirmed setelah pembayaran terverifikasi oleh payment gateway (transfer bank, virtual account, gerai retail, QRIS, atau e-wallet).' =>
        '所示价格为印尼盾最终价格，除非另有说明。付款经支付网关（银行转账、虚拟账户、零售网点、QRIS 或电子钱包）核实后，订单方为确认状态。',
    'Batas waktu pembayaran mengikuti ketentuan tiap channel pembayaran. Pesanan yang melewati batas waktu otomatis dibatalkan oleh sistem.' =>
        '付款时限遵循各支付渠道的规定。超过时限的订单将由系统自动取消。',
    'Pelanggan wajib memberikan data yang benar (nama, kontak, tanggal perjalanan, jumlah peserta). Kesalahan data yang menyebabkan kegagalan layanan menjadi tanggung jawab pelanggan.' =>
        '客户必须提供正确的数据（姓名、联系方式、出行日期、参加人数）。因数据错误导致服务失败的责任由客户承担。',
    'Jadwal, maskapai, operator ferry/kereta, atau itinerary dapat berubah karena kondisi operasional atau force majeure. Bila layanan dibatalkan oleh penyedia, pelanggan berhak atas penjadwalan ulang atau refund sesuai Kebijakan Refund.' =>
        '航班时刻、航空公司、渡轮/火车运营商或行程可能因运营状况或不可抗力而变更。若服务由提供商取消，客户有权根据退款政策获得改期或退款。',
    ':brand bertindak sebagai perantara pemesanan antara pelanggan dan penyedia layanan. Tanggung jawab atas pelaksanaan layanan (penerbangan, menginap, tour) berada pada masing-masing penyedia, sesuai syarat mereka.' =>
        ':brand 作为客户与服务提供商之间的预订中介。服务（航班、住宿、旅游）的履行责任由各提供商按其条款承担。',
    'Pertanyaan terkait ketentuan ini dapat disampaikan melalui:' => '如对本条款有疑问，可通过以下方式联系我们：',
    ':brand mengumpulkan data yang Anda berikan saat mendaftar dan memesan: nama, email, nomor telepon, detail perjalanan, dan riwayat transaksi. Kami juga mencatat data teknis dasar (perangkat, browser) untuk keamanan.' =>
        ':brand 收集您在注册和预订时提供的数据：姓名、电子邮件、电话号码、行程详情和交易记录。出于安全考虑，我们还会记录基本的技术数据（设备、浏览器）。',
    'Data digunakan untuk memproses pemesanan, verifikasi pembayaran melalui payment gateway resmi, mengirim e-tiket/notifikasi, dukungan pelanggan, dan peningkatan layanan. Kami tidak menjual data pribadi Anda.' =>
        '数据用于处理订单、通过官方支付网关核实付款、发送电子票/通知、客户支持和改进服务。我们不会出售您的个人数据。',
    'Data dibagikan secara terbatas kepada pihak yang diperlukan untuk memenuhi pesanan: penyedia layanan (maskapai, hotel, operator tour), payment gateway, dan penyedia pengiriman notifikasi — hanya sebatas yang dibutuhkan.' =>
        '数据仅在完成订单所必需的范围内有限共享给相关方：服务提供商（航空公司、酒店、旅游运营商）、支付网关和通知发送服务商。',
    'Data pembayaran diproses langsung oleh payment gateway bersertifikat; kami tidak menyimpan nomor kartu. Akses data internal dibatasi dan dilindungi. Anda dapat meminta perbaikan atau penghapusan data melalui kontak di bawah.' =>
        '支付数据由持牌支付网关直接处理；我们不存储卡号。内部数据访问受到限制和保护。您可通过下方联系方式请求更正或删除数据。',
    'Pengajuan refund hanya berlaku untuk booking berstatus confirmed yang belum digunakan. Dana refund yang disetujui dikreditkan ke saldo wallet (KlookCash) akun Anda.' =>
        '退款申请仅适用于尚未使用且状态为已确认的订单。获批的退款将存入您账户的钱包余额（KlookCash）。',
    'Sebagian produk memiliki kebijakan khusus: full_refund (selalu 100%) atau non_refundable (selalu 0%), tercantum di halaman detail produk. Tiket pesawat, hotel, ferry, dan kereta mengikuti kebijakan maskapai/penyedia masing-masing.' =>
        '部分产品有特殊政策：full_refund（始终 100%）或 non_refundable（始终 0%），已标注在产品详情页。机票、酒店、渡轮和火车遵循各航空公司/提供商的政策。',
    'Buka halaman Booking Saya, pilih booking confirmed, klik Minta Refund, dan isi alasan. Tim kami akan meninjau pengajuan maksimal 3x24 jam hari kerja. Status pengajuan (requested / approved / rejected) dapat dipantau di halaman yang sama.' =>
        '打开“我的预订”页面，选择已确认的订单，点击“申请退款”并填写原因。我们将在最多 3×24 个工作小时内审核。申请状态（requested / approved / rejected）可在同一页面查看。',
];

$stored = 0;
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
foreach ([['en', $en], ['zh', $zh]] as [$lang, $dict]) {
    foreach ($dict as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $stored++;
    }
}
echo "Upserted $stored UI translation rows.\n";

// ---- FAQ content: kolom per-bahasa + terjemahan ----
foreach (['question_en', 'question_zh', 'answer_en', 'answer_zh'] as $col) {
    try {
        db()->exec("ALTER TABLE faq_items ADD COLUMN `$col` TEXT NULL");
        echo "Added column faq_items.$col\n";
    } catch (Throwable $e) {
        // sudah ada
    }
}

$faqEn = [
    1 => ['How do I book a tour?', 'Choose the tour you want, set the date and number of participants, then fill in the booking form. After payment, you will receive confirmation via email and WhatsApp.'],
    2 => ['Do I need an account to book?', 'Not required. You can book as a guest, but we recommend creating an account so you can track bookings and earn KlookCash.'],
    3 => ['What payment methods are available?', 'We accept bank transfer, virtual account, QRIS, and on-site payment for selected products.'],
    4 => ['Can I pay on site?', 'For selected products labeled "Pay on Site", you can pay directly at the venue.'],
    5 => ['What is the cancellation policy?', 'Products labeled "Free Cancellation" can be cancelled free of charge before D-1. Other products follow the provider policy.'],
    6 => ['How long does a refund take?', 'Refunds are processed within 3-7 business days after the cancellation is approved, back to the original payment method.'],
    7 => ['What is KlookCash?', 'KlookCash is reward balance you earn at 5% of every booking. It can be used to offset your next transaction.'],
    8 => ['How do I use KlookCash?', 'Tick the "Use KlookCash" option on the booking form and your balance will automatically reduce the total payment.'],
];
$faqZh = [
    1 => ['如何预订旅游？', '选择您想要的旅游线路，确定日期和参加人数，然后填写预订表单。付款后，您将通过电子邮件和 WhatsApp 收到确认。'],
    2 => ['预订需要账户吗？', '不强制。您可以以访客身份预订，但我们建议创建账户，以便跟踪订单并获得 KlookCash。'],
    3 => ['有哪些付款方式？', '我们接受银行转账、虚拟账户、QRIS，部分产品支持现场付款。'],
    4 => ['可以现场付款吗？', '对于标注“现场付款”的部分产品，您可以直接在场地付款。'],
    5 => ['取消政策是怎样的？', '标注“免费取消”的产品可在出发前一天（D-1）前免费取消。其他产品遵循提供商政策。'],
    6 => ['退款需要多长时间？', '取消申请获批后，退款将在 3-7 个工作日内按原支付方式退回。'],
    7 => ['什么是 KlookCash？', 'KlookCash 是您每笔订单可获得 5% 的奖励余额，可用于抵扣下一笔交易的付款。'],
    8 => ['如何使用 KlookCash？', '在预订表单中勾选“使用 KlookCash”，您的余额将自动抵扣总付款额。'],
];

$faqStmt = db()->prepare("UPDATE faq_items SET question_en = ?, answer_en = ?, question_zh = ?, answer_zh = ? WHERE id = ?");
foreach ($faqEn as $id => [$q, $a]) {
    [$qZh, $aZh] = $faqZh[$id];
    $faqStmt->execute([$q, $a, $qZh, $aZh, $id]);
}
echo "Updated " . count($faqEn) . " FAQ items with en/zh content.\n";

// ---- Kolom konten per-bahasa yang belum ada (transfers, collections) ----
foreach (['transfers' => ['name_zh', 'description_zh'], 'collections' => ['name_zh', 'description_zh'], 'attractions' => ['name_zh', 'description_zh']] as $table => $cols) {
    foreach ($cols as $col) {
        try {
            db()->exec("ALTER TABLE `$table` ADD COLUMN `$col` TEXT NULL");
            echo "Added column $table.$col\n";
        } catch (Throwable $e) {
            // sudah ada
        }
    }
}

// ---- Terjemahan konten: posts (artikel blog) ----
$postZh = [
    1 => [
        'title' => '如何选择家庭旅游套餐',
        'excerpt' => '挑选适合家庭的旅游套餐简要指南。',
        'body' => '预订前需要注意几点：行程时长、儿童活动安排、靠近景点中心的酒店，以及中文/印尼语导游。同时确认取消政策是否灵活。',
    ],
    2 => [
        'title' => '2026 亚洲热门目的地',
        'excerpt' => '今年预订量最高的亚洲城市。',
        'body' => '东京、首尔和曼谷依然热门。想体验新鲜感，可以尝试台湾或越南，费用更实惠。',
    ],
    3 => [
        'title' => '第一次去日本旅行指南',
        'excerpt' => '首次赴日旅行的必备准备。',
        'body' => '签证、JR Pass、eSIM、乘车礼仪和换汇技巧。起飞前您需要了解的一切。',
    ],
];
$postStmt = db()->prepare("UPDATE posts SET title_zh = ?, excerpt_zh = ?, body_zh = ? WHERE id = ?");
foreach ($postZh as $id => $t) {
    $postStmt->execute([$t['title'], $t['excerpt'], $t['body'], $id]);
}
echo "Updated " . count($postZh) . " posts with zh content.\n";

// ---- Terjemahan konten: attractions ----
$attractionZh = [
    1 => ['Taman Mini Indonesia Indah 门票', '凭日票在 TMII 探索印尼文化。'],
    2 => ['Monas 观景台门票', '从国家纪念碑顶端欣赏雅加达美景。'],
    3 => ['Waterbom 巴厘岛一日通票', '亚洲顶级水上乐园，拥有 20 多条滑道，全天畅玩。'],
    4 => ['婆罗浮屠日出门票', '在世界最大的佛教寺庙观赏日出。'],
    5 => ['雅加达水族馆门票', '设有海底隧道和动物表演的海洋馆。'],
];
$attStmt = db()->prepare("UPDATE attractions SET name_zh = ?, description_zh = ? WHERE id = ?");
foreach ($attractionZh as $id => [$n, $d]) {
    $attStmt->execute([$n, $d, $id]);
}
echo "Updated " . count($attractionZh) . " attractions with zh content.\n";

// ---- Terjemahan konten: transfers ----
$transferEn = [
    1 => 'Soekarno-Hatta Airport to Jakarta City',
    2 => 'Ngurah Rai Airport to Kuta & Seminyak',
    3 => 'YIA Airport to Malioboro',
    4 => 'Merak Port to Soekarno-Hatta Airport',
    5 => 'Juanda Airport to Central Surabaya',
];
$transferZh = [
    1 => ['苏加诺-哈达机场至雅加达市区', '雅加达机场私人接送。'],
    2 => ['伍拉·赖机场至库塔 & 水明漾', '巴厘岛机场接送，前往库塔或水明漾地区。'],
    3 => ['YIA 机场至马里奥波罗', '从日惹机场前往马里奥波罗地区。'],
    4 => ['Merak 港至苏加诺-哈达机场', '从 Merak 港前往机场的接送服务。'],
    5 => ['Juanda 机场至泗水市中心', '从 Juanda 机场前往泗水市中心的接送。'],
];
$trStmt = db()->prepare("UPDATE transfers SET name_en = ?, name_zh = ?, description_zh = ? WHERE id = ?");
foreach ($transferZh as $id => [$n, $d]) {
    $trStmt->execute([$transferEn[$id], $n, $d, $id]);
}
echo "Updated " . count($transferZh) . " transfers with en/zh content.\n";

// ---- Terjemahan konten: collections ----
db()->exec("UPDATE collections SET name_en = 'Best Seller', description_en = 'The most popular tours among our customers', name_zh = '热销', description_zh = '最受客户欢迎的旅游线路' WHERE id = 1");
echo "Updated collections content (en/zh).\n";
