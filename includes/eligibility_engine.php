<?php
require_once __DIR__ . '/compliance_ledger.php';

function eligibility_queue_message(
    mysqli $conn,
    int $target_user_id,
    string $channel,
    string $subject,
    string $message
): bool {
    $stmt = $conn->prepare(
        "INSERT INTO institutional_message_queue
         (target_user_id, channel, subject, message)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            message = VALUES(message),
            status = 'Queued',
            created_at = NOW()"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('isss', $target_user_id, $channel, $subject, $message);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok && function_exists('create_notification')) {
        create_notification($conn, $target_user_id, null, $subject . ': ' . $message);
    }

    return $ok;
}

function eligibility_users_by_role(mysqli $conn, array $roles): array
{
    if (!$roles) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $types = str_repeat('s', count($roles));

    $stmt = $conn->prepare(
        "SELECT user_id, email, role
         FROM users
         WHERE role IN ($placeholders)
         ORDER BY FIELD(role, $placeholders), user_id ASC"
    );

    if (!$stmt) {
        return [];
    }

    $params = array_merge($roles, $roles);
    $stmt->bind_param($types . $types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];

    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'user_id' => (int) $row['user_id'],
            'email' => (string) $row['email'],
            'role' => (string) $row['role'],
        ];
    }

    $stmt->close();

    return $users;
}

function run_exam_eligibility_review(mysqli $conn): int
{
    $result = $conn->query(
        "SELECT u.user_id, u.fullname, u.email,
                ar.record_id, ar.unit_code, ar.unit_name,
                ar.attendance_percent, ar.current_grade,
                fa.balance_amount, fa.clearance_status
         FROM users u
         JOIN student_academic_records ar ON u.user_id = ar.user_id
         JOIN student_finance_accounts fa ON u.user_id = fa.user_id
         WHERE u.role = 'student'
         ORDER BY u.fullname"
    );

    if (!$result) {
        return 0;
    }

    $touched = 0;

    while ($row = $result->fetch_assoc()) {
        $student_id = (int) $row['user_id'];
        $attendance = (float) $row['attendance_percent'];
        $balance = (float) $row['balance_amount'];

        $eligible = ($attendance >= 70.0 && $balance <= 0.0) ? 1 : 0;

        if ($eligible) {
            $reason = 'Eligible. Attendance and clearance are okay.';
        } elseif ($attendance < 70.0 && $balance > 0.0) {
            $reason = 'Not eligible. Academic and clearance review required.';
        } elseif ($attendance < 70.0) {
            $reason = 'Not eligible. Attendance is below 70%.';
        } else {
            $reason = 'Not eligible. Clearance review required.';
        }

        $update = $conn->prepare(
            "UPDATE student_academic_records
             SET exam_eligible = ?,
                 eligibility_reason = ?,
                 last_reviewed_at = NOW()
             WHERE record_id = ?"
        );

        if ($update) {
            $record_id = (int) $row['record_id'];
            $update->bind_param('isi', $eligible, $reason, $record_id);
            $update->execute();
            $update->close();
            $touched++;
        }

        if ($attendance < 70.0) {
            $subject = 'Attendance risk: ' . $row['unit_code'];

            $academic_message = $row['fullname'] .
                ' is below the 70% attendance threshold for ' .
                $row['unit_name'] . '.';

            foreach (eligibility_users_by_role($conn, ['lecturer', 'registrar']) as $staff) {
                eligibility_queue_message(
                    $conn,
                    (int) $staff['user_id'],
                    'Internal',
                    $subject,
                    $academic_message
                );
            }

            foreach (eligibility_users_by_role($conn, ['dean']) as $staff) {
                eligibility_queue_message(
                    $conn,
                    (int) $staff['user_id'],
                    'Internal',
                    'Student academic risk',
                    $row['fullname'] . ' needs academic welfare review.'
                );
            }

            eligibility_queue_message(
                $conn,
                $student_id,
                'Email',
                'Attendance below exam threshold',
                'Your attendance in ' . $row['unit_name'] . ' is below 70%. Please contact your lecturer or registrar.'
            );
        }

        if ($balance > 0.0) {
            foreach (eligibility_users_by_role($conn, ['finance']) as $staff) {
                eligibility_queue_message(
                    $conn,
                    (int) $staff['user_id'],
                    'Internal',
                    'Fee clearance risk',
                    $row['fullname'] . ' has an outstanding finance clearance issue before exams.'
                );
            }

            eligibility_queue_message(
                $conn,
                $student_id,
                'Email',
                'Fee balance before exam clearance',
                'You have an outstanding finance clearance issue. Please contact the finance office before exam approval.'
            );
        }

        if ($eligible) {
            eligibility_queue_message(
                $conn,
                $student_id,
                'Internal',
                'Exam eligibility confirmed',
                'You are currently exam eligible for ' . $row['unit_name'] . '.'
            );
        }
    }

    compliance_log_event(
        $conn,
        'EXAM_ELIGIBILITY_REVIEW_RUN',
        'academic_eligibility_batch',
        date('YmdHi'),
        [
            'records_touched' => $touched,
            'attendance_threshold' => 70,
            'privacy_mode' => 'role-minimized',
        ]
    );

    return $touched;
}
