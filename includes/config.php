<?php
// ============================================================
// CampusGuard — Configuration
// Local development configuration for PHP frontend.
// ============================================================

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'campus_guard');
define('DB_USER', 'campusguard_user');
define('DB_PASS', 'CampusGuardLocal123!');

define('SITE_NAME', 'CampusGuard');
define('SITE_URL', 'http://localhost:8080');

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_MAX_MB', 5);
define('UPLOAD_ALLOWED', ['jpg', 'jpeg', 'png', 'pdf', 'gif']);
