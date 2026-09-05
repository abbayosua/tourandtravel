<?php
/**
 * SEO helpers — meta description, Open Graph, canonical, JSON-LD.
 * Pemakaian di header: seoHead($metaDesc, $ogImage, $jsonLd)
 */
function seoCanonical(): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return BASE_URL . $uri;
}

function seoHead(?string $metaDesc = null, ?string $ogImage = null, ?array $jsonLd = null): void {
    $desc = $metaDesc ?: t('TourAndTravel — paket tour, hotel, tiket pesawat, dan aktivitas wisata terbaik dengan harga transparan.');
    $canon = seoCanonical();
    $ogImg = $ogImage ?: BASE_URL . '/assets/img/og-default.jpg';
    echo '<meta name="description" content="' . e(mb_substr($desc, 0, 160)) . '">' . "\n";
    echo '<link rel="canonical" href="' . e($canon) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:title" content="' . e($pageTitle ?? SITE_NAME) . '">' . "\n";
    echo '<meta property="og:description" content="' . e(mb_substr($desc, 0, 160)) . '">' . "\n";
    echo '<meta property="og:image" content="' . e($ogImg) . '">' . "\n";
    echo '<meta property="og:url" content="' . e($canon) . '">' . "\n";
    if ($jsonLd) {
        echo '<script type="application/ld+json">' . json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
}

function seoOrganization(): array {
    return [
        '@context' => 'https://schema.org',
        '@type' => 'TravelAgency',
        'name' => SITE_NAME,
        'url' => BASE_URL,
    ];
}

function seoTour(array $tour): array {
    return [
        '@context' => 'https://schema.org',
        '@type' => 'TouristTrip',
        'name' => tContent($tour, 'title'),
        'description' => mb_substr((string)tContent($tour, 'description'), 0, 300),
        'url' => BASE_URL . '/tour-detail.php?slug=' . $tour['slug'],
        'image' => getTourImage($tour, 'medium'),
        'offers' => ['@type' => 'Offer', 'price' => (float)$tour['price'], 'priceCurrency' => 'IDR'],
    ];
}

function seoHotel(array $hotel): array {
    return [
        '@context' => 'https://schema.org',
        '@type' => 'Hotel',
        'name' => tContent($hotel, 'name'),
        'description' => mb_substr((string)tContent($hotel, 'description'), 0, 300),
        'url' => BASE_URL . '/hotel-detail.php?slug=' . $hotel['slug'],
        'starRating' => ['@type' => 'Rating', 'ratingValue' => (int)$hotel['star_rating']],
        'priceRange' => formatRupiah($hotel['price_per_night']),
    ];
}
