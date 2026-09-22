<?php
/**
 * includes/insurance.php — Fase 4: travel insurance add-on (flat internal).
 * Premi = 3% × total harga booking, dibulatkan ke ratusan terdekat.
 * Disimpan ke booking_addons (type='insurance'), idempotent via UNIQUE key.
 */

const INSURANCE_RATE = 0.03;

/** Hitung premi asuransi dari total. Return float (bulat ke ratusan). */
function calculateInsurancePremium(float $total): float {
    if ($total <= 0) return 0.0;
    $premi = $total * INSURANCE_RATE;
    return round($premi / 100) * 100;
}

/** Simpan add-on insurance untuk booking (idempotent — replace jika sudah ada). */
function addInsuranceAddon(string $bookingType, int $bookingId, float $premi, string $meta = ''): bool {
    if ($premi <= 0) return false;
    $stmt = db()->prepare("INSERT INTO booking_addons (booking_type, booking_id, type, amount, meta)
        VALUES (?, ?, 'insurance', ?, ?)
        ON DUPLICATE KEY UPDATE amount = VALUES(amount), meta = VALUES(meta)");
    return $stmt->execute([$bookingType, $bookingId, $premi, $meta]);
}

/** Ambil premi insurance suatu booking (0 jika tidak ada). */
function getInsuranceAddon(string $bookingType, int $bookingId): float {
    $stmt = db()->prepare("SELECT amount FROM booking_addons WHERE booking_type = ? AND booking_id = ? AND type = 'insurance'");
    $stmt->execute([$bookingType, $bookingId]);
    return (float)($stmt->fetchColumn() ?: 0);
}

/** Hapus add-on insurance (mis. saat booking dibatalkan). */
function removeInsuranceAddon(string $bookingType, int $bookingId): bool {
    $stmt = db()->prepare("DELETE FROM booking_addons WHERE booking_type = ? AND booking_id = ? AND type = 'insurance'");
    return $stmt->execute([$bookingType, $bookingId]);
}
