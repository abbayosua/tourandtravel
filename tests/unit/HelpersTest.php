<?php
/**
 * HelpersTest — validasi runner terhadap helper inti aplikasi.
 * getSetting/setSetting (settings DB), formatRupiah/formatNumber (locale),
 * formatDate (nama hari/bulan per bahasa).
 */

function testGetSettingReturnsDefaultWhenMissing() {
    assertEquals('fallback-value', getSetting('unit_test_missing_key_xyz', 'fallback-value'));
}

function testSetThenGetRoundtrip() {
    setSetting('unit_test_roundtrip', 'alpha');
    assertEquals('alpha', getSetting('unit_test_roundtrip', 'default'));
    // update via upsert
    setSetting('unit_test_roundtrip', 'beta');
    assertEquals('beta', getSetting('unit_test_roundtrip', 'default'));
    // cleanup
    db()->prepare("DELETE FROM settings WHERE setting_key = ?")->execute(['unit_test_roundtrip']);
    assertEquals('default', getSetting('unit_test_roundtrip', 'default'));
}

function testGetSettingEmptyValueFallsBackToDefault() {
    setSetting('unit_test_empty', '');
    assertEquals('dflt', getSetting('unit_test_empty', 'dflt'));
    db()->prepare("DELETE FROM settings WHERE setting_key = ?")->execute(['unit_test_empty']);
}

function testFormatRupiahSeparatorPerLocale() {
    $_SESSION['lang'] = 'id'; $_COOKIE['lang'] = 'id';
    assertSame('Rp 1.500.000', formatRupiah(1500000), 'ID pakai titik ribuan');
    $_SESSION['lang'] = 'en'; $_COOKIE['lang'] = 'en';
    assertSame('Rp 1,500,000', formatRupiah(1500000), 'EN pakai koma ribuan');
    // kembali ke id agar test lain tidak terkontaminasi
    $_SESSION['lang'] = 'id';
}

function testFormatNumberSeparatorPerLocale() {
    $_SESSION['lang'] = 'id'; $_COOKIE['lang'] = 'id';
    assertSame('12.345', formatNumber(12345));
    $_SESSION['lang'] = 'en'; $_COOKIE['lang'] = 'en';
    assertSame('12,345', formatNumber(12345));
    $_SESSION['lang'] = 'id';
}

function testFormatDateIndonesianVsEnglish() {
    $_SESSION['lang'] = 'id'; $_COOKIE['lang'] = 'id';
    assertSame('Selasa, 1 Desember 2026', formatDate('2026-12-01'));
    $_SESSION['lang'] = 'en'; $_COOKIE['lang'] = 'en';
    assertSame('Tuesday, 1 December 2026', formatDate('2026-12-01'));
    $_SESSION['lang'] = 'id';
}

function testFormatDateInvalidInputReturnsEmpty() {
    $_SESSION['lang'] = 'id';
    assertSame('', formatDate('bukan-tanggal'));
}

function testIsValidLangRegistry() {
    assertTrue(isValidLang('id'));
    assertTrue(isValidLang('en'));
    assertTrue(!isValidLang('fr'), 'bahasa tak terdaftar ditolak');
    assertTrue(!isValidLang(null));
}
