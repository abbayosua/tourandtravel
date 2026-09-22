<?php
/**
 * renderLiveHotelCard — kartu hotel untuk hasil LIVE (Booking.com/OYO/NusaTrip).
 * Bentuk data mengikuti normalisasi includes/hotelapi.php:
 *   ['source','external_id','name','star','price','currency','price_formatted',
 *    'image','lat','lng','address','desc','url']
 *
 * @param array  $h        Hotel ternormalisasi
 * @param string $city     Kota pencarian (untuk halaman detail live)
 * @param string $checkin
 * @param string $checkout
 * @param int    $guests
 */
function renderLiveHotelCard(array $h, string $city = '', string $checkin = '', string $checkout = '', int $guests = 2): void {
    $name = (string)($h['name'] ?? '');
    $img = $h['image'] ?: 'https://placehold.co/640x480?text=' . urlencode($name);
    $star = (int)($h['star'] ?? 0);
    $price = $h['price_formatted'] ?? ($h['price'] ? formatRupiah((float)$h['price']) : '-');
    $sourceLabel = ['oyorooms' => 'OYO', 'nusatrip' => 'NusaTrip', 'booking' => 'Booking.com'][$h['source'] ?? ''] ?? ($h['source'] ?? '');
    $detailParams = http_build_query(array_filter([
        'live' => 1,
        'src' => $h['source'] ?? '',
        'city' => $city,
        'id' => $h['external_id'] ?? '',
        'checkin' => $checkin,
        'checkout' => $checkout,
        'guests' => $guests,
    ], fn($v) => $v !== '' && $v !== null));
    ?>
    <div class="card border-0 shadow-sm mb-3 overflow-hidden klook-hover-card position-relative">
        <?php if ($sourceLabel): ?>
            <span class="badge bg-dark position-absolute top-0 start-0 m-2" style="z-index:5;font-size:10px;"><?= e($sourceLabel) ?></span>
        <?php endif; ?>
        <div class="row g-0">
            <div class="col-md-3 col-4" style="min-height: 160px;">
                <img src="<?= e($img) ?>" class="w-100 h-100" style="object-fit: cover;" alt="<?= e($name) ?>" loading="lazy"
                     onerror="this.src='https://placehold.co/640x480?text=<?= urlencode($name) ?>'">
            </div>
            <div class="col-md-9 col-8">
                <div class="card-body p-3 d-flex flex-column h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="fw-semibold mb-1"><?= e($name) ?></h6>
                            <?php if ($star > 0): ?><div class="small text-warning mb-1"><?= str_repeat('★', $star) ?></div><?php endif; ?>
                            <?php if (!empty($h['address'])): ?>
                                <small class="text-muted d-block"><i class="bi bi-geo-alt me-1"></i><?= e(mb_substr((string)$h['address'], 0, 80)) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold text-primary fs-5" data-testid="card-price"><?= e($price) ?></span>
                            <small class="d-block text-muted" style="font-size: 11px;"><?= t('/malam') ?></small>
                        </div>
                    </div>
                    <div class="mt-auto pt-2 d-flex gap-2">
                        <a href="hotel-detail.php?<?= e($detailParams) ?>" class="btn btn-primary rounded-pill px-4 py-1" style="font-size: 13px;"><?= t('Lihat') ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
