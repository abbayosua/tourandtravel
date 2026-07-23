<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Admin') ?> - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand navbar-dark bg-primary sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="bi bi-airplane-engines-fill"></i> Admin Panel
        </a>
        <div class="d-flex align-items-center">
            <span class="text-white me-3 small"><?= e($_SESSION['admin_username']) ?></span>
            <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 bg-dark sidebar p-3 d-none d-md-block">
            <nav class="nav flex-column">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'tours.php' ? 'active' : '' ?>" href="tours.php">
                    <i class="bi bi-map"></i> Kelola Tour
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'active' : '' ?>" href="bookings.php">
                    <i class="bi bi-ticket-perforated"></i> Kelola Booking
                </a>
                <hr class="border-secondary">
                <a class="nav-link" href="../index.php" target="_blank">
                    <i class="bi bi-globe"></i> Lihat Website
                </a>
            </nav>
        </div>
        <div class="col-md-10 ms-auto p-4">
