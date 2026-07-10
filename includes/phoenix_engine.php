<?php
require_once __DIR__ . '/compliance_ledger.php';

function phoenix_normalize(string $value): string
{
    return strtolower(trim(preg_replace('/\s+/', ' ', $value)));
}

function phoenix_fetch_incident_context(mysqli $conn, int $incident_id): ?array
{
    $stmt = $conn->prepare(
        "SELECT i.incident_id, i.title, i.description, i.location, i.priority, i.status,
                i.user_id, c.category_name,
                p.prediction_id, p.predicted_risk_score, p.predicted_priority,
                p.recommended_route, p.recommended_action, p.confidence_score
         FROM incidents i
         JOIN categories c ON i.category_id = c.category_id
         LEFT JOIN incident_predictions p ON i.incident_id = p.incident_id
         WHERE i.incident_id = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $incident_id);
    $stmt->execute();
    $context = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $context ?: null;
}

function phoenix_domain_rules(mysqli $conn): array
{
    $rules = [];

    $result = $conn->query(
        "SELECT pd.domain_id, pd.code, pd.name, pd.risk_weight,
                pdr.keyword, pdr.weight
         FROM phoenix_domains pd
         LEFT JOIN phoenix_domain_rules pdr ON pd.domain_id = pdr.domain_id
         WHERE pd.is_active = 1
         ORDER BY pd.domain_id, pdr.weight DESC"
    );

    if (!$result) {
        return $rules;
    }

    while ($row = $result->fetch_assoc()) {
        $code = (string) $row['code'];

        if (!isset($rules[$code])) {
            $rules[$code] = [
                'domain_id' => (int) $row['domain_id'],
                'code' => $code,
                'name' => (string) $row['name'],
                'risk_weight' => (float) $row['risk_weight'],
                'keywords' => [],
            ];
        }

        if (!empty($row['keyword'])) {
            $rules[$code]['keywords'][] = [
                'keyword' => phoenix_normalize((string) $row['keyword']),
                'weight' => (int) $row['weight'],
            ];
        }
    }

    return $rules;
}

function phoenix_score_domains(mysqli $conn, array $context): array
{
    $rules = phoenix_domain_rules($conn);

    if (!$rules) {
        return [];
    }

    $text = phoenix_normalize(
        (string) $context['title'] . ' ' .
        (string) $context['description'] . ' ' .
        (string) $context['location'] . ' ' .
        (string) $context['category_name'] . ' ' .
        (string) ($context['recommended_route'] ?? '')
    );

    $scores = [];

    foreach ($rules as $code => $domain) {
        $score = 0.0;

        foreach ($domain['keywords'] as $rule) {
            if (str_contains($text, $rule['keyword'])) {
                $score += (float) $rule['weight'];
            }
        }

        $route = (string) ($context['recommended_route'] ?? '');

        if ($code === 'ACADEMICS_ATTENDANCE' && (
            str_contains($route, 'Academic') ||
            str_contains($route, 'Lecturer')
        )) {
            $score += 35;
        }

        if ($code === 'FOOD_DINING' && str_contains($route, 'Dining')) {
            $score += 35;
        }

        if ($code === 'FINANCE_ACCOUNTS' && str_contains($route, 'Finance')) {
            $score += 35;
        }

        if ($code === 'FACILITIES_SPACES' && (
            str_contains($route, 'Infrastructure') ||
            str_contains($route, 'Maintenance')
        )) {
            $score += 30;
        }

        if ($code === 'SAFETY_IT_ADMIN' && (
            str_contains($route, 'IT') ||
            str_contains($route, 'Cyber') ||
            str_contains($route, 'Security')
        )) {
            $score += 30;
        }

        $risk_score = (float) ($context['predicted_risk_score'] ?? 0);

        if ($risk_score >= 70) {
            $score += 12;
        } elseif ($risk_score >= 50) {
            $score += 6;
        }

        $score *= (float) $domain['risk_weight'];

        if ($score > 0) {
            $scores[$code] = [
                'domain_id' => (int) $domain['domain_id'],
                'code' => $code,
                'name' => (string) $domain['name'],
                'score' => round($score, 2),
            ];
        }
    }

    uasort($scores, fn ($a, $b) => $b['score'] <=> $a['score']);

    return $scores;
}

function phoenix_advisory_type(string $domain_code, array $context): string
{
    $text = phoenix_normalize((string) $context['title'] . ' ' . (string) $context['description']);

    if ($domain_code === 'ACADEMICS_ATTENDANCE') {
        if (
            str_contains($text, 'attendance') ||
            str_contains($text, 'absent') ||
            str_contains($text, 'cat eligibility') ||
            str_contains($text, 'exam eligibility')
        ) {
            return 'Academic Attendance';
        }

        return 'Facilities Relocation';
    }

    return match ($domain_code) {
        'FOOD_DINING' => 'Food Continuity',
        'FINANCE_ACCOUNTS' => 'Finance Clearance',
        'FACILITIES_SPACES' => 'Facilities Relocation',
        'SAFETY_IT_ADMIN' => 'IT Security',
        default => 'General',
    };
}

function phoenix_domain_action(string $domain_code, array $context): string
{
    return match ($domain_code) {
        'ACADEMICS_ATTENDANCE' =>
            'Privately notify lecturer/registrar. Validate attendance evidence, room allocation, CAT/exam eligibility, and student academic record impact.',

        'FOOD_DINING' =>
            'Notify cafe or kitchen manager. Confirm stock status, queue impact, replenishment source, and student service continuity.',

        'FINANCE_ACCOUNTS' =>
            'Notify finance office. Review student account status, payment evidence, clearance impact, and any academic-service hold.',

        'FACILITIES_SPACES' =>
            'Notify facilities/maintenance office. Confirm asset status, room availability, hazard level, and relocation or repair plan.',

        'SAFETY_IT_ADMIN' =>
            'Notify IT/security/admin command. Confirm system or safety impact, containment steps, evidence preservation, and escalation threshold.',

        default =>
            'Notify responsible institutional office and track outcome until closure.',
    };
}

