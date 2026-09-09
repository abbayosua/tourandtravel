# PRD: Admin Dashboard Operational System

> Versi: 1.0 | Created: 2026-09-09 | Status: Draft
> Target: Admin panel layaknya Traveloka/Klook operational dashboard untuk owner

---

## 1. Background & Problem

Admin panel saat ini hanya memiliki:
- Dashboard dasar (5 stat cards + recent bookings)
- Analytics sederhana (SVG chart)
- Payments list (Midtrans)

**Kekurangan:**
- Tidak ada profit/loss tracking
- Tidak ada COGS (Cost of Goods Sold) per booking
- Tidak ada expense management
- Tidak ada sales report yang bisa di-export
- Sidebar belum terorganisir (flat list 30+ menu)
- Tidak ada conversion metrics atau AOV

---

## 2. Goals

| Goal | Metric |
|------|--------|
| Real-time business visibility | KPI cards updated setiap load |
| Profit tracking | P&L statement per bulan |
| Sales analytics | Filter per vertikal, tanggal, export CSV |
| Operational efficiency | Sidebar terorganisir, quick actions |
| Owner-level reporting | Dashboard cukup untuk keputusan bisnis |

---

## 3. Feature Specifications

### 3.1 Enhanced Dashboard (`admin/dashboard.php`)

**KPI Cards (8 cards):**

| Card | Icon | Color | Formula |
|------|------|-------|---------|
| Tour Aktif | bi-map | primary | `COUNT(tours WHERE is_active=1)` |
| Total Booking | bi-ticket-perforated | info | `COUNT(ALL bookings)` |
| Pending | bi-hourglass-split | warning | `COUNT(WHERE status='pending')` |
| Confirmed | bi-check-circle | success | `COUNT(WHERE status='confirmed')` |
| Revenue | bi-currency-dollar | primary | `SUM(total_price WHERE confirmed/paid)` |
| Profit | bi-graph-up-arrow | success | `Revenue - COGS - Expenses` |
| Avg Order Value | bi-receipt | info | `Revenue / Total Booking` |
| Conversion Rate | bi-percentage | warning | `Confirmed / (Confirmed + Cancelled) × 100` |

**Charts (Chart.js CDN):**

1. **Revenue Trend** — Line chart 30 hari terakhir
   - X: tanggal, Y: revenue (Rp)
   - Data: `SUM(total_price) GROUP BY DATE(created_at)`
   
2. **Booking per Vertikal** — Horizontal bar chart
   - 7 vertikal: Tour, Hotel, Flight, Attraction, Transfer, Train, eSIM
   - Data: `COUNT(*) GROUP BY type FROM UNION semua tables`

**Recent Activity Feed:**
- 10 aktivitas terakhir (booking baru, status change, payment)
- Icon + timestamp + deskripsi singkat

**Quick Actions Panel:**
- Link ke: Kelola Booking, Sales Report, Accounting, Tambah Tour

---

### 3.2 Sales Report (`admin/sales-report.php`) [BARU]

**Filter Controls:**
- Date range picker (from/to)
- Dropdown vertikal: All, Tour, Hotel, Flight, Attraction, Transfer, Train, eSIM, Ferry
- Status filter: All, Pending, Confirmed, Paid, Cancelled

**Tabel Detail Transaksi:**

| Kolom | Sumber |
|-------|--------|
| Tanggal | `created_at` |
| Kode Booking | `booking_code` |
| Pelanggan | `name`, `email` |
| Item | `item_title` (JOIN product table) |
| Tipe | `btype` (badge) |
| Qty | `participants/rooms/seats` |
| Gross Amount | `total_price` |
| Diskon/Promo | `discount_amount` (jika ada) |
| Net Amount | `gross - discount` |
| Status | `status` (badge) |
| Payment Method | `payments.payment_type` |

**Summary Cards:**
- Total Transaksi (count)
- Total Revenue (sum net)
- Average per Transaksi
- Top Vertikal

**Export CSV:**
- Tombol "Export CSV" → generate CSV dari current filter
- Filename: `sales-report-{from}-{to}.csv`

**Revenue Breakdown:**
- Pie chart per vertikal (Chart.js)

---

### 3.3 Accounting Page (`admin/accounting.php`) [BARU]

**P&L Statement (Profit & Loss):**

