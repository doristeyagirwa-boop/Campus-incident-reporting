<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$incident_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$comment_text = trim($_POST['comment_text'] ?? '');
$user_id = $_SESSION['user_id'] ?? 1;

if ($incident_id && $comment_text !== '') {
    $pdo = get_db();

    $stmt = $pdo->prepare("INSERT INTO comments (incident_id, user_id, comment_text) VALUES (:incident_id, :user_id, :comment_text)");
    $stmt->execute([
        'incident_id' => $incident_id,
        'user_id' => $user_id,
        'comment_text' => $comment_text,
    ]);

    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)");
    $stmt->execute([
        'user_id' => $user_id,
        'message' => "New comment added on incident #$incident_id",
    ]);

    set_flash('Comment added successfully!');
}

header('Location: ' . BASE_URL . '/comments.php?id=' . $incident_id);
exit;
