<?php

function fetch_latest_prediction_id(mysqli $conn, int $incident_id): ?int
{
    $stmt = $conn->prepare(
        "SELECT prediction_id
         FROM incident_predictions
         WHERE incident_id = ?
         ORDER BY created_at DESC
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $incident_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ? (int) $row['prediction_id'] : null;
}

function fetch_department_for_route(mysqli $conn, string $route): ?array
{
    $stmt = $conn->prepare(
        "SELECT d.department_id, d.code, d.name, d.severity_level,
                r.default_action, r.auto_assign
         FROM department_route_rules r
         JOIN departments d ON r.department_id = d.department_id
         WHERE r.recommended_route = ?
           AND d.is_active = 1
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $route);
    $stmt->execute();
    $department = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $department ?: null;
}

function find_department_responder(mysqli $conn, int $department_id): ?int
{
    $stmt = $conn->prepare(
        "SELECT u.user_id
         FROM department_members dm
         JOIN users u ON dm.user_id = u.user_id
         WHERE dm.department_id = ?
         ORDER BY dm.is_primary DESC, dm.member_id ASC
         LIMIT 1"
    );

    if ($stmt) {
        $stmt->bind_param('i', $department_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            return (int) $row['user_id'];
        }
    }

    $fallback = $conn->query(
        "SELECT user_id
         FROM users
         WHERE role = 'technician'
         ORDER BY user_id ASC
         LIMIT 1"
    );

    if ($fallback && ($row = $fallback->fetch_assoc())) {
        return (int) $row['user_id'];
    }

    return null;
}

function fetch_incident_for_duty(mysqli $conn, int $incident_id): ?array
{
    $stmt = $conn->prepare(
        "SELECT i.incident_id, i.title, i.user_id AS reporter_id
         FROM incidents i
         WHERE i.incident_id = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $incident_id);
    $stmt->execute();
    $incident = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $incident ?: null;
}

function allocate_incident_duty(mysqli $conn, array $prediction): bool
{
    $incident_id = (int) $prediction['incident_id'];
    $route = (string) $prediction['recommended_route'];

    $department = fetch_department_for_route($conn, $route);
    $incident = fetch_incident_for_duty($conn, $incident_id);

    if (!$department || !$incident) {
        return false;
    }

    $department_id = (int) $department['department_id'];
    $prediction_id = fetch_latest_prediction_id($conn, $incident_id);
    $assigned_user_id = find_department_responder($conn, $department_id);

    $status = $assigned_user_id ? 'Assigned' : 'Queued';

    $risk_score = (float) ($prediction['predicted_risk_score'] ?? 0);
    $predicted_priority = (string) ($prediction['predicted_priority'] ?? 'Medium');

    if ($risk_score >= 80 || $predicted_priority === 'High') {
        $status = $assigned_user_id ? 'In Progress' : 'Queued';
    }

    $summary = 'AIOS routed this incident to ' . $department['name'] .
        '. Recommended route: ' . $route .
        '. Recommended action: ' . $prediction['recommended_action'];

    $stmt = $conn->prepare(
        "INSERT INTO incident_duties
         (incident_id, department_id, prediction_id, assigned_user_id, duty_status, duty_summary)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            prediction_id = VALUES(prediction_id),
            assigned_user_id = VALUES(assigned_user_id),
            duty_status = VALUES(duty_status),
            duty_summary = VALUES(duty_summary),
            updated_at = NOW()"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        'iiiiss',
        $incident_id,
        $department_id,
        $prediction_id,
        $assigned_user_id,
        $status,
        $summary
    );

    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return false;
    }

    if (($risk_score >= 80 || $predicted_priority === 'High') && function_exists('create_notification')) {
        $admin_result = $conn->query(
            "SELECT user_id
             FROM users
             WHERE role = 'admin'
             ORDER BY user_id ASC"
        );

        if ($admin_result) {
            while ($admin = $admin_result->fetch_assoc()) {
                create_notification(
                    $conn,
                    (int) $admin['user_id'],
                    $incident_id,
                    'CRITICAL AIOS escalation: incident #' . $incident_id . ' requires immediate command review.'
                );
            }
        }
    }

    if ($assigned_user_id && function_exists('create_notification')) {
        create_notification(
            $conn,
            $assigned_user_id,
            $incident_id,
            'AIOS assigned a department duty to you for incident #' . $incident_id . ': ' . $incident['title'] . '.'
        );
    }

    if (function_exists('create_notification')) {
        create_notification(
            $conn,
            (int) $incident['reporter_id'],
            $incident_id,
            'Your incident "' . $incident['title'] . '" was routed to ' . $department['name'] . '.'
        );
    }

    if (function_exists('audit_log')) {
        audit_log(
            $conn,
            'AIOS_DUTY_ALLOCATED',
            'incident',
            $incident_id,
            'Routed to ' . $department['name'] . ' with duty status ' . $status
        );
    }

    return true;
}
