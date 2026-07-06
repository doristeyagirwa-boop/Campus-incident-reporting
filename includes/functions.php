<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function set_flash(string $message): void
{
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = $message;
}

function get_flash_messages(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    $_SESSION['flash_messages'] = [];
    return $messages;
}

function is_admin_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}

function require_admin_login(): void
{
    if (!is_admin_logged_in()) {
        set_flash('Please log in to access the admin area.');
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

function get_notification_count(PDO $pdo): int
{
    $user_id = $_SESSION['user_id'] ?? 1;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = FALSE');
    $stmt->execute(['user_id' => $user_id]);
    return (int) $stmt->fetchColumn();
}
