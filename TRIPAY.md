# TRIPAY.md — Dokumentasi Integrasi Tripay

Sumber: https://tripay.co.id/developer (dibaca 2026-09-23, Bahasa Indonesia).
SDK resmi PHP: https://github.com/zerosdev/tripay-sdk-php

---

## 1. Konsep Dasar

### Open vs Closed Payment

| | Open Payment | Closed Payment |
|---|---|---|
| Nominal | Bebas, diisi pelanggan | Ditentukan merchant |
| Kode bayar / VA | Dipakai berkali-kali | Sekali pakai |
| Fee | Hanya ke merchant | Ke merchant atau pelanggan |

> Untuk booking tour/travel → pakai **Closed Payment** (nominal fix per booking).

### Direct vs Redirect

- **DIRECT**: semua proses di situs sendiri, API mengembalikan `pay_code` / nomor VA.
  Channel: semua VA bank, gerai (Alfamart/Indomaret/Alfamidi), QRIS.
- **REDIRECT**: pelanggan dialihkan ke URL pembayaran (`pay_url`).
  Channel: OVO, DANA, ShopeePay.

### Mode & Credential

- **Sandbox**: testing. Credential di member area → **API & Integrasi > Simulator > Merchant > Detail**.
- **Production**: live. Credential di **Merchant > Opsi > Edit**.
- Channel harus diaktifkan dulu: sandbox via **Simulator > Merchant > Channel Pembayaran**,
  production via **Merchant > Opsi > Atur Channel Pembayaran**.

---

## 2. Daftar Channel & Biaya

Base URL: sandbox `https://tripay.co.id/api-sandbox`, production `https://tripay.co.id/api`.
Semua request memakai header `Authorization: Bearer {api_key}`.

| Kode | Channel | Tipe | Fee standar |
|---|---|---|---|
| PERMATAVA | Permata VA | DIRECT | Rp 4.250 |
| BNIVA | BNI VA | DIRECT | Rp 4.250 |
| BRIVA | BRI VA | DIRECT | Rp 4.250 |
| MANDIRIVA | Mandiri VA | DIRECT | Rp 4.250 |
| BCAVA | BCA VA | DIRECT | Rp 5.500 |
| MUAMALATVA | Muamalat VA | DIRECT | Rp 4.250 |
| CIMBVA | CIMB Niaga VA | DIRECT | Rp 4.250 |
| BSIVA | BSI VA | DIRECT | Rp 4.250 |
| OCBCVA | OCBC NISP VA | DIRECT | Rp 4.250 |
| DANAMONVA | Danamon VA | DIRECT | Rp 4.250 |
| OTHERBANKVA | Other Bank VA | DIRECT | Rp 4.250 |
| ALFAMART | Alfamart | DIRECT | Rp 3.500 |
| INDOMARET | Indomaret | DIRECT | Rp 3.500 |
| ALFAMIDI | Alfamidi | DIRECT | Rp 3.500 |
| OVO | OVO | REDIRECT | 3% |
| QRIS / QRISC / QRIS2 | QRIS | DIRECT | Rp 750 + 0,7% |
| DANA | DANA | REDIRECT | 3% |
| SHOPEEPAY | ShopeePay | REDIRECT | 3% |

Catatan:
- VA: min Rp 10.000, max Rp 10.000.000. Expired min 15–60 mnt, max 180–4.320 mnt (per channel).
- Gerai: max Rp 2.500.000 + Rp 3.000 dibebankan ke pelanggan di kasir.
- OVO/DANA/SHOPEEPAY: fee < Rp 1.000 dibulatkan jadi Rp 1.000.

---

## 3. Closed Payment — Signature

Wajib sebelum request transaksi. HMAC-SHA256 dari
`merchantCode + merchantRef + amount`, dikunci private key.

