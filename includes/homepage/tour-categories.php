<!-- Category Grid (horizontal scroll) -->
<?php
$catIcons = ['Domestik' => '🇮🇩', 'Internasional' => '🌍', 'China' => '🇨🇳', 'Jepang' => '🇯🇵', 'Korea Selatan' => '🇰🇷', 'Vietnam' => '🇻🇳', 'Taiwan' => '🇹🇼', 'Kanada' => '🇨🇦'];
$catCounts = [];
foreach ($categories as $cat) {
    $c = db()->prepare("SELECT COUNT(*) FROM tours WHERE category = ? AND is_active = 1");
    $c->execute([$cat]);
    $catCounts[$cat] = (int)$c->fetchColumn();
}
renderCategoryGrid($categories, $catIcons, $catCounts);
?>

