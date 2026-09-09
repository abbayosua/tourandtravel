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

function testRenderEmailTemplateTrilingualZh() {
    $zhCreated = renderEmailTemplate('booking-created', ['booking_code' => 'TAT-2', 'total' => 'Rp 100', 'pay_link' => 'http://x/pay'], 'zh');
    assertContains('预订编号', $zhCreated['html']);
    assertContains('立即支付', $zhCreated['html']);

    $zhStatus = renderEmailTemplate('booking-status', ['booking_code' => 'TAT-3', 'status' => 'paid', 'track_link' => 'http://x/track'], 'zh');
    assertContains('您的订单状态已更新', $zhStatus['html']);
    assertContains('查询订单', $zhStatus['html']);

    $zhReset = renderEmailTemplate('reset-password', ['reset_link' => 'http://x/reset'], 'zh');
    assertContains('设置新密码', $zhReset['html']);
    assertContains('忽略此邮件', $zhReset['html']);

    $zhWelcome = renderEmailTemplate('welcome', [], 'zh');
    assertContains('欢迎来到', $zhWelcome['html']);

    $zhInvoice = renderEmailTemplate('invoice', ['order_id' => 'ORD-1', 'amount' => 'Rp 50'], 'zh');
    assertContains('已收到付款', $zhInvoice['html']);
    assertContains('订单', $zhInvoice['html']);
    assertContains('金额', $zhInvoice['html']);
}

function testRenderEmailTemplateZhSubjectAndShell() {
    $t = renderEmailTemplate('booking-created', ['booking_code' => 'TAT-ZH', 'total' => 'Rp 1', 'subject' => '您的预订已收到'], 'zh');
    assertSame('您的预订已收到', $t['subject'], 'subject custom dipakai apa pun bahasanya');
    assertContains(SITE_NAME, $t['html'], 'brand shell tetap tampil untuk zh');
    assertContains('<div style="background:#0d6efd', $t['html'], 'shell html utuh');

    $t2 = renderEmailTemplate('welcome', [], 'zh');
    assertContains('欢迎来到', $t2['html']);
}

function testRenderEmailTemplateUnknownLangFallsBackToId() {
    $t = renderEmailTemplate('booking-created', ['booking_code' => 'TAT-X', 'total' => 'Rp 1', 'pay_link' => 'http://x'], 'fr');
    assertContains('Kode Booking', $t['html'], 'lang tak dikenal fallback ke id');
}
