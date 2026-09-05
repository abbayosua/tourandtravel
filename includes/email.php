<?php
/**
 * email.php — abstraksi email transaksional (tanpa composer).
 *
 * Driver:
 *  - 'log' (default): TIDAK mengirim sungguhan — hanya tulis email_log. Aman untuk dev/E2E.
 *  - 'api'  : kirim via HTTP API (resend-style) memakai email_api_key/email_api_endpoint.
 *
 * Kontrak: sendEmail TIDAK PERNAH throw — gagal kirim = baris email_log
 * berstatus 'failed' + error message. Dipakai di event flow yang tidak boleh
 * terganggu oleh kegagalan email.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function emailDriver(): string {
    return in_array(getSetting('email_driver', 'log'), ['log', 'api']) ? getSetting('email_driver') : 'log';
}

function emailFrom(): string {
    return (string)getSetting('email_from', 'noreply@tourandtravel.web.id');
}

/** Kirim via API; kembalikan [ok, error] */
function emailSendViaApi(string $to, string $subject, string $html): array {
    $apiKey = (string)getSetting('email_api_key', '');
    $endpoint = (string)getSetting('email_api_endpoint', 'https://api.resend.com/emails');
    if ($apiKey === '') return [false, 'api_key kosong'];

    $payload = json_encode([
        'from' => emailFrom(),
        'to' => [$to],
        'subject' => $subject,
        'html' => $html,
    ]);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 15,
    ]);
    curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($http >= 200 && $http < 300) return [true, null];
    return [false, 'api http=' . $http . ($err ? ' curl=' . $err : '')];
}

/**
 * Kirim email + log. TIDAK throw.
 *
 * @return array ['ok' => bool, 'log_id' => int, 'error' => ?string]
 */
function sendEmail(string $to, string $subject, string $html, ?string $event = null): array {
    $to = trim($to);
    $driver = emailDriver();
    $ok = false;
    $error = null;

    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $error = 'alamat email tidak valid';
    } elseif ($driver === 'log') {
        $ok = true; // dev/E2E: tidak mengirim sungguhan
    } else {
        [$ok, $error] = emailSendViaApi($to, $subject, $html);
    }

    try {
        $stmt = db()->prepare("INSERT INTO email_log (to_email, subject, event, driver, status, error) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$to, $subject, $event, $driver, $ok ? 'sent' : 'failed', $error]);
        $logId = (int)db()->lastInsertId();
    } catch (Throwable $e) {
        $logId = 0;
        $error = $error ?: $e->getMessage();
    }

    return ['ok' => $ok, 'log_id' => $logId, 'error' => $error];
}

/**
 * Render template email bilingual + brand shell.
 * Template file: includes/email-templates/<event>.php — variabel: $data (array), $lang.
 */
function renderEmailTemplate(string $event, array $data = [], ?string $lang = null): array {
    $lang = $lang ?? (getCurrentLang() ?? 'id');
    $file = __DIR__ . '/email-templates/' . basename($event) . '.php';
    if (!is_file($file)) {
        $file = __DIR__ . '/email-templates/generic.php';
    }

    ob_start();
    $tplData = $data;
    include $file;
    $body = ob_get_clean();

    $subject = $data['subject'] ?? null;
    if ($subject === null) {
        $subject = ucfirst(str_replace('-', ' ', $event));
        if (!empty($data['booking_code'])) $subject .= ' - ' . $data['booking_code'];
    }

    $shell = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
      <div style="background:#0d6efd;color:#fff;padding:16px 24px;font-weight:bold;font-size:18px;">' . SITE_NAME . '</div>
      <div style="padding:24px;color:#111827;font-size:14px;line-height:1.6;">' . $body . '</div>
      <div style="padding:16px 24px;background:#f3f4f6;color:#6b7280;font-size:12px;">&copy; ' . date('Y') . ' ' . SITE_NAME . '</div>
    </div>';

    return ['subject' => $subject, 'html' => $shell];
}

/** Kirim template lengkap: render + sendEmail. TIDAK pernah throw. */
function sendEmailTemplate(string $to, string $event, array $data = [], ?string $lang = null): array {
    try {
        $t = renderEmailTemplate($event, $data, $lang);
        return sendEmail($to, $t['subject'], $t['html'], $event);
    } catch (Throwable $e) {
        // Fallback: kirim email generic polos agar log tetap tercatat
        try {
            return sendEmail($to, ucfirst(str_replace('-', ' ', $event)) . (isset($data['booking_code']) ? ' - ' . $data['booking_code'] : ''), '<p>' . t('Notifikasi dari ' . SITE_NAME) . '</p>', $event);
        } catch (Throwable $e2) {
            return ['ok' => false, 'log_id' => 0, 'error' => $e->getMessage()];
        }
    }
}
