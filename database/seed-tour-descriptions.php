<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$seeds = [
63 => [
    'en' => "8D7N JIANGNAN HIGHLIGHTS INK-WASH JIANGNAN + WUZHEN WATER TOWN\n\nExperience the classic beauty of Shanghai, Hangzhou, Suzhou, Wuxi and Wuzhen on an 8-day, 7-night journey through the Jiangnan region.",
    'zh' => "8天7晚江南精华：水墨江南＋乌镇水乡\n\n体验上海、杭州、苏州、无锡和乌镇的经典之美，8天7晚深度游历江南地区。"
],
140 => [
    'en' => "Full-Day Small Group Tea Picking at Xixi Wetland Hangzhou + Longjing Village (Half-Day Option)\n\nHighlights:\n- Book for tomorrow\n- Free cancellation\n- Instant confirmation",
    'zh' => "杭州西溪湿地采茶一日游小团＋龙井村（可选半日）\n\n亮点：\n- 可预订明日\n- 免费取消\n- 即时确认"
],
143 => [
    'en' => "Spectrum of the Seas Cruise to South Korea from Shanghai by Royal Caribbean International\n\nHighlights:\n- Instant confirmation",
    'zh' => "皇家加勒比海洋光谱号上海出发韩国邮轮\n\n亮点：\n- 即时确认"
],
144 => [
    'en' => "Shanghai Huangpu River Night Tour (Michelin Hairy Crab Dinner Included)\n\nHighlights:\n- Book for tomorrow\n- Free cancellation",
    'zh' => "上海黄浦江夜游（含米其林大闸蟹晚餐）\n\n亮点：\n- 可预订明日\n- 免费取消"
],
148 => [
    'en' => "Half-Day Small Group Tour: Xian Terracotta Army\n\nHighlights:\n- Book for tomorrow\n- Private tour\n- Free cancellation\n- Instant confirmation",
    'zh' => "西安兵马俑半日小团游\n\n亮点：\n- 可预订明日\n- 私家团\n- 免费取消\n- 即时确认"
],
149 => [
    'en' => "5-Day Essential Tour: Xian Terracotta Warriors + Yellow Emperor Mausoleum + Hukou Waterfall\n\nHighlights:\n- Free cancellation\n- Instant confirmation",
    'zh' => "5日精华游：西安兵马俑＋黄帝陵＋壶口瀑布\n\n亮点：\n- 免费取消\n- 即时确认"
],
150 => [
    'en' => "Daming Palace Banquet Xian\n\nHighlights:\n- Free cancellation",
    'zh' => "西安大明宫宴\n\n亮点：\n- 免费取消"
],
151 => [
    'en' => "Klook Pick: 3-Day Pure Jiuzhaigou Huanglong with High-Speed Train from Chengdu (Mandarin Group)\n\nHighlights:\n- Free cancellation",
    'zh' => "Klook精选：成都出发九寨沟黄龙3日纯玩高铁团（中文团）\n\n亮点：\n- 免费取消"
],
152 => [
    'en' => "2-Day Boutique Tour: Siguniang Mountain & Bipenggou Valley Sichuan\n\nHighlights:\n- Book for tomorrow\n- Private tour\n- Free cancellation\n- Instant confirmation",
    'zh' => "四姑娘山毕棚沟四川2日精品游\n\n亮点：\n- 可预订明日\n- 私家团\n- 免费取消\n- 即时确认"
],
153 => [
    'en' => "Shu Palace Banquet: Immersive Shu Culture Dinner Show | Chunxi Road Chengdu\n\nHighlights:\n- Book for tomorrow\n- Up to 3 hours\n- Free cancellation",
    'zh' => "蜀宫宴·蜀文化沉浸式晚宴秀｜成都春熙路\n\n亮点：\n- 可预订明日\n- 最长3小时\n- 免费取消"
],
154 => [
    'en' => "Full-Day Guided Tour: Panda Base & Leshan Giant Buddha\n\nHighlights:\n- Book for tomorrow\n- Free cancellation\n- Instant confirmation",
    'zh' => "熊猫基地＋乐山大佛一日导览游\n\n亮点：\n- 可预订明日\n- 免费取消\n- 即时确认"
],
155 => [
    'en' => "Klook Pick: Jiuzhaigou Premium + Panda Base/Huanglong | Various Packages\n\nHighlights:\n- Free cancellation\n- Instant confirmation",
    'zh' => "Klook精选：九寨沟精品＋熊猫基地/黄龙｜多种套餐可选\n\n亮点：\n- 免费取消\n- 即时确认"
],
158 => [
    'en' => "One-Day Four-Star Cruise on Guilin Li River & Yulong Bamboo Raft\n\nHighlights:\n- Free cancellation\n- Instant confirmation",
    'zh' => "桂林漓江四星游船＋遇龙河竹筏一日游\n\n亮点：\n- 免费取消\n- 即时确认"
],
161 => [
    'en' => "5-Day VIP Zhangjiajie + Tianmen Mountain (Luxury Mountaintop Homestay Option)\n\nHighlights:\n- Book for tomorrow\n- Free cancellation\n- Instant confirmation",
    'zh' => "张家界＋天门山5日VIP游（可选山顶豪华民宿）\n\n亮点：\n- 可预订明日\n- 免费取消\n- 即时确认"
],
162 => [
    'en' => "Zhangjiajie 1 Day: Furong Town + Avatar, Tianmen OR Glass Bridge\n\nHighlights:\n- Book for tomorrow\n- Free cancellation\n- Instant confirmation",
    'zh' => "张家界一日：芙蓉镇＋阿凡达、天门或玻璃桥\n\n亮点：\n- 可预订明日\n- 免费取消\n- 即时确认"
],
166 => [
    'en' => "Private Tour: 6-Day Yunnan Dali Lijiang Shangri-La Holiday\n\nHighlights:\n- Book for tomorrow\n- Hotel pickup\n- Private tour\n- Private group\n- Instant confirmation",
    'zh' => "私家团：云南大理丽江香格里拉6日游\n\n亮点：\n- 可预订明日\n- 酒店接送\n- 私家团\n- 私人小团\n- 即时确认"
],
171 => [
    'en' => "Ice and Snow Fantasy A | 7-Day Harbin, Changbai Mountain, Xuexiang, Yanji Northeast Tour\n\nHighlights:\n- Private tour",
    'zh' => "冰雪奇缘A｜哈尔滨、长白山、雪乡、延吉东北7日游\n\n亮点：\n- 私家团"
],
174 => [
    'en' => "1-Day Trip to Yabuli Ski & Snow Village\n\nHighlights:\n- Small group\n- Free cancellation\n- Instant confirmation",
    'zh' => "亚布力滑雪＋雪乡一日游\n\n亮点：\n- 小团\n- 免费取消\n- 即时确认"
],
179 => [
    'en' => "Century Cruises: Luxury Yangtze Three Gorges Cruise 4D3N/5D4N from Chongqing/Yichang\n\nHighlights:\n- Afternoon/evening departure",
    'zh' => "世纪游轮：长江三峡豪华游轮4天3晚/5天4晚（重庆/宜昌出发）\n\n亮点：\n- 下午/晚间出发"
],
];

$n = 0;
foreach ($seeds as $id => $s) {
    $stmt = db()->prepare('UPDATE tours SET description_en = ?, description_zh = ? WHERE id = ?');
    $stmt->execute([$s['en'], $s['zh'], $id]);
    $n++;
    echo "seeded $id\n";
}
echo "DONE: $n\n";
