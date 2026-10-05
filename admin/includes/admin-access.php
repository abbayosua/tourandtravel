<?php
/**
 * admin/includes/admin-access.php — RBAC sederhana panel admin.
 *
 * Model:
 * - admins.role: 'superadmin' (semua halaman) | 'staff' (hanya page_key
 *   yang tercatat di admin_permissions). Akun lama = superadmin.
 * - Halaman 'dashboard' selalu boleh dibuka semua akun login.
 * - Role + izin dibaca fresh dari DB tiap request (tidak basi setelah
 *   superadmin mengubah grant; tanpa perlu login ulang).
 * - Pre-migration safety: bila kolom/tabel belum ada (Throwable),
 *   perlakukan sebagai superadmin agar tidak terkunci.
 *
 * Sumber tunggal peta halaman: adminPages() — dipakai guard,
 * sidebar, form CRUD akun, dan preset.
 * key kanonis = basename file sidebar tanpa .php; file turunan
 * (form edit/ajax) ikut izin induk via 'via'.
 */
function adminPages(): array {
    // key => [file, section, label(t-key), icon, show-di-sidebar, via]
    return [
        // ===== Overview =====
        'dashboard'   => ['file' => 'dashboard.php', 'section' => 'Overview', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'show' => true, 'via' => null],
        'analytics'   => ['file' => 'analytics.php', 'section' => 'Overview', 'label' => 'Analytics', 'icon' => 'bi-graph-up-arrow', 'show' => true, 'via' => null],
        // ===== Inventory =====
        'tours'       => ['file' => 'tours.php', 'section' => 'Inventory', 'label' => 'Kelola Tour', 'icon' => 'bi-map', 'show' => true, 'via' => null],
        'tour-add'    => ['file' => 'tour-add.php', 'section' => 'Inventory', 'label' => 'Kelola Tour', 'icon' => 'bi-map', 'show' => false, 'via' => 'tours'],
        'tour-edit'   => ['file' => 'tour-edit.php', 'section' => 'Inventory', 'label' => 'Kelola Tour', 'icon' => 'bi-map', 'show' => false, 'via' => 'tours'],
        'hotels'      => ['file' => 'hotels.php', 'section' => 'Inventory', 'label' => 'Kelola Hotel', 'icon' => 'bi-building', 'show' => true, 'via' => null],
        'hotel-edit'  => ['file' => 'hotel-edit.php', 'section' => 'Inventory', 'label' => 'Kelola Hotel', 'icon' => 'bi-building', 'show' => false, 'via' => 'hotels'],
        'hotel-rooms' => ['file' => 'hotel-rooms.php', 'section' => 'Inventory', 'label' => 'Kelola Hotel', 'icon' => 'bi-building', 'show' => false, 'via' => 'hotels'],
        'flights'     => ['file' => 'flights.php', 'section' => 'Inventory', 'label' => 'Kelola Pesawat', 'icon' => 'bi-airplane', 'show' => true, 'via' => null],
        'flight-edit' => ['file' => 'flight-edit.php', 'section' => 'Inventory', 'label' => 'Kelola Pesawat', 'icon' => 'bi-airplane', 'show' => false, 'via' => 'flights'],
        'ferries'     => ['file' => 'ferries.php', 'section' => 'Inventory', 'label' => 'Kelola Ferry', 'icon' => 'bi-water', 'show' => true, 'via' => null],
        'ferry-edit'  => ['file' => 'ferry-edit.php', 'section' => 'Inventory', 'label' => 'Kelola Ferry', 'icon' => 'bi-water', 'show' => false, 'via' => 'ferries'],
        'rental-cars' => ['file' => 'rental-cars.php', 'section' => 'Inventory', 'label' => 'Kelola Rental', 'icon' => 'bi-car-front', 'show' => true, 'via' => null],
        'rental-car-edit' => ['file' => 'rental-car-edit.php', 'section' => 'Inventory', 'label' => 'Kelola Rental', 'icon' => 'bi-car-front', 'show' => false, 'via' => 'rental-cars'],
        'attractions' => ['file' => 'attractions.php', 'section' => 'Inventory', 'label' => 'Kelola Atraksi', 'icon' => 'bi-signpost-2', 'show' => true, 'via' => null],
        'attraction-edit' => ['file' => 'attraction-edit.php', 'section' => 'Inventory', 'label' => 'Kelola Atraksi', 'icon' => 'bi-signpost-2', 'show' => false, 'via' => 'attractions'],
        'transfers'   => ['file' => 'transfers.php', 'section' => 'Inventory', 'label' => 'Kelola Transfer', 'icon' => 'bi-car-front', 'show' => true, 'via' => null],
        'transfer-edit' => ['file' => 'transfer-edit.php', 'section' => 'Inventory', 'label' => 'Kelola Transfer', 'icon' => 'bi-car-front', 'show' => false, 'via' => 'transfers'],
        'trains'      => ['file' => 'trains.php', 'section' => 'Inventory', 'label' => 'Kelola Kereta', 'icon' => 'bi-train-front', 'show' => true, 'via' => null],
        'train-edit'  => ['file' => 'train-edit.php', 'section' => 'Inventory', 'label' => 'Kelola Kereta', 'icon' => 'bi-train-front', 'show' => false, 'via' => 'trains'],
        'esim'        => ['file' => 'esim.php', 'section' => 'Inventory', 'label' => 'Kelola eSIM', 'icon' => 'bi-sim', 'show' => true, 'via' => null],
        'esim-edit'   => ['file' => 'esim-edit.php', 'section' => 'Inventory', 'label' => 'Kelola eSIM', 'icon' => 'bi-sim', 'show' => false, 'via' => 'esim'],
        // ===== Bookings =====
        'bookings'    => ['file' => 'bookings.php', 'section' => 'Bookings', 'label' => 'Kelola Booking', 'icon' => 'bi-ticket-perforated', 'show' => true, 'via' => null],
        'payments'    => ['file' => 'payments.php', 'section' => 'Bookings', 'label' => 'Pembayaran', 'icon' => 'bi-credit-card', 'show' => true, 'via' => null],
        // ===== Marketing =====
        'flash-sales' => ['file' => 'flash-sales.php', 'section' => 'Marketing', 'label' => 'Flash Sale', 'icon' => 'bi-lightning-charge', 'show' => true, 'via' => null],
        'promo-codes' => ['file' => 'promo-codes.php', 'section' => 'Marketing', 'label' => 'Kode Promo', 'icon' => 'bi-tag', 'show' => true, 'via' => null],
        'price-alerts' => ['file' => 'price-alerts.php', 'section' => 'Marketing', 'label' => 'Price Alerts', 'icon' => 'bi-bell', 'show' => true, 'via' => null],
        'push-notifications' => ['file' => 'push-notifications.php', 'section' => 'Marketing', 'label' => 'Push Notifikasi', 'icon' => 'bi-bell-fill', 'show' => true, 'via' => null],
        'corporate-rates' => ['file' => 'corporate-rates.php', 'section' => 'Marketing', 'label' => 'Corporate Rates', 'icon' => 'bi-building', 'show' => true, 'via' => null],
        'collections' => ['file' => 'collections.php', 'section' => 'Marketing', 'label' => 'Koleksi', 'icon' => 'bi-collection', 'show' => true, 'via' => null],
        'ab-tests'   => ['file' => 'ab-tests.php', 'section' => 'Marketing', 'label' => 'AB Testing', 'icon' => 'bi-bezier2', 'show' => false, 'via' => null],
        // ===== Finance =====
        'sales-report' => ['file' => 'sales-report.php', 'section' => 'Finance', 'label' => 'Sales Report', 'icon' => 'bi-graph-up', 'show' => true, 'via' => null],
        'accounting'  => ['file' => 'accounting.php', 'section' => 'Finance', 'label' => 'Accounting', 'icon' => 'bi-cash-stack', 'show' => true, 'via' => null],
        'loyalty-settings' => ['file' => 'loyalty-settings.php', 'section' => 'Finance', 'label' => 'Loyalty Settings', 'icon' => 'bi-award', 'show' => true, 'via' => null],
        // ===== Reseller =====
        'resellers'   => ['file' => 'resellers.php', 'section' => 'Reseller', 'label' => 'Kelola Reseller', 'icon' => 'bi-shop', 'show' => true, 'via' => null],
        'reseller-topups' => ['file' => 'reseller-topups.php', 'section' => 'Reseller', 'label' => 'Topup Reseller', 'icon' => 'bi-wallet2', 'show' => true, 'via' => null],
        'reseller-pricing' => ['file' => 'reseller-pricing.php', 'section' => 'Reseller', 'label' => 'Harga Reseller', 'icon' => 'bi-tags', 'show' => true, 'via' => null],
        // ===== Content =====
        'posts'       => ['file' => 'posts.php', 'section' => 'Content', 'label' => 'Blog', 'icon' => 'bi-journal-richtext', 'show' => true, 'via' => null],
        'post-edit'   => ['file' => 'post-edit.php', 'section' => 'Content', 'label' => 'Blog', 'icon' => 'bi-journal-richtext', 'show' => false, 'via' => 'posts'],
        'reviews'     => ['file' => 'reviews.php', 'section' => 'Content', 'label' => 'Ulasan', 'icon' => 'bi-chat-square-heart', 'show' => true, 'via' => null],
        'faq'         => ['file' => 'faq.php', 'section' => 'Content', 'label' => 'Kelola FAQ', 'icon' => 'bi-question-circle', 'show' => true, 'via' => null],
        'faq-edit'    => ['file' => 'faq-edit.php', 'section' => 'Content', 'label' => 'Kelola FAQ', 'icon' => 'bi-question-circle', 'show' => false, 'via' => 'faq'],
        'faq-category' => ['file' => 'faq-category.php', 'section' => 'Content', 'label' => 'Kelola FAQ', 'icon' => 'bi-question-circle', 'show' => false, 'via' => 'faq'],
        'faq-category-edit' => ['file' => 'faq-category-edit.php', 'section' => 'Content', 'label' => 'Kelola FAQ', 'icon' => 'bi-question-circle', 'show' => false, 'via' => 'faq'],
        'appearance'  => ['file' => 'appearance.php', 'section' => 'Content', 'label' => 'Tampilan Homepage', 'icon' => 'bi-layout-text-window-reverse', 'show' => true, 'via' => null],
        'brand-settings' => ['file' => 'brand-settings.php', 'section' => 'Content', 'label' => 'Brand & Logo', 'icon' => 'bi-award', 'show' => true, 'via' => null],
        'nav-menus'   => ['file' => 'nav-menus.php', 'section' => 'Content', 'label' => 'Menu Navigasi', 'icon' => 'bi-menu-button-wide', 'show' => true, 'via' => null],
        'hero-slides' => ['file' => 'hero-slides.php', 'section' => 'Content', 'label' => 'Hero Slides', 'icon' => 'bi-images', 'show' => false, 'via' => null],
        'hero-slide-edit' => ['file' => 'hero-slide-edit.php', 'section' => 'Content', 'label' => 'Hero Slides', 'icon' => 'bi-images', 'show' => false, 'via' => 'hero-slides'],
        // ===== Settings =====
        'wa-settings' => ['file' => 'wa-settings.php', 'section' => 'Settings', 'label' => 'Pengaturan WA', 'icon' => 'bi-whatsapp', 'show' => true, 'via' => null],
        'wa-ajax'     => ['file' => 'wa-ajax.php', 'section' => 'Settings', 'label' => 'Pengaturan WA', 'icon' => 'bi-whatsapp', 'show' => false, 'via' => 'wa-settings'],
        'wa-test'     => ['file' => 'wa-test.php', 'section' => 'Settings', 'label' => 'Pengaturan WA', 'icon' => 'bi-whatsapp', 'show' => false, 'via' => 'wa-settings'],
        'chat-settings' => ['file' => 'chat-settings.php', 'section' => 'Settings', 'label' => 'Live Chat', 'icon' => 'bi-chat-dots', 'show' => true, 'via' => null],
        'email-log'   => ['file' => 'email-log.php', 'section' => 'Settings', 'label' => 'Log Email', 'icon' => 'bi-envelope-paper', 'show' => true, 'via' => null],
        'currency-settings' => ['file' => 'currency-settings.php', 'section' => 'Settings', 'label' => 'Mata Uang', 'icon' => 'bi-currency-exchange', 'show' => true, 'via' => null],
        'hotel-api-settings' => ['file' => 'hotel-api-settings.php', 'section' => 'Settings', 'label' => 'Hotel API', 'icon' => 'bi-building-gear', 'show' => true, 'via' => null],
        'flight-api-settings' => ['file' => 'flight-api-settings.php', 'section' => 'Settings', 'label' => 'Flight API', 'icon' => 'bi-airplane', 'show' => true, 'via' => null],
        // ===== Team (superadmin only) =====
        'admins'      => ['file' => 'admins.php', 'section' => 'Tim', 'label' => 'Kelola Admin', 'icon' => 'bi-people', 'show' => true, 'via' => null],
    ];
}

