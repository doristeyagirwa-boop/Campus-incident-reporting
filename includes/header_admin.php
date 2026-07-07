<?php
// includes/header_admin.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title ?? SITE_NAME . ' Admin') ?></title>
<link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body>
<div class="app-shell">

  <aside class="sidebar sidebar-admin">
    <div class="sidebar-brand">
      <span class="crest">CG</span>
      <span><?= SITE_NAME ?></span>
    </div>
    <div class="admin-tag">Admin Panel</div>
    <nav class="sidebar-nav">
      <a href="<?= SITE_URL ?>/admin/dashboard.php"
         class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
        <span class="nav-icon">📊</span> Dashboard
      </a>
      <a href="<?= SITE_URL ?>/admin/reports.php"
         class="<?= $current_page === 'reports.php' ? 'active' : '' ?>">
         <span class="nav-icon">📈</span> Reports
      </a>
      <a href="<?= SITE_URL ?>/admin/intelligence.php"
         class="<?= $current_page === 'intelligence.php' ? 'active' : '' ?>">
         <span class="nav-icon">🧠</span> Intelligence
      </a>
      <a href="<?= SITE_URL ?>/admin/neural_intelligence.php"
         class="<?= $current_page === 'neural_intelligence.php' ? 'active' : '' ?>">
         <span class="nav-icon">🧬</span> Neural Engine
      </a>
      <a href="<?= SITE_URL ?>/admin/department_command.php"
         class="<?= $current_page === 'department_command.php' ? 'active' : '' ?>">
         <span class="nav-icon">🏢</span> Department Command
      </a>
      <a href="<?= SITE_URL ?>/admin/academic_command.php"
         class="<?= $current_page === 'academic_command.php' ? 'active' : '' ?>">
         <span class="nav-icon">🎓</span> Academic Command
      </a>
      <a href="<?= SITE_URL ?>/admin/dining_command.php"
         class="<?= $current_page === 'dining_command.php' ? 'active' : '' ?>">
         <span class="nav-icon">☕</span> Dining Command
      </a>
      <a href="<?= SITE_URL ?>/admin/institution_accounts.php"
         class="<?= $current_page === 'institution_accounts.php' ? 'active' : '' ?>">
         <span class="nav-icon">🏛️</span> Institution Accounts
      </a>
      <a href="<?= SITE_URL ?>/admin/incidents.php"
         class="<?= $current_page === 'incidents.php' ? 'active' : '' ?>">
        <span class="nav-icon">🗂️</span> Manage Incidents
      </a>
      <a href="<?= SITE_URL ?>/admin/users.php"
         class="<?= $current_page === 'users.php' ? 'active' : '' ?>">
        <span class="nav-icon">👥</span> Manage Users
      </a>
    </nav>
    <div class="sidebar-footer">
      Logged in as<br>
      <strong><?= htmlspecialchars($_SESSION['fullname'] ?? '') ?></strong>
      <a href="<?= SITE_URL ?>/logout.php" class="logout-link">Log out</a>
    </div>
  </aside>

  <div class="main">
    <div class="topstrip">
      <h1><?= htmlspecialchars($page_title ?? '') ?></h1>
      <div class="user-chip">
        <div class="avatar admin-avatar"><?= strtoupper(substr($_SESSION['fullname'] ?? 'A', 0, 2)) ?></div>
        <?= htmlspecialchars($_SESSION['fullname'] ?? '') ?>
        <span class="role-tag">Admin</span>
      </div>
    </div>
    <div class="content">