```php
<?php
$privateKey   = 'ytf6ooi2gmlNPfpchd94jDOk8hRWOu';
$merchantCode = 'T0001';
$merchantRef  = 'INV55567';
$amount       = 1500000;

$signature = hash_hmac('sha256', $merchantCode.$merchantRef.$amount, $privateKey);
// 9f167eba844d1fcb369404e2bda53702e2f78f7aa12e91da6715414e65b8c86a
?>
```

---

## 4. Closed Payment — Request Transaksi (bikin VA)

`POST /transaction/create` (tambah prefix `-sandbox` untuk testing).

Header: `Authorization: Bearer {api_key}`, body form-urlencoded (`http_build_query`).

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `method` | string | YA | Kode channel, mis. `BRIVA` |
| `merchant_ref` | string | YA | No invoice/order sistem sendiri; dikirim balik saat callback |
| `amount` | int | YA | Total pembayaran |
| `customer_name` | string | YA | Nama pelanggan |
| `customer_email` | string | YA | Email pelanggan |
| `customer_phone` | string | TIDAK (YA utk bbrp channel) | No HP |
| `order_items` | array | YA | Rincian produk, key wajib: `name`, `price`, `quantity` (opsional: `sku`, `product_url`, `image_url`) |
| `callback_url` | string | TIDAK | Override URL callback default merchant |
| `return_url` | string | TIDAK | Redirect pelanggan kembali |
| `expired_time` | int | TIDAK | Unix timestamp batas bayar; default 24 jam |
| `signature` | string | YA | Lihat bagian 3 |

```php
<?php
$apiKey       = 'api_key_anda';
$privateKey   = 'private_key_anda';
$merchantCode = 'kode merchant anda';
$merchantRef  = 'nomor referensi merchant anda';
$amount       = 1000000;

$data = [
    'method'         => 'BRIVA',
    'merchant_ref'   => $merchantRef,
    'amount'         => $amount,
    'customer_name'  => 'Nama Pelanggan',
    'customer_email' => 'emailpelanggan@domain.com',
    'customer_phone' => '081234567890',
    'order_items'    => [
        [
            'sku'         => 'FB-06',
            'name'        => 'Nama Produk 1',
            'price'       => 500000,
            'quantity'    => 1,
            'product_url' => 'https://tokokamu.com/product/nama-produk-1',
            'image_url'   => 'https://tokokamu.com/product/nama-produk-1.jpg',
        ],
    ],
    'return_url'   => 'https://domainanda.com/redirect',
    'expired_time' => (time() + (24 * 60 * 60)), // 24 jam
    'signature'    => hash_hmac('sha256', $merchantCode.$merchantRef.$amount, $privateKey)
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_FRESH_CONNECT  => true,
    CURLOPT_URL            => 'https://tripay.co.id/api/transaction/create',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => false,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$apiKey],
    CURLOPT_FAILONERROR    => false,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($data),
    CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
]);
$response = curl_exec($curl);
$error = curl_error($curl);
curl_close($curl);
echo empty($error) ? $response : $error;
?>
```

### Response sukses

```json
{
  "success": true,
  "data": {
    "reference": "T0001000000000000006",
    "merchant_ref": "INV345675",
    "payment_method": "BRIVA",
    "payment_name": "BRI Virtual Account",
    "amount": 1000000,
    "fee_merchant": 1500,
    "fee_customer": 0,
    "total_fee": 1500,
    "amount_received": 998500,
    "pay_code": "57585748548596587",
    "pay_url": null,
    "checkout_url": "https://tripay.co.id/checkout/T0001000000000000006",
    "status": "UNPAID",
    "expired_time": 1582855837,
    "order_items": [ { "sku": "PRODUK1", "name": "Nama Produk 1", "price": 500000, "quantity": 1, "subtotal": 500000 } ],
    "instructions": [ { "title": "Internet Banking", "steps": ["Login ke internet banking Bank BRI Anda", "..."] } ],
    "qr_string": null,
    "qr_url": null
  }
}
```

