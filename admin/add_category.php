<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name !== '') {
        $pdo = get_db();
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
        $stmt->execute(['name' => $name, 'description' => $description]);
        set_flash('Category added successfully!');
    }
}

header('Location: ' . BASE_URL . '/admin/settings.php');
exit;