/** Urutan section di sidebar. */
function adminSections(): array {
    return ['Overview', 'Inventory', 'Bookings', 'Marketing', 'Finance', 'Reseller', 'Content', 'Settings', 'Tim'];
}

/** Key kanonis untuk sebuah file (ikuti rantai via). */
function adminCanonicalKey(string $key): string {
    $pages = adminPages();
    $seen = [];
    while (isset($pages[$key]) && !empty($pages[$key]['via']) && !isset($seen[$key])) {
        $seen[$key] = true;
        $key = $pages[$key]['via'];
    }
    return $key;
}

/** Key kanonis dari nama file (mis. 'tour-edit.php' → 'tours'). */
function adminKeyForFile(string $file): string {
    $base = basename($file, '.php');
    $pages = adminPages();
    if (isset($pages[$base])) return adminCanonicalKey($base);
    foreach ($pages as $k => $p) {
        if ($p['file'] === basename($file)) return adminCanonicalKey($k);
    }
    return $base;
}

/** File-file yang tercakup satu key kanonis (untuk status aktif sidebar). */
function adminFilesForKey(string $key): array {
    $out = [];
    foreach (adminPages() as $k => $p) {
        if (adminCanonicalKey($k) === $key) $out[] = $p['file'];
    }
    return $out;
}

