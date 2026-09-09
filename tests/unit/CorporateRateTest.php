<?php
/**
 * CorporateRateTest — getCorporateDiscount / applyCorporateDiscount:
 * anggota aktif, bukan anggota, company nonaktif, clamp 0-100%, pembulatan.
 */

const CR_UID = 1;

function crCleanup() {
    db()->prepare("UPDATE users SET corporate_company_id = NULL WHERE id = ?")->execute([CR_UID]);
    db()->prepare("DELETE FROM corporate_companies WHERE name LIKE 'CR-TEST%'")->execute();
}

function crSeed(float $pct, int $active = 1): int {
    db()->prepare("INSERT INTO corporate_companies (name, discount_percent, is_active) VALUES (?, ?, ?)")->execute(['CR-TEST-' . random_int(1000, 9999), $pct, $active]);
    $id = (int)db()->lastInsertId();
    db()->prepare("UPDATE users SET corporate_company_id = ? WHERE id = ?")->execute([$id, CR_UID]);
    return $id;
}

function testCorporateDiscountMemberActive() {
    crCleanup();
    crSeed(10.0);
    assertEquals(10.0, getCorporateDiscount(CR_UID), 'anggota aktif → 10%');
    crCleanup();
}

function testCorporateDiscountNonMemberZero() {
    crCleanup();
    assertEquals(0.0, getCorporateDiscount(CR_UID), 'bukan anggota → 0');
    assertEquals(0.0, getCorporateDiscount(0), 'userId 0 → 0');
    assertEquals(0.0, getCorporateDiscount(99999999), 'userId tak ada → 0');
}

function testCorporateDiscountInactiveCompanyZero() {
    crCleanup();
    crSeed(15.0, 0);
    assertEquals(0.0, getCorporateDiscount(CR_UID), 'company nonaktif → 0');
    crCleanup();
}

function testApplyCorporateDiscountMath() {
    crCleanup();
    crSeed(10.0);
    assertEquals(900.0, applyCorporateDiscount(CR_UID, 1000.0), '1000 -10% = 900');
    assertEquals(4570.2, applyCorporateDiscount(CR_UID, 5078.0), '5078 -10% = 4570.2');
    crSeed(50.0);
    assertEquals(500.0, applyCorporateDiscount(CR_UID, 1000.0), '50% → 500');
    crCleanup();
}

function testApplyCorporateDiscountRounding() {
    crCleanup();
    crSeed(12.5);
    // 1234 * 0.875 = 1079.75
    assertEquals(1079.75, applyCorporateDiscount(CR_UID, 1234.0), '12.5% → round 2 desimal');
    crCleanup();
}

function testApplyNoDiscountWhenZeroPercent() {
    crCleanup();
    crSeed(0.0);
    assertEquals(1000.0, applyCorporateDiscount(CR_UID, 1000.0), '0% → harga tetap');
    crCleanup();
}

function testDiscountClampedToHundred() {
    crCleanup();
    crSeed(150.0);
    assertEquals(100.0, getCorporateDiscount(CR_UID), 'clamp maksimal 100%');
    assertEquals(0.0, applyCorporateDiscount(CR_UID, 1000.0), '100% → gratis');
    crCleanup();
}
