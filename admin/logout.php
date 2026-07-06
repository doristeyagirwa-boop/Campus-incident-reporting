<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';

unset($_SESSION['admin_id'], $_SESSION['admin_name']);
set_flash('You have been logged out.');
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