- `pay_code` (DIRECT) → tampilkan ke pelanggan + `instructions`.
- `pay_url` (REDIRECT: OVO/DANA/ShopeePay) → redirect pelanggan ke sana.
- `checkout_url` → halaman checkout Tripay sebagai alternatif.
- Response gagal: `{"success": false, "message": "Invalid API Key"}`.

---

## 5. Closed Payment — Detail Transaksi

`GET /transaction/detail?reference={reference}`

```php
<?php
$payload = ['reference' => 'T0001000000000000006'];
// GET https://tripay.co.id/api/transaction/detail?{http_build_query($payload)}
// Header: Authorization: Bearer {api_key}
?>
```

Response = struktur sama seperti create + `paid_at`, `status` (`UNPAID`/`PAID`/…).

---

## 6. Closed Payment — Cek Status

`GET /transaction/check-status?reference={reference}`

```php
<?php
$payload = ['reference' => 'T0001000000455HFGRY'];
// GET https://tripay.co.id/api/transaction/check-status?{http_build_query($payload)}
?>
```

Response: `{"success": true, "message": "Status transaksi saat ini PAID"}`.

---

## 7. Open Payment (ringkas — tidak dipakai booking)

- Signature: `hash_hmac('sha256', merchantCode.method.merchantRef, privateKey)` (**tanpa amount**).
- `POST /open-payment/create` — body hanya `method`, `merchant_ref` (opsional),
  `customer_name` (opsional), `signature`. Response: `uuid` + `pay_code` (dipakai berkali-kali).
- `GET /open-payment/{uuid}/detail` dan `GET /open-payment/{uuid}/transactions`
  (riwayat bayar: `reference`, `amount` bebas, `status`, `paid_at`, pagination).

---

## 8. Callback

Tripay `POST` JSON ke URL callback (diatur di halaman Merchant, bisa di-override
per transaksi via `callback_url`).

Whitelist IP: `95.111.200.230` (IPv4), `2a04:3543:1000:2310:ac92:4cff:fe87:63f9` (IPv6).
Tester: member area → developer → callback-tester (production),
`tripay.co.id/simulator/console/callback` (sandbox).

### Header

| Key | Contoh | Keterangan |
|---|---|---|
| `Content-Type` | `application/json` | — |
| `X-Callback-Signature` | `85d99ec90d36c93dad61a98928ef63…` | HMAC-SHA256 raw body + private key |
| `X-Callback-Event` | `payment_status` | Satu-satunya event |

### Body

```json
{
    "reference": "T0001000023000XXXXX",
    "merchant_ref": "INV123456",
    "payment_method": "BCA Virtual Account",
    "payment_method_code": "BCAVA",
    "total_amount": 200000,
    "fee_merchant": 2000,
    "fee_customer": 0,
    "total_fee": 2000,
    "amount_received": 198000,
    "is_closed_payment": 1,
    "status": "PAID",
    "paid_at": 1608133017,
    "note": null
}
```

`is_closed_payment`: `1` = closed, `0` = open.
Status callback: `PAID` | `FAILED` | `EXPIRED` | `REFUND`
(`UNPAID` tidak pernah dikirim via callback.)

### Verifikasi signature

```php
<?php
$privateKey = 'private_key_anda';
$json = file_get_contents('php://input');
$signature = hash_hmac('sha256', $json, $privateKey);
// bandingkan dengan $_SERVER['HTTP_X_CALLBACK_SIGNATURE']
?>
```

### Handler lengkap (native)