function phoenix_target_users(mysqli $conn, string $domain_code): array
{
    $roles = match ($domain_code) {
        'ACADEMICS_ATTENDANCE' => ['lecturer', 'registrar', 'dean'],
        'FOOD_DINING' => ['dining_manager', 'kitchen_manager'],
        'FINANCE_ACCOUNTS' => ['finance'],
        'FACILITIES_SPACES' => ['maintenance', 'technician'],
        'SAFETY_IT_ADMIN' => ['security', 'technician', 'admin'],
        default => ['admin'],
    };

    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $types = str_repeat('s', count($roles));

    $stmt = $conn->prepare(
        "SELECT user_id, email, role
         FROM users
         WHERE role IN ($placeholders)
         ORDER BY FIELD(role, $placeholders), user_id ASC
         LIMIT 12"
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

function phoenix_save_signal(
    mysqli $conn,
    int $incident_id,
    int $domain_id,
    string $signal_type,
    float $score,
    string $summary,
    string $action,
    string $visibility
): bool {
    $stmt = $conn->prepare(
        "INSERT INTO phoenix_intelligence_signals
         (incident_id, domain_id, signal_type, signal_score, summary, recommended_action, visibility)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            signal_score = VALUES(signal_score),
            summary = VALUES(summary),
            recommended_action = VALUES(recommended_action),
            visibility = VALUES(visibility),
            updated_at = NOW()"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        'iisdsss',
        $incident_id,
        $domain_id,
        $signal_type,
        $score,
        $summary,
        $action,
        $visibility
    );

    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function phoenix_save_private_advisory(
    mysqli $conn,
    int $incident_id,
    int $target_user_id,
    int $domain_id,
    string $advisory_type,
    string $message
): bool {
    $exists = $conn->prepare(
        "SELECT advisory_id
         FROM phoenix_private_advisories
         WHERE incident_id = ?
           AND target_user_id = ?
           AND advisory_type = ?
         LIMIT 1"
    );

    if ($exists) {
        $exists->bind_param('iis', $incident_id, $target_user_id, $advisory_type);
        $exists->execute();
        $row = $exists->get_result()->fetch_assoc();
        $exists->close();

        if ($row) {
            return true;
        }
    }

    $stmt = $conn->prepare(
        "INSERT INTO phoenix_private_advisories
         (incident_id, target_user_id, domain_id, advisory_type, message)
         VALUES (?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        'iiiss',
        $incident_id,
        $target_user_id,
        $domain_id,
        $advisory_type,
        $message
    );

    $ok = $stmt->execute();
    $stmt->close();

    if ($ok && function_exists('create_notification')) {
        create_notification(
            $conn,
            $target_user_id,
            $incident_id,
            'AI private advisory: ' . $message
        );
    }

    return $ok;
}

function phoenix_run_incident_intelligence(mysqli $conn, int $incident_id, string $source = 'system'): int
{
    $context = phoenix_fetch_incident_context($conn, $incident_id);

    if (!$context) {
        return 0;
    }

    $scores = phoenix_score_domains($conn, $context);

    if (!$scores) {
        return 0;
    }

    $created = 0;

    foreach ($scores as $domain) {
        if ((float) $domain['score'] < 25) {
            continue;
        }

        $domain_code = (string) $domain['code'];
        $domain_id = (int) $domain['domain_id'];
        $domain_name = (string) $domain['name'];
        $score = (float) $domain['score'];

        $summary = 'AI detected ' . $domain_name .
            ' relevance for incident #' . $incident_id .
            ' with cognitive score ' . number_format($score, 1) .
            '. Source: ' . $source . '.';

        $action = phoenix_domain_action($domain_code, $context);
        $advisory_type = phoenix_advisory_type($domain_code, $context);

        if (phoenix_save_signal(
            $conn,
            $incident_id,
            $domain_id,
            'Domain Detection',
            $score,
            $summary,
            $action,
            'Assigned Office'
        )) {
            $created++;
        }

        if ($score >= 35) {
            $message = 'Incident #' . $incident_id . ' requires ' . $domain_name .
                ' review. ' . $action . ' Keep this advisory inside authorized institutional staff only.';

            foreach (phoenix_target_users($conn, $domain_code) as $user) {
                if (phoenix_save_private_advisory(
                    $conn,
                    $incident_id,
                    (int) $user['user_id'],
                    $domain_id,
                    $advisory_type,
                    $message
                )) {
                    $created++;
                }
            }
        }
    }

    if ($created > 0 && function_exists('audit_log')) {
        audit_log(
            $conn,
            'PHOENIX_AI_INTELLIGENCE_RUN',
            'incident',
            $incident_id,
            'AI generated/updated ' . $created . ' intelligence records for incident #' . $incident_id
        );
    }

    if ($created > 0) {
        compliance_log_event(
            $conn,
            'PHOENIX_AI_INTELLIGENCE_RUN',
            'incident',
            $incident_id,
            [
                'source' => $source,
                'records_touched' => $created,
                'privacy_mode' => 'role-minimized',
            ]
        );
    }

    foreach ($scores as $domain) {
        if ((float) $domain['score'] >= 100) {
            compliance_issue_alert_token(
                $conn,
                'phoenix_ai',
                'High',
                'incident',
                $incident_id,
                [
                    'reason' => 'AI high-score domain threshold exceeded',
                    'domain' => $domain['code'],
                    'score' => $domain['score'],
                ]
            );
        }
    }

    return $created;
}
