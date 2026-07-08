<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/eligibility_engine.php';

run_exam_eligibility_review($conn);

$user_id = (int) ($_SESSION['user_id'] ?? 0);

function inst_safe(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$stmt = $conn->prepare(
    "SELECT user_id, fullname, email, role
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);

$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: ' . SITE_URL . '/logout.php');
    exit;
}

$role = (string) $user['role'];
$name = (string) $user['fullname'];
$email = (string) $user['email'];

$role_title = match ($role) {
    'registrar' => 'Registrar Desk',
    'lecturer' => 'Lecturer Desk',
    'dean' => 'Dean Desk',
    'finance' => 'Finance Desk',
    'kitchen_manager' => 'Main Kitchen Desk',
    'dining_manager' => 'Cafe Desk',
    'maintenance' => 'Facilities Desk',
    'security' => 'Security Desk',
    'technician' => 'Technical Desk',
    default => 'Institution Desk',
};

$mission = match ($role) {
    'registrar' => 'Review academic records, attendance, and exam eligibility.',
    'lecturer' => 'Review your class attendance, grades, and course issues.',
    'dean' => 'Review student welfare and serious academic risk.',
    'finance' => 'Review fee balances, clearance, and exam finance holds.',
    'kitchen_manager' => 'Coordinate food supply and replenishment.',
    'dining_manager' => 'Handle cafe stock and student service.',
    'maintenance' => 'Handle rooms, power, furniture, and repairs.',
    'security' => 'Handle safety alerts and urgent risks.',
    'technician' => 'Handle IT, network, and system incidents.',
    default => 'Review assigned institutional work.',
};

$assigned = $conn->prepare(
    "SELECT id.duty_id, id.incident_id, id.duty_status,
            i.title, i.priority, i.location,
            d.name AS department
     FROM incident_duties id
     JOIN incidents i ON id.incident_id = i.incident_id
     JOIN departments d ON id.department_id = d.department_id
     WHERE id.assigned_user_id = ?
     ORDER BY id.updated_at DESC
     LIMIT 8"
);

$assigned->bind_param('i', $user_id);
$assigned->execute();
$assigned_result = $assigned->get_result();

$messages = $conn->prepare(
    "SELECT message_id, channel, subject, message, status, created_at
     FROM institutional_message_queue
     WHERE target_user_id = ?
     ORDER BY created_at DESC
     LIMIT 8"
);

$messages->bind_param('i', $user_id);
$messages->execute();
$message_result = $messages->get_result();

$academic_result = null;
$finance_result = null;
$dean_result = null;

if ($role === 'lecturer') {
    /*
     * Kenya Data Protection Act principle:
     * Lecturer sees academic records needed for teaching only.
     * No fee balances.
     */
    $academic_result = $conn->query(
        "SELECT u.fullname, u.email,
                ar.unit_code, ar.unit_name,
                ar.attendance_percent, ar.current_grade,
                ar.exam_eligible,
                CASE
                    WHEN ar.attendance_percent < 70 THEN 'Attendance below 70%'
                    WHEN ar.exam_eligible = 0 THEN 'Eligibility hold'
                    ELSE 'Ready'
                END AS visible_reason
         FROM users u
         JOIN student_academic_records ar ON u.user_id = ar.user_id
         WHERE u.role = 'student'
           AND ar.unit_code = 'BCNS-WEB'
         ORDER BY ar.exam_eligible ASC, ar.attendance_percent ASC, u.fullname
         LIMIT 20"
    );
}

if ($role === 'registrar') {
    /*
     * Registrar sees academic eligibility and clearance status.
     * No fee amount.
     */
    $academic_result = $conn->query(
        "SELECT u.fullname, u.email,
                ar.unit_code, ar.unit_name,
                ar.attendance_percent, ar.current_grade,
                ar.exam_eligible,
                CASE
                    WHEN ar.attendance_percent < 70 THEN 'Attendance review needed'
                    WHEN ar.exam_eligible = 0 THEN 'Clearance hold'
                    ELSE 'Ready'
                END AS visible_reason
         FROM users u
         JOIN student_academic_records ar ON u.user_id = ar.user_id
         WHERE u.role = 'student'
         ORDER BY ar.exam_eligible ASC, ar.attendance_percent ASC, u.fullname
         LIMIT 20"
    );
}

