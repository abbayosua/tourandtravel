<?php
/**
 * BrandTest — nama & logo custom dari Admin Panel (admin/brand-settings.php).
 * Regresi: siteName()/siteLogoUrl()/brandText() mengikuti settings.
 */

require_once __DIR__ . '/../../includes/email.php';

function brandTestBackup(): array {
    return [
        'site_name' => getSetting('site_name', ''),
        'site_logo' => getSetting('site_logo', ''),
        'site_tagline' => getSetting('site_tagline', ''),
    ];
}

function brandTestRestore(array $b): void {
    setSetting('site_name', $b['site_name']);
    setSetting('site_logo', $b['site_logo']);
    setSetting('site_tagline', $b['site_tagline']);
}

function testSiteNameFollowsSetting() {
    $b = brandTestBackup();
    try {
        setSetting('site_name', 'WisataKita');
        assertSame('WisataKita', siteName(), 'siteName ikut setting');
        assertContains('WisataKita', brandText('Liburan bareng TourAndTravel mantap'), 'brandText ganti brand lama');
        assertContains('WISATAKITA', brandText('TOURANDTRAVEL - hemat'), 'brandText ganti versi kapital');
        assertSame('Teks polos tanpa brand', brandText('Teks polos tanpa brand'), 'teks tanpa brand utuh');
    } finally { brandTestRestore($b); }
}

function testSiteNameFallbackWhenEmpty() {
    $b = brandTestBackup();
    try {
        setSetting('site_name', '');
        assertSame(SITE_NAME, siteName(), 'kosong → fallback konstanta');
        assertSame('Promo TourAndTravel!', brandText('Promo TourAndTravel!'), 'default tidak diubah');
    } finally { brandTestRestore($b); }
}

function testSiteLogoUrlEmptyByDefault() {
    $b = brandTestBackup();
    try {
        setSetting('site_logo', '');
        assertSame('', siteLogoPath(), 'path kosong bila belum dipasang');
        assertSame('', siteLogoUrl(), 'url kosong bila belum dipasang');
    } finally { brandTestRestore($b); }
}

function testSiteLogoPathRejectsTraversal() {
    $b = brandTestBackup();
    try {
        setSetting('site_logo', '../includes/config.php');
        assertSame('', siteLogoPath(), 'path traversal ditolak');
    } finally { brandTestRestore($b); }
}

function testEmailShellFollowsCustomBrand() {
    $b = brandTestBackup();
    try {
        setSetting('site_name', 'WisataKita');
        $t = renderEmailTemplate('welcome', [], 'id');
        assertContains('WisataKita', $t['html'], 'shell email ikut brand custom');
        assertContains('Selamat datang di WisataKita', $t['html'], 'isi welcome ikut brand custom');
    } finally { brandTestRestore($b); }
}

function testPdfHtmlFollowsCustomBrand() {
    $b = brandTestBackup();
    require_once __DIR__ . '/../../includes/pdf-dompdf.php';
    try {
        setSetting('site_name', 'WisataKita');
        $h = pdfTourHtml('Tour Kuta', '', '', [['day_number' => 1, 'title' => 'Tiba', 'description' => '', 'meals' => '', 'accommodation' => '']]);
        assertContains('WisataKita', $h, 'PDF ikut brand custom');
        assertTrue(strpos($h, 'TourAndTravel') === false, 'brand lama hilang dari PDF');
    } finally { brandTestRestore($b); }
}