```
═══════════════════════════════════════
  PROFIT & LOSS STATEMENT
  Periode: {from} — {to}
═══════════════════════════════════════

REVENUE
  Tour              Rp XX.XXX.XXX
  Hotel             Rp XX.XXX.XXX
  Flight            Rp XX.XXX.XXX
  Attraction        Rp XX.XXX.XXX
  Transfer          Rp XX.XXX.XXX
  Train             Rp XX.XXX.XXX
  eSIM              Rp XX.XXX.XXX
  ─────────────────────────────────
  Total Revenue     Rp XX.XXX.XXX

COGS (Cost of Goods Sold)
  Tour              Rp XX.XXX.XXX
  Hotel             Rp XX.XXX.XXX
  Flight            Rp XX.XXX.XXX
  ...
  ─────────────────────────────────
  Total COGS        Rp XX.XXX.XXX

  ─────────────────────────────────
  GROSS PROFIT      Rp XX.XXX.XXX  (XX%)

EXPENSES
  Marketing         Rp XX.XXX.XXX
  Operasional       Rp XX.XXX.XXX
  Gaji              Rp XX.XXX.XXX
  ...
  ─────────────────────────────────
  Total Expenses    Rp XX.XXX.XXX

  ═══════════════════════════════════
  NET PROFIT        Rp XX.XXX.XXX  (XX%)
```

**Expense Management:**
- Tabel `expenses` (CRUD via modal)
- Kolom: id, category, description, amount, booking_type (nullable), booking_id (nullable), created_at
- Kategori pre-defined: Marketing, Operasional, Gaji, Sewa, Utilitas, Lainnya

**Charts:**
- Monthly comparison: Revenue vs Expense vs Profit (bar chart 6 bulan)
- Expense breakdown: Pie chart per kategori

**Export CSV:**
- P&L statement dalam format CSV

---

### 3.4 Sidebar Reorganize (`admin/includes/admin-header.php`)

**Struktur Baru:**

```
📋 OVERVIEW
  ├── Dashboard
  └── Analytics

🏨 INVENTORY
  ├── Tour
  ├── Hotel
  ├── Pesawat
  ├── Ferry
  ├── Rental Mobil
  ├── Atraksi
  ├── Transfer
  ├── Kereta
  └── eSIM

🎫 BOOKINGS
  ├── Kelola Booking
  └── Pembayaran

📢 MARKETING
  ├── Flash Sale
  ├── Kode Promo
  ├── Price Alerts
  ├── Corporate Rates
  └── Koleksi

💰 FINANCE
  ├── Sales Report ← NEW
  ├── Accounting ← NEW
  └── Loyalty Settings

📝 CONTENT
  ├── Blog
  ├── Ulasan
  ├── FAQ
  └── Tampilan

⚙️ SETTINGS
  ├── WA
  ├── Live Chat
  ├── Email Log
  └── Mata Uang

🌐 Lihat Website (external)
```

**Section Headers:**
- Small text (`text-secondary`), uppercase, letter-spacing
- Non-clickable, hanya label

---

### 3.5 Analytics Enhancement (`admin/analytics.php`)

**Update:**
- Tambah tabs filter per vertikal
- Pie chart revenue per vertikal (Chart.js)
- Bar chart top 10 produk (bukan hanya tour)
- Export CSV untuk data analytics

---

## 4. Database Changes

### 4.1 New Table: `expenses`

```sql
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,        -- Marketing, Operasional, Gaji, Sewa, Utilitas, Lainnya
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    booking_type VARCHAR(20) NULL,         -- nullable: tour, hotel, flight, dll (jika expense terkait booking)
    booking_id INT NULL,                   -- nullable: ID booking terkait
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_created_at (created_at)
);
```

### 4.2 ALTER TABLE: Add `cogs` column

```sql
-- Semua booking tables
ALTER TABLE bookings ADD COLUMN cogs DECIMAL(12,2) DEFAULT 0;
ALTER TABLE hotel_bookings ADD COLUMN cogs DECIMAL(12,2) DEFAULT 0;
ALTER TABLE flight_bookings ADD COLUMN cogs DECIMAL(12,2) DEFAULT 0;
ALTER TABLE attraction_bookings ADD COLUMN cogs DECIMAL(12,2) DEFAULT 0;
ALTER TABLE transfer_bookings ADD COLUMN cogs DECIMAL(12,2) DEFAULT 0;
ALTER TABLE train_bookings ADD COLUMN cogs DECIMAL(12,2) DEFAULT 0;
ALTER TABLE connectivity_bookings ADD COLUMN cogs DECIMAL(12,2) DEFAULT 0;
```

---

## 5. Technical Architecture

### 5.1 Query Strategy

**Sales Report Query:**
```sql
-- Pattern: UNION semua booking tables
SELECT * FROM (
    SELECT b.id, b.booking_code, b.name, b.email, b.total_price, b.cogs, b.status,
           b.created_at, 'tour' AS btype, t.title AS item_title
    FROM bookings b JOIN tours t ON b.tour_id = t.id
    WHERE b.created_at BETWEEN ? AND ?
    UNION ALL
    SELECT hb.id, NULL, hb.name, hb.email, hb.total_price, hb.cogs, hb.status,
           hb.created_at, 'hotel', h.name
    FROM hotel_bookings hb JOIN hotels h ON hb.hotel_id = h.id
    WHERE hb.created_at BETWEEN ? AND ?
    -- ... 5 more UNION ALL
) AS all_bookings
ORDER BY created_at DESC;
```

