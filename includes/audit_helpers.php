<?php

function audit_log(
    mysqli $conn,
    string $action,
    string $entity_type,
    ?int $entity_id = null,
    ?string $details = null
): void {
    $actor_user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    $actor_role = isset($_SESSION['role']) ? (string) $_SESSION['role'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $conn->prepare(
        "INSERT INTO audit_logs
         (actor_user_id, actor_role, action, entity_type, entity_id, details, ip_address)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return;
    }

    $stmt->bind_param(
        'isssiss',
        $actor_user_id,
        $actor_role,
        $action,
        $entity_type,
        $entity_id,
        $details,
        $ip_address
    );

    $stmt->execute();
    $stmt->close();
}
