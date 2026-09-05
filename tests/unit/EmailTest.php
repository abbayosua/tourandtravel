<?php
/**
 * EmailTest — driver log/api, log tidak duplikat, template bilingual.
 * Semua test memakai driver 'log' (tidak mengirim sungguhan).
 */

require_once __DIR__ . '/../../includes/email.php';

function emailTestSetup(): string {
    $oldDriver = getSetting('email_driver', 'log');
    setSetting('email_driver', 'log');
    db()->exec("DELETE FROM email_log WHERE to_email LIKE '%@email-test.local'");
    return $oldDriver;
}

function emailTestTeardown(string $oldDriver): void {
    setSetting('email_driver', $oldDriver);
    db()->exec("DELETE FROM email_log WHERE to_email LIKE '%@email-test.local'");
}

function testSendEmailLogDriverWritesLog() {
    $old = emailTestSetup();
    try {
        $r = sendEmail('user@email-test.local', 'Subjek Uji', '<p>halo</p>', 'booking_created');
        assertTrue($r['ok'], 'driver log harus ok');
        assertTrue($r['log_id'] > 0, 'log id terisi');
        $row = db()->query("SELECT * FROM email_log WHERE id = " . (int)$r['log_id'])->fetch();
        assertSame('sent', $row['status']);
        assertSame('log', $row['driver']);
        assertSame('booking_created', $row['event']);
        assertSame('Subjek Uji', $row['subject']);
    } finally { emailTestTeardown($old); }
}

function testSendEmailInvalidAddressLoggedAsFailed() {
    $old = emailTestSetup();
    try {
        $r = sendEmail('bukan-email', 'Subjek', '<p>x</p>', 'booking_created');
        assertTrue($r['ok'] === false, 'email invalid harus gagal');
        assertTrue($r['error'] !== null, 'error terisi');
        $row = db()->query("SELECT status, error FROM email_log WHERE id = " . (int)$r['log_id'])->fetch();
        assertSame('failed', $row['status']);
        assertContains('tidak valid', $row['error']);
    } finally { emailTestTeardown($old); }
}

function testSendEmailNeverThrowsOnGarbageInput() {
    $old = emailTestSetup();
    try {
        $r = sendEmail('', '', '', null);
        assertTrue(isset($r['ok']), 'return array selalu ada');
    } finally { emailTestTeardown($old); }
}

function testRenderEmailTemplateBilingual() {
    $id = renderEmailTemplate('booking-created', ['booking_code' => 'TAT-1', 'total' => 'Rp 100', 'pay_link' => 'http://x/pay'], 'id');
    assertContains('Kode Booking', $id['html']);
    assertContains('Bayar Sekarang', $id['html']);

    $en = renderEmailTemplate('booking-created', ['booking_code' => 'TAT-1', 'total' => 'Rp 100', 'pay_link' => 'http://x/pay'], 'en');
    assertContains('Booking code', $en['html']);
    assertContains('Pay Now', $en['html']);
    assertContains(SITE_NAME, $en['html'], 'brand shell tampil');
}

function testRenderEmailTemplateFallsBackToGeneric() {
    $t = renderEmailTemplate('event-tak-ada', ['message' => 'halo dunia'], 'id');
    assertContains('halo dunia', $t['html']);
    assertContains(SITE_NAME, $t['html']);
}

function testSendEmailTemplateLogsEvent() {
    $old = emailTestSetup();
    try {
        $r = sendEmailTemplate('user@email-test.local', 'booking-status', ['booking_code' => 'TAT-9', 'status' => 'paid'], 'id');
        assertTrue($r['ok']);
        $row = db()->query("SELECT event, subject FROM email_log WHERE id = " . (int)$r['log_id'])->fetch();
        assertSame('booking-status', $row['event']);
        assertContains('TAT-9', $row['subject']);
    } finally { emailTestTeardown($old); }
}
