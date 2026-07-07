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

function detect_dining_location_from_incident(mysqli $conn, int $incident_id): ?array
{
    $stmt = $conn->prepare(
        "SELECT title, description, location
         FROM incidents
         WHERE incident_id = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $incident_id);
    $stmt->execute();
    $incident = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$incident) {
        return null;
    }

    $text = strtolower(
        (string) $incident['title'] . ' ' .
        (string) $incident['description'] . ' ' .
        (string) $incident['location']
    );

    $code = null;

    if (str_contains($text, 'cafe one') || str_contains($text, 'café one')) {
        $code = 'CAFE_ONE';
    } elseif (str_contains($text, 'cafe two') || str_contains($text, 'café two')) {
        $code = 'CAFE_TWO';
    } elseif (str_contains($text, 'cafe three') || str_contains($text, 'café three')) {
        $code = 'CAFE_THREE';
    } elseif (str_contains($text, 'main kitchen') || str_contains($text, 'production kitchen')) {
        $code = 'MAIN_KITCHEN';
    }

    if (!$code) {
        return null;
    }

    $location_stmt = $conn->prepare(
        "SELECT dl.location_id, dl.code, dl.name, dl.manager_user_id,
                u.email AS manager_email
         FROM dining_locations dl
         LEFT JOIN users u ON dl.manager_user_id = u.user_id
         WHERE dl.code = ?
           AND dl.is_active = 1
         LIMIT 1"
    );

    if (!$location_stmt) {
        return null;
    }

    $location_stmt->bind_param('s', $code);
    $location_stmt->execute();
    $location = $location_stmt->get_result()->fetch_assoc();
    $location_stmt->close();

    return $location ?: null;
}

function detect_finished_food_item(mysqli $conn, int $incident_id): ?string
{
    $stmt = $conn->prepare(
        "SELECT title, description
         FROM incidents
         WHERE incident_id = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $incident_id);
    $stmt->execute();
    $incident = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$incident) {
        return null;
    }

    $text = strtolower((string) $incident['title'] . ' ' . (string) $incident['description']);

    $items = [
        'rice' => 'Rice',
        'beans' => 'Beans',
        'ugali' => 'Ugali',
        'chapati' => 'Chapati',
        'tea' => 'Tea',
        'milk' => 'Milk',
        'chicken' => 'Chicken',
        'beef' => 'Beef',
        'vegetables' => 'Vegetables',
        'food' => 'Food',
    ];

    $finished_words = ['finished', 'done', 'out of stock', 'stock finished', 'not available', 'unavailable'];

    $has_finished_signal = false;

    foreach ($finished_words as $word) {
        if (str_contains($text, $word)) {
            $has_finished_signal = true;
            break;
        }
    }

    if (!$has_finished_signal) {
        return null;
    }

    foreach ($items as $keyword => $label) {
        if (str_contains($text, $keyword)) {
            return $label;
        }
    }

    return 'Food';
}

function allocate_incident_duty(mysqli $conn, array $prediction): bool
{
    $incident_id = (int) $prediction['incident_id'];
    $route = (string) $prediction['recommended_route'];

    $department = fetch_department_for_route($conn, $route);
    /*
     * University-grade escalation:
     * If a lecturer attendance issue affects official records,
     * registrar must also be notified through a second institutional duty.
     */
    $incident = fetch_incident_for_duty($conn, $incident_id);

    if (!$department || !$incident) {
        return false;
    }

    $department_id = (int) $department['department_id'];
    $prediction_id = fetch_latest_prediction_id($conn, $incident_id);
    $assigned_user_id = find_department_responder($conn, $department_id);
    $dining_location = null;
    $finished_food_item = null;

    if ($route === 'Cafes, Dining & Retail') {
        $dining_location = detect_dining_location_from_incident($conn, $incident_id);
        $finished_food_item = detect_finished_food_item($conn, $incident_id);

        if ($dining_location && !empty($dining_location['manager_user_id'])) {
            $assigned_user_id = (int) $dining_location['manager_user_id'];
        }
    }

    /*
     * Prevent stale department duties.
     * If a route changes from IT to Registrar, old incorrect department duties are removed.
     * Secondary academic duties are created later when needed.
     */
    $cleanup_stmt = $conn->prepare(
        "DELETE FROM incident_duties
         WHERE incident_id = ?
           AND department_id <> ?"
    );

    if ($cleanup_stmt) {
        $cleanup_stmt->bind_param('ii', $incident_id, $department_id);
        $cleanup_stmt->execute();
        $cleanup_stmt->close();
    }

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

    if (
        $route === 'Cafes, Dining & Retail'
        && $dining_location
        && $finished_food_item
        && function_exists('create_dining_stock_alert')
    ) {
        create_dining_stock_alert(
            $conn,
            (string) $dining_location['code'],
            $finished_food_item,
            'Finished',
            $incident_id
        );
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

    if ($route === 'Academic Registrar Escalation') {
        $lecturer_department = fetch_department_for_route($conn, 'Lecturer / Course Owner Review');

        if ($lecturer_department) {
            $lecturer_department_id = (int) $lecturer_department['department_id'];
            $lecturer_user_id = find_department_responder($conn, $lecturer_department_id);

            $extra_summary = 'Secondary academic duty created because registrar escalation may require lecturer attendance validation.';

            $extra_stmt = $conn->prepare(
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

            if ($extra_stmt) {
                $extra_status = $lecturer_user_id ? 'Assigned' : 'Queued';

                $extra_stmt->bind_param(
                    'iiiiss',
                    $incident_id,
                    $lecturer_department_id,
                    $prediction_id,
                    $lecturer_user_id,
                    $extra_status,
                    $extra_summary
                );

                $extra_stmt->execute();
                $extra_stmt->close();

                if ($lecturer_user_id && function_exists('create_notification')) {
                    create_notification(
                        $conn,
                        $lecturer_user_id,
                        $incident_id,
                        'AIOS academic escalation: lecturer validation required for incident #' . $incident_id . '.'
                    );
                }
            }
        }
    }

    if ($route === 'Lecturer / Course Owner Review' && $risk_score >= 55) {
        $registrar_department = fetch_department_for_route($conn, 'Academic Registrar Escalation');

        if ($registrar_department) {
            $registrar_department_id = (int) $registrar_department['department_id'];
            $registrar_user_id = find_department_responder($conn, $registrar_department_id);

            $extra_summary = 'Secondary registrar duty created because attendance/lecturer issue may affect official academic eligibility.';

            $extra_stmt = $conn->prepare(
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

            if ($extra_stmt) {
                $extra_status = $registrar_user_id ? 'Assigned' : 'Queued';

                $extra_stmt->bind_param(
                    'iiiiss',
                    $incident_id,
                    $registrar_department_id,
                    $prediction_id,
                    $registrar_user_id,
                    $extra_status,
                    $extra_summary
                );

                $extra_stmt->execute();
                $extra_stmt->close();

                if ($registrar_user_id && function_exists('create_notification')) {
                    create_notification(
                        $conn,
                        $registrar_user_id,
                        $incident_id,
                        'AIOS registrar escalation: academic record review required for incident #' . $incident_id . '.'
                    );
                }
            }
        }
    }

    return true;
}
