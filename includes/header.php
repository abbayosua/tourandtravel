<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? SITE_NAME) ?> - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- Navbar Line 1: Logo + User -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top pb-0">
    <div class="container">
        <a class="navbar-brand fw-bold py-2" href="<?= BASE_URL ?>/">
            <i class="bi bi-airplane-engines-fill"></i> <?= SITE_NAME ?>
        </a>
        <!-- Search bar muncul pas scroll -->
        <div class="mx-auto d-none d-lg-block" style="flex: 1; max-width: 400px;">
            <div class="nav-search-wrapper search-wrapper">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted small"></i></span>
                    <input type="text" class="form-control border-0 shadow-none small" placeholder="Cari destinasi..." id="navSearch" autocomplete="off" onkeypress="if(event.key==='Enter' && this.value.trim()) window.location='tours.php?search='+encodeURIComponent(this.value)">
                </div>
                <div class="search-dropdown" id="navSearchDropdown"></div>
            </div>
        </div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link py-2 <?= basename($_SERVER['PHP_SELF']) === 'wishlist.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/wishlist.php">
                        <i class="bi bi-heart"></i>
                    </a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle py-2" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i><?= e($_SESSION['user_name'] ?? 'User') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-circle me-2"></i>Profil</a></li>
                        <li><a class="dropdown-item" href="my-bookings.php"><i class="bi bi-ticket-perforated me-2"></i>Booking Saya</a></li>
                        <li><a class="dropdown-item" href="wishlist.php"><i class="bi bi-heart me-2"></i>Wishlist</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link py-2" href="login.php"><i class="bi bi-person me-1"></i>Masuk</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Navbar Line 2: Category tabs -->
<?php
$navCategories = ['Domestik', 'Internasional', 'China', 'Jepang', 'Korea Selatan', 'Vietnam', 'Taiwan', 'Kanada', 'Kazakhstan'];
?>
<nav class="navbar navbar-expand navbar-dark bg-primary pt-0 border-top border-white border-opacity-10" style="margin-top: -1px;">
    <div class="container">
        <ul class="navbar-nav flex-row gap-1 overflow-auto py-1 w-100 kategori-scroll">
            <li class="nav-item">
                <a class="nav-link text-white fw-semibold small py-1 px-3 rounded-3 <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active bg-white bg-opacity-25' : '' ?>" href="<?= BASE_URL ?>/">Semua</a>
            </li>
            <?php foreach ($navCategories as $cat): ?>
                <?php
                $isActive = (basename($_SERVER['PHP_SELF']) === 'tours.php' && ($_GET['category'] ?? '') === $cat);
                ?>
                <li class="nav-item">
                    <a class="nav-link text-white-50 small py-1 px-3 rounded-3 flex-shrink-0 <?= $isActive ? 'active bg-white bg-opacity-25 text-white' : '' ?>" href="<?= BASE_URL ?>/tours.php?category=<?= urlencode($cat) ?>"><?= e($cat) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
