# Laporan Terjemahan Paket Tour — DB `tourandtravel`

Diambil: langsung dari tabel `tours` (kolom `title`/`description` + varian `_en` dan `_zh`).
Mekanisme situs: `tContent($tour,'field')` → pakai kolom `{field}_{lang}`, fallback ke kolom asli (ID) bila kosong.

## Ringkasan

| Kategori | Jumlah |
|---|---|
| Total paket tour | 59 |
| Aktif (published) | 20 |
| Nonaktif (draft) | 39 |
| Aktif & punya terjemahan EN + ZH | 20 (semua yang aktif) |
| Nonaktif & punya terjemahan | 0 |

Catatan: **semua 20 tour aktif sudah punya terjemahan EN + ZH** (minimal judul + deskripsi).
**Semua 39 tour nonaktif belum punya terjemahan** (hanya bahasa dasar).

## A. Tour AKTIF — sudah ada terjemahan ID + EN + ZH (19)

Bahasa dasar `id` (Indonesia). Kolom `_en` & `_zh` terisi.

| ID | Indonesia | English | 中文 |
|---|---|---|---|
| 131 | Beijing Qushui Lanting \| Cabang Sihui | Beijing Qushui Lanting \| Sihui Branch | 北京曲水兰亭｜四会分店 |
| 140 | Tur Sehari Penuh Kelompok Kecil Memetik Teh di Lahan Basah Xixi Hangzhou + Desa Longjing (Pilihan Setengah Hari Tersedia) | Full-Day Small Group Tea Picking at Xixi Wetland Hangzhou + Longjing Village (Half-Day Option) | 杭州西溪湿地采茶一日游小团＋龙井村（可选半日） |
| 143 | Pelayaran Spectrum of the Seas ke Korea Selatan dari Shanghai oleh Royal Caribbean International | Spectrum of the Seas Cruise to South Korea from Shanghai by Royal Caribbean | 皇家加勒比海洋光谱号上海出发韩国邮轮 |
| 144 | Tur Malam Sungai Huangpu Shanghai (termasuk makan malam kepiting berbulu Michelin) | Shanghai Huangpu River Night Tour (Michelin Hairy Crab Dinner Included) | 上海黄浦江夜游（含米其林大闸蟹晚餐） |
| 148 | Tur Grup Kecil Setengah Hari Xi'an Terracotta Army | Half-Day Small Group Tour: Xian Terracotta Army | 西安兵马俑半日小团游 |
| 149 | Tur 5 Hari Esensial Prajurit Terakota Xi'an + Mausoleum Kaisar Kuning + Air Terjun Hukou | 5-Day Essential Tour: Xian Terracotta Warriors + Yellow Emperor Mausoleum + Hukou Waterfall | 5日精华游：西安兵马俑＋黄帝陵＋壶口瀑布 |
| 150 | Perjamuan Istana Daming Xi'an | Daming Palace Banquet Xian | 西安大明宫宴 |
| 151 | Pilihan KLOOK\|Tur 3 Hari Murni Jiuzhaigou Huanglong dengan Kereta Cepat Berkualitas Tinggi (Berangkat dari Chengdu・Grup Berbahasa Mandarin) | Klook Pick: 3-Day Pure Jiuzhaigou Huanglong with High-Speed Train from Chengdu (Mandarin Group) | Klook精选：成都出发九寨沟黄龙3日纯玩高铁团（中文团） |
| 152 | Tur 2 Hari Butik Gunung Siguniang Lembah Bipenggou Sichuan | 2-Day Boutique Tour: Siguniang Mountain & Bipenggou Valley Sichuan | 四姑娘山毕棚沟四川2日精品游 |
| 153 | Perjamuan Istana Shu · Pertunjukan Makan Malam Imersif Budaya Shu \| Jalan Chunxi Chengdu | Shu Palace Banquet: Immersive Shu Culture Dinner Show \| Chunxi Road Chengdu | 蜀宫宴·蜀文化沉浸式晚宴秀｜成都春熙路 |
| 154 | Tur Berpemandu Sehari Penuh Panda Base & Leshan Giant Buddha | Full-Day Guided Tour: Panda Base & Leshan Giant Buddha | 熊猫基地＋乐山大佛一日导览游 |
| 155 | 【Pilihan Klook】Jiuzhaigou Premium + Pangkalan Panda/Huanglong \| Tersedia Berbagai Paket | Klook Pick: Jiuzhaigou Premium + Panda Base/Huanglong \| Various Packages | Klook精选：九寨沟精品＋熊猫基地/黄龙｜多种套餐可选 |
| 158 | Perjalanan Sehari Kapal Bintang Empat Sungai Li Guilin & Rakit Bambu Sungai Yulong | One-Day Four-Star Cruise on Guilin Li River & Yulong Bamboo Raft | 桂林漓江四星游船＋遇龙河竹筏一日游 |
| 161 | Tur VIP 5 Hari Zhangjiajie + Gunung Tianmen (Pilihan Menginap di Homestay Mewah di Puncak Gunung) | 5-Day VIP Zhangjiajie + Tianmen Mountain (Luxury Mountaintop Homestay Option) | 张家界＋天门山5日VIP游（可选山顶豪华民宿） |
| 162 | Zhangjiajie 1 Hari: Kota Furong + Avatar, Tianmen ATAU Jembatan Kaca | Zhangjiajie 1 Day: Furong Town + Avatar, Tianmen OR Glass Bridge | 张家界一日：芙蓉镇＋阿凡达、天门或玻璃桥 |
| 166 | 【Tur Pribadi】Liburan 6 Hari di Yunnan Dali Lijiang Shangri-La | Private Tour: 6-Day Yunnan Dali Lijiang Shangri-La Holiday | 私家团：云南大理丽江香格里拉6日游 |
| 171 | Fantasi Es dan Salju A \| Tur 7 Hari Harbin, Changbai Mountain, Xuexiang, Yanji di Timur Laut | Ice and Snow Fantasy A \| 7-Day Harbin, Changbai Mountain, Xuexiang, Yanji Northeast Tour | 冰雪奇缘A｜哈尔滨、长白山、雪乡、延吉东北7日游 |
| 174 | Tur 1 hari ke Yabuli Ski & Desa Salju | 1-Day Trip to Yabuli Ski & Snow Village | 亚布力滑雪＋雪乡一日游 |
| 179 | Century Cruises\|Kapal Pesiar Mewah Tiga Ngarai Yangtze 4 Hari 3 Malam/5 Hari 4 Malam (Berangkat dari Chongqing/Yichang) | Century Cruises: Luxury Yangtze Three Gorges Cruise 4D3N/5D4N from Chongqing/Yichang | 世纪游轮：长江三峡豪华游轮4天3晚/5天4晚（重庆/宜昌出发） |