/** Role akun admin login (fresh DB). Fallback superadmin bila skema lama. */
function adminRole(?int $adminId = null): string {
    $adminId = $adminId ?? (int)($_SESSION['admin_id'] ?? 0);
    if ($adminId <= 0) return '';
    try {
        $stmt = db()->prepare("SELECT role FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        $role = $stmt->fetchColumn();
        return $role === 'staff' ? 'staff' : 'superadmin';
    } catch (Throwable $e) {
        return 'superadmin';
    }
}

function isSuperadmin(?int $adminId = null): bool {
    return adminRole($adminId) === 'superadmin';
}

/** Daftar page_key yang diberikan ke akun. */
function adminPermissions(?int $adminId = null): array {
    $adminId = $adminId ?? (int)($_SESSION['admin_id'] ?? 0);
    if ($adminId <= 0) return [];
    try {
        $stmt = db()->prepare("SELECT page_key FROM admin_permissions WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {
        return [];
    }
}

/** Apakah akun boleh membuka key halaman (sudah dikanonikalisasi). */
function canAccessPage(string $key, ?int $adminId = null): bool {
    $adminId = $adminId ?? (int)($_SESSION['admin_id'] ?? 0);
    if ($adminId <= 0) return false;
    if (isSuperadmin($adminId)) return true;
    $key = adminCanonicalKey($key);
    if ($key === 'dashboard') return true;
    if ($key === 'admins') return false;
    return in_array($key, adminPermissions($adminId), true);
}

/**
 * Guard halaman admin — pengganti cekLogin().
 * Tanpa argumen: kunci diambil dari nama file pemanggil.
 * Ditolak: endpoint JSON → 403 JSON; halaman biasa → flash + dashboard.
 */
function requireAdminPage(?string $key = null): void {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
    if ($key === null) {
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $caller = $bt[0]['file'] ?? ($_SERVER['PHP_SELF'] ?? '');
        $key = adminKeyForFile($caller);
    }
    if (canAccessPage($key)) return;
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $isJson = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($accept, 'application/json');
    if ($isJson) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'forbidden']);
        exit;
    }
    $_SESSION['admin_flash'] = ['type' => 'danger', 'msg' => t('Anda tidak memiliki akses ke halaman ini.')];
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

