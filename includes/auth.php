<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirect_to(string $path): void
{
    header('Location: ' . SITE_URL . $path);
    exit;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id'])
        && !empty($_SESSION['role'])
        && !empty($_SESSION['email']);
}

function is_role(string $role): bool
{
    return is_logged_in() && $_SESSION['role'] === $role;
}

function dashboard_for_role(string $role): string
{
    return match ($role) {
        'student' => SITE_URL . '/student/dashboard.php',
        'admin' => SITE_URL . '/admin/dashboard.php',
        'technician' => SITE_URL . '/technician/dashboard.php',

        'registrar',
        'lecturer',
        'dean',
        'finance',
        'kitchen_manager',
        'dining_manager',
        'maintenance',
        'security' => SITE_URL . '/institutional/dashboard.php',

        default => SITE_URL . '/login.php',
    };
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect_to('/login.php');
    }
}

function require_role(string $role): void
{
    require_login();

    if ($_SESSION['role'] !== $role) {
        redirect_to(dashboard_for_role((string) $_SESSION['role']));
    }
}

function redirect_after_login(string $role): void
{
    redirect_to(dashboard_for_role($role));
}

function create_notification($conn, int $user_id, ?int $incident_id, string $message): void
{
    $stmt = $conn->prepare(
        "INSERT INTO notifications (user_id, incident_id, message) VALUES (?, ?, ?)"
    );

    if (!$stmt) {
        return;
    }

    $stmt->bind_param('iis', $user_id, $incident_id, $message);
    $stmt->execute();
    $stmt->close();
}

function unread_notifications($conn, int $user_id): int
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0"
    );

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return (int) $count;
}