if ($role === 'dean') {
    /*
     * Dean sees risk status only.
     * No fee amounts and no unnecessary financial detail.
     */
    $dean_result = $conn->query(
        "SELECT u.fullname, u.email,
                ar.unit_code,
                ar.attendance_percent,
                ar.exam_eligible,
                CASE
                    WHEN ar.attendance_percent < 70 THEN 'Attendance risk'
                    WHEN ar.exam_eligible = 0 THEN 'Eligibility concern'
                    ELSE 'No current concern'
                END AS risk_label
         FROM users u
         JOIN student_academic_records ar ON u.user_id = ar.user_id
         WHERE u.role = 'student'
           AND ar.exam_eligible = 0
         ORDER BY ar.attendance_percent ASC, u.fullname
         LIMIT 20"
    );
}

if ($role === 'finance') {
    /*
     * Finance sees financial clearance only.
     * No grades.
     * No detailed attendance.
     */
    $finance_result = $conn->query(
        "SELECT u.fullname, u.email,
                fa.balance_amount, fa.clearance_status, fa.last_payment_ref,
                CASE
                    WHEN fa.balance_amount > 0 THEN 'Reminder needed'
                    ELSE 'Cleared'
                END AS finance_action
         FROM users u
         JOIN student_finance_accounts fa ON u.user_id = fa.user_id
         WHERE u.role = 'student'
         ORDER BY fa.balance_amount DESC, u.fullname
         LIMIT 20"
    );
}

$count_stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM incident_duties
     WHERE assigned_user_id = ?
       AND duty_status IN ('Assigned','In Progress','Queued')"
);

$count_stmt->bind_param('i', $user_id);
$count_stmt->execute();
$needs_attention = (int) ($count_stmt->get_result()->fetch_assoc()['total'] ?? 0);
$count_stmt->close();

$msg_count = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM institutional_message_queue
     WHERE target_user_id = ?
       AND status = 'Queued'"
);

