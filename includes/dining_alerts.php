<?php

function create_dining_stock_alert(
    mysqli $conn,
    string $location_code,
    string $item_name,
    string $stock_status,
    ?int $incident_id = null
): bool {
    $stmt = $conn->prepare(
        "SELECT location_id, manager_user_id, name
         FROM dining_locations
         WHERE code = ?
           AND is_active = 1
         LIMIT 1"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $location_code);
    $stmt->execute();
    $location = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$location) {
        return false;
    }

    $message = $item_name . ' stock status at ' . $location['name'] . ': ' . $stock_status . '.';

    if ($stock_status === 'Finished') {
        $message .= ' Immediate replenishment required from Main Production Kitchen.';
    } elseif ($stock_status === 'Low') {
        $message .= ' Replenishment warning generated.';
    }

    $insert = $conn->prepare(
        "INSERT INTO dining_stock_alerts
         (location_id, item_name, stock_status, alert_message, incident_id)
         VALUES (?, ?, ?, ?, ?)"
    );

    if (!$insert) {
        return false;
    }

    $location_id = (int) $location['location_id'];

    $insert->bind_param(
        'isssi',
        $location_id,
        $item_name,
        $stock_status,
        $message,
        $incident_id
    );

    $ok = $insert->execute();
    $insert->close();

    if (!$ok) {
        return false;
    }

    if (!empty($location['manager_user_id']) && function_exists('create_notification')) {
        create_notification(
            $conn,
            (int) $location['manager_user_id'],
            $incident_id,
            $message
        );
    }

    if ($stock_status === 'Finished' && function_exists('create_notification')) {
        $kitchen = $conn->query(
            "SELECT u.user_id
             FROM users u
             JOIN dining_locations dl ON dl.manager_user_id = u.user_id
             WHERE dl.code = 'MAIN_KITCHEN'
             LIMIT 1"
        );

        if ($kitchen && ($row = $kitchen->fetch_assoc())) {
            create_notification(
                $conn,
                (int) $row['user_id'],
                $incident_id,
                $item_name . ' is finished at ' . $location['name'] . '. Prepare replenishment from Main Production Kitchen.'
            );
        }
    }

    return true;
}
