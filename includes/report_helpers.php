<?php

function report_safe(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function report_count(mysqli $conn, string $sql): int
{
    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_row();

    return (int) ($row[0] ?? 0);
}

function report_status_breakdown(mysqli $conn): array
{
    $stats = [
        'Open' => 0,
        'Assigned' => 0,
        'In Progress' => 0,
        'Resolved' => 0,
        'Closed' => 0,
    ];

    $result = $conn->query(
        "SELECT status, COUNT(*) AS total
         FROM incidents
         GROUP BY status"
    );

    if (!$result) {
        return $stats;
    }

    while ($row = $result->fetch_assoc()) {
        $status = (string) $row['status'];

        if (array_key_exists($status, $stats)) {
            $stats[$status] = (int) $row['total'];
        }
    }

    return $stats;
}

function report_priority_breakdown(mysqli $conn): array
{
    $stats = [
        'High' => 0,
        'Medium' => 0,
        'Low' => 0,
    ];

    $result = $conn->query(
        "SELECT priority, COUNT(*) AS total
         FROM incidents
         GROUP BY priority"
    );

    if (!$result) {
        return $stats;
    }

    while ($row = $result->fetch_assoc()) {
        $priority = (string) $row['priority'];

        if (array_key_exists($priority, $stats)) {
            $stats[$priority] = (int) $row['total'];
        }
    }

    return $stats;
}

function report_category_breakdown(mysqli $conn): array
{
    $rows = [];

    $result = $conn->query(
        "SELECT c.category_name, COUNT(i.incident_id) AS total
         FROM categories c
         LEFT JOIN incidents i ON i.category_id = c.category_id
         GROUP BY c.category_id, c.category_name
         ORDER BY total DESC, c.category_name ASC"
    );

    if (!$result) {
        return $rows;
    }

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}
