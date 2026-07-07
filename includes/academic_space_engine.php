<?php

function academic_space_text(mysqli $conn, int $incident_id): ?array
{
    $stmt = $conn->prepare(
        "SELECT incident_id, title, description, location, user_id
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

    return $incident ?: null;
}

function incident_needs_classroom_relocation(mysqli $conn, int $incident_id): bool
{
    $incident = academic_space_text($conn, $incident_id);

    if (!$incident) {
        return false;
    }

    $text = strtolower(
        (string) $incident['title'] . ' ' .
        (string) $incident['description'] . ' ' .
        (string) $incident['location']
    );

    $signals = [
        'class occupied',
        'classroom occupied',
        'room occupied',
        'lecture room occupied',
        'room unavailable',
        'classroom unavailable',
        'venue conflict',
        'room conflict',
        'double booked',
        'double-booked',
        'no classroom',
        'no class room',
        'lecture moved',
        'students waiting outside',
        'found another class inside',
        'class is occupied',
    ];

    foreach ($signals as $signal) {
        if (str_contains($text, $signal)) {
            return true;
        }
    }

    return false;
}

function recommend_available_classroom(mysqli $conn, int $incident_id): ?array
{
    $incident = academic_space_text($conn, $incident_id);

    if (!$incident) {
        return null;
    }

    $day = date('l');
    $time = date('H:i:s');

    $preferred_building = null;
    $location = strtolower((string) $incident['location']);

    if (str_contains($location, 'sbs')) {
        $preferred_building = 'SBS Block';
    } elseif (str_contains($location, 'msb')) {
        $preferred_building = 'MSB Block';
    } elseif (str_contains($location, 'library')) {
        $preferred_building = 'Library Block';
    } elseif (str_contains($location, 'lt4') || str_contains($location, 'main academic')) {
        $preferred_building = 'Main Academic Block';
    }

    $sql = "
        SELECT c.room_id, c.room_code, c.building, c.room_name, c.capacity, c.room_type,
               COALESCE(latest_status.status, 'Available') AS current_status,
               COALESCE(latest_status.status_note, 'No blocking status recorded.') AS status_note
        FROM classrooms c
        LEFT JOIN (
            SELECT cs1.room_id, cs1.status, cs1.status_note
            FROM classroom_status cs1
            INNER JOIN (
                SELECT room_id, MAX(status_id) AS latest_status_id
                FROM classroom_status
                GROUP BY room_id
            ) latest ON cs1.status_id = latest.latest_status_id
        ) latest_status ON c.room_id = latest_status.room_id
        WHERE c.is_active = 1
          AND COALESCE(latest_status.status, 'Available') = 'Available'
          AND NOT EXISTS (
              SELECT 1
              FROM class_sessions s
              WHERE s.room_id = c.room_id
                AND s.day_of_week = ?
                AND s.start_time <= ?
                AND s.end_time > ?
          )
        ORDER BY
          CASE WHEN c.building = ? THEN 0 ELSE 1 END,
          c.capacity DESC,
          c.room_code ASC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return null;
    }

    $building_for_sort = $preferred_building ?: '';

    $stmt->bind_param('ssss', $day, $time, $time, $building_for_sort);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $room ?: null;
}

function create_academic_space_recommendation(mysqli $conn, int $incident_id): ?string
{
    if (!incident_needs_classroom_relocation($conn, $incident_id)) {
        return null;
    }

    $incident = academic_space_text($conn, $incident_id);

    if (!$incident) {
        return null;
    }

    $existing = $conn->prepare(
        "SELECT recommendation_message
         FROM academic_relocation_recommendations
         WHERE incident_id = ?
         LIMIT 1"
    );

    if ($existing) {
        $existing->bind_param('i', $incident_id);
        $existing->execute();
        $row = $existing->get_result()->fetch_assoc();
        $existing->close();

        if ($row) {
            return (string) $row['recommendation_message'];
        }
    }

    $room = recommend_available_classroom($conn, $incident_id);

    if (!$room) {
        return 'No available classroom could be confirmed. Academic office should manually assign a room.';
    }

    $reason = 'AIOS detected a classroom occupancy or venue conflict and selected an available room using room status and timetable availability.';

    $message = 'Please proceed to ' .
        $room['room_code'] . ' - ' .
        $room['room_name'] . ' in ' .
        $room['building'] .
        '. Capacity: ' . (int) $room['capacity'] . '.';

    $stmt = $conn->prepare(
        "INSERT INTO academic_relocation_recommendations
         (incident_id, from_room_text, recommended_room_id, reason, recommendation_message)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            recommended_room_id = VALUES(recommended_room_id),
            reason = VALUES(reason),
            recommendation_message = VALUES(recommendation_message)"
    );

    if (!$stmt) {
        return null;
    }

    $from_room = (string) $incident['location'];
    $room_id = (int) $room['room_id'];

    $stmt->bind_param(
        'isiss',
        $incident_id,
        $from_room,
        $room_id,
        $reason,
        $message
    );

    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return null;
    }

    if (function_exists('create_notification')) {
        create_notification(
            $conn,
            (int) $incident['user_id'],
            $incident_id,
            'Academic Space AIOS: ' . $message
        );

        $lecturers = $conn->query(
            "SELECT user_id
             FROM users
             WHERE role IN ('lecturer','registrar','admin')
             ORDER BY FIELD(role, 'lecturer','registrar','admin'), user_id ASC"
        );

        if ($lecturers) {
            while ($user = $lecturers->fetch_assoc()) {
                create_notification(
                    $conn,
                    (int) $user['user_id'],
                    $incident_id,
                    'Academic Space AIOS relocation recommendation for incident #' . $incident_id . ': ' . $message
                );
            }
        }
    }

    return $message;
}
