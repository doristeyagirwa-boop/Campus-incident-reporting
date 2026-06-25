<?php
// includes/header_student.php
// Include AFTER require_login() / require_role() checks.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$notif_count = is_logged_in() ? unread_notifications($conn, $_SESSION['user_id']) : 0;
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title ?? SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body>
<div class="app-shell">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <span class="crest">CG</span>
      <span><?= SITE_NAME ?></span>
    </div>
    <nav class="sidebar-nav">
      <a href="<?= SITE_URL ?>/student/dashboard.php"
         class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
        <span class="nav-icon">📊</span> Dashboard
      </a>
      <a href="<?= SITE_URL ?>/student/report.php"
         class="<?= $current_page === 'report.php' ? 'active' : '' ?>">
        <span class="nav-icon">➕</span> Report Incident
      </a>
      <a href="<?= SITE_URL ?>/student/my_reports.php"
         class="<?= $current_page === 'my_reports.php' ? 'active' : '' ?>">
        <span class="nav-icon">📁</span> My Reports
      </a>
      <a href="<?= SITE_URL ?>/student/notifications.php"
         class="<?= $current_page === 'notifications.php' ? 'active' : '' ?>">
        <span class="nav-icon">🔔</span> Notifications
        <?php if ($notif_count > 0): ?>
          <span class="notif-badge"><?= $notif_count ?></span>
        <?php endif; ?>
      </a>
      <a href="<?= SITE_URL ?>/student/profile.php"
         class="<?= $current_page === 'profile.php' ? 'active' : '' ?>">
        <span class="nav-icon">👤</span> Profile
      </a>
    </nav>
    <div class="sidebar-footer">
      Logged in as<br>
      <strong><?= htmlspecialchars($_SESSION['fullname'] ?? '') ?></strong>
      <a href="<?= SITE_URL ?>/logout.php" class="logout-link">Log out</a>
    </div>
  </aside>

  <!-- Main area -->
  <div class="main">
    <div class="topstrip">
      <h1><?= htmlspecialchars($page_title ?? '') ?></h1>
      <div class="user-chip">
        <div class="avatar"><?= strtoupper(substr($_SESSION['fullname'] ?? 'U', 0, 2)) ?></div>
        <?= htmlspecialchars($_SESSION['fullname'] ?? '') ?>
      </div>
    </div>
    <div class="content">
