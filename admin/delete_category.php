<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int) ($_POST['category_id'] ?? 0);

    if ($category_id > 0) {
        $pdo = get_db();
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->execute(['id' => $category_id]);
        set_flash('Category deleted successfully!');
    }
}

header('Location: ' . BASE_URL . '/admin/settings.php');
exit;
