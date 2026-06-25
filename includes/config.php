<?php
// ============================================================
// CampusGuard — Configuration
//
// This is the ONLY file you need to change when setting up
// on a new machine. Update the values below to match your
// local XAMPP / WAMP / LAMP setup.
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'campus_guard');
define('DB_USER', 'root');      // Change if your MySQL user is different
define('DB_PASS', '');          // Change if you have a MySQL password set

define('SITE_NAME', 'CampusGuard');
define('SITE_URL',  'http://localhost/campusguard');  // No trailing slash

define('UPLOAD_DIR',     __DIR__ . '/../uploads/');
define('UPLOAD_MAX_MB',  5);
define('UPLOAD_ALLOWED', ['jpg', 'jpeg', 'png', 'pdf', 'gif']);
