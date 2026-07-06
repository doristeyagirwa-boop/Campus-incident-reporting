<?php

function student_status_class(string $status): string
{
    return match ($status) {
        'Open' => 's-open',
        'Assigned' => 's-assigned',
        'In Progress' => 's-progress',
        'Resolved' => 's-resolved',
        'Closed' => 's-closed',
        default => 's-open',
    };
}

function student_status_badge(string $status): string
{
    return match ($status) {
        'Open' => 'b-open',
        'Assigned' => 'b-assigned',
        'In Progress' => 'b-progress',
        'Resolved' => 'b-resolved',
        'Closed' => 'b-closed',
        default => 'b-open',
    };
}

function student_priority_badge(string $priority): string
{
    return match ($priority) {
        'High' => 'b-high',
        'Medium' => 'b-medium',
        'Low' => 'b-low',
        default => 'b-medium',
    };
}

function student_safe(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function student_format_date(string|null $value): string
{
    if (!$value) {
        return 'N/A';
    }

    $timestamp = strtotime($value);

    if (!$timestamp) {
        return 'N/A';
    }

    return date('d M Y, H:i', $timestamp);
}

function student_initials(string|null $name): string
{
    $name = trim((string) $name);

    if ($name === '') {
        return 'ST';
    }

    $parts = preg_split('/\s+/', $name);

    $first = strtoupper(substr($parts[0] ?? 'S', 0, 1));
    $second = strtoupper(substr($parts[1] ?? $parts[0] ?? 'T', 0, 1));

    return $first . $second;
}

function student_unread_notification_count(mysqli $conn, int $user_id): int
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

function student_incident_counts(mysqli $conn, int $user_id): array
{
    $stats = [
        'total' => 0,
        'open' => 0,
        'assigned' => 0,
        'progress' => 0,
        'resolved' => 0,
        'closed' => 0,
    ];

    $stmt = $conn->prepare(
        "SELECT status, COUNT(*) AS total
         FROM incidents
         WHERE user_id = ?
         GROUP BY status"
    );

    if (!$stmt) {
        return $stats;
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    $rows = $stmt->get_result();

    while ($row = $rows->fetch_assoc()) {
        $status = (string) $row['status'];
        $count = (int) $row['total'];

        $stats['total'] += $count;

        if ($status === 'Open') {
            $stats['open'] += $count;
        }

        if ($status === 'Assigned') {
            $stats['assigned'] += $count;
        }

        if ($status === 'In Progress') {
            $stats['progress'] += $count;
        }

        if ($status === 'Resolved') {
            $stats['resolved'] += $count;
        }

        if ($status === 'Closed') {
            $stats['closed'] += $count;
        }
    }

    $stmt->close();

    return $stats;
}