**P&L Query:**
```sql
-- Revenue per vertikal
SELECT btype, SUM(total_price) AS revenue, SUM(cogs) AS total_cogs
FROM all_bookings  -- same UNION as above
WHERE status IN ('confirmed', 'paid')
GROUP BY btype;

-- Expenses per kategori
SELECT category, SUM(amount) AS total
FROM expenses
WHERE created_at BETWEEN ? AND ?
GROUP BY category;
```

### 5.2 Chart.js Integration

```html
<!-- CDN (sudah pattern project) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>

<script>
// Revenue trend line chart
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($dates) ?>,
        datasets: [{
            label: 'Revenue',
            data: <?= json_encode($revenues) ?>,
            borderColor: '#0d6efd',
            tension: 0.3
        }]
    }
});
</script>
```

### 5.3 CSV Export

```php
function exportCSV($filename, $headers, $rows) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}
```

---

## 6. File Structure

```
admin/
├── dashboard.php          (REWRITE — enhanced KPI + charts)
├── sales-report.php       (NEW — sales report + export)
├── accounting.php         (NEW — P&L + expenses CRUD)
├── analytics.php          (UPDATE — pie chart + bar chart)
├── bookings.php           (MINOR — tambah COGS column display)
├── payments.php           (NO CHANGE)
└── includes/
    ├── admin-header.php   (REWRITE — sidebar reorganize)
    └── admin-footer.php   (MINOR — Chart.js script)

includes/
└── analytics.php          (UPDATE — tambah helper functions)

database/
├── migrate-admin-finance.sql  (NEW — expenses table + cogs column)
└── migrate-expenses.php       (NEW — runner script)
```

---

## 7. Translation Keys

```php
// Keys baru untuk t() wrapper
'Sales Report'           => 'Laporan Penjualan'
'Accounting'             => 'Akuntansi'
'Profit & Loss'          => 'Laba Rugi'
'COGS'                   => 'Harga Pokok Penjualan'
'Gross Profit'           => 'Laba Kotor'
'Net Profit'             => 'Laba Bersih'
'Expenses'               => 'Pengeluaran'
'Add Expense'            => 'Tambah Pengeluaran'
'Edit Expense'           => 'Edit Pengeluaran'
'Delete Expense'         => 'Hapus Pengeluaran'
'Export CSV'             => 'Export CSV'
'Revenue'                => 'Pendapatan'
'Avg Order Value'        => 'Rata-rata per Transaksi'
'Conversion Rate'        => 'Tingkat Konversi'
'Total Revenue'          => 'Total Pendapatan'
'Total COGS'             => 'Total HPP'
'Total Expenses'         => 'Total Pengeluaran'
'Revenue Breakdown'      => 'Breakdown Pendapatan'
'Expense Breakdown'      => 'Breakdown Pengeluaran'
'Monthly Comparison'     => 'Perbandingan Bulanan'
```

---

## 8. Implementation Phases

| Phase | Task | Est. Time |
|-------|------|-----------|
| 1 | DB Migration (expenses table + cogs column) | 30 min |
| 2 | Enhanced Dashboard (KPI + charts) | 2-3 hours |
| 3 | Sales Report (table + filter + export) | 2-3 hours |
| 4 | Accounting (P&L + expenses CRUD) | 2-3 hours |
| 5 | Sidebar Reorganize | 30 min |
| 6 | Analytics Enhancement | 1 hour |
| 7 | Translation Keys | 30 min |
| 8 | Smoke Test & Bug Fix | 1 hour |

**Total Estimasi:** 10-12 hours

---

## 9. Acceptance Criteria

- [ ] Dashboard menampilkan 8 KPI cards dengan data real-time
- [ ] Revenue trend chart menampilkan 30 hari terakhir
- [ ] Booking per vertikal chart berfungsi
- [ ] Sales Report bisa filter per vertikal, tanggal, status
- [ ] Sales Report export CSV berfungsi
- [ ] Accounting page menampilkan P&L statement
- [ ] Expense CRUD berfungsi (add/edit/delete)
- [ ] Monthly comparison chart berfungsi
- [ ] Sidebar terorganisir dengan section headers
- [ ] Semua halaman bilingual (ID/EN)
- [ ] Tidak ada error di browser console
- [ ] Query performant (< 2 detik load)

---

## 10. Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Query lambat (UNION 7+ tables) | High | Tambah index `created_at + status`; pakai cache untuk summary |
| COGS belum terisi untuk booking lama | Medium | Default 0; owner isi manual atau import |
| Chart.js CDN down | Low | Fallback SVG static (sudah ada pattern di analytics.php) |
| Translation tidak sync | Low | Jalankan `regenerate-en.php` setelah semua selesai |

---

## 11. Future Enhancements (Phase 2)

- Invoice PDF generation
- Multi-currency support (sudah ada基础 di currency-settings)
- Multi-admin role/permission (admin, finance, viewer)
- Real-time dashboard via WebSocket/AJAX polling
- Mobile responsive dashboard
- Dashboard widget customization
- Automated daily/weekly email reports
