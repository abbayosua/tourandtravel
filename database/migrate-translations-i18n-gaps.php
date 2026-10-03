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
    // Detail pages: price alert widget + flight schedule + hotel reviews
    'Cek Harga per Tanggal' => 'Check Price by Date',
    'Harga Target' => 'Target Price',
    'Harga Target per Malam' => 'Target Price per Night',
    'Harga saat ini' => 'Current price',
    'Jadwal Penerbangan' => 'Flight Schedule',
    'Login untuk Set Price Alert' => 'Log in to Set a Price Alert',
    'Kami akan memberi tahu Anda jika harga turun ke target.' => 'We will notify you when the price drops to your target.',
    'Masukkan harga target' => 'Enter target price',
    'Belum ada ulasan untuk hotel ini.' => 'No reviews for this hotel yet.',
    '/malam · harga live dari' => '/night · live price from',
    'Simpan Alert' => 'Save Alert',
    'Nama itinerary, mis: Trip Bali 3 Hari' => 'Itinerary name, e.g. Bali Trip 3 Days',
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
    // Detail pages: price alert widget + flight schedule + hotel reviews
    'Cek Harga per Tanggal' => '按日期查价',
    'Harga Target' => '目标价',
    'Harga Target per Malam' => '每晚目标价',
    'Harga saat ini' => '当前价格',
    'Jadwal Penerbangan' => '航班时刻',
    'Login untuk Set Price Alert' => '登录后设置降价提醒',
    'Kami akan memberi tahu Anda jika harga turun ke target.' => '价格降到目标价时我们会通知您。',
    'Masukkan harga target' => '输入目标价',
    'Belum ada ulasan untuk hotel ini.' => '此酒店暂无评价。',
    '/malam · harga live dari' => '/晚 · 实时价格起',
    'Simpan Alert' => '保存提醒',
    'Nama itinerary, mis: Trip Bali 3 Hari' => '行程名称，例如：巴厘岛 3 日游',
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
    'Pengajuan refund hanya berlaku untuk booking berstatus confirmed yang belum digunakan. Dana refund yang disetujui dikreditkan ke saldo wallet (TravelPoints) akun Anda.' =>
        '退款申请仅适用于尚未使用且状态为已确认的订单。获批的退款将存入您账户的钱包余额（TravelPoints）。',
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
    2 => ['Do I need an account to book?', 'Not required. You can book as a guest, but we recommend creating an account so you can track bookings and earn TravelPoints.'],
    3 => ['What payment methods are available?', 'We accept bank transfer, virtual account, QRIS, and on-site payment for selected products.'],
    4 => ['Can I pay on site?', 'For selected products labeled "Pay on Site", you can pay directly at the venue.'],
    5 => ['What is the cancellation policy?', 'Products labeled "Free Cancellation" can be cancelled free of charge before D-1. Other products follow the provider policy.'],
    6 => ['How long does a refund take?', 'Refunds are processed within 3-7 business days after the cancellation is approved, back to the original payment method.'],
    7 => ['What is TravelPoints?', 'TravelPoints is reward balance you earn at 5% of every booking. It can be used to offset your next transaction.'],
    8 => ['How do I use TravelPoints?', 'Tick the "Use TravelPoints" option on the booking form and your balance will automatically reduce the total payment.'],
];
$faqZh = [
    1 => ['如何预订旅游？', '选择您想要的旅游线路，确定日期和参加人数，然后填写预订表单。付款后，您将通过电子邮件和 WhatsApp 收到确认。'],
    2 => ['预订需要账户吗？', '不强制。您可以以访客身份预订，但我们建议创建账户，以便跟踪订单并获得 TravelPoints。'],
    3 => ['有哪些付款方式？', '我们接受银行转账、虚拟账户、QRIS，部分产品支持现场付款。'],
    4 => ['可以现场付款吗？', '对于标注“现场付款”的部分产品，您可以直接在场地付款。'],
    5 => ['取消政策是怎样的？', '标注“免费取消”的产品可在出发前一天（D-1）前免费取消。其他产品遵循提供商政策。'],
    6 => ['退款需要多长时间？', '取消申请获批后，退款将在 3-7 个工作日内按原支付方式退回。'],
    7 => ['什么是 TravelPoints？', 'TravelPoints 是您每笔订单可获得 5% 的奖励余额，可用于抵扣下一笔交易的付款。'],
    8 => ['如何使用 TravelPoints？', '在预订表单中勾选“使用 TravelPoints”，您的余额将自动抵扣总付款额。'],
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
$transferDescEn = [
    1 => 'Private airport transfer to central Jakarta.',
    2 => 'Bali airport pickup to the Kuta or Seminyak area.',
    3 => 'Transfer from Yogyakarta International Airport to the Malioboro area.',
    4 => 'Shuttle between Merak Port and the airport.',
    5 => 'Transfer from Juanda Airport to central Surabaya.',
];
$transferZh = [
    1 => ['苏加诺-哈达机场至雅加达市区', '雅加达机场私人接送。'],
    2 => ['伍拉·赖机场至库塔 & 水明漾', '巴厘岛机场接送，前往库塔或水明漾地区。'],
    3 => ['YIA 机场至马里奥波罗', '从日惹机场前往马里奥波罗地区。'],
    4 => ['Merak 港至苏加诺-哈达机场', '从 Merak 港前往机场的接送服务。'],
    5 => ['Juanda 机场至泗水市中心', '从 Juanda 机场前往泗水市中心的接送。'],
];
$trStmt = db()->prepare("UPDATE transfers SET name_en = ?, description_en = ?, name_zh = ?, description_zh = ? WHERE id = ?");
foreach ($transferZh as $id => [$n, $d]) {
    $trStmt->execute([$transferEn[$id], $transferDescEn[$id], $n, $d, $id]);
}
echo "Updated " . count($transferZh) . " transfers with en/zh content.\n";

// ---- Terjemahan konten: collections ----
db()->exec("UPDATE collections SET name_en = 'Best Seller', description_en = 'The most popular tours among our customers', name_zh = '热销', description_zh = '最受客户欢迎的旅游线路' WHERE id = 1");
echo "Updated collections content (en/zh).\n";

// ---- Terjemahan konten: tours (meeting_point / important_notes / flight_info) ----
$tourContent = [
    63 => [
        'meeting_point_en' => 'Soekarno-Hatta International Airport, Terminal 3 (3 hours before departure)',
        'meeting_point_zh' => '苏加诺-哈达国际机场 3 号航站楼（起飞前 3 小时）',
        'important_notes_en' => "Prices may change at any time following exchange rates & fuel surcharge\nDeposit of Rp 3,000,000/pax at registration, balance due D-21\nFlight schedules may change per airline policy\nHotels may be substituted with equivalent hotels depending on conditions",
        'important_notes_zh' => "价格可能随汇率和燃油附加费随时变动\n报名时支付每位 Rp 3,000,000 定金，出发前 21 天付清余款\n航班时刻可能依航空公司政策变动\n酒店可能视情况以同级酒店替换",
        'flight_info_en' => "CGK - PVG (transit, full service, 25KG baggage)\nPVG - CGK (transit, full service, 25KG baggage)",
        'flight_info_zh' => "CGK - PVG（转机，全服务，25KG 行李）\nPVG - CGK（转机，全服务，25KG 行李）",
    ],
    131 => [
        'meeting_point_en' => 'Terminal 3 Soekarno-Hatta Airport, 3 hours before departure',
        'meeting_point_zh' => '苏加诺-哈达机场 3 号航站楼，起飞前 3 小时',
        'important_notes_en' => "Passport valid for at least 7 months before departure\n50% deposit at registration, balance due D-21\nParticipants under 18 must be accompanied by a parent\nSchedules may change to suit field conditions\nPrice may change if the USD rate exceeds Rp 16,500",
        'important_notes_zh' => "护照须在出发前至少 7 个月内有效\n报名时支付 50% 定金，出发前 21 天付清余款\n18 岁以下参与者须由父母陪同\n行程可能视实际情况调整\n若美元汇率超过 Rp 16,500，价格可能变动",
        'flight_info_en' => "GA 890 Jakarta (CGK) 23:15 - Beijing (PEK) 06:10+1\nGA 891 Beijing (PEK) 11:40 - Jakarta (CGK) 16:55",
        'flight_info_zh' => "GA 890 雅加达 (CGK) 23:15 - 北京 (PEK) 06:10+1\nGA 891 北京 (PEK) 11:40 - 雅加达 (CGK) 16:55",
    ],
];
$tourStmt = db()->prepare(
    "UPDATE tours SET meeting_point_en = ?, meeting_point_zh = ?, important_notes_en = ?, important_notes_zh = ?, flight_info_en = ?, flight_info_zh = ? WHERE id = ?"
);
foreach ($tourContent as $id => $c) {
    $tourStmt->execute([
        $c['meeting_point_en'], $c['meeting_point_zh'],
        $c['important_notes_en'], $c['important_notes_zh'],
        $c['flight_info_en'], $c['flight_info_zh'], $id,
    ]);
}
echo "Updated " . count($tourContent) . " tours with en/zh content.\n";

// ---- Halaman akun terautentikasi (poin, refund, itinerary, price alert, profil penumpang) ----
$authExtra = [
    'en' => [
        'Poin Saya' => 'My Points',
        'Tidak disebutkan' => 'Not specified',
        'Pengajuan refund diterima' => 'Refund request received',
        'Booking sudah dibayar tidak dapat dibatalkan sendiri. Silakan ajukan refund.' => 'Paid bookings cannot be cancelled by yourself. Please submit a refund request.',
        'Termasuk asuransi perjalanan' => 'Includes travel insurance',
        'Minta Refund' => 'Request Refund',
        'Status Refund' => 'Refund Status',
        'Diajukan' => 'Submitted',
        'Refund dihitung otomatis: 100% (≥H-8), 50% (H-4–H-7), 0% (<H-3) sesuai kebijakan produk.' => 'Refunds are calculated automatically: 100% (≥D-8), 50% (D-4–D-7), 0% (<D-3) per product policy.',
        'Alasan Refund' => 'Refund Reason',
        'Contoh: Perubahan jadwal perjalanan' => 'Example: Travel schedule change',
        'Ajukan Refund' => 'Submit Refund',
        'Tanggal Keberangkatan' => 'Departure Date',
        'Minimal penukaran 100 points' => 'Minimum redemption 100 points',
        'Saldo points tidak cukup' => 'Insufficient points balance',
        'Berhasil menukar :p points menjadi :a' => 'Successfully redeemed :p points for :a',
        'Saldo Points' => 'Points Balance',
        '1 point = Rp 100 · diperoleh dari booking yang dibayar' => '1 point = Rp 100 · earned from paid bookings',
        'Tukar points' => 'Redeem points',
        'Tukar' => 'Redeem',
        'Riwayat Points' => 'Points History',
        'Belum ada mutasi points' => 'No points transactions yet',
        'Poin & Loyalitas' => 'Points & Loyalty',
        'Tier Anda' => 'Your Tier',
        'booking selesai' => 'bookings completed',
        'booking lagi untuk' => 'more bookings for',
        'Tier maksimum tercapai!' => 'Maximum tier reached!',
        'poin tersedia' => 'points available',
        'Riwayat Poin' => 'Points History',
        'Keterangan' => 'Description',
        'Earn dari booking' => 'Earn from bookings',
        'Redeem poin' => 'Redeem points',
        'Refund poin' => 'Refund points',
        'Penyesuaian' => 'Adjustment',
        'Belum ada riwayat poin.' => 'No points history yet.',
        'Poin akan ditambahkan setelah booking selesai.' => 'Points will be added after the booking is completed.',
        'Total Reward' => 'Total Reward',
        'Top Referrer' => 'Top Referrer',
        'Min' => 'Min',
        'Itinerary Saya' => 'My Itinerary',
        'Rencana perjalanan yang kamu simpan.' => 'Travel plans you saved.',
        'Belum ada itinerary.' => 'No itineraries yet.',
        'Tanggal belum diset' => 'Date not set',
        'Buka' => 'Open',
        'Buka halaman checkout' => 'Open checkout page',
        'Price Alert Saya' => 'My Price Alerts',
        'Terakhir Notif' => 'Last Notif',
        'Belum ada price alert.' => 'No price alerts yet.',
        'Klik tombol "Set Price Alert" pada detail tour atau hotel untuk membuat alert.' => 'Click the "Set Price Alert" button on a tour or hotel detail page to create an alert.',
        'Profil Penumpang' => 'Passenger Profiles',
        'Simpan data penumpang untuk checkout lebih cepat.' => 'Save passenger data for faster checkout.',
        'Profil tersimpan' => 'Profile saved',
        'Profil dihapus' => 'Profile deleted',
        'Default diperbarui' => 'Default updated',
        'Edit Profil' => 'Edit Profile',
        'Tambah Profil Baru' => 'Add New Profile',
        'Nomor Paspor' => 'Passport Number',
        'Kewarganegaraan' => 'Nationality',
        'Tanggal Lahir' => 'Date of Birth',
        'Jadikan default' => 'Set as default',
        'Hapus profil ini?' => 'Delete this profile?',
        'Belum ada profil penumpang.' => 'No passenger profiles yet.',
        'Tambahkan profil di sisi kiri untuk checkout lebih cepat.' => 'Add a profile on the left for faster checkout.',
        'Scan voucher' => 'Scan voucher',
        'Tracking' => 'Tracking',
        'Buka halaman Booking Saya, pilih booking confirmed, klik Minta Refund, dan isi alasan. Tim kami akan meninjau pengajuan maksimal 3x24 jam hari kerja. Status pengajuan (requested / approved / rejected) dapat dipantau di halaman yang sama.' => 'Open My Bookings, pick a confirmed booking, click Request Refund, and fill in the reason. Our team reviews within 3x24 business hours. Track the status (requested / approved / rejected) on the same page.',
        'Pengajuan refund hanya berlaku untuk booking berstatus confirmed yang belum digunakan. Dana refund yang disetujui dikreditkan ke saldo wallet (TravelPoints) akun Anda.' => 'Refund requests apply only to confirmed, unused bookings. Approved refunds are credited to your account wallet balance (TravelPoints).',
    ],
    'zh' => [
        'Poin Saya' => '我的积分',
        'Tidak disebutkan' => '未说明',
        'Pengajuan refund diterima' => '已收到退款申请',
        'Booking sudah dibayar tidak dapat dibatalkan sendiri. Silakan ajukan refund.' => '已付款的订单无法自行取消，请提交退款申请。',
        'Termasuk asuransi perjalanan' => '含旅行保险',
        'Minta Refund' => '申请退款',
        'Status Refund' => '退款状态',
        'Diajukan' => '已提交',
        'Refund dihitung otomatis: 100% (≥H-8), 50% (H-4–H-7), 0% (<H-3) sesuai kebijakan produk.' => '退款自动计算：100%（≥出发前8天）、50%（出发前4–7天）、0%（<出发前3天），依产品政策而定。',
        'Alasan Refund' => '退款原因',
        'Contoh: Perubahan jadwal perjalanan' => '例如：行程时间变更',
        'Ajukan Refund' => '提交退款',
        'Tanggal Keberangkatan' => '出发日期',
        'Minimal penukaran 100 points' => '最低兑换 100 积分',
        'Saldo points tidak cukup' => '积分余额不足',
        'Berhasil menukar :p points menjadi :a' => '成功将 :p 积分兑换为 :a',
        'Saldo Points' => '积分余额',
        '1 point = Rp 100 · diperoleh dari booking yang dibayar' => '1 积分 = Rp 100 · 来自已付款订单',
        'Tukar points' => '兑换积分',
        'Tukar' => '兑换',
        'Riwayat Points' => '积分历史',
        'Belum ada mutasi points' => '暂无积分变动',
        'Poin & Loyalitas' => '积分与会员',
        'Tier Anda' => '您的等级',
        'booking selesai' => '个已完成订单',
        'booking lagi untuk' => '个订单后升级至',
        'Tier maksimum tercapai!' => '已达最高等级！',
        'poin tersedia' => '可用积分',
        'Riwayat Poin' => '积分历史',
        'Keterangan' => '说明',
        'Earn dari booking' => '来自订单的积分',
        'Redeem poin' => '兑换积分',
        'Refund poin' => '退还积分',
        'Penyesuaian' => '调整',
        'Belum ada riwayat poin.' => '暂无积分历史。',
        'Poin akan ditambahkan setelah booking selesai.' => '积分将在订单完成后添加。',
        'Total Reward' => '奖励总额',
        'Top Referrer' => '推荐达人',
        'Min' => '最少',
        'Itinerary Saya' => '我的行程',
        'Rencana perjalanan yang kamu simpan.' => '您保存的旅行计划。',
        'Belum ada itinerary.' => '暂无行程。',
        'Tanggal belum diset' => '日期未设置',
        'Buka' => '打开',
        'Buka halaman checkout' => '打开结账页面',
        'Price Alert Saya' => '我的降价提醒',
        'Terakhir Notif' => '最近通知',
        'Belum ada price alert.' => '暂无降价提醒。',
        'Klik tombol "Set Price Alert" pada detail tour atau hotel untuk membuat alert.' => '在旅行团或酒店详情页点击“设降价提醒”创建提醒。',
        'Profil Penumpang' => '乘客档案',
        'Simpan data penumpang untuk checkout lebih cepat.' => '保存乘客信息，加快结账。',
        'Profil tersimpan' => '档案已保存',
        'Profil dihapus' => '档案已删除',
        'Default diperbarui' => '默认已更新',
        'Edit Profil' => '编辑档案',
        'Tambah Profil Baru' => '添加新档案',
        'Nomor Paspor' => '护照号码',
        'Kewarganegaraan' => '国籍',
        'Tanggal Lahir' => '出生日期',
        'Jadikan default' => '设为默认',
        'Hapus profil ini?' => '删除此档案？',
        'Belum ada profil penumpang.' => '暂无乘客档案。',
        'Tambahkan profil di sisi kiri untuk checkout lebih cepat.' => '在左侧添加档案以加快结账。',
        'Scan voucher' => '扫描凭证',
        'Tracking' => '追踪',
        'Buka halaman Booking Saya, pilih booking confirmed, klik Minta Refund, dan isi alasan. Tim kami akan meninjau pengajuan maksimal 3x24 jam hari kerja. Status pengajuan (requested / approved / rejected) dapat dipantau di halaman yang sama.' => '打开“我的订单”，选择已确认的订单，点击“申请退款”并填写原因。我们将在最多 3×24 个工作小时内审核。申请状态（requested / approved / rejected）可在同一页面查看。',
        'Pengajuan refund hanya berlaku untuk booking berstatus confirmed yang belum digunakan. Dana refund yang disetujui dikreditkan ke saldo wallet (TravelPoints) akun Anda.' => '退款申请仅适用于尚未使用且状态为已确认的订单。获批的退款将存入您账户的钱包余额（TravelPoints）。',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$authCount = 0;
foreach ($authExtra as $lang => $dict) {
    foreach ($dict as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $authCount++;
    }
}
echo "Upserted $authCount authenticated-page translation rows.\n";

// ---- Notifikasi harga turun (in-app + email fallback) ----
$priceAlertExtra = [
    'en' => [
        'Harga Turun! 🎉' => 'Price Dropped! 🎉',
        '%s sekarang %s (target: %s)' => '%s is now %s (target: %s)',
    ],
    'zh' => [
        'Harga Turun! 🎉' => '价格下降！🎉',
        '%s sekarang %s (target: %s)' => '%s 现为 %s（目标价：%s）',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$paCount = 0;
foreach ($priceAlertExtra as $lang => $dict) {
    foreach ($dict as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $paCount++;
    }
}
echo "Upserted $paCount price-alert notification rows.\n";

// ---- JS i18n (promo result, newsletter toast, upload hint) ----
$jsExtra = [
    'en' => [
        'Berhasil! Cek email Anda.' => 'Success! Check your email.',
        'Gagal. Coba lagi.' => 'Failed. Try again.',
        'Diskon:' => 'Discount:',
        'Kode promo tidak valid' => 'Invalid promo code',
        'Kode promo tidak berlaku' => 'Promo code not applicable',
        'JPG/PNG/WebP, maks 5MB' => 'JPG/PNG/WebP, max 5MB',
    ],
    'zh' => [
        'Berhasil! Cek email Anda.' => '成功！请查看您的邮箱。',
        'Gagal. Coba lagi.' => '失败，请重试。',
        'Diskon:' => '折扣：',
        'Kode promo tidak valid' => '优惠码无效',
        'Kode promo tidak berlaku' => '优惠码不适用',
        'JPG/PNG/WebP, maks 5MB' => 'JPG/PNG/WebP，最大 5MB',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$jsCount = 0;
foreach ($jsExtra as $lang => $dict) {
    foreach ($dict as $key => $value) {
        $stmt->execute([$key, $lang, $value]);
        $jsCount++;
    }
}
echo "Upserted $jsCount JS i18n rows.\n";

// ---- Halaman PELNI (ferry) ----
$pelniExtra = [
    'en' => [
        'Pesan tiket kapal PELNI — Batam, Jakarta, dan rute lainnya.' => 'Book PELNI ferry tickets — Batam, Jakarta, and other routes.',
        'Pelabuhan asal' => 'Departure port',
        'Pelabuhan tujuan' => 'Destination port',
        'ships' => 'ships',
        'Tidak ada jadwal kapal untuk rute/tanggal ini.' => 'No ferry schedules found for this route/date.',
        'Tidak ada jadwal kapal ditemukan untuk rute/tanggal ini.' => 'No ferry schedules found for this route/date.',
        'Pelabuhan asal/tujuan tidak ditemukan. Coba: Batam, Jakarta.' => 'Departure/destination port not found. Try: Batam, Jakarta.',
    ],
    'zh' => [
        'Pesan tiket kapal PELNI — Batam, Jakarta, dan rute lainnya.' => '预订 PELNI 船票 — 巴淡、雅加达及其他航线。',
        'Pelabuhan asal' => '出发港',
        'Pelabuhan tujuan' => '目的港',
        'ships' => '班次',
        'Tidak ada jadwal kapal untuk rute/tanggal ini.' => '该航线/日期暂无船班。',
        'Tidak ada jadwal kapal ditemukan untuk rute/tanggal ini.' => '该航线/日期暂无船班。',
        'Pelabuhan asal/tujuan tidak ditemukan. Coba: Batam, Jakarta.' => '未找到出发港/目的港。请尝试：巴淡、雅加达。',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$pelniCount = 0;
foreach ($pelniExtra as $lang => $dict) {
    foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $pelniCount++; }
}
echo "Upserted $pelniCount PELNI translation rows.\n";

// ---- Konten: connectivity_products (eSIM/SIM/WiFi) zh + en ----
foreach (['name_zh', 'description_zh'] as $col) {
    try { db()->exec("ALTER TABLE connectivity_products ADD COLUMN `$col` TEXT NULL"); echo "Added connectivity_products.$col\n"; } catch (Throwable $e) {}
}
$connectivity = [
    1 => ['name_en' => 'Indonesia eSIM 5GB', 'name_zh' => '印度尼西亚 eSIM 5GB', 'description_en' => 'Instant eSIM for Indonesia with 5GB data, valid 7 days.', 'description_zh' => '印度尼西亚即时 eSIM，5GB 流量，有效期 7 天。'],
    2 => ['name_en' => 'Bali eSIM 10GB', 'name_zh' => '巴厘岛 eSIM 10GB', 'description_en' => 'High-data eSIM for the Bali area, active for 14 days.', 'description_zh' => '巴厘岛地区大流量 eSIM，有效期 14 天。'],
    3 => ['name_en' => 'Traveloka SIM Card 5GB', 'name_zh' => 'Traveloka SIM 卡 5GB', 'description_en' => 'Physical SIM with 5GB data, delivered nationwide.', 'description_zh' => '实体 SIM 卡，5GB 流量，全国配送。'],
    4 => ['name_en' => 'Pocket WiFi 4G Unlimited', 'name_zh' => '随身 WiFi 4G 无限量', 'description_en' => 'Unlimited Pocket WiFi for all of Indonesia.', 'description_zh' => '印度尼西亚全境无限量随身 WiFi。'],
    5 => ['name_en' => 'Southeast Asia eSIM 20GB', 'name_zh' => '东南亚 eSIM 20GB', 'description_en' => 'Regional eSIM for 10 Southeast Asian countries.', 'description_zh' => '适用于 10 个东南亚国家的区域 eSIM。'],
];
$coStmt = db()->prepare("UPDATE connectivity_products SET name_en = ?, name_zh = ?, description_en = ?, description_zh = ? WHERE id = ?");
foreach ($connectivity as $id => $c) { $coStmt->execute([$c['name_en'], $c['name_zh'], $c['description_en'], $c['description_zh'], $id]); }
echo "Updated " . count($connectivity) . " connectivity products (en/zh).\n";

// ---- NusaTrip booking flow errors ----
$nusaExtra = [
    'en' => [
        'Parameter booking tidak lengkap.' => 'Booking parameters are incomplete.',
        'Kamar tidak tersedia (rates kosong / index salah).' => 'Room not available (empty rates / wrong index).',
        'Gagal membuat sesi booking. Silakan coba lagi atau pilih kamar lain.' => 'Failed to create a booking session. Please try again or pick another room.',
        'Harga kamar ini tidak tersedia di NusaTrip' => 'This room rate is not available on NusaTrip',
        'validasi gagal' => 'validation failed',
        'Pilih kamar lain.' => 'Choose another room.',
        'Sesi tidak valid. Kembali dan ulangi.' => 'Invalid session. Go back and try again.',
        'Sesi booking kedaluwarsa. Ulangi dari halaman hotel.' => 'Booking session expired. Restart from the hotel page.',
        'Harga belum tervalidasi NusaTrip. Ulangi dari halaman hotel.' => 'Price not validated by NusaTrip yet. Restart from the hotel page.',
        'Metode pembayaran tidak valid.' => 'Invalid payment method.',
        'Submit gagal. Silakan coba lagi.' => 'Submit failed. Please try again.',
        'Tidak ada taskId. Ulangi booking.' => 'No taskId. Please redo the booking.',
    ],
    'zh' => [
        'Parameter booking tidak lengkap.' => '预订参数不完整。',
        'Kamar tidak tersedia (rates kosong / index salah).' => '房间不可用（价格为空 / 索引错误）。',
        'Gagal membuat sesi booking. Silakan coba lagi atau pilih kamar lain.' => '创建预订会话失败。请重试或选择其他房间。',
        'Harga kamar ini tidak tersedia di NusaTrip' => '此房价在 NusaTrip 上不可用',
        'validasi gagal' => '验证失败',
        'Pilih kamar lain.' => '请选择其他房间。',
        'Sesi tidak valid. Kembali dan ulangi.' => '会话无效。请返回重试。',
        'Sesi booking kedaluwarsa. Ulangi dari halaman hotel.' => '预订会话已过期。请从酒店页面重新开始。',
        'Harga belum tervalidasi NusaTrip. Ulangi dari halaman hotel.' => '价格尚未经 NusaTrip 验证。请从酒店页面重新开始。',
        'Metode pembayaran tidak valid.' => '付款方式无效。',
        'Submit gagal. Silakan coba lagi.' => '提交失败。请重试。',
        'Tidak ada taskId. Ulangi booking.' => '缺少 taskId。请重新预订。',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$nusaCount = 0;
foreach ($nusaExtra as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $nusaCount++; } }
echo "Upserted $nusaCount NusaTrip error rows.\n";

// ---- Error messages: ferries/flights/wallet/refund/paypal ----
$errExtra = [
    'en' => [
        'Tidak ada jadwal ferry ditemukan untuk rute/tanggal ini.' => 'No ferry schedules found for this route/date.',
        'Kota asal/tujuan tidak ditemukan. Coba: Batam, Singapore, Johor.' => 'Origin/destination city not found. Try: Batam, Singapore, Johor.',
        'Minimal 2 leg untuk perjalanan multi-kota.' => 'At least 2 legs are required for a multi-city trip.',
        'Silakan isi kota asal dan tujuan.' => 'Please enter the origin and destination cities.',
        'Jumlah tidak valid' => 'Invalid amount',
        'Saldo TravelPoints tidak mencukupi' => 'Insufficient TravelPoints balance',
        'Pembayaran menggunakan TravelPoints' => 'Payment using TravelPoints',
        'Pembayaran TravelPoints berhasil' => 'TravelPoints payment successful',
        'Booking tidak ditemukan' => 'Booking not found',
        'Bukan booking Anda' => 'Not your booking',
        'Tidak ada pengajuan refund pending' => 'No pending refund request',
        'Gagal memproses refund' => 'Failed to process refund',
        'Pengajuan refund gagal' => 'Refund request failed',
        'Login diperlukan' => 'Login required',
    ],
    'zh' => [
        'Tidak ada jadwal ferry ditemukan untuk rute/tanggal ini.' => '该航线/日期暂无渡轮班次。',
        'Kota asal/tujuan tidak ditemukan. Coba: Batam, Singapore, Johor.' => '未找到出发/目的城市。请尝试：巴淡、新加坡、柔佛。',
        'Minimal 2 leg untuk perjalanan multi-kota.' => '多城市行程至少需要 2 段。',
        'Silakan isi kota asal dan tujuan.' => '请填写出发城市和目的城市。',
        'Jumlah tidak valid' => '金额无效',
        'Saldo TravelPoints tidak mencukupi' => 'TravelPoints 余额不足',
        'Pembayaran menggunakan TravelPoints' => '使用 TravelPoints 付款',
        'Pembayaran TravelPoints berhasil' => 'TravelPoints 支付成功',
        'Booking tidak ditemukan' => '未找到预订',
        'Bukan booking Anda' => '不是您的预订',
        'Tidak ada pengajuan refund pending' => '没有待处理的退款申请',
        'Gagal memproses refund' => '处理退款失败',
        'Pengajuan refund gagal' => '退款申请失败',
        'Login diperlukan' => '需要登录',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$errCount = 0;
foreach ($errExtra as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $errCount++; } }
echo "Upserted $errCount error-message rows.\n";

// ---- Koreksi: key 'Pesan' dipakai sebagai tombol "Book" (bukan "message") ----
$fixPesan = [
    'en' => ['Pesan' => 'Book'],
    'zh' => ['Pesan' => '预订'],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
foreach ($fixPesan as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); } }
echo "Corrected 'Pesan' translation (en/zh).\n";

// ---- Error messages: refund/flightlist/duffel ----
$err2 = [
    'en' => [
        'Hanya booking confirmed yang bisa diajukan refund' => 'Only confirmed bookings can be refunded',
        'Refund sudah diajukan/sebelumnya' => 'Refund already requested/previously submitted',
        'Pengajuan refund sebelumnya sudah ditolak' => 'Previous refund request was rejected',
        'Kode bandara tidak valid. Contoh: CGK, DPS, atau pilih dari daftar.' => 'Invalid airport code. E.g. CGK, DPS, or pick from the list.',
        'Kota asal dan tujuan tidak boleh sama.' => 'Origin and destination cannot be the same.',
        'Tanggal tidak valid.' => 'Invalid date.',
        'Tanggal keberangkatan tidak boleh di masa lalu.' => 'Departure date cannot be in the past.',
        'Penerbangan FlightList tidak ditemukan (sesi kadaluarsa). Silakan cari ulang.' => 'FlightList flight not found (session expired). Please search again.',
        'Minimal 1 leg.' => 'At least 1 leg.',
    ],
    'zh' => [
        'Hanya booking confirmed yang bisa diajukan refund' => '仅已确认的订单可以申请退款',
        'Refund sudah diajukan/sebelumnya' => '退款已申请/此前已提交',
        'Pengajuan refund sebelumnya sudah ditolak' => '此前的退款申请已被拒绝',
        'Kode bandara tidak valid. Contoh: CGK, DPS, atau pilih dari daftar.' => '机场代码无效。例如：CGK、DPS，或从列表中选择。',
        'Kota asal dan tujuan tidak boleh sama.' => '出发地和目的地不能相同。',
        'Tanggal tidak valid.' => '日期无效。',
        'Tanggal keberangkatan tidak boleh di masa lalu.' => '出发日期不能是过去。',
        'Penerbangan FlightList tidak ditemukan (sesi kadaluarsa). Silakan cari ulang.' => '未找到 FlightList 航班（会话已过期）。请重新搜索。',
        'Minimal 1 leg.' => '至少 1 段。',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c2 = 0;
foreach ($err2 as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c2++; } }
echo "Upserted $c2 refund/flight error rows.\n";

// ---- Koreksi: key 'Cari' dipakai tombol "Search" (bukan "Go") ----
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$stmt->execute(['Cari', 'en', 'Search']);
echo "Corrected 'Cari' translation (en).\n";

// ---- Error/status messages: trains, refund timeline, profile, upload ----
$err3 = [
    'en' => [
        'Tidak ada jadwal kereta ditemukan untuk rute/tanggal ini.' => 'No train schedules found for this route/date.',
        'Stasiun tidak ditemukan. Coba: Jakarta Kota, Bandung, Yogyakarta.' => 'Station not found. Try: Jakarta Kota, Bandung, Yogyakarta.',
        'Menunggu persetujuan admin' => 'Awaiting admin approval',
        'Disetujui' => 'Approved',
        'ke TravelPoints' => 'to TravelPoints',
        'Ditolak admin' => 'Rejected by admin',
        'Nama wajib diisi' => 'Name is required',
        'Gagal upload file' => 'File upload failed',
        'Gagal menyimpan file' => 'Failed to save file',
        'Gagal upload' => 'Upload failed',
        'Ukuran file maksimal 2MB' => 'Maximum file size is 2MB',
        'Tipe file harus JPG/PNG/WebP' => 'File type must be JPG/PNG/WebP',
    ],
    'zh' => [
        'Tidak ada jadwal kereta ditemukan untuk rute/tanggal ini.' => '该航线/日期暂无火车班次。',
        'Stasiun tidak ditemukan. Coba: Jakarta Kota, Bandung, Yogyakarta.' => '未找到车站。请尝试：雅加达城区、万隆、日惹。',
        'Menunggu persetujuan admin' => '等待管理员批准',
        'Disetujui' => '已批准',
        'ke TravelPoints' => '至 TravelPoints',
        'Ditolak admin' => '管理员已拒绝',
        'Nama wajib diisi' => '请填写姓名',
        'Gagal upload file' => '文件上传失败',
        'Gagal menyimpan file' => '保存文件失败',
        'Gagal upload' => '上传失败',
        'Ukuran file maksimal 2MB' => '文件大小上限为 2MB',
        'Tipe file harus JPG/PNG/WebP' => '文件类型必须为 JPG/PNG/WebP',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c3 = 0;
foreach ($err3 as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c3++; } }
echo "Upserted $c3 train/status/upload rows.\n";

// ---- Live hotel API error messages ----
$apiExtra = [
    'en' => [
        'unknown action' => 'Unknown action',
        'cc/pagename tidak valid' => 'Invalid cc/pagename',
        'hotel_id tidak valid' => 'Invalid hotel_id',
        'Modul OYO nonaktif' => 'OYO module is disabled',
        'Modul NusaTrip nonaktif' => 'NusaTrip module is disabled',
        'rates gagal' => 'Failed to load rates',
        'detail gagal' => 'Failed to load details',
        'policy gagal' => 'Failed to load policy',
        'Respons tidak valid' => 'Invalid response',
        'autocomplete kosong' => 'Empty autocomplete',
        'pagename/cc Booking.com tidak valid' => 'Invalid Booking.com pagename/cc',
        'Booking.com tidak mengembalikan data hotel' => 'Booking.com returned no hotel data',
        'Kota kosong' => 'City is empty',
        'Kota tidak valid' => 'Invalid city',
        'OYO: tidak ada hasil / diblokir' => 'OYO: no results / blocked',
        'NusaTrip autocomplete kosong' => 'NusaTrip autocomplete is empty',
        'Token NusaTrip kosong' => 'NusaTrip token is empty',
        'Token tidak valid (key angka atau rkey hex dari /hotels/result?...)' => 'Invalid token (numeric key or rkey hex from /hotels/result?...).',
        'NusaTrip: key tidak valid / kosong' => 'NusaTrip: invalid / empty key',
        'Live hotel API dimatikan' => 'Live hotel API is disabled',
        'Semua modul live nonaktif' => 'All live modules are disabled',
        'Hotel tidak ditemukan' => 'Hotel not found',
        'Kota tidak ditemukan di NusaTrip' => 'City not found on NusaTrip',
        'locationId kosong' => 'locationId is empty',
    ],
    'zh' => [
        'unknown action' => '未知操作',
        'cc/pagename tidak valid' => 'cc/pagename 无效',
        'hotel_id tidak valid' => 'hotel_id 无效',
        'Modul OYO nonaktif' => 'OYO 模块已禁用',
        'Modul NusaTrip nonaktif' => 'NusaTrip 模块已禁用',
        'rates gagal' => '房价加载失败',
        'detail gagal' => '详情加载失败',
        'policy gagal' => '政策加载失败',
        'Respons tidak valid' => '响应无效',
        'autocomplete kosong' => '自动补全为空',
        'pagename/cc Booking.com tidak valid' => 'Booking.com pagename/cc 无效',
        'Booking.com tidak mengembalikan data hotel' => 'Booking.com 未返回酒店数据',
        'Kota kosong' => '城市为空',
        'Kota tidak valid' => '城市无效',
        'OYO: tidak ada hasil / diblokir' => 'OYO：无结果 / 被拦截',
        'NusaTrip autocomplete kosong' => 'NusaTrip 自动补全为空',
        'Token NusaTrip kosong' => 'NusaTrip 令牌为空',
        'Token tidak valid (key angka atau rkey hex dari /hotels/result?...)' => '令牌无效（数字 key 或来自 /hotels/result?... 的 rkey 十六进制）。',
        'NusaTrip: key tidak valid / kosong' => 'NusaTrip：密钥无效/为空',
        'Live hotel API dimatikan' => '实时酒店 API 已关闭',
        'Semua modul live nonaktif' => '所有实时模块均已禁用',
        'Hotel tidak ditemukan' => '未找到酒店',
        'Kota tidak ditemukan di NusaTrip' => '在 NusaTrip 上未找到城市',
        'locationId kosong' => 'locationId 为空',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c4 = 0;
foreach ($apiExtra as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c4++; } }
echo "Upserted $c4 hotel API error rows.\n";

// ---- Flight search date/format errors (duffel/flightlist) ----
$err4 = [
    'en' => [
        'Tanggal tidak boleh di masa lalu' => 'Date cannot be in the past',
        'Tanggal terlalu jauh (maks 360 hari).' => 'Date is too far ahead (max 360 days).',
        'Tanggal terlalu jauh (maks 360 hari)' => 'Date is too far ahead (max 360 days)',
        'Format FlightList tidak valid' => 'Invalid FlightList format',
    ],
    'zh' => [
        'Tanggal tidak boleh di masa lalu' => '日期不能是过去',
        'Tanggal terlalu jauh (maks 360 hari).' => '日期过于遥远（最多 360 天）。',
        'Tanggal terlalu jauh (maks 360 hari)' => '日期过于遥远（最多 360 天）',
        'Format FlightList tidak valid' => 'FlightList 格式无效',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c5 = 0;
foreach ($err4 as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c5++; } }
echo "Upserted $c5 flight date error rows.\n";

// ---- Admin sidebar / nav labels ----
$adminNav = [
    'en' => [
        'Ulasan' => 'Reviews',
        'Push Notifikasi' => 'Push Notifications',
        'Menu Navigasi' => 'Navigation Menu',
    ],
    'zh' => [
        'Overview' => '概览',
        'Inventory' => '库存',
        'Bookings' => '订单',
        'Marketing' => '营销',
        'Finance' => '财务',
        'Sales Report' => '销售报表',
        'Accounting' => '会计',
        'Content' => '内容',
        'Brand & Logo' => '品牌与标志',
        'Menu Navigasi' => '导航菜单',
        'Settings' => '设置',
        'Hotel API' => '酒店 API',
        'Eksternal' => '外部',
        'Push Notifikasi' => '推送通知',
        'Ulasan' => '评价',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c6 = 0;
foreach ($adminNav as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c6++; } }
echo "Upserted $c6 admin sidebar rows.\n";

// ---- Admin common labels (lists/forms/reports) ----
$adminCommon = [
    'en' => [
        'Dari' => 'From',
        'Tambah Slide' => 'Add Slide',
        'Edit Slide' => 'Edit Slide',
        'Edit Ferry' => 'Edit Ferry',
        'Edit Hotel' => 'Edit Hotel',
        'Catatan internal' => 'Internal note',
        'Foto' => 'Photo',
    ],
    'zh' => [
        'Status' => '状态', 'Edit' => '编辑', 'Hotel' => '酒店', 'Transfer' => '接送',
        'Item' => '项目', 'Filter' => '筛选', 'Ferry' => '船票', 'Total' => '总计',
        'Pending' => '待处理', 'Sales Report' => '销售报表', 'Accounting' => '会计',
        'Total Pendapatan' => '总收入', 'Laba Bersih' => '净利润', 'Refund' => '退款',
        'Dashboard' => '仪表盘', 'Booking' => '订单', 'Price Alerts' => '降价提醒',
        'COGS' => '销售成本', 'Laba Kotor' => '毛利润', 'Total Pengeluaran' => '总支出',
        'Tambah Pengeluaran' => '添加支出', 'Belum ada data' => '暂无数据',
        'Corporate Rates' => '企业价', 'Analytics' => '数据分析', 'Flash Sale' => '限时特惠',
        'Blog' => '博客', 'Log Email' => '邮件日志', 'Best Seller' => '热销', 'Qty' => '数量',
        'Refresh' => '刷新', 'Admin Panel' => '管理后台', 'Min Pax' => '最少人数',
        'Confirmed' => '已确认', 'Revenue' => '收入', 'Draft' => '草稿',
        'Profit & Loss' => '损益表', 'Total HPP' => '总成本', 'Periode' => '期间',
        'Menu' => '菜单', 'Reseller' => '分销商', 'Live Chat' => '在线客服',
        'Hotel API' => '酒店 API', 'Tambah Slide' => '添加幻灯片', 'Edit Slide' => '编辑幻灯片',
        'aktif' => '启用', 'nonaktif' => '停用', 'Target' => '目标', 'Indonesia' => '印度尼西亚',
        'English' => '英语', 'Export CSV' => '导出 CSV', 'Label' => '标签',
        'Environment' => '环境', 'Sandbox' => '沙盒', 'Production' => '生产',
        'Jadwal' => '时刻', 'Foto' => '照片', 'Catatan internal' => '内部备注',
        'FAQ' => '常见问题', 'Email' => '电子邮件', 'Reset' => '重置', 'Event' => '事件',
        'Rate' => '评分', 'Edit Ferry' => '编辑船票', 'Edit Hotel' => '编辑酒店',
        'Brand & logo tersimpan' => '品牌与标志已保存', 'transaksi' => '交易', 'Dari' => '从',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c7 = 0;
foreach ($adminCommon as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c7++; } }
echo "Upserted $c7 admin common rows.\n";

// ---- Admin: fix identity-EN Indonesian labels ----
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
foreach ([
    ['Belum ada data', 'en', 'No data yet'],
    ['Brand & logo tersimpan', 'en', 'Brand & logo saved'],
    ['Ulasan', 'en', 'Reviews'],
] as [$k, $l, $v]) { $stmt->execute([$k, $l, $v]); }
echo "Fixed admin identity-EN labels.\n";

// ---- Admin dashboard labels ----
$adminDash = [
    'en' => [
        'Laba bersih' => 'Net Profit',
        'Rata-rata per transaksi' => 'Average per transaction',
        'Tingkat konversi' => 'Conversion rate',
        'Belum ada aktivitas' => 'No activity yet',
    ],
    'zh' => [
        'Booking baru' => '新订单',
        'Net Profit' => '净利润',
        'Laba bersih' => '净利润',
        'Avg Order Value' => '平均订单价值',
        'Rata-rata per transaksi' => '每笔交易平均',
        'Conversion Rate' => '转化率',
        'Tingkat konversi' => '转化率',
        'Tren Pendapatan' => '收入趋势',
        '30 hari terakhir' => '最近 30 天',
        'Booking per Vertikal' => '各业务订单',
        'Aksi Cepat' => '快捷操作',
        'Aktivitas Terakhir' => '最近活动',
        'Belum ada aktivitas' => '暂无活动',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c8 = 0;
foreach ($adminDash as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c8++; } }
echo "Upserted $c8 admin dashboard rows.\n";

// ---- Admin bookings page ----
$adminBookings = [
    'en' => [
        'Kasur' => 'Bed',
        'Ubah Status' => 'Change Status',
        'Catatan untuk tim (tidak dikirim ke pelanggan email)' => 'Note for the team (not sent to the customer email)',
        'Peserta' => 'Participants',
        'ditolak' => 'Rejected',
        'Setujui refund booking ini? Dana akan dikredit ke TravelPoints user.' => "Approve this booking refund? Funds will be credited to the user's TravelPoints.",
        'Setujui Refund' => 'Approve Refund',
        'Tolak pengajuan refund ini?' => 'Reject this refund request?',
        'Tolak Refund' => 'Reject Refund',
        'Booking %s' => 'Booking %s',
    ],
    'zh' => [
        'Simpan COGS' => '保存成本',
        'Jadwal' => '时刻',
        'Kasur' => '床型',
        'Ubah Status' => '更改状态',
        'Peserta' => '参与者',
        'ditolak' => '已拒绝',
        'Setujui refund booking ini? Dana akan dikredit ke TravelPoints user.' => '批准此订单退款？款项将存入用户的 TravelPoints。',
        'Setujui Refund' => '批准退款',
        'Tolak pengajuan refund ini?' => '拒绝此退款申请？',
        'Tolak Refund' => '拒绝退款',
        'Catatan untuk tim (tidak dikirim ke pelanggan email)' => '团队备注（不会发送到客户邮箱）',
        'Booking %s' => '订单 %s',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c9 = 0;
foreach ($adminBookings as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c9++; } }
echo "Upserted $c9 admin bookings rows.\n";

// ---- Admin tour editor ----
$adminTourEdit = [
    'en' => [
        'Itinerary berhasil ditambahkan' => 'Itinerary added successfully',
        'Itinerary berhasil dihapus' => 'Itinerary deleted successfully',
        'Tanggal keberangkatan berhasil ditambahkan' => 'Departure date added successfully',
        'Tanggal keberangkatan berhasil dihapus' => 'Departure date deleted successfully',
        'Belum ada jadwal keberangkatan.' => 'No departure schedules yet.',
        'Upload beberapa gambar (JPG/PNG/WebP, max 2MB)' => 'Upload multiple images (JPG/PNG/WebP, max 2MB)',
        'Upload Galeri' => 'Upload Gallery',
        'Belum ada foto galeri. Upload untuk mengganti galeri auto (loremflickr).' => 'No gallery photos yet. Upload to replace the auto gallery (loremflickr).',
        'asli' => 'original',
        '+ Tambah' => '+ Add',
        'Edit Tour' => 'Edit Tour',
        'Edit Tour:' => 'Edit Tour:',
        'Update Tour' => 'Update Tour',
        'Itinerary' => 'Itinerary',
        'Low Season' => 'Low Season',
        'Single' => 'Single',
        'Slot' => 'Slot',
    ],
    'zh' => [
        'Itinerary berhasil ditambahkan' => '行程添加成功',
        'Itinerary berhasil dihapus' => '行程删除成功',
        'Tanggal keberangkatan berhasil ditambahkan' => '出发日期添加成功',
        'Tanggal keberangkatan berhasil dihapus' => '出发日期删除成功',
        'Belum ada jadwal keberangkatan.' => '暂无出发日期。',
        'Upload beberapa gambar (JPG/PNG/WebP, max 2MB)' => '上传多张图片（JPG/PNG/WebP，最大 2MB）',
        'Upload Galeri' => '上传相册',
        'Belum ada foto galeri. Upload untuk mengganti galeri auto (loremflickr).' => '暂无相册照片。上传以替换自动相册（loremflickr）。',
        'asli' => '原图',
        '+ Tambah' => '+ 添加',
        'Edit Tour' => '编辑旅游',
        'Edit Tour:' => '编辑旅游：',
        'Update Tour' => '更新旅游',
        'Itinerary' => '行程',
        'Low Season' => '淡季',
        'Single' => '单人间',
        'Slot' => '名额',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c10 = 0;
foreach ($adminTourEdit as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c10++; } }
echo "Upserted $c10 admin tour-edit rows.\n";

// ---- Admin accounting / sales report labels ----
$adminAccounting = [
    'en' => [
        'Pengeluaran berhasil diperbarui' => 'Expense updated successfully',
        'Pengeluaran berhasil ditambahkan' => 'Expense added successfully',
        'Deskripsi dan jumlah wajib diisi (jumlah > 0)' => 'Description and amount are required (amount > 0)',
        'Pengeluaran berhasil dihapus' => 'Expense deleted successfully',
    ],
    'zh' => [
        'Pengeluaran berhasil diperbarui' => '支出更新成功',
        'Pengeluaran berhasil ditambahkan' => '支出添加成功',
        'Deskripsi dan jumlah wajib diisi (jumlah > 0)' => '描述和金额为必填（金额 > 0）',
        'Pengeluaran berhasil dihapus' => '支出删除成功',
        'REVENUE' => '收入',
        'EXPENSES' => '支出',
        'Laba Rugi' => '损益',
        'dari pendapatan' => '占收入',
        'Harga Pokok Penjualan' => '销售成本',
        'Pengeluaran' => '支出',
        'Perbandingan Bulanan' => '月度对比',
        'bulan' => '月',
        'Breakdown Pengeluaran' => '支出明细',
        'Daftar Pengeluaran' => '支出列表',
        'Belum ada pengeluaran pada periode ini' => '本期暂无支出',
        'Edit Pengeluaran' => '编辑支出',
        'Biaya' => '费用',
        'Profit & Loss' => '损益表',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c11 = 0;
foreach ($adminAccounting as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c11++; } }
echo "Upserted $c11 admin accounting rows.\n";

// ---- Admin payments settings ----
$adminPayments = [
    'en' => [
        'Mode Pembayaran Paket Tour' => 'Tour Package Payment Mode',
        'Manual — approve admin' => 'Manual — admin approval',
        'Instant — payment gateway' => 'Instant — payment gateway',
        'Manual: booking pending, admin konfirmasi. Instant: pelanggan bayar via gateway.' => 'Manual: booking pending, admin confirms. Instant: customer pays via gateway.',
        'Gateway (mode instant)' => 'Gateway (instant mode)',
        'Instant AKTIF via ' => 'Instant ACTIVE via ',
        'Berjalan MANUAL — semua gateway nonaktif' => 'Running MANUAL — all gateways disabled',
        'Pengaturan Tripay' => 'Tripay Settings',
    ],
    'zh' => [
        'Mode Pembayaran Paket Tour' => '旅游套餐支付模式',
        'Mode' => '模式',
        'Manual — approve admin' => '手动 — 管理员审核',
        'Instant — payment gateway' => '即时 — 支付网关',
        'Manual: booking pending, admin konfirmasi. Instant: pelanggan bayar via gateway.' => '手动：订单待处理，由管理员确认。即时：客户通过支付网关付款。',
        'Gateway (mode instant)' => '网关（即时模式）',
        'Instant AKTIF via ' => '即时已启用，经由 ',
        'Berjalan MANUAL — semua gateway nonaktif' => '以手动模式运行 — 所有网关已禁用',
        'Pengaturan Tripay' => 'Tripay 设置',
        'Gateway' => '网关',
        'Merchant Code' => '商户代码',
        'Private Key' => '私钥',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c12 = 0;
foreach ($adminPayments as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c12++; } }
echo "Upserted $c12 admin payments rows.\n";

// ---- Hotel booking messages ----
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
foreach ([
    ['Tanggal sudah dibooking untuk hotel ini. Pilih tanggal lain.', 'en', 'These dates are already booked for this hotel. Choose another date.'],
    ['Tanggal sudah dibooking untuk hotel ini. Pilih tanggal lain.', 'zh', '该酒店此日期已被预订。请选择其他日期。'],
    ['Booking berhasil! Total:', 'en', 'Booking successful! Total:'],
    ['Booking berhasil! Total:', 'zh', '预订成功！总计：'],
] as [$k, $l, $v]) { $stmt->execute([$k, $l, $v]); }
echo "Added hotel booking message rows.\n";

// ---- Admin sales report ----
$adminSalesReport = [
    'en' => [
        'Total Transaksi' => 'Total Transactions',
    ],
    'zh' => [
        'Vertikal' => '业务',
        'Total Transaksi' => '总交易数',
        'Rata-rata per Transaksi' => '每笔交易平均',
        'Vertikal Teratas' => '顶级业务',
        'Breakdown Pendapatan' => '收入明细',
        'Detail Transaksi' => '交易明细',
        'Belum ada transaksi pada periode ini' => '本期暂无交易',
        'Menampilkan 200 dari' => '显示 200 条，共',
        'gunakan filter atau Export CSV untuk data lengkap' => '使用筛选或导出 CSV 查看完整数据',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c13 = 0;
foreach ($adminSalesReport as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c13++; } }
echo "Upserted $c13 admin sales-report rows.\n";

// ---- Admin hotel API settings ----
$adminHotelApi = [
    'en' => [
        'Sumber live tidak valid' => 'Invalid live source',
        'rkey NusaTrip harus hex 32–160 karakter' => 'NusaTrip rkey must be 32–160 hex characters',
        'Pengaturan Hotel API tersimpan' => 'Hotel API settings saved',
        'Aktifkan live hotel API (Booking.com/OYO/NusaTrip)' => 'Enable live hotel API (Booking.com/OYO/NusaTrip)',
        'Aktifkan modul NusaTrip (search + booking + VA)' => 'Enable NusaTrip module (search + booking + VA)',
        'Aktifkan modul OYO (fallback listing per kota)' => 'Enable OYO module (fallback listing per city)',
        'Sumber utama' => 'Primary source',
        'NusaTrip rkey (lama/opsional)' => 'NusaTrip rkey (legacy/optional)',
        'Native API aktif tanpa rkey. rkey lama hanya untuk fallback scraping bila diperlukan.' => 'Native API works without rkey. The legacy rkey is only for scraping fallback if needed.',
        'Uji pencarian live' => 'Test live search',
        'Uji' => 'Test',
        'Kosong/gagal' => 'Empty/failed',
        'Modul NusaTrip:' => 'NusaTrip module:',
        'Modul OYO:' => 'OYO module:',
        'rkey NusaTrip' => 'NusaTrip rkey',
        'terisi' => 'filled',
        'kosong' => 'empty',
        'aktif' => 'active',
        'nonaktif' => 'inactive',
    ],
    'zh' => [
        'Sumber live tidak valid' => '实时来源无效',
        'rkey NusaTrip harus hex 32–160 karakter' => 'NusaTrip rkey 必须为 32–160 位十六进制字符',
        'Pengaturan Hotel API tersimpan' => '酒店 API 设置已保存',
        'Live Hotel API' => '实时酒店 API',
        'Aktifkan live hotel API (Booking.com/OYO/NusaTrip)' => '启用实时酒店 API（Booking.com/OYO/NusaTrip）',
        'Aktifkan modul NusaTrip (search + booking + VA)' => '启用 NusaTrip 模块（搜索 + 预订 + VA）',
        'Aktifkan modul OYO (fallback listing per kota)' => '启用 OYO 模块（按城市回退列表）',
        'Sumber utama' => '主要来源',
        'NusaTrip rkey (lama/opsional)' => 'NusaTrip rkey（旧版/可选）',
        'Native API aktif tanpa rkey. rkey lama hanya untuk fallback scraping bila diperlukan.' => '原生 API 无需 rkey 即可使用。旧 rkey 仅在需要时用于抓取回退。',
        'Uji pencarian live' => '测试实时搜索',
        'Uji' => '测试',
        'OK' => '正常',
        'hotel' => '酒店',
        'Kosong/gagal' => '为空/失败',
        'Modul NusaTrip:' => 'NusaTrip 模块：',
        'Modul OYO:' => 'OYO 模块：',
        'rkey NusaTrip' => 'NusaTrip rkey',
        'terisi' => '已填写',
        'kosong' => '为空',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c14 = 0;
foreach ($adminHotelApi as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c14++; } }
echo "Upserted $c14 admin hotel-api rows.\n";

// ---- Admin push notifications ----
$adminPush = [
    'en' => [
        'Semua pengguna' => 'All users',
        'Per bahasa (sesuai token)' => 'By language (per token)',
        'User ID tertentu' => 'Specific User IDs',
        'Bahasa' => 'Language',
        'opsional' => 'optional',
        'Judul notifikasi' => 'Notification title',
        'Isi Pesan' => 'Message body',
        'Isi pesan notifikasi' => 'Notification message',
        'Kirim Notifikasi' => 'Send Notification',
        'Riwayat Pengiriman' => 'Delivery History',
        'Belum ada pengiriman.' => 'No deliveries yet.',
        'Terkirim' => 'Sent',
        'Oleh' => 'By',
        'gagal' => 'failed',
    ],
    'zh' => [
        'Target' => '目标',
        'Semua pengguna' => '所有用户',
        'Per bahasa (sesuai token)' => '按语言（按令牌）',
        'User ID tertentu' => '指定用户 ID',
        'Bahasa' => '语言',
        'User ID' => '用户 ID',
        'opsional' => '可选',
        'Judul notifikasi' => '通知标题',
        'Isi Pesan' => '消息内容',
        'Isi pesan notifikasi' => '通知消息',
        'Kirim Notifikasi' => '发送通知',
        'Riwayat Pengiriman' => '发送历史',
        'Belum ada pengiriman.' => '暂无发送记录。',
        'Terkirim' => '已发送',
        'Oleh' => '发送者',
        'gagal' => '失败',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c15 = 0;
foreach ($adminPush as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c15++; } }
echo "Upserted $c15 admin push-notification rows.\n";

// ---- Admin flash sales ----
$adminFlash = [
    'en' => [
        'Item, periode wajib diisi; akhir harus setelah mulai' => 'Item and period are required; end must be after start',
        'Akhir' => 'End',
        'Terjual/Kuota' => 'Sold/Quota',
        'Belum ada flash sale' => 'No flash sales yet',
        'Edit Flash Sale' => 'Edit Flash Sale',
        'Tambah Flash Sale' => 'Add Flash Sale',
        'Tipe item' => 'Item type',
        'ID item' => 'Item ID',
        'Diskon (%)' => 'Discount (%)',
        'Kuota (kosong = tanpa batas)' => 'Quota (empty = unlimited)',
    ],
    'zh' => [
        'Item, periode wajib diisi; akhir harus setelah mulai' => '商品和周期为必填；结束时间必须晚于开始时间',
        'Flash Sale' => '限时特惠',
        'Item' => '商品',
        'Diskon' => '折扣',
        'Akhir' => '结束',
        'Terjual/Kuota' => '已售/配额',
        'Status' => '状态',
        'Edit' => '编辑',
        'Belum ada flash sale' => '暂无限时特惠',
        'Edit Flash Sale' => '编辑限时特惠',
        'Tambah Flash Sale' => '添加限时特惠',
        'Tipe item' => '商品类型',
        'ID item' => '商品 ID',
        'Diskon (%)' => '折扣（%）',
        'Kuota (kosong = tanpa batas)' => '配额（留空 = 不限）',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c16 = 0;
foreach ($adminFlash as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c16++; } }
echo "Upserted $c16 admin flash-sale rows.\n";

// ---- Admin email log ----
$adminEmailLog = [
    'en' => [
        'Log Email' => 'Email Log',
        'Resend dari log oleh admin.' => 'Resend from log by admin.',
        'Email berhasil dikirim ulang.' => 'Email resent successfully.',
        'Resend gagal — lihat log terbaru.' => 'Resend failed — see the latest log.',
        'Kepada' => 'To',
        'Subjek' => 'Subject',
        'Belum ada log email.' => 'No email logs yet.',
        'Resend' => 'Resend',
    ],
    'zh' => [
        'Log Email' => '邮件日志',
        'Resend dari log oleh admin.' => '由管理员从日志重发。',
        'Email berhasil dikirim ulang.' => '邮件重发成功。',
        'Resend gagal — lihat log terbaru.' => '重发失败 — 请查看最新日志。',
        'Status' => '状态',
        'Event' => '事件',
        'Filter' => '筛选',
        'Reset' => '重置',
        'Kepada' => '收件人',
        'Subjek' => '主题',
        'Driver' => '驱动',
        'Belum ada log email.' => '暂无邮件日志。',
        'Resend' => '重发',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c17 = 0;
foreach ($adminEmailLog as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c17++; } }
echo "Upserted $c17 admin email-log rows.\n";

// ---- Admin WhatsApp settings ----
$adminWa = [
    'en' => [
        'Nomor WA harus diawali 62 (contoh: 6285174488415)' => 'WhatsApp number must start with 62 (e.g. 6285174488415)',
        'Scan QR ini dengan WhatsApp Anda' => 'Scan this QR with your WhatsApp',
        'Refresh QR' => 'Refresh QR',
        'Refresh' => 'Refresh',
        'Nama Tour' => 'Tour Name',
        'Nama & No. WA Pemesan' => 'Customer Name & WhatsApp No.',
        'Link Tracking' => 'Tracking Link',
        'Akun:' => 'Account:',
        'Siap mengirim notifikasi ke nomor supplier.' => 'Ready to send notifications to supplier numbers.',
        'Belum terhubung. Klik "Hubungkan Nomor Baru" untuk scan QR.' => 'Not connected. Click "Connect New Number" to scan the QR.',
        'Error: ' => 'Error: ',
    ],
    'zh' => [
        'Nomor WA harus diawali 62 (contoh: 6285174488415)' => 'WhatsApp 号码必须以 62 开头（例如：6285174488415）',
        'Scan QR ini dengan WhatsApp Anda' => '用您的 WhatsApp 扫描此二维码',
        'Refresh QR' => '刷新二维码',
        'Refresh' => '刷新',
        'WUZAPI Server' => 'WUZAPI 服务器',
        'Server URL' => '服务器 URL',
        'Nama Tour' => '旅游名称',
        'Nama & No. WA Pemesan' => '客户姓名及 WhatsApp 号码',
        'Link Tracking' => '追踪链接',
        'Akun:' => '账户：',
        'Siap mengirim notifikasi ke nomor supplier.' => '已准备好向供应商号码发送通知。',
        'Belum terhubung. Klik "Hubungkan Nomor Baru" untuk scan QR.' => '尚未连接。点击“连接新号码”扫描二维码。',
        'Error: ' => '错误：',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c18 = 0;
foreach ($adminWa as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c18++; } }
echo "Upserted $c18 admin wa-settings rows.\n";

// ---- Admin nav menus ----
$adminNavMenus = [
    'en' => [
        'Status diubah' => 'Status changed',
        'Label dan URL wajib diisi' => 'Label and URL are required',
        'Atur menu yang muncul di SELURUH header website (tab + menu mobile).' => 'Manage menus shown across the whole website header (tabs + mobile menu).',
        'Tambah Menu' => 'Add Menu',
        'Ikon' => 'Icon',
        'Match key' => 'Match key',
        'Tab' => 'Tab',
    ],
    'zh' => [
        'Status diubah' => '状态已更改',
        'Label dan URL wajib diisi' => '标签和 URL 为必填',
        'Atur menu yang muncul di SELURUH header website (tab + menu mobile).' => '管理在网站页眉中显示的菜单（标签页 + 移动端菜单）。',
        'Tambah Menu' => '添加菜单',
        'Edit' => '编辑',
        'Menu' => '菜单',
        'Label' => '标签',
        'Ikon' => '图标',
        'Match key' => '匹配键',
        'Tab' => '标签页',
        'Status' => '状态',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c19 = 0;
foreach ($adminNavMenus as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c19++; } }
echo "Upserted $c19 admin nav-menus rows.\n";

// ---- Admin brand settings ----
$adminBrand = [
    'en' => [
        'Nama travel wajib diisi' => 'Travel name is required',
        'Nama travel maksimal 60 karakter' => 'Travel name must be at most 60 characters',
        'Nama tersimpan, logo dihapus' => 'Name saved, logo removed',
        'Nama travel' => 'Travel name',
        'Tampil di navbar, footer, judul tab, email, notifikasi WA, dan PDF.' => 'Shown in the navbar, footer, tab title, emails, WhatsApp notifications, and PDFs.',
        'Tagline (opsional)' => 'Tagline (optional)',
        'Logo (JPG/PNG/WebP, maks 2MB)' => 'Logo (JPG/PNG/WebP, max 2MB)',
        'Hapus logo' => 'Remove logo',
        'Pratinjau' => 'Preview',
    ],
    'zh' => [
        'Nama travel wajib diisi' => '旅游名称不能为空',
        'Nama travel maksimal 60 karakter' => '旅游名称最多 60 个字符',
        'Nama tersimpan, logo dihapus' => '名称已保存，标志已删除',
        'Brand & Logo' => '品牌与标志',
        'Nama travel' => '旅游名称',
        'Tampil di navbar, footer, judul tab, email, notifikasi WA, dan PDF.' => '显示在导航栏、页脚、标签页标题、邮件、WhatsApp 通知和 PDF 中。',
        'Tagline (opsional)' => '标语（可选）',
        'Logo (JPG/PNG/WebP, maks 2MB)' => '标志（JPG/PNG/WebP，最大 2MB）',
        'Hapus logo' => '删除标志',
        'Pratinjau' => '预览',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c20 = 0;
foreach ($adminBrand as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c20++; } }
echo "Upserted $c20 admin brand-settings rows.\n";

// ---- Admin live chat settings ----
$adminChat = [
    'en' => [
        'Property ID tawk.to harus 24 karakter hex (contoh: 5f1a2b3c4d5e6f7a8b9c0d1e)' => 'tawk.to Property ID must be 24 hex characters (e.g. 5f1a2b3c4d5e6f7a8b9c0d1e)',
        'Widget ID tawk.to hanya alfanumerik maks 10 karakter' => 'tawk.to Widget ID must be alphanumeric, max 10 characters',
        'Pengaturan live chat tersimpan' => 'Live chat settings saved',
        'Live Chat (tawk.to)' => 'Live Chat (tawk.to)',
        'Umumnya "default" (1i[...]) — biarkan bila tidak yakin.' => 'Usually "default" (1i[...]) — leave as is if unsure.',
        'Widget tidak dirender sampai Property ID diisi.' => 'The widget is not rendered until the Property ID is filled in.',
    ],
    'zh' => [
        'Property ID tawk.to harus 24 karakter hex (contoh: 5f1a2b3c4d5e6f7a8b9c0d1e)' => 'tawk.to Property ID 必须为 24 位十六进制字符（例如：5f1a2b3c4d5e6f7a8b9c0d1e）',
        'Widget ID tawk.to hanya alfanumerik maks 10 karakter' => 'tawk.to Widget ID 只能为字母数字，最多 10 个字符',
        'Pengaturan live chat tersimpan' => '在线客服设置已保存',
        'Live Chat' => '在线客服',
        'Live Chat (tawk.to)' => '在线客服（tawk.to）',
        'Property ID' => 'Property ID',
        'Widget ID' => 'Widget ID',
        'Umumnya "default" (1i[...]) — biarkan bila tidak yakin.' => '通常为“default”（1i[...]）—— 不确定时请保持原样。',
        'Status' => '状态',
        'Widget tidak dirender sampai Property ID diisi.' => '在填写 Property ID 之前不会渲染该组件。',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c21 = 0;
foreach ($adminChat as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c21++; } }
echo "Upserted $c21 admin chat-settings rows.\n";

// ---- Admin reseller pricing ----
$adminResellerPricing = [
    'en' => [
        'Harga Reseller per Tour' => 'Reseller Price per Tour',
        'Tour sudah memiliki harga reseller. Edit yang sudah ada.' => 'This tour already has a reseller price. Edit the existing one.',
        'Edit Harga Reseller' => 'Edit Reseller Price',
        'Tambah Harga Reseller' => 'Add Reseller Price',
        'Harga Normal' => 'Regular Price',
        'Toggle' => 'Toggle',
        'Toggle status?' => 'Toggle status?',
    ],
    'zh' => [
        'Harga Reseller per Tour' => '每个旅游的分销价',
        'Tour sudah memiliki harga reseller. Edit yang sudah ada.' => '该旅游已有分销价。请编辑现有价格。',
        'Edit Harga Reseller' => '编辑分销价',
        'Tambah Harga Reseller' => '添加分销价',
        'Min Pax' => '最少人数',
        'Update' => '更新',
        'Harga Normal' => '原价',
        'Status' => '状态',
        'Edit' => '编辑',
        'Toggle' => '切换',
        'Toggle status?' => '切换状态？',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c22 = 0;
foreach ($adminResellerPricing as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c22++; } }
echo "Upserted $c22 admin reseller-pricing rows.\n";

// ---- Admin price alerts ----
$adminPriceAlerts = [
    'en' => [
        'Pengecekan selesai' => 'Check completed',
        'alert dinotifikasi' => 'alerts notified',
        'Jalankan Pengecekan' => 'Run Check',
        'total alert' => 'total alerts',
        'Target:' => 'Target:',
        'Notif:' => 'Notif:',
    ],
    'zh' => [
        'Price Alerts' => '降价提醒',
        'Pengecekan selesai' => '检查完成',
        'alert dinotifikasi' => '个提醒已通知',
        'Jalankan Pengecekan' => '运行检查',
        'total alert' => '个提醒',
        'Target:' => '目标：',
        'Notif:' => '通知：',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c23 = 0;
foreach ($adminPriceAlerts as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c23++; } }
echo "Upserted $c23 admin price-alert rows.\n";

// ---- Admin reseller topups / esim edit / analytics ----
$adminMisc = [
    'en' => [
        'Semua Status' => 'All Statuses',
        'Nama Produk' => 'Product Name',
        'Top 10 Produk' => 'Top 10 Products',
    ],
    'zh' => [
        'Semua Status' => '所有状态',
        'Pending' => '待处理',
        'Approved' => '已批准',
        'Rejected' => '已拒绝',
        'Filter' => '筛选',
        'Approve' => '批准',
        'Reject' => '拒绝',
        'Edit eSIM' => '编辑 eSIM',
        'Edit' => '编辑',
        'Nama Produk' => '产品名称',
        'SIM' => 'SIM',
        'Pocket WiFi' => '随身 WiFi',
        'Status' => '状态',
        'Analytics' => '数据分析',
        'Hotel' => '酒店',
        'Transfer' => '接送',
        'Ferry' => '船票',
        'Dari' => '从',
        'Booking' => '订单',
        'Revenue' => '收入',
        'Top 10 Produk' => '热销商品 Top 10',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c24 = 0;
foreach ($adminMisc as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c24++; } }
echo "Upserted $c24 admin misc rows.\n";

// ---- Admin form validation/help messages (en identity fixes) ----
$adminFormMsgs = [
    'en' => [
        'Diskon % (mis. 10)' => 'Discount % (e.g. 10)',
        'Judul wajib diisi' => 'Title is required',
        'Link CTA harus nama file .php, path internal (diawali /), atau URL' => 'CTA link must be a .php filename, an internal path (starting with /), or a URL',
        'Pilih Tour (centang untuk menambahkan)' => 'Select Tours (check to add)',
        'Slide tampil di homepage dengan fokus ini (atau semua).' => 'Slide appears on the homepage with this focus (or all).',
    ],
    'zh' => [
        'Diskon % (mis. 10)' => '折扣%（例如 10）',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c25 = 0;
foreach ($adminFormMsgs as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c25++; } }
echo "Upserted $c25 admin form-message rows.\n";

// ---- Reseller pages ----
$reseller = [
    'en' => [
        'Dashboard Reseller' => 'Reseller Dashboard',
        'Kelola saldo dan booking Anda sebagai reseller.' => 'Manage your balance and bookings as a reseller.',
        'Saldo Reseller' => 'Reseller Balance',
        'Booking Baru' => 'New Booking',
        'Lihat Booking' => 'View Booking',
        'Belum ada booking reseller.' => 'No reseller bookings yet.',
        'Riwayat Topup' => 'Topup History',
        'Belum ada riwayat topup.' => 'No topup history yet.',
        'Minimal topup Rp 50.000' => 'Minimum topup Rp 50,000',
        'File harus format JPG/PNG/WebP' => 'File must be JPG/PNG/WebP',
        'Permintaan topup berhasil dikirim! Menunggu persetujuan admin.' => 'Topup request sent! Awaiting admin approval.',
        'Topup Saldo Reseller' => 'Reseller Balance Topup',
        'Isi saldo untuk booking paket wisata dengan harga reseller.' => 'Top up your balance to book tour packages at reseller prices.',
        'Saldo Saat Ini' => 'Current Balance',
        'Permintaan Topup Baru' => 'New Topup Request',
        'Jumlah Topup' => 'Topup Amount',
    ],
    'zh' => [
        'Dashboard Reseller' => '分销商仪表板',
        'Kelola saldo dan booking Anda sebagai reseller.' => '以分销商身份管理您的余额和订单。',
        'Saldo Reseller' => '分销商余额',
        'Topup' => '充值',
        'Booking Baru' => '新订单',
        'Lihat Booking' => '查看订单',
        'Belum ada booking reseller.' => '暂无分销商订单。',
        'Riwayat Topup' => '充值历史',
        'Belum ada riwayat topup.' => '暂无充值记录。',
        'Minimal topup Rp 50.000' => '最低充值 Rp 50,000',
        'Minimal Rp 50.000' => '最低 Rp 50,000',
        'File harus format JPG/PNG/WebP' => '文件格式必须为 JPG/PNG/WebP',
        'Permintaan topup berhasil dikirim! Menunggu persetujuan admin.' => '充值申请已发送！等待管理员批准。',
        'Topup Saldo Reseller' => '分销商余额充值',
        'Isi saldo untuk booking paket wisata dengan harga reseller.' => '充值余额，以分销价预订旅游套餐。',
        'Saldo Saat Ini' => '当前余额',
        'Permintaan Topup Baru' => '新充值申请',
        'Jumlah Topup' => '充值金额',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c26 = 0;
foreach ($reseller as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c26++; } }
echo "Upserted $c26 reseller rows.\n";

// ---- Public booking/label/empty-state gaps (en-identity) ----
$publicGaps = [
    'en' => [
        'Nama lengkap wajib diisi.' => 'Full name is required.',
        'Email tidak valid.' => 'Invalid email.',
        'Nomor telepon tidak valid.' => 'Invalid phone number.',
        'Nama penumpang' => 'Passenger name',
        'wajib diisi.' => 'is required.',
        'Simpan kode booking Anda. Petugas akan meminta kode ini saat check-in di pelabuhan.' => 'Save your booking code. Staff will ask for it at port check-in.',
        'Atau pilih channel lain' => 'Or choose another channel',
        'Pilih channel pembayaran' => 'Choose a payment channel',
        'Pilih bank' => 'Choose a bank',
        'Harga/pax' => 'Price/pax',
        'Nama Penumpang' => 'Passenger Name',
        'Harga per penumpang' => 'Price per passenger',
        'Jumlah penumpang' => 'Number of passengers',
        'Penerbangan Tidak Tersedia' => 'Flight Not Available',
        'Rute multi-kota' => 'Multi-city route',
        'Add-on tidak tersedia untuk penerbangan ini.' => 'Add-ons are not available for this flight.',
        'Semua penerbangan sudah dimuat.' => 'All flights loaded.',
        'Semua hotel sudah dimuat.' => 'All hotels loaded.',
        'Semua tour sudah dimuat.' => 'All tours loaded.',
        'Tidak ada jadwal lokal yang cocok dengan filter.' => 'No local schedules match the filter.',
        'Harga termurah' => 'Cheapest',
        'Pilih Kamar' => 'Select Room',
        'Kalender harga' => 'Price calendar',
        'Kembali ke hasil' => 'Back to results',
        'Harga & ketersediaan dapat berubah. Pembelian dilakukan di penyedia.' => 'Prices & availability may change. Purchase is completed with the provider.',
        'Ulasan' => 'Reviews',
        'Bahasa ulasan' => 'Review language',
        'Pilih tipe kamar' => 'Select room type',
        '-- Pilih Profil --' => '-- Select Profile --',
        'Simpan sebagai profil penumpang' => 'Save as passenger profile',
        'Semua tipe kamar sudah habis. Silakan pilih tanggal lain.' => 'All room types are sold out. Please choose another date.',
        'Peta harga hotel' => 'Hotel price map',
        'Sesi tidak valid, silakan muat ulang halaman.' => 'Invalid session, please reload the page.',
        'Jadwal Kapal PELNI' => 'PELNI Ship Schedule',
        'Masukkan pelabuhan asal dan tujuan untuk mencari jadwal kapal.' => 'Enter the origin and destination ports to search ship schedules.',
        'Coba: Batam → Jakarta, atau ubah tanggal.' => 'Try: Batam → Jakarta, or change the date.',
        'Pilih tanggal keberangkatan.' => 'Select a departure date.',
        'Jumlah peserta melebihi kapasitas tour.' => 'Number of participants exceeds tour capacity.',
        'Tanggal keberangkatan tidak valid.' => 'Invalid departure date.',
        'Slot tidak cukup untuk tanggal tersebut.' => 'Not enough slots for that date.',
        'Booking berhasil! Kode booking: ' => 'Booking successful! Booking code: ',
        'Saldo tersisa: ' => 'Remaining balance: ',
        'Harga Normal' => 'Regular Price',
        'Saldo Anda' => 'Your Balance',
        'Topup Saldo' => 'Topup Balance',
        'Pilih tanggal' => 'Select a date',
        'Bayar dari Saldo Reseller' => 'Pay from Reseller Balance',
        'Kirim Permintaan Topup' => 'Send Topup Request',
        'Tour tidak ditemukan' => 'Tour not found',
        'No. WhatsApp tidak valid' => 'Invalid WhatsApp number',
        'Jumlah peserta melebihi kapasitas tour' => 'Number of participants exceeds tour capacity',
        'Foto Ulasan' => 'Review Photo',
        'Booking Reseller' => 'Reseller Booking',
        'Diskon korporat' => 'Corporate discount',
        'diskon' => 'discount',
        'Bayar dari Saldo' => 'Pay from Balance',
        'Tambah Item' => 'Add Item',
        'KAI tidak ditemukan' => 'KAI not found',
        'Transfer tidak ditemukan' => 'Transfer not found',
        'Tiket tidak ditemukan' => 'Ticket not found',
        'Produk tidak ditemukan' => 'Product not found',
        'Collection tidak ditemukan' => 'Collection not found',
        'Pembayaran 1-klik gagal: ' => 'One-click payment failed: ',
        'Harga Terbaik' => 'Best Price',
        'Untuk booking paket wisata dengan harga reseller' => 'For booking tour packages at reseller prices',
    ],
    'zh' => [
        'Nama lengkap wajib diisi.' => '请填写全名。',
        'Email tidak valid.' => '电子邮件无效。',
        'Nomor telepon tidak valid.' => '电话号码无效。',
        'Nama penumpang' => '乘客姓名',
        'wajib diisi.' => '为必填。',
        'Simpan kode booking Anda. Petugas akan meminta kode ini saat check-in di pelabuhan.' => '请保存您的预订编号，码头办理登船手续时工作人员会要求出示。',
        'Atau pilih channel lain' => '或选择其他渠道',
        'Pilih channel pembayaran' => '选择支付渠道',
        'Pilih bank' => '选择银行',
        'Harga/pax' => '价格/人',
        'Nama Penumpang' => '乘客姓名',
        'Harga per penumpang' => '每位乘客价格',
        'Jumlah penumpang' => '乘客人数',
        'Penerbangan Tidak Tersedia' => '航班不可用',
        'Rute multi-kota' => '多城市航线',
        'Add-on tidak tersedia untuk penerbangan ini.' => '此航班不提供附加服务。',
        'Semua penerbangan sudah dimuat.' => '已加载全部航班。',
        'Semua hotel sudah dimuat.' => '已加载全部酒店。',
        'Semua tour sudah dimuat.' => '已加载全部旅游。',
        'Tidak ada jadwal lokal yang cocok dengan filter.' => '没有符合筛选条件的本地班次。',
        'Harga termurah' => '最低价',
        'Pilih Kamar' => '选择房间',
        'Kalender harga' => '价格日历',
        'Kembali ke hasil' => '返回结果',
        'Harga & ketersediaan dapat berubah. Pembelian dilakukan di penyedia.' => '价格和可用性可能变动，购买在服务商处完成。',
        'Ulasan' => '评价',
        'Bahasa ulasan' => '评价语言',
        'Pilih tipe kamar' => '选择房型',
        '-- Pilih Profil --' => '-- 选择档案 --',
        'Simpan sebagai profil penumpang' => '保存为乘客档案',
        'Semua tipe kamar sudah habis. Silakan pilih tanggal lain.' => '所有房型已售罄，请选择其他日期。',
        'Peta harga hotel' => '酒店价格地图',
        'Sesi tidak valid, silakan muat ulang halaman.' => '会话无效，请重新加载页面。',
        'Jadwal Kapal PELNI' => 'PELNI 船班时刻',
        'Masukkan pelabuhan asal dan tujuan untuk mencari jadwal kapal.' => '请输入出发港和目的港以搜索船班。',
        'Coba: Batam → Jakarta, atau ubah tanggal.' => '试试：巴淡 → 雅加达，或更改日期。',
        'Pilih tanggal keberangkatan.' => '请选择出发日期。',
        'Jumlah peserta melebihi kapasitas tour.' => '参加人数超过旅游容量。',
        'Tanggal keberangkatan tidak valid.' => '出发日期无效。',
        'Slot tidak cukup untuk tanggal tersebut.' => '该日期名额不足。',
        'Booking berhasil! Kode booking: ' => '预订成功！预订编号：',
        'Saldo tersisa: ' => '剩余余额：',
        'Harga Normal' => '原价',
        'Saldo Anda' => '您的余额',
        'Topup Saldo' => '充值余额',
        'Pilih tanggal' => '选择日期',
        'Bayar dari Saldo Reseller' => '使用分销商余额支付',
        'Kirim Permintaan Topup' => '发送充值申请',
        'Tour tidak ditemukan' => '未找到旅游',
        'No. WhatsApp tidak valid' => 'WhatsApp 号码无效',
        'Jumlah peserta melebihi kapasitas tour' => '参加人数超过旅游容量',
        'Foto Ulasan' => '评价照片',
        'Booking Reseller' => '分销商订单',
        'Diskon korporat' => '企业折扣',
        'diskon' => '折扣',
        'Bayar dari Saldo' => '使用余额支付',
        'Tambah Item' => '添加项目',
        'KAI tidak ditemukan' => '未找到 KAI',
        'Transfer tidak ditemukan' => '未找到接送',
        'Tiket tidak ditemukan' => '未找到门票',
        'Produk tidak ditemukan' => '未找到产品',
        'Collection tidak ditemukan' => '未找到合集',
        'Pembayaran 1-klik gagal: ' => '一键支付失败：',
        'Harga Terbaik' => '最优惠价格',
        'Untuk booking paket wisata dengan harga reseller' => '用于以分销价预订旅游套餐',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c27 = 0;
foreach ($publicGaps as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c27++; } }
echo "Upserted $c27 public gap rows.\n";

// ---- Reseller booking messages ----
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
foreach ([
    ['Saldo tidak cukup. Butuh ', 'en', 'Insufficient balance. Need '],
    ['Saldo tidak cukup. Butuh ', 'zh', '余额不足。需要 '],
    [', saldo Anda ', 'en', ', your balance '],
    [', saldo Anda ', 'zh', '，您的余额 '],
    ['Gagal memotong saldo. Silakan coba lagi.', 'en', 'Failed to deduct balance. Please try again.'],
    ['Gagal memotong saldo. Silakan coba lagi.', 'zh', '扣减余额失败，请重试。'],
    ['Slot tidak cukup. Silakan pilih tanggal lain.', 'en', 'Not enough slots. Please choose another date.'],
    ['Slot tidak cukup. Silakan pilih tanggal lain.', 'zh', '名额不足，请选择其他日期。'],
] as [$k, $l, $v]) { $stmt->execute([$k, $l, $v]); }
echo "Added reseller-booking message rows.\n";

// ---- Admin tours/appearance/currency + SEO tagline ----
$miscGaps = [
    'en' => [
        'Tambah Tour Baru' => 'Add New Tour',
        'Belum ada tour' => 'No tours yet',
        'Kurs berhasil diperbarui: IDR=%s, SGD=%s, USD=%s' => 'Exchange rates updated: IDR=%s, SGD=%s, USD=%s',
        'Homepage menonjolkan hotel: pencarian menginap dominan, deal hotel terbaik, hotel per kota.' => 'Homepage highlights hotels: dominant stay search, best hotel deals, hotels by city.',
        'Halaman lain (tour/hotel/flight detail) tidak berubah.' => 'Other pages (tour/hotel/flight detail) are unchanged.',
        'TourAndTravel — paket tour, hotel, tiket pesawat, dan aktivitas wisata terbaik dengan harga transparan.' => 'TourAndTravel — the best tour packages, hotels, flights, and travel activities at transparent prices.',
    ],
    'zh' => [
        'Tambah Tour Baru' => '添加新旅游',
        'Belum ada tour' => '暂无旅游',
        'Kurs berhasil diperbarui: IDR=%s, SGD=%s, USD=%s' => '汇率已更新：IDR=%s、SGD=%s、USD=%s',
        'Homepage menonjolkan hotel: pencarian menginap dominan, deal hotel terbaik, hotel per kota.' => '首页突出酒店：以住宿搜索为主、最优酒店优惠、按城市浏览酒店。',
        'Halaman lain (tour/hotel/flight detail) tidak berubah.' => '其他页面（旅游/酒店/航班详情）保持不变。',
        'TourAndTravel — paket tour, hotel, tiket pesawat, dan aktivitas wisata terbaik dengan harga transparan.' => 'TourAndTravel — 最优旅游套餐、酒店、机票和旅行活动，价格透明。',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c28 = 0;
foreach ($miscGaps as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c28++; } }
echo "Upserted $c28 misc gap rows.\n";

// ---- Final residual gaps ----
$finalGaps = [
    'en' => [
        'Data Penumpang Utama' => 'Primary Passenger Data',
        'Homepage menonjolkan penerbangan: form cari tiket dominan, promo & rute populer.' => 'Homepage highlights flights: dominant ticket search, promos & popular routes.',
        'Penerbangan berhasil dipesan (dengan add-on)! Booking ref: ' => 'Flight booked successfully (with add-on)! Booking ref: ',
        'Pelabuhan' => 'Port',
        'add-on gagal: ' => 'add-on failed: ',
        'tipe kamar tersedia' => 'room types available',
        'Booking Hotel' => 'Hotel Booking',
    ],
    'zh' => [
        'Data Penumpang Utama' => '主要乘客信息',
        'Homepage menonjolkan penerbangan: form cari tiket dominan, promo & rute populer.' => '首页突出机票：以机票搜索为主、促销和热门航线。',
        'Penerbangan berhasil dipesan (dengan add-on)! Booking ref: ' => '航班预订成功（含附加服务）！预订编号：',
        'Pelabuhan' => '港口',
        'add-on gagal: ' => '附加服务失败：',
        'tipe kamar tersedia' => '种房型可选',
        'Booking Hotel' => '酒店预订',
        'Reseller booking' => '分销商订单',
        'Topup History' => '充值历史',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c29 = 0;
foreach ($finalGaps as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c29++; } }
echo "Upserted $c29 final gap rows.\n";

// ---- Google sign-in alert ----
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
foreach ([
    ['Login Google gagal', 'en', 'Google login failed'],
    ['Login Google gagal', 'zh', 'Google 登录失败'],
] as [$k, $l, $v]) { $stmt->execute([$k, $l, $v]); }
echo "Added Google sign-in alert rows.\n";

// ---- Admin Singapay headings ----
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
foreach ([
    ['Pengaturan Singapay (Virtual Account)', 'en', 'Singapay Settings (Virtual Account)'],
    ['Pengaturan Singapay (Virtual Account)', 'zh', 'Singapay 设置（虚拟账户）'],
    ['Webhook Singapay:', 'en', 'Singapay Webhook:'],
    ['Webhook Singapay:', 'zh', 'Singapay Webhook：'],
] as [$k, $l, $v]) { $stmt->execute([$k, $l, $v]); }
echo "Added Singapay heading rows.\n";

// ---- Konten: hotels zh ----
$hotelZh = [
    1  => ['巴厘岛君悦大酒店', '位于努沙杜瓦的豪华海滨度假村，拥有无边际泳池和世界级水疗。'],
    2  => ['雅加达丽思卡尔顿酒店', '位于首都中心的五星级酒店，可欣赏城市天际线景观，设有高级餐厅。'],
    3  => ['雅加达凯宾斯基酒店', '位于印尼酒店环岛的地标酒店，豪华客房，可直达 Grand Indonesia 购物中心。'],
    4  => ['努沙杜瓦穆丽雅度假村', '全包式度假村，拥有私人海滩、高尔夫球场和 7 家餐厅。'],
    5  => ['泗水香格里拉大酒店', '市中心的五星级酒店，设有屋顶泳池和海景。'],
    6  => ['乌布四季酒店', '坐落于乌布丛林中的度假村，拥有私人别墅、无边际泳池和瑜伽。'],
    7  => ['日惹桑提卡高级酒店', '位置优越的四星级酒店，靠近马里奥波罗街，设施齐全。'],
    8  => ['巴淡阿斯顿酒店', '位于巴淡商务中心的现代酒店，设有泳池和健身房。'],
    9  => ['万隆宜必思尚品酒店', '万隆市中心的实惠酒店，色彩缤纷的设计，含免费早餐。'],
    10 => ['雅加达希尔顿花园酒店', '位于金三角区的商务酒店，靠近商场和写字楼。'],
    11 => ['巴淡景观海滩度假村', '海滨度假村，设有泳池、水疗和海景，适合家庭度假。'],
    12 => ['巴淡名古屋山酒店', '位于名古屋中心的便利酒店，靠近购物中心，客房现代舒适。'],
    13 => ['巴淡假日酒店', '名古屋的五星级酒店，设施齐全，设有屋顶泳池。'],
    14 => ['太平洋宫殿酒店', '巴淡市中心的实惠酒店，靠近港口和特产购物区。'],
    15 => ['泛太平洋贝斯特韦斯特高级酒店', '豪华度假村，设有高尔夫球场、奥林匹克泳池和 6 家餐厅。'],
    16 => ['雅加达婆罗浮屠酒店', '位于班登广场的历史酒店，拥有雅加达最大的泳池和广阔花园。'],
    17 => ['ARTOTEL 格罗拉史纳延酒店', '位于史纳延地区的艺术设计酒店，靠近 GBK 和 fX Sudirman。'],
    18 => ['曼腾阿雅杜塔酒店', '位于曼腾中心的酒店，可欣赏现代城市景观，设有屋顶泳池。'],
    19 => ['白宫精品酒店', '位于 SCBD 地区的精品酒店，内饰优雅，价格实惠。'],
    20 => ['雅加达中央公园铂尔曼酒店', '与中央公园购物中心一体的五星级酒店，可直达商场。'],
];
$hStmt = db()->prepare("UPDATE hotels SET name_zh = ?, description_zh = ? WHERE id = ?");
foreach ($hotelZh as $id => [$n, $d]) { $hStmt->execute([$n, $d, $id]); }
echo "Updated " . count($hotelZh) . " hotels with zh content.\n";

// ---- Admin hero-slide / collections ----
$adminSlideGaps = [
    'en' => [
        'JPG/PNG/WebP, maks 2MB. Rekomendasi 1920px lebar.' => 'JPG/PNG/WebP, max 2MB. Recommended 1920px width.',
        'Cari Sekarang' => 'Search Now',
    ],
    'zh' => [
        'JPG/PNG/WebP, maks 2MB. Rekomendasi 1920px lebar.' => 'JPG/PNG/WebP，最大 2MB。建议宽度 1920px。',
        'Cari Sekarang' => '立即搜索',
        'Slug' => '别名',
        'Item' => '项目',
        'tour' => '旅游',
    ],
];
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
$c30 = 0;
foreach ($adminSlideGaps as $lang => $dict) { foreach ($dict as $key => $value) { $stmt->execute([$key, $lang, $value]); $c30++; } }
echo "Upserted $c30 hero-slide/collection rows.\n";

// ---- EN-missing keys that already had zh ----
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
foreach ([
    ['Dengan memesan, Anda menyetujui syarat & ketentuan pemesanan ferry kami.', 'en', 'By booking, you agree to our ferry booking terms & conditions.'],
    ['Lanjutkan pembayaran di bawah untuk konfirmasi instan.', 'en', 'Continue payment below for instant confirmation.'],
    ['Peta tidak tersedia untuk item ini.', 'en', 'Map is not available for this item.'],
    ['Rating per Aspek', 'en', 'Rating per Aspect'],
    ['Tanggal Pergi', 'en', 'Departure Date'],
] as [$k, $l, $v]) { $stmt->execute([$k, $l, $v]); }
echo "Added 5 EN-missing rows.\n";

// ---- In-app notification strings ----
$stmt = db()->prepare("INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
foreach ([
    ['Pembayaran diterima', 'en', 'Payment received'], ['Pembayaran diterima', 'zh', '已收到付款'],
    ['telah dibayar.', 'en', 'has been paid.'], ['telah dibayar.', 'zh', '已支付。'],
    ['Status Topup', 'en', 'Topup Status'], ['Status Topup', 'zh', '充值状态'],
    ['telah disetujui. Saldo bertambah.', 'en', 'has been approved. Balance added.'], ['telah disetujui. Saldo bertambah.', 'zh', '已获批准，余额已增加。'],
    ['telah ditolak.', 'en', 'has been rejected.'], ['telah ditolak.', 'zh', '已被拒绝。'],
] as [$k, $l, $v]) { $stmt->execute([$k, $l, $v]); }
echo "Added in-app notification rows.\n";
