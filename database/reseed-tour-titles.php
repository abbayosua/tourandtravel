<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
$seeds = [
63 => ['en' => '8D7N Shanghai Jiangnan Highlights: Ink-Wash Jiangnan + Wuzhen Water Town', 'zh' => '8天7晚上海江南精华：水墨江南＋乌镇水乡'],
131 => ['en' => 'Beijing Qushui Lanting | Sihui Branch', 'zh' => '北京曲水兰亭｜四会分店'],
140 => ['en' => 'Full-Day Small Group Tea Picking at Xixi Wetland Hangzhou + Longjing Village (Half-Day Option)', 'zh' => '杭州西溪湿地采茶一日游小团＋龙井村（可选半日）'],
143 => ['en' => 'Spectrum of the Seas Cruise to South Korea from Shanghai by Royal Caribbean', 'zh' => '皇家加勒比海洋光谱号上海出发韩国邮轮'],
144 => ['en' => 'Shanghai Huangpu River Night Tour (Michelin Hairy Crab Dinner Included)', 'zh' => '上海黄浦江夜游（含米其林大闸蟹晚餐）'],
148 => ['en' => 'Half-Day Small Group Tour: Xian Terracotta Army', 'zh' => '西安兵马俑半日小团游'],
149 => ['en' => '5-Day Essential Tour: Xian Terracotta Warriors + Yellow Emperor Mausoleum + Hukou Waterfall', 'zh' => '5日精华游：西安兵马俑＋黄帝陵＋壶口瀑布'],
150 => ['en' => 'Daming Palace Banquet Xian', 'zh' => '西安大明宫宴'],
151 => ['en' => 'Klook Pick: 3-Day Pure Jiuzhaigou Huanglong with High-Speed Train from Chengdu (Mandarin Group)', 'zh' => 'Klook精选：成都出发九寨沟黄龙3日纯玩高铁团（中文团）'],
152 => ['en' => '2-Day Boutique Tour: Siguniang Mountain & Bipenggou Valley Sichuan', 'zh' => '四姑娘山毕棚沟四川2日精品游'],
153 => ['en' => 'Shu Palace Banquet: Immersive Shu Culture Dinner Show | Chunxi Road Chengdu', 'zh' => '蜀宫宴·蜀文化沉浸式晚宴秀｜成都春熙路'],
154 => ['en' => 'Full-Day Guided Tour: Panda Base & Leshan Giant Buddha', 'zh' => '熊猫基地＋乐山大佛一日导览游'],
155 => ['en' => 'Klook Pick: Jiuzhaigou Premium + Panda Base/Huanglong | Various Packages', 'zh' => 'Klook精选：九寨沟精品＋熊猫基地/黄龙｜多种套餐可选'],
158 => ['en' => 'One-Day Four-Star Cruise on Guilin Li River & Yulong Bamboo Raft', 'zh' => '桂林漓江四星游船＋遇龙河竹筏一日游'],
161 => ['en' => '5-Day VIP Zhangjiajie + Tianmen Mountain (Luxury Mountaintop Homestay Option)', 'zh' => '张家界＋天门山5日VIP游（可选山顶豪华民宿）'],
162 => ['en' => 'Zhangjiajie 1 Day: Furong Town + Avatar, Tianmen OR Glass Bridge', 'zh' => '张家界一日：芙蓉镇＋阿凡达、天门或玻璃桥'],
166 => ['en' => 'Private Tour: 6-Day Yunnan Dali Lijiang Shangri-La Holiday', 'zh' => '私家团：云南大理丽江香格里拉6日游'],
171 => ['en' => 'Ice and Snow Fantasy A | 7-Day Harbin, Changbai Mountain, Xuexiang, Yanji Northeast Tour', 'zh' => '冰雪奇缘A｜哈尔滨、长白山、雪乡、延吉东北7日游'],
174 => ['en' => '1-Day Trip to Yabuli Ski & Snow Village', 'zh' => '亚布力滑雪＋雪乡一日游'],
179 => ['en' => 'Century Cruises: Luxury Yangtze Three Gorges Cruise 4D3N/5D4N from Chongqing/Yichang', 'zh' => '世纪游轮：长江三峡豪华游轮4天3晚/5天4晚（重庆/宜昌出发）'],
];
$n = 0;
foreach ($seeds as $id => $s) {
    $stmt = db()->prepare('UPDATE tours SET title_en = ?, title_zh = ? WHERE id = ?');
    $stmt->execute([$s['en'], $s['zh'], $id]);
    $n++;
}
echo "DONE: $n\n";