## B. Tour AKTIF — bahasa dasar English, terjemahan EN + ZH (1, tanpa Indonesia)

| ID | English (dasar) | 中文 | Indonesia |
|---|---|---|---|
| 63 | 8D7N Shanghai Jiangnan Highlights: Ink-Wash Jiangnan + Wuzhen Water Town | 8天7晚上海江南精华：水墨江南＋乌镇水乡 | (belum ada) |

## C. Kelengkapan per-field untuk tour aktif

Field yang **paling sering kosong** (belum diterjemahkan):

| Field | EN terisi | ZH terisi |
|---|---|---|
| title | 20/20 | 20/20 |
| description | 20/20 | 19/20 (id 131 kosong) |
| includes | 1/20 (id 131) | 20/20 |
| excludes | 1/20 (id 131) | 20/20 |
| highlights | 5/20 (63,131,140,143,144) | 5/20 (63,131,140,143,144) |
| category | 0/20 | 0/20 |
| location_city | 0/20 | 0/20 |
| flight_info / meeting_point / important_notes | 1/20 (id 131) | — |

Kesimpulan: **tidak ada satu pun tour yang 100% lengkap di semua field**. Yang paling mendekati: **id 131** (hanya kurang `description_zh`, `category_*`, `location_city_*`).

## D. Tour NONAKTIF — belum ada terjemahan (39)

Bahasa dasar saja, kolom `_en`/`_zh` kosong.

