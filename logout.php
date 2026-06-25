<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

$_SESSION = [];
session_destroy();
header('Location: ' . SITE_URL . '/login.php');
exit;
