<?php
/**
 * seed-content-en.php — isi kolom _en untuk konten DB lokal (Fase 7).
 * Strategi: kamus terjemahan per-tabel (id => en) untuk data seed yang dikenal;
 * baris tanpa kamus dibiarkan NULL (fallback tContent() ke nilai asli — aman).
 * Idempotent: hanya UPDATE bila kolom _en masih NULL/kosong.
 *
 * Jalankan: php database/seed-content-en.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

function seedTable(string $table, string $idCol, array $fields, array $dict): void {
    $set = implode(', ', array_map(fn($f) => "`{$f}_en` = ?", $fields));
    foreach ($dict as $id => $translations) {
        $vals = [];
        foreach ($fields as $f) {
            $v = $translations[$f] ?? null;
            $vals[] = ($v === '' ? null : $v);
        }
        $check = db()->prepare("SELECT 1 FROM `$table` WHERE `$idCol` = ? AND (`{$fields[0]}_en` IS NULL OR `{$fields[0]}_en` = '')");
        $check->execute([$id]);
        if (!$check->fetch()) continue; // sudah terisi — skip (idempotent)
        $upd = db()->prepare("UPDATE `$table` SET $set WHERE `$idCol` = ?");
        $upd->execute([...$vals, $id]);
    }
    $cnt = db()->query("SELECT COUNT(*) c FROM `$table` WHERE `{$fields[0]}_en` IS NOT NULL AND `{$fields[0]}_en` != ''")->fetch()['c'] ?? 0;
    $tot = db()->query("SELECT COUNT(*) c FROM `$table`")->fetch()['c'] ?? 0;
    echo str_pad($table, 24) . ": $cnt/$tot baris ber-_en\n";
}

// ===================== HOTELS =====================
seedTable('hotels', 'id', ['name', 'description'], [
    1 => ['name' => 'Grand Hyatt Bali', 'description' => 'Luxury beachfront resort in Nusa Dua with lagoon pools and direct beach access.'],
    2 => ['name' => 'The Ritz-Carlton Jakarta, Mega Kuningan', 'description' => 'Five-star hotel in the heart of Jakarta business district with fine dining and spa.'],
    3 => ['name' => 'Hotel Indonesia Kempinski Jakarta', 'description' => 'Legendary landmark hotel on Jalan Thamrin with classic Indonesian hospitality.'],
    4 => ['name' => 'Mulia Resort Nusa Dua', 'description' => 'Grand resort on Nusa Dua beach, one of Bali\u2019s largest luxury destinations.'],
    5 => ['name' => 'Shangri-La Hotel Surabaya', 'description' => 'Elegant city hotel with lush gardens, adjacent to the packed Surabaya Zoo.'],
]);

// ===================== RENTAL CARS =====================
seedTable('rental_cars', 'id', ['name', 'description'], [
    1 => ['name' => 'Toyota Avanza', 'description' => 'Compact 7-seater MPV, ideal for city driving and small families.'],
    2 => ['name' => 'Toyota Innova', 'description' => 'Comfortable 7-seater MPV for long trips, spacious luggage room.'],
    3 => ['name' => 'Honda Brio', 'description' => 'Fuel-efficient city hatchback, easy to park, great for couples.'],
]);

// ===================== ATTRACTIONS =====================
seedTable('attractions', 'id', ['name', 'description'], [
    1 => ['name' => 'Taman Mini Indonesia Indah Entrance Ticket', 'description' => 'Culture-themed park showcasing Indonesian provinces, museums and cable car.'],
    2 => ['name' => 'Monas Observation Deck Ticket', 'description' => 'Skip-the-line ticket to the National Monument observation deck.'],
    3 => ['name' => 'Waterbom Bali Day Pass', 'description' => 'Full-day access to Asia\u2019s #1 waterpark with 20+ slides.'],
]);

// ===================== TRANSFERS =====================
seedTable('transfers', 'id', ['name', 'description'], [
    1 => ['name' => 'Soekarno-Hatta Airport to Jakarta City', 'description' => 'Private airport transfer to central Jakarta.'],
    2 => ['name' => 'Ngurah Rai Airport to Kuta & Seminyak', 'description' => 'Bali airport pickup to Kuta or Seminyak area.'],
    3 => ['name' => 'YIA Airport to Malioboro', 'description' => 'Transfer from Yogyakarta International Airport to Malioboro area.'],
]);

// ===================== TRAINS =====================
seedTable('trains', 'id', ['name', 'description'], [
    1 => ['name' => 'Argo Bromo Anggrek', 'description' => 'Premium executive service linking Jakarta Gambir and Surabaya Pasar Turi.'],
    2 => ['name' => 'Argo Parahyangan', 'description' => 'Executive service between Jakarta and Bandung.'],
    3 => ['name' => 'Taksaka', 'description' => 'Night train service from Jakarta to Yogyakarta.'],
]);

// ===================== eSIM / CONNECTIVITY =====================
seedTable('connectivity_products', 'id', ['name', 'description'], [
    1 => ['name' => 'Indonesia eSIM 5GB', 'description' => 'Instant eSIM for Indonesia with 5GB data, valid 7 days.'],
    2 => ['name' => 'Bali eSIM 10GB', 'description' => 'High-data eSIM for Bali area, active for 14 days.'],
    3 => ['name' => 'Traveloka SIM Card 5GB', 'description' => 'Physical SIM with 5GB data, delivered nationwide.'],
]);

// ===================== ITINERARIES (per tour, generik) =====================
// Itinerary lokal kosong; terjemahan akan mengikuti saat admin mengisi (opsional).
$itCount = db()->query("SELECT COUNT(*) c FROM itineraries")->fetch()['c'] ?? 0;
echo str_pad('itineraries', 24) . ": 0/$itCount (kosong — diisi saat konten dibuat)\n";

echo "Selesai.\n";