| ID | Bahasa dasar | Judul |
|---|---|---|
| 61 | en | 8D HUNAN ZHANGJIAJIE + FENGHUANG ANCIENT TOWN + FURONG TOWN |
| 62 | en | 6D TOKYO WONDERS |
| 64 | en | 11D9N AMAZING NEW ZEALAND |
| 65 | en | 7D6N YICHUN SPECIAL TOUR TO XIAOXING'AN MOUNTAIN |
| 66 | en | 12D WONDERS OF TÜRKİYE + GUANGZHOU |
| 67 | en | 8D7N WINTER FUN IN CENTRAL HOKKAIDO |
| 68 | en | 8D7N SNOWY WINTER IN NORTHERN HOKKAIDO |
| 115 | en | 北京经典之旅 5天4晚 |
| 132 | id | Tur Sehari Mutianyu Great Wall+Summer Palace/Yuanmingyuan Garden |
| 133 | id | Tur Sehari Beijing Mutianyu Great Wall & Forbidden City |
| 134 | id | Tur 2 Hari Forbidden City & Great Wall Beijing dengan Sorotan Utama |
| 135 | id | Tur Pribadi Tembok Besar Mutianyu + Istana Musim Panas/Tembok Besar Huanghuacheng di Atas Air/Makam Ming |
| 136 | id | Qushui Lanting \| Toko Hangzhou Chengdong |
| 137 | id | Tur Mewah 6 Hari Shanghai + Nanjing + Wuxi + Suzhou + Hangzhou + Wuzhen |
| 138 | id | Tur Sehari Hangzhou West Lake & Lingyin Temple dengan Tiket |
| 139 | id | Tur kelompok Nanjing+Wuxi+Suzhou+Wuzhen+Hangzhou 4/6 hari |
| 141 | id | Klook Outbound Custom Travel (Turkey) |
| 142 | id | Eksplorasi Sehari Penuh Shanghai Yu Garden & Zhujiajiao Water Town |
| 145 | id | Tur Kota Kuno Setengah Hari Shanghai Zhujiajiao dengan Transfer |
| 146 | id | Tur Klook yang Disesuaikan di Xi'an, Shaanxi (Terracotta Army/Pagoda Angsa Liar/Istana Huaqing/Gunung Hua/Air Terjun Hukou) |
| 147 | id | Tur Setengah Hari Pasukan Terakota Xi'an dengan Makan Siang Prasmanan |
| 156 | id | Klook Perjalanan yang Disesuaikan Secara Pribadi di Guangxi, Tiongkok (Nanning/Guilin/Beihai/Chongzuo/Liuzhou/Baise) |
| 157 | id | Tur pribadi 5 hari di Guilin, Sungai Li, Sawah Terasering Longji, dan Yangshuo |
| 159 | id | Arung Jeram Bambu Sungai Yulong & Tur Sehari Gunung Xiangong |
| 160 | id | Perjalanan 1 Hari dari Guilin ke Terasering Longji, Desa Zhuang Ping'an, Desa Changfa, Terasering Jinkeng |
| 163 | id | Tur 2 Hari Taman Nasional & Gunung Tianmen Zhangjiajie |
| 164 | id | Tur 5 Hari Zhangjiajie Hunan yang Nyaman (Saluran VIP Opsional + Kelompok Kecil 6 Orang + Taman Hutan + Jembatan Kaca) |
| 165 | id | Qixing Mountain & Via Ferrata Full-Day Private Tour |
| 167 | id | Tur Pribadi 8 Hari Yunnan Kunming Dali Lijiang Lugu Lake Shangri-La |
| 168 | id | Tur 10 Hari Yunnan Kunming, Dali, Lijiang, Shangri-La, Lugu Lake |
| 169 | id | Tur Sehari Penuh Lijiang Jade Dragon Snow Mountain & Blue Moon Valley dengan Wahana Kereta Gantung |
| 170 | id | Pengalaman Studi Warisan Budaya Takbenda Memetik dan Membuat Teh di Gunung Cangshan, Dali |
| 172 | id | Tur Pribadi 5 Hari Harbin, Desa Salju, Yabuli, Hengdaohezi |
| 173 | id | Tur Pribadi 6 Hari Harbin Snow Town Changbai Mountain Yanji (termasuk hotel mata air panas) |
| 175 | id | Tur Ski Sehari Harbin-Yabuli \| Termasuk Bus Langsung + Peralatan Ski + Ski Sepanjang Hari |
| 176 | id | Chongqing Wulong Tiansheng Sanqiao + Longshui Gorge Ground Fissure/Xiannyu Mountain 2+1 Tur Bus Pengasuh |
| 177 | id | Chongqing Tiansheng Three Bridges & Fairy Mountain Day Tour |
| 178 | id | Chongqing Tiansheng Three Bridges & Xiannyushan Small Group Tour |
| 180 | en | Chongqing Wulong Karst National Park Day Tour |