```php
<?php
require('db_connection.php');
$json = file_get_contents('php://input');
$callbackSignature = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';
$privateKey = 'private_key_anda';
$signature = hash_hmac('sha256', $json, $privateKey);

if ($callbackSignature !== $signature) {
    exit(json_encode(['success' => false, 'message' => 'Invalid signature']));
}
$data = json_decode($json);
if (JSON_ERROR_NONE !== json_last_error()) {
    exit(json_encode(['success' => false, 'message' => 'Invalid data sent by payment gateway']));
}
if ('payment_status' !== ($_SERVER['HTTP_X_CALLBACK_EVENT'] ?? '')) {
    exit(json_encode(['success' => false, 'message' => 'Unrecognized callback event']));
}

$invoiceId = $db->real_escape_string($data->merchant_ref);
$tripayReference = $db->real_escape_string($data->reference);
$status = strtoupper((string) $data->status);

if ($data->is_closed_payment === 1) {
    // idempoten: hanya proses yang masih UNPAID
    $result = $db->query("SELECT * FROM tbl_invoices WHERE id = '{$invoiceId}' AND tripay_reference = '{$tripayReference}' AND status = 'UNPAID' LIMIT 1");
    if (!$result) {
        exit(json_encode(['success' => false, 'message' => 'Invoice not found or already paid']));
    }
    while ($invoice = $result->fetch_object()) {
        switch ($status) {
            case 'PAID':
                $db->query("UPDATE tbl_invoices SET status = 'PAID' WHERE id = {$invoice->id}");
                break;
            case 'EXPIRED':
                $db->query("UPDATE tbl_invoices SET status = 'EXPIRED' WHERE id = {$invoice->id}");
                break;
            case 'FAILED':
                $db->query("UPDATE tbl_invoices SET status = 'FAILED' WHERE id = {$invoice->id}");
                break;
            default:
                exit(json_encode(['success' => false, 'message' => 'Unrecognized payment status']));
        }
        exit(json_encode(['success' => true]));
    }
}
?>
```

### Response wajib

```json
{ "success": true }
```

Tanpa response ini Tripay **retry tiap 2 menit, maks 3x**. Buat handler idempoten
(cek `merchant_ref` + `reference` + status saat ini).

---

## 9. Endpoint Pendukung

| Endpoint | Method | Fungsi |
|---|---|---|
| `/payment/instruction?code=BRIVA&pay_code=…&amount=…` | GET | Langkah bayar per channel (`title` + `steps`, `{{pay_code}}`/`{{amount}}` auto-substitusi) |
| `/merchant/payment-channel` | GET | Channel aktif + fee (`fee_merchant`, `fee_customer`, min/max amount, `icon_url`) |
| `/merchant/fee-calculator?code=QRIS&amount=100000` | GET | Hitung fee per nominal |
| `/merchant/transactions?page=1&per_page=25` | GET | Daftar transaksi (filter: `reference`, `merchant_ref`, `method`, `status`) |

---

## 10. Testing (Simulator)

- Simulator: https://tripay.co.id/simulator (login dulu).
- Setelah create transaksi sandbox, bayar via simulator / callback tester,
  lalu pastikan callback masuk dan status berubah.

---

## 11. Catatan Integrasi ke tourandtravel

Pola yang sudah ada (`includes/payments.php`, Midtrans) bisa dipakai ulang untuk Tripay:

- `generateMidtransOrderId()` (`TAT-{TYPE}-{id}-{rand}`) → jadi `merchant_ref`.
- `payments` tabel (booking_type, booking_id, order_id, gross_amount, status, paid_at)
  → tambah kolom `reference` (Tripay), `payment_method`, `pay_code`, `checkout_url`.
- `handleMidtransNotification()` idempoten → tiru untuk `handleTripayCallback()`
  (verifikasi HMAC → cari by reference → skip bila final → update + sinkron booking +
  slot/poin).
- Settings baru di tabel `settings`: `tripay_api_key`, `tripay_private_key`,
  `tripay_merchant_code`, `tripay_env` (`sandbox`/`production`),
  `payment_gateway` (`midtrans`/`tripay`).
- Endpoint baru: `webhook-tripay.php` (publik, tanpa session, balas `{"success":true}`).
- Flow checkout: checkout → `POST /transaction/create` → simpan reference →
  tampilkan `pay_code` + instruksi (DIRECT) atau redirect `pay_url` (ewallet) →
  callback `PAID` → booking lunas.
