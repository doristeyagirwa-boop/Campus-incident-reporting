<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to their dashboard
if (is_logged_in()) {
    if (is_role('admin') || is_role('technician')) {
        header('Location: ' . SITE_URL . '/admin/dashboard.php');
    } else {
        header('Location: ' . SITE_URL . '/student/dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= SITE_NAME ?> — Campus Incident Reporting</title>
<link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body>

<!-- Top Bar -->
<header class="topbar">
  <div class="topbar-brand">
    <span class="crest">CG</span>
    <?= SITE_NAME ?>
  </div>
  <nav class="topbar-nav">
    <a href="<?= SITE_URL ?>/login.php">Login</a>
    <a href="<?= SITE_URL ?>/register.php" class="btn btn-primary btn-sm">Register</a>
  </nav>
</header>

<!-- Hero -->
<section class="hero">
  <div class="hero-eyebrow">Internet Programming Project · CNS 2106</div>
  <h1>Report campus incidents.<br>Track them to resolution.</h1>
  <p>One platform for students and staff to log network, security, infrastructure and academic issues — and follow every one through to a fix.</p>
  <div class="hero-actions">
    <a href="<?= SITE_URL ?>/register.php" class="btn btn-primary">Report an Incident</a>
    <a href="<?= SITE_URL ?>/login.php"    class="btn btn-ghost">I already have an account</a>
  </div>
</section>

<!-- Category Cards (overlap hero) -->
<div class="category-strip">
  <div class="category-grid">
    <div class="cat-card net">
      <span class="cat-icon">📶</span>
      <h3>Network</h3>
      <p>WiFi outages, slow internet, rogue access points, authentication failures.</p>
    </div>
    <div class="cat-card sec">
      <span class="cat-icon">🛡️</span>
      <h3>Security</h3>
      <p>Phishing emails, suspicious activity, unauthorised access attempts.</p>
    </div>
    <div class="cat-card infra">
      <span class="cat-icon">🏗️</span>
      <h3>Infrastructure</h3>
      <p>Power outages, broken equipment, facility faults.</p>
    </div>
    <div class="cat-card acad">
      <span class="cat-icon">🎓</span>
      <h3>Academic</h3>
      <p>LMS errors, computer lab faults, portal access issues.</p>
    </div>
  </div>
</div>

<!-- How it works -->
<div class="how-section">
  <div class="section-head">
    <div class="section-eyebrow">Process</div>
    <h2>How it works</h2>
  </div>
  <div class="how-steps">
    <div class="how-step">
      <div class="step-num">1</div>
      <h4>Submit a report</h4>
      <p>Describe the issue, pick a category and priority, attach evidence if you have it.</p>
    </div>
    <div class="how-step">
      <div class="step-num">2</div>
      <h4>Get triaged</h4>
      <p>Admin staff review, assign priority and route it to the right technician.</p>
    </div>
    <div class="how-step">
      <div class="step-num">3</div>
      <h4>Track progress</h4>
      <p>Watch the status update from Open through to Resolved in real time.</p>
    </div>
    <div class="how-step">
      <div class="step-num">4</div>
      <h4>Confirm resolution</h4>
      <p>Comment on your report, get notified, and close the loop.</p>
    </div>
  </div>
</div>

<!-- Footer -->
<footer class="site-footer">
  <div class="footer-inner">
    <div>
      <div class="footer-brand"><span class="crest" style="width:26px;height:26px;font-size:11px;">CG</span> <?= SITE_NAME ?></div>
      <p>A centralised incident reporting platform built for university campuses, covering network, security, infrastructure and academic issues.</p>
    </div>
    <div class="footer-col">
      <h5>Quick Links</h5>
      <a href="<?= SITE_URL ?>/login.php">Login</a>
      <a href="<?= SITE_URL ?>/register.php">Register</a>
    </div>
    <div class="footer-col">
      <h5>Contact</h5>
      <span>support@campusguard.ac.ke</span>
      <span>IT Helpdesk, Block C</span>
      <span>Mon–Fri, 8am–5pm</span>
    </div>
  </div>
  <div class="footer-bottom">
    <span>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Web Programming Final Project.</span>
    <span>Nairobi, Kenya</span>
  </div>
</footer>

<script src="<?= SITE_URL ?>/js/app.js"></script>
</body>
</html>
