<?php
// Always call session_start() before using any session functions.
// Include this file at the top of every protected page.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirect to login if user is not logged in at all.
 */
function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

/**
 * Redirect to login if user does not have the required role.
 * @param string $role  'student' | 'technician' | 'admin'
 */
function require_role(string $role) {
    require_login();
    if ($_SESSION['role'] !== $role) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

/**
 * Returns true if the currently logged-in user has a given role.
 */
function is_role(string $role): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Returns true if anyone is logged in.
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Create a notification record for a user.
 * Requires $conn to be available in scope or passed in.
 */
function create_notification($conn, int $user_id, int $incident_id, string $message): void {
    $stmt = $conn->prepare(
        "INSERT INTO notifications (user_id, incident_id, message) VALUES (?, ?, ?)"
    );
    $stmt->bind_param('iis', $user_id, $incident_id, $message);
    $stmt->execute();
    $stmt->close();
}

/**
 * Count unread notifications for a user.
 */
function unread_notifications($conn, int $user_id): int {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return (int) $count;
}