/** Guard khusus superadmin (mis. Kelola Admin). */
function requireSuperadmin(): void {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
    if (isSuperadmin()) return;
    $_SESSION['admin_flash'] = ['type' => 'danger', 'msg' => t('Anda tidak memiliki akses ke halaman ini.')];
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

/** Simpan grant staff (ganti total; hanya key valid; 'admins' dikecualikan). */
function setAdminPermissions(int $adminId, array $keys): void {
    $pages = adminPages();
    $valid = [];
    foreach ($keys as $k) {
        $k = (string)$k;
        if (!isset($pages[$k]) || $k === 'admins') continue;
        $valid[adminCanonicalKey($k)] = true;
    }
    db()->prepare("DELETE FROM admin_permissions WHERE admin_id = ?")->execute([$adminId]);
    $stmt = db()->prepare("INSERT IGNORE INTO admin_permissions (admin_id, page_key) VALUES (?, ?)");
    foreach (array_keys($valid) as $k) {
        $stmt->execute([$adminId, $k]);
    }
}

/** Preset centang cepat saat membuat akun staff. */
function adminPresets(): array {
    $inventory = ['tours', 'hotels', 'flights', 'ferries', 'rental-cars', 'attractions', 'transfers', 'trains', 'esim'];
    return [
        'operasional' => ['label' => 'Operasional', 'pages' => array_merge(['dashboard'], $inventory, ['bookings', 'reviews'])],
        'keuangan'    => ['label' => 'Keuangan', 'pages' => ['dashboard', 'bookings', 'payments', 'sales-report', 'accounting', 'reseller-topups']],
        'konten'      => ['label' => 'Konten & Marketing', 'pages' => ['dashboard', 'flash-sales', 'promo-codes', 'collections', 'push-notifications', 'posts', 'reviews', 'faq', 'appearance', 'hero-slides']],
    ];
}

/** Flash sekali-tampil untuk panel admin. */
function adminFlash(string $msg, string $type = 'success'): void {
    $_SESSION['admin_flash'] = ['type' => $type, 'msg' => $msg];
}

function adminFlashHtml(): string {
    if (empty($_SESSION['admin_flash'])) return '';
    $f = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
    $type = in_array($f['type'] ?? '', ['success', 'danger', 'warning', 'info'], true) ? $f['type'] : 'info';
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert" data-testid="admin-flash">'
        . e($f['msg'] ?? '') . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}
