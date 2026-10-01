<?php
/**
 * seed-tour-i18n-complete.php — Lengkapi terjemahan tour (idempotent).
 * - Perbaiki kategori yang salah + isi category_en / category_zh
 * - Isi highlights_en / highlights_zh, includes_en, excludes_en yang kosong
 * - Isi description_zh tour 131
 * - Tambah/perbaiki key UI di tabel translations (label fasilitas, header, dll)
 * Jalankan: php database/seed-tour-i18n-complete.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = db();

// ── 1. Kategori: [kategori_baru, category_en, category_zh] ────────────────
$cat = [
    63  => ['China', 'China', '中国'],
    131 => ['Beijing', 'Beijing', '北京'],
    140 => ['Hangzhou', 'Hangzhou', '杭州'],
    143 => ['Shanghai', 'Shanghai', '上海'],
    144 => ['Shanghai', 'Shanghai', '上海'],
    148 => ["Xi'an", "Xi'an", '西安'],
    149 => ["Xi'an", "Xi'an", '西安'],
    150 => ["Xi'an", "Xi'an", '西安'],
    151 => ['Chengdu', 'Chengdu', '成都'],
    152 => ['Chengdu', 'Chengdu', '成都'],
    153 => ['Chengdu', 'Chengdu', '成都'],
    154 => ['Chengdu', 'Chengdu', '成都'],
    155 => ['Chengdu', 'Chengdu', '成都'],
    158 => ['Guilin', 'Guilin', '桂林'],
    161 => ['Zhangjiajie', 'Zhangjiajie', '张家界'],
    162 => ['Zhangjiajie', 'Zhangjiajie', '张家界'],
    166 => ['Yunnan', 'Yunnan', '云南'],
    171 => ['Harbin', 'Harbin', '哈尔滨'],
    174 => ['Harbin', 'Harbin', '哈尔滨'],
    179 => ['Chongqing', 'Chongqing', '重庆'],
];

$st = $db->prepare('UPDATE tours SET category=?, category_en=?, category_zh=? WHERE id=?');
foreach ($cat as $id => $c) {
    $st->execute([$c[0], $c[1], $c[2], $id]);
}
echo 'Kategori diperbarui: ' . count($cat) . "\n";

// ── 2. highlights_en untuk seluruh 20 tour aktif ─────────────────────────
$hlEn = [
    63  => "The Bund — Shanghai's iconic skyline on the Huangpu River, most beautiful at night\nHangzhou West Lake — UNESCO lake with captivating lakeside scenery\nLongjing Tea Plantation — tea picking + Hanfu costume experience\nSuzhou Gardens — Couple's Retreat Garden (Ou Garden), a UNESCO heritage site\nWuzhen Water Town — timeless water town: canals, stone bridges, and Xizha Scenic Area\nJiangnan specialty cuisine — Dongpo Pork, Squirrel-shaped Mandarin Fish, Wuxi Boat Cuisine",
    131 => "Mutianyu Great Wall with round-trip cable car\nForbidden City and Tiananmen Square\nSummer Palace and Temple of Heaven\nAuthentic Peking duck dinner\nShopping at Wangfujing Street\nIndonesian-speaking tour leader",
    140 => "Xixi Wetland (UNESCO) with a traditional boat\nLongjing Village: tea picking straight from the plantation\nLearn the Longjing tea roasting process\nOptional Hanfu costume photo session\nMandarin/English-speaking tour guide",
    143 => "Luxury cruise Spectrum of the Seas by Royal Caribbean\nRoute Shanghai - Busan (Korea) - Shanghai\nShip facilities: FlowRider, rock climbing, Broadway show\nBusan visit: Jagalchi Market, Gamcheon Village\nComfortable cabin with sea view",
    144 => "The Bund at sunset with the Pudong skyline\nEvening cruise on the Huangpu River\nMichelin hairy crab dinner\nMandarin/English-speaking tour guide\nIconic photos of Oriental Pearl Tower and Shanghai Tower",
    148 => "Terracotta Army Museum (UNESCO World Heritage)\nPit 1: thousands of terracotta warriors in battle formation\nPit 2: cavalry and infantry\nPit 3: army headquarters\nBronze Chariot Museum: the emperor's bronze chariots\nMandarin/English-speaking tour guide",
    149 => "Terracotta Army Museum (UNESCO World Heritage)\nYellow Emperor Mausoleum (UNESCO)\nHukou Waterfall: the largest waterfall on the Yellow River\nXi'an City Wall: the finest ancient city wall in China\nMuslim Quarter and Bell Tower\nMandarin/English-speaking tour guide",
    150 => "Daming Palace Heritage Park: replica of a Tang Dynasty palace\nTraditional Tang Dynasty dance and music performance\nMulti-course imperial banquet\nAuthentic traditional atmosphere\nMandarin/English-speaking tour guide",
    151 => "Huanglong Scenic Area: colorful travertine pools (UNESCO)\nJiuzhaigou Valley (UNESCO World Heritage)\nNuorilang Waterfall, Five Flower Lake, Long Lake\nHigh-speed train Chengdu - Huanglong - Chengdu\nShuzheng Village and Reed Lake\nMandarin-speaking tour guide",
    152 => "Siguniang Mountain: China's Alps with snow-capped peaks\nChangping Valley: pine forests and alpine lakes\nBipenggou Valley: limestone formations and waterfalls\nSeasonal colorful forests\nMandarin-speaking tour guide",
    153 => "Chunxi Road Chengdu: a historic pedestrian street\nImmersive Shu Culture dinner show\nTraditional Sichuan dance, music, and theater\nMulti-course imperial banquet in the style of the Shu kings\nAuthentic traditional atmosphere\nMandarin/English-speaking tour guide",
    154 => "Chengdu Research Base of Giant Panda Breeding\nLeshan Giant Buddha (UNESCO World Heritage)\nThe largest Buddha statue in the world\nBoat to view the Buddha from the river\nRed pandas and baby pandas\nMandarin/English-speaking tour guide",
    155 => "Jiuzhaigou Valley (UNESCO World Heritage)\nNuorilang Waterfall, Five Flower Lake, Long Lake\nShuzheng Village and Reed Lake\nPrince Cingga Lake\nMandarin-speaking tour guide\nPackage options: Panda Base or Huanglong",
    158 => "Four-star cruise on the Li River (4 hours)\nIconic Guilin karst scenery\nTraditional bamboo raft on the Yulong River\nRice field and mountain views\nMandarin/English-speaking tour guide",
    161 => "Zhangjiajie National Forest Park (UNESCO)\nAvatar Hallelujah Mountain from the movie Avatar\nTianmen Mountain: Tianmen Cave and glass skywalk\nThe world's longest cable car\nLuxury mountaintop homestay\n5-star hotel (3 nights)",
    162 => "Zhangjiajie National Forest Park (UNESCO)\nAvatar Hallelujah Mountain from the movie Avatar\nChoice: Tianmen Mountain (Tianmen Cave + glass skywalk)\nOr the Glass Bridge\nGolden Whip Stream and Yuanjiajie\nMandarin/English-speaking tour guide",
    166 => "Dali Old Town and Erhai Lake (UNESCO)\nLijiang Old Town (UNESCO) and Mu Palace\nJade Dragon Snow Mountain and Blue Moon Valley\nShangri-La: Songzanlin Monastery (Tibetan Buddhism)\nPudacuo National Park\n4-star hotel (5 nights)",
    171 => "Harbin Ice and Snow World: ice palaces and giant ice sculptures\nChangbai Mountain (UNESCO): Tianchi (Heaven Lake)\nXuexiang (Snow Village): snow village with wooden houses\nYanji: Korean Folk Village and Border River\n4-star hotel (6 nights)\nMandarin-speaking tour guide",
    174 => "Yabuli Ski Resort: full-day skiing\nFull ski equipment (skis, boots, poles)\nSki instructor for beginners\nSnow Village: wooden houses covered in snow\nSnow tubing and photography\nMandarin/English-speaking tour guide",
    179 => "Luxury cruise by Century Cruises\nYangtze Three Gorges: Qutang, Wu, and Xiling Gorge\nOptional tour: Fengdu Ghost City or Shibaozhai\nOptional tour: Shennong Stream or Three Gorges Dam\nComfortable cabin with river view\nMandarin-speaking tour guide",
];
$st = $db->prepare('UPDATE tours SET highlights_en=? WHERE id=?');
foreach ($hlEn as $id => $v) $st->execute([$v, $id]);
echo 'highlights_en: ' . count($hlEn) . "\n";

// ── 3. highlights_zh yang masih kosong ───────────────────────────────────
$hlZh = [
    148 => "兵马俑博物馆（UNESCO世界遗产）\n一号坑：数千兵马俑列阵\n二号坑：骑兵与步兵\n三号坑：军队指挥部\n铜车马博物馆：秦始皇铜车马\n中文/英文导游",
    149 => "兵马俑博物馆（UNESCO世界遗产）\n黄帝陵（UNESCO）\n壶口瀑布：黄河上最大的瀑布\n西安城墙：中国最完整的古代城墙\n回民街与钟楼\n中文/英文导游",
    150 => "大明宫遗址公园：唐代宫殿复刻\n传统唐代歌舞表演\n宫廷多道式宴席\n地道的传统氛围\n中文/英文导游",
    151 => "黄龙风景区：五彩钙化池（UNESCO）\n九寨沟（UNESCO世界遗产）\n诺日朗瀑布、五花海、长海\n成都—黄龙—成都高铁\n树正寨与芦苇海\n中文导游",
    152 => "四姑娘山：中国的阿尔卑斯，雪峰林立\n长坪沟：松林与高山湖泊\n毕棚沟：石灰岩地貌与瀑布\n四季色彩斑斓的森林\n中文导游",
    153 => "成都春熙路：历史步行街\n蜀文化沉浸式晚宴秀\n四川传统舞蹈、音乐与戏曲\n蜀王宫廷多道式宴席\n地道的传统氛围\n中文/英文导游",
    154 => "成都大熊猫繁育研究基地\n乐山大佛（UNESCO世界遗产）\n世界最大的佛像\n乘船从江上观赏大佛\n小熊猫与熊猫幼崽\n中文/英文导游",
    155 => "九寨沟（UNESCO世界遗产）\n诺日朗瀑布、五花海、长海\n树正寨与芦苇海\n公主海\n中文导游\n套餐选择：熊猫基地或黄龙",
    158 => "漓江四星游船（4小时）\n桂林标志性喀斯特风光\n遇龙河传统竹筏\n田园与山峦景色\n中文/英文导游",
    161 => "张家界国家森林公园（UNESCO）\n电影《阿凡达》中的哈利路亚山\n天门山：天门洞与玻璃栈道\n世界最长索道\n山顶豪华民宿\n五星级酒店（3晚）",
    162 => "张家界国家森林公园（UNESCO）\n电影《阿凡达》中的哈利路亚山\n可选：天门山（天门洞+玻璃栈道）\n或玻璃桥\n金鞭溪与袁家界\n中文/英文导游",
    166 => "大理古城与洱海（UNESCO）\n丽江古城（UNESCO）与木府\n玉龙雪山与蓝月谷\n香格里拉：松赞林寺（藏传佛教）\n普达措国家公园\n四星级酒店（5晚）",
    171 => "哈尔滨冰雪大世界：冰宫与巨型冰雕\n长白山（UNESCO）：天池\n雪乡：木屋雪村\n延吉：朝鲜族民俗村与界河\n四星级酒店（6晚）\n中文导游",
    174 => "亚布力滑雪场：全天滑雪\n全套滑雪装备（雪板、雪靴、雪杖）\n初学者滑雪教练\n雪乡：覆雪木屋\n雪圈与摄影\n中文/英文导游",
    179 => "世纪游轮豪华游轮\n长江三峡：瞿塘峡、巫峡、西陵峡\n自选项目：丰都鬼城或石宝寨\n自选项目：神农溪或三峡大坝\n舒适客舱，江景尽览\n中文导游",
];
$st = $db->prepare('UPDATE tours SET highlights_zh=? WHERE id=?');
foreach ($hlZh as $id => $v) $st->execute([$v, $id]);
echo 'highlights_zh: ' . count($hlZh) . "\n";

// ── 4. includes_en ───────────────────────────────────────────────────────
$incEn = [
    63  => "Round-trip airfare + 25KG checked & 7KG cabin baggage\n4-5* hotel accommodation (twin/triple share) 7 nights as per itinerary\nAir-conditioned licensed tour bus transportation\nMeals as per program (7x breakfast, 6x lunch, 6x dinner)\nEntrance tickets to attractions as per itinerary (1st gate)\nTour leader + local Mandarin-speaking guide\n1 bottle mineral water/day/pax + travel insurance",
    140 => "Mandarin/English-speaking tour guide\nXixi Wetland entrance ticket\nTraditional boat at Xixi\nTea picking experience in Longjing\nLocal cuisine lunch\nAir-conditioned transport from meeting point",
    143 => "Spectrum of the Seas cruise ticket\nCabin accommodation on board\nAll meals on board (full board)\nAccess to ship facilities: pool, spa, fitness\nOptional tour in Busan",
    144 => "Mandarin/English-speaking tour guide\nHuangpu River night cruise ticket\nMichelin hairy crab dinner\nAir-conditioned transport from meeting point",
    148 => "Mandarin/English-speaking tour guide\nTerracotta Army Museum entrance ticket\nBronze Chariot Museum entrance ticket\nAir-conditioned transport from meeting point\nLight breakfast",
    149 => "Mandarin/English-speaking tour guide\nEntrance tickets to all attractions\n4-star hotel (4 nights)\nAir-conditioned transport\nDaily breakfast\nLocal dinner (2x)",
    150 => "Mandarin/English-speaking tour guide\nDaming Palace Heritage Park entrance ticket\nDance and music show ticket\nMulti-course imperial banquet\nAir-conditioned transport from meeting point",
    151 => "Mandarin-speaking tour guide\nHigh-speed train ticket Chengdu - Huanglong - Chengdu\nHuanglong and Jiuzhaigou entrance tickets\nHotel (2 nights)\nAir-conditioned transport\nDaily breakfast",
    152 => "Mandarin-speaking tour guide\nSiguniang Mountain and Bipenggou entrance tickets\nHotel (1 night)\nAir-conditioned transport\nDaily breakfast\nDinner on the first day",
    153 => "Mandarin/English-speaking tour guide\nImmersive dinner show ticket\nMulti-course imperial banquet\nAir-conditioned transport from meeting point",
    154 => "Mandarin/English-speaking tour guide\nPanda Base entrance ticket\nLeshan Giant Buddha entrance ticket\nBoat at Leshan\nLunch\nAir-conditioned transport",
    155 => "Mandarin-speaking tour guide\nJiuzhaigou entrance ticket\nHotel (2 nights)\nAir-conditioned transport\nDaily breakfast\nDinner on the first day",
    158 => "Mandarin/English-speaking tour guide\nFour-star Li River cruise ticket\nYulong River bamboo raft ticket\nLunch\nAir-conditioned transport from meeting point",
    161 => "Mandarin/English-speaking tour guide\nEntrance tickets to all attractions\n5-star hotel (3 nights)\nLuxury homestay (1 night)\nAir-conditioned transport\nDaily breakfast\nLocal dinner (2x)",
    162 => "Mandarin/English-speaking tour guide\nZhangjiajie National Forest Park entrance ticket\nOptional ticket: Tianmen Mountain or Glass Bridge\nLunch\nAir-conditioned transport from meeting point",
    166 => "Mandarin/English-speaking tour guide\nEntrance tickets to all attractions\n4-star hotel (5 nights)\nAir-conditioned transport\nDaily breakfast\nLocal dinner (2x)",
    171 => "Mandarin-speaking tour guide\nEntrance tickets to all attractions\n4-star hotel (6 nights)\nAir-conditioned transport\nDaily breakfast\nLocal dinner (3x)",
    174 => "Mandarin/English-speaking tour guide\nYabuli Ski Resort entrance ticket\nFull ski equipment\nSki instructor for beginners\nLunch\nAir-conditioned transport from meeting point",
    179 => "Mandarin-speaking tour guide\nCentury Cruises ship ticket\nCabin accommodation on board\nAll meals on board (full board)\nOptional shore excursions",
];
$st = $db->prepare('UPDATE tours SET includes_en=? WHERE id=?');
foreach ($incEn as $id => $v) $st->execute([$v, $id]);
echo 'includes_en: ' . count($incEn) . "\n";

// ── 5. excludes_en ───────────────────────────────────────────────────────
$excEn = [
    63  => "Guide, driver & tour leader tipping: IDR 850,000/pax/tour (mandatory)\nSingle supplement (if staying alone)\nPersonal expenses: laundry, mini bar, telephone, excess baggage\nOptional tours & costs outside the program\nTravel insurance for age >69 (surcharge)\nItems not mentioned in INCLUDE",
    140 => "Hotel and accommodation\nFlight tickets\nPersonal expenses\nGuide tipping (optional)\nTravel insurance",
    143 => "Flight tickets to/from Shanghai\nHotel in Shanghai\nPersonal expenses on board\nKorea visa (if required)\nShip crew tipping",
    144 => "Hotel and accommodation\nFlight tickets\nPersonal expenses\nGuide tipping (optional)\nTravel insurance",
    148 => "Hotel and accommodation\nFlight tickets\nLunch and dinner\nPersonal expenses\nGuide tipping (optional)",
    149 => "Flight tickets Jakarta - Xi'an - Jakarta\nLunch and dinner (except those mentioned)\nPersonal expenses\nGuide tipping (optional)\nTravel insurance",
    150 => "Hotel and accommodation\nFlight tickets\nPersonal expenses\nGuide tipping (optional)\nTravel insurance",
    151 => "Flight tickets to/from Chengdu\nLunch and dinner (except those mentioned)\nPersonal expenses\nGuide tipping (optional)\nTravel insurance",
    152 => "Flight tickets to/from Chengdu\nLunch and dinner (except those mentioned)\nPersonal expenses\nGuide tipping (optional)\nTravel insurance",
    153 => "Hotel and accommodation\nFlight tickets\nPersonal expenses\nGuide tipping (optional)\nTravel insurance",
    154 => "Hotel and accommodation\nFlight tickets\nPersonal expenses\nGuide tipping (optional)\nTravel insurance",
    155 => "Flight tickets to/from Chengdu\nLunch and dinner (except those mentioned)\nPersonal expenses\nGuide tipping (optional)\nTravel insurance",
    158 => "Hotel and accommodation\nFlight tickets\nPersonal expenses\nGuide tipping (optional)\nTravel insurance",
    161 => "Flight tickets Jakarta - Zhangjiajie - Jakarta\nLunch and dinner (except those mentioned)\nPersonal expenses\nGuide tipping (optional)\nTravel insurance",
    162 => "Hotel and accommodation\nFlight tickets\nPersonal expenses\nGuide tipping (optional)\nTravel insurance",
    166 => "Flight tickets Jakarta - Kunming - Shangri-La - Jakarta\nLunch and dinner (except those mentioned)\nPersonal expenses\nGuide tipping (optional)\nTravel insurance",
    171 => "Flight tickets Jakarta - Harbin - Yanji - Jakarta\nLunch and dinner (except those mentioned)\nPersonal expenses\nGuide tipping (optional)\nTravel insurance",
    174 => "Hotel and accommodation\nFlight tickets\nPersonal expenses\nGuide tipping (optional)\nTravel insurance",
    179 => "Flight tickets to/from Chongqing/Yichang\nHotel in Chongqing/Yichang\nPersonal expenses on board\nShip crew tipping\nTravel insurance",
];
$st = $db->prepare('UPDATE tours SET excludes_en=? WHERE id=?');
foreach ($excEn as $id => $v) $st->execute([$v, $id]);
echo 'excludes_en: ' . count($excEn) . "\n";

// ── 6. description_zh tour 131 ───────────────────────────────────────────
$desc131 = "在北京开启难忘的5日之旅，尽享这座古都之美。游览慕田峪长城、故宫、颐和园，体验地道的曲水兰亭。\n- 乘缆车游览慕田峪长城\n- 探索故宫与天安门广场\n- 品尝正宗北京烤鸭\n- 印尼语领队全程陪同";
$db->prepare('UPDATE tours SET description_zh=? WHERE id=131 AND (description_zh IS NULL OR description_zh=?)')->execute([$desc131, '']);
echo "description_zh 131: ok\n";

// ── 7. Key UI di tabel translations ──────────────────────────────────────
$uiKeys = [
    'Hotel Bintang 4'  => ['4-Star Hotel', '四星级酒店'],
    'Transport AC'     => ['Air-Conditioned Transport', '空调车'],
    'Makan Sesuai Itinerary' => ['Meals as per itinerary', '按行程用餐'],
    'Tour Guide Profesional' => ['Professional Tour Guide', '专业导游'],
    'Dokumentasi'      => ['Documentation', '摄影记录'],
    'Paket Termasuk'   => ['Package Includes', '套餐包含'],
    'Tidak Termasuk'   => ['Not Included', '不包含'],
    'Perlindungan pembatalan, keterlambatan, dan kehilangan barang.' => ['Cancellation, delay, and loss protection', '取消、延误及物品遗失保障'],
    'Simpan ke Itinerary Saya' => ['Save to My Itinerary', '保存到我的行程'],
    'Simpan ke Itinerary' => ['Save to Itinerary', '保存到行程'],
    'untuk menyimpan itinerary' => ['to save the itinerary', '保存行程'],
    'Silakan'          => ['Please', '请'],
    'Akun'             => ['Account', '账户'],
    'Home'             => ['Home', '首页'],
    'Subtotal'         => ['Subtotal', '小计'],
    'Loading...'       => ['Loading...', '加载中...'],
    'Tambah Asuransi Perjalanan (+3%)' => ['Add Travel Insurance (+3%)', '添加旅游保险 (+3%)'],
    'Tambah Asuransi Perjalanan' => ['Add Travel Insurance', '添加旅游保险'],
    // Label kategori (dipakai filter tours.php via t())
    'China' => ['China', '中国'],
    'Beijing' => ['Beijing', '北京'],
    'Shanghai' => ['Shanghai', '上海'],
    'Guangzhou' => ['Guangzhou', '广州'],
    'Chengdu' => ['Chengdu', '成都'],
    "Xi'an" => ["Xi'an", '西安'],
    'Hangzhou' => ['Hangzhou', '杭州'],
    'Zhangjiajie' => ['Zhangjiajie', '张家界'],
    'Chongqing' => ['Chongqing', '重庆'],
    'Guilin' => ['Guilin', '桂林'],
    'Shenzhen' => ['Shenzhen', '深圳'],
    'Yunnan' => ['Yunnan', '云南'],
    'Harbin' => ['Harbin', '哈尔滨'],
];
$st = $db->prepare('INSERT INTO translations (`key`, lang, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
$n = 0;
foreach ($uiKeys as $key => $tr) {
    $st->execute([$key, 'en', $tr[0]]);
    $st->execute([$key, 'zh', $tr[1]]);
    $n += 2;
}
echo "UI keys diupsert: $n\n";
echo "SELESAI\n";
