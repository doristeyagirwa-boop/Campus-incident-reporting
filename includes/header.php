<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/functions.php';

$notification_count = get_notification_count(get_db());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Incident Reporting</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/static/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/index.php">
            <i class="bi bi-shield-exclamation"></i> Campus Incident Reporting
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/reports.php">
                        <i class="bi bi-bar-chart"></i> Reports
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-speedometer2"></i> Admin
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-grid"></i> Dashboard</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/incidents.php"><i class="bi bi-exclamation-triangle"></i> Incidents</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/users.php"><i class="bi bi-people"></i> Users</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/reports.php"><i class="bi bi-bar-chart-line"></i> Reports</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if (is_admin_logged_in()): ?>
                            <li><span class="dropdown-item-text text-muted small"><i class="bi bi-person-check"></i> <?= htmlspecialchars($_SESSION['admin_name']) ?></span></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/notifications.php">
                        <i class="bi bi-bell"></i> Notifications
                        <?php if ($notification_count > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?= $notification_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash Messages -->
<div class="container mt-3">
    <?php foreach (get_flash_messages() as $message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
</div>

<!-- Main Content -->
<div class="container mt-4">