$msg_count->bind_param('i', $user_id);
$msg_count->execute();
$message_count = (int) ($msg_count->get_result()->fetch_assoc()['total'] ?? 0);
$msg_count->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= inst_safe($role_title) ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body>
<div class="app-shell">
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1><?= inst_safe($role_title) ?></h1>
                <p><?= inst_safe($name) ?> · <?= inst_safe($email) ?></p>
            </div>

            <div>
                <a class="btn btn-outline btn-sm" href="<?= SITE_URL ?>/logout.php">Logout</a>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-card c-progress">
                <div class="s-label">Your Job</div>
                <div class="s-value" style="font-size:20px;"><?= inst_safe($mission) ?></div>
            </div>

            <div class="stat-card c-critical">
                <div class="s-label">Assigned Work</div>
                <div class="s-value"><?= (int) $needs_attention ?></div>
            </div>

            <div class="stat-card c-resolved">
                <div class="s-label">Private Alerts</div>
                <div class="s-value"><?= (int) $message_count ?></div>
            </div>
        </div>

        <?php if ($academic_result): ?>
            <div class="panel mb-24">
                <div class="panel-head">
                    <h3>Exam Readiness</h3>
                </div>

                <?php while ($row = $academic_result->fetch_assoc()): ?>
                    <div class="incident-row">
                        <span class="inc-id"><?= $row['exam_eligible'] ? 'Ready' : 'Hold' ?></span>

                        <div class="inc-info">
                            <h4><?= inst_safe($row['fullname']) ?></h4>
                            <div class="inc-meta">
                                <span><?= inst_safe($row['unit_code']) ?></span>
                                <span>·</span>
                                <span>Attendance <?= number_format((float) $row['attendance_percent'], 1) ?>%</span>
                                <span>·</span>
                                <span>Grade <?= inst_safe($row['current_grade']) ?></span>
                            </div>
                        </div>

                        <div class="inc-badges">
                            <span class="badge <?= $row['exam_eligible'] ? 'b-low' : 'b-high' ?>">
                                <?= inst_safe($row['visible_reason']) ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <?php if ($dean_result): ?>
            <div class="panel mb-24">
                <div class="panel-head">
                    <h3>Student Risk Review</h3>
                </div>

                <?php while ($row = $dean_result->fetch_assoc()): ?>
                    <div class="incident-row">
                        <span class="inc-id"><?= $row['exam_eligible'] ? 'Ready' : 'Risk' ?></span>

                        <div class="inc-info">
                            <h4><?= inst_safe($row['fullname']) ?></h4>
                            <div class="inc-meta">
                                <span><?= inst_safe($row['unit_code']) ?></span>
                                <span>·</span>
                                <span>Attendance <?= number_format((float) $row['attendance_percent'], 1) ?>%</span>
                            </div>
                        </div>

                        <div class="inc-badges">
                            <span class="badge b-high"><?= inst_safe($row['risk_label']) ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <?php if ($finance_result): ?>
            <div class="panel mb-24">
                <div class="panel-head">
                    <h3>Fee Clearance</h3>
                </div>

                <?php while ($row = $finance_result->fetch_assoc()): ?>
                    <div class="incident-row">
                        <span class="inc-id"><?= inst_safe($row['clearance_status']) ?></span>

                        <div class="inc-info">
                            <h4><?= inst_safe($row['fullname']) ?></h4>
                            <div class="inc-meta">
                                <span><?= inst_safe($row['email']) ?></span>
                                <span>·</span>
                                <span>Balance KES <?= number_format((float) $row['balance_amount'], 2) ?></span>
                                <span>·</span>
                                <span><?= inst_safe($row['last_payment_ref']) ?></span>
                            </div>
                        </div>

                        <div class="inc-badges">
                            <span class="badge <?= ((float) $row['balance_amount'] > 0) ? 'b-high' : 'b-low' ?>">
                                <?= inst_safe($row['finance_action']) ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <div class="panel mb-24">
            <div class="panel-head">
                <h3>Assigned Work</h3>
            </div>

            <?php if (!$assigned_result || $assigned_result->num_rows === 0): ?>
                <div class="empty-state">
                    <p>No assigned work right now.</p>
                </div>
            <?php else: ?>
                <?php while ($work = $assigned_result->fetch_assoc()): ?>
                    <div class="incident-row">
                        <span class="inc-id">#<?= (int) $work['incident_id'] ?></span>

                        <div class="inc-info">
                            <h4><?= inst_safe($work['title']) ?></h4>
                            <div class="inc-meta">
                                <span><?= inst_safe($work['location']) ?></span>
                                <span>·</span>
                                <span><?= inst_safe($work['department']) ?></span>
                                <span>·</span>
                                <span><?= inst_safe($work['duty_status']) ?></span>
                            </div>
                        </div>

                        <div class="inc-badges">
                            <span class="badge"><?= inst_safe($work['priority']) ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>Private Alerts</h3>
            </div>

            <?php if (!$message_result || $message_result->num_rows === 0): ?>
                <div class="empty-state">
                    <p>No private alerts right now.</p>
                </div>
            <?php else: ?>
                <?php while ($msg = $message_result->fetch_assoc()): ?>
                    <div class="incident-row">
                        <span class="inc-id"><?= inst_safe($msg['channel']) ?></span>

                        <div class="inc-info">
                            <h4><?= inst_safe($msg['subject']) ?></h4>
                            <div class="inc-meta">
                                <span><?= inst_safe($msg['message']) ?></span>
                            </div>
                        </div>

                        <div class="inc-badges">
                            <span class="badge"><?= inst_safe($msg['status']) ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
