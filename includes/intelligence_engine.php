<?php

function normalize_text_for_aios(string|null $value): string
{
    $value = strtolower((string) $value);
    $value = preg_replace('/[^a-z0-9\s]/', ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);

    return trim($value);
}

function keyword_score(string $text, array $keywords, float $weight): float
{
    $score = 0.0;

    foreach ($keywords as $keyword) {
        if (str_contains($text, $keyword)) {
            $score += $weight;
        }
    }

    return $score;
}

function tokenize_aios_text(string $text): array
{
    $stop_words = [
        'the', 'and', 'or', 'a', 'an', 'to', 'at', 'in', 'on', 'of', 'for',
        'is', 'are', 'was', 'were', 'it', 'this', 'that', 'with', 'not',
        'working', 'unavailable', 'broken'
    ];

    $tokens = explode(' ', normalize_text_for_aios($text));
    $tokens = array_filter($tokens, fn ($token) => strlen($token) > 2 && !in_array($token, $stop_words, true));

    return array_values(array_unique($tokens));
}

function similarity_score(string $a, string $b): float
{
    $tokens_a = tokenize_aios_text($a);
    $tokens_b = tokenize_aios_text($b);

    if (!$tokens_a || !$tokens_b) {
        return 0.0;
    }

    $intersection = array_intersect($tokens_a, $tokens_b);
    $union = array_unique(array_merge($tokens_a, $tokens_b));

    if (!$union) {
        return 0.0;
    }

    return count($intersection) / count($union);
}

function find_similar_incident(mysqli $conn, array $incident): ?array
{
    $current_id = (int) $incident['incident_id'];
    $current_text = $incident['title'] . ' ' . $incident['description'] . ' ' . $incident['location'];

    $result = $conn->query(
        "SELECT i.incident_id, i.title, i.description, i.location, i.status,
                ii.root_cause, ii.resolution_summary
         FROM incidents i
         LEFT JOIN incident_intelligence ii ON i.incident_id = ii.incident_id
         WHERE i.incident_id <> {$current_id}
         ORDER BY i.created_at DESC
         LIMIT 50"
    );

    if (!$result) {
        return null;
    }

    $best = null;
    $best_score = 0.0;

    while ($row = $result->fetch_assoc()) {
        $candidate_text = $row['title'] . ' ' . $row['description'] . ' ' . $row['location'];
        $score = similarity_score($current_text, $candidate_text);

        if ($score > $best_score) {
            $best_score = $score;
            $best = $row;
            $best['similarity'] = $score;
        }
    }

    if (!$best || $best_score < 0.12) {
        return null;
    }

    return $best;
}

function aios_route_from_text(string $text, string $category): string
{
    $combined = normalize_text_for_aios($text . ' ' . $category);

    if (keyword_score($combined, ['wifi', 'network', 'router', 'server', 'computer', 'computers', 'internet', 'cyber', 'hacked', 'password'], 1) > 0) {
        return 'IT / Cyber Response';
    }

    if (keyword_score($combined, ['fight', 'injury', 'hurt', 'blood', 'attack', 'weapon', 'threat'], 1) > 0) {
        return 'Safety / Security Response';
    }

    if (keyword_score($combined, ['bully', 'bullying', 'harassment', 'crying', 'threat', 'abuse'], 1) > 0) {
        return 'Student Welfare / Counselling';
    }

    if (keyword_score($combined, ['exam', 'cheating', 'plagiarism', 'ai plagiarism', 'academic'], 1) > 0) {
        return 'Academic Integrity Review';
    }

    if (keyword_score($combined, ['water', 'electricity', 'light', 'door', 'chair', 'desk', 'building'], 1) > 0) {
        return 'Facilities / Maintenance';
    }

    return 'General Administration Review';
}

function aios_recommended_action(string $route, float $risk_score): string
{
    if ($risk_score >= 80) {
        return 'Immediate escalation required. Assign responsible officer, notify administrator, and monitor until closure.';
    }

    if ($risk_score >= 55) {
        return 'Assign to responsible technician or officer. Require progress note before resolution.';
    }

    if ($route === 'IT / Cyber Response') {
        return 'Assign IT technician, inspect affected device/network area, capture root cause, and record resolution intelligence.';
    }

    if ($route === 'Student Welfare / Counselling') {
        return 'Route to welfare/counselling team and preserve notes for follow-up.';
    }

    return 'Review, assign owner, and track progress through normal incident workflow.';
}

function predict_priority_from_risk(float $risk_score): string
{
    if ($risk_score >= 70) {
        return 'High';
    }

    if ($risk_score >= 40) {
        return 'Medium';
    }

    return 'Low';
}

function analyze_incident_aios(mysqli $conn, int $incident_id): ?array
{
    $stmt = $conn->prepare(
        "SELECT i.*, c.category_name
         FROM incidents i
         JOIN categories c ON i.category_id = c.category_id
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

    if (!$incident) {
        return null;
    }

    $text = normalize_text_for_aios(
        $incident['title'] . ' ' .
        $incident['description'] . ' ' .
        $incident['location'] . ' ' .
        $incident['category_name']
    );

    $risk = 20.0;
    $explanations = [];

    $danger_score = keyword_score($text, ['fight', 'attack', 'injury', 'blood', 'weapon', 'threat', 'fire', 'emergency'], 18.0);
    if ($danger_score > 0) {
        $risk += $danger_score;
        $explanations[] = 'Physical safety or emergency keywords detected.';
    }

    $cyber_score = keyword_score($text, ['wifi', 'network', 'router', 'server', 'password', 'hacked', 'cyber', 'computer', 'computers', 'internet'], 10.0);
    if ($cyber_score > 0) {
        $risk += $cyber_score;
        $explanations[] = 'IT or cyber infrastructure keywords detected.';
    }

    $welfare_score = keyword_score($text, ['bully', 'bullying', 'crying', 'harassment', 'abuse', 'mental', 'self harm'], 15.0);
    if ($welfare_score > 0) {
        $risk += $welfare_score;
        $explanations[] = 'Student welfare or safeguarding indicators detected.';
    }

    if ($incident['priority'] === 'High') {
        $risk += 15.0;
        $explanations[] = 'Reporter/admin priority is already High.';
    } elseif ($incident['priority'] === 'Medium') {
        $risk += 8.0;
        $explanations[] = 'Reporter/admin priority is Medium.';
    }

    if ($incident['status'] === 'Open') {
        $risk += 5.0;
        $explanations[] = 'Incident is still open and unassigned.';
    }

    if ($incident['assigned_to'] === null) {
        $risk += 5.0;
        $explanations[] = 'No technician or owner assigned yet.';
    }

    $similar = find_similar_incident($conn, $incident);

    $suggested_root_cause = null;
    $similar_incident_id = null;
    $similarity_boost = 0.0;

    if ($similar) {
        $similar_incident_id = (int) $similar['incident_id'];
        $similarity_boost = (float) $similar['similarity'] * 20.0;

        if (!empty($similar['root_cause'])) {
            $suggested_root_cause = $similar['root_cause'];
            $explanations[] = 'Similar historical incident with root cause was found.';
        } else {
            $explanations[] = 'Similar historical incident was found, but no root cause was captured.';
        }

        $risk += min($similarity_boost, 10.0);
    }

    $risk = max(0.0, min(100.0, $risk));

    $route = aios_route_from_text($text, (string) $incident['category_name']);
    $recommended_action = aios_recommended_action($route, $risk);
    $predicted_priority = predict_priority_from_risk($risk);

    $confidence = 45.0;

    if ($similar) {
        $confidence += min(30.0, ((float) $similar['similarity']) * 50.0);
    }

    if (count($explanations) >= 3) {
        $confidence += 15.0;
    }

    $confidence = max(0.0, min(95.0, $confidence));

    if (!$explanations) {
        $explanations[] = 'No strong risk indicators found. Default institutional triage applied.';
    }

    return [
        'incident_id' => $incident_id,
        'predicted_risk_score' => round($risk, 2),
        'predicted_priority' => $predicted_priority,
        'recommended_route' => $route,
        'recommended_action' => $recommended_action,
        'similar_incident_id' => $similar_incident_id,
        'suggested_root_cause' => $suggested_root_cause,
        'confidence_score' => round($confidence, 2),
        'explanation' => implode(' ', $explanations),
    ];
}

function save_incident_prediction(mysqli $conn, array $prediction): bool
{
    $stmt = $conn->prepare(
        "INSERT INTO incident_predictions
         (incident_id, predicted_risk_score, predicted_priority,
          recommended_route, recommended_action, similar_incident_id,
          suggested_root_cause, confidence_score, explanation)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return false;
    }

    $incident_id = (int) $prediction['incident_id'];
    $risk = (float) $prediction['predicted_risk_score'];
    $priority = (string) $prediction['predicted_priority'];
    $route = (string) $prediction['recommended_route'];
    $action = (string) $prediction['recommended_action'];
    $similar_id = $prediction['similar_incident_id'] !== null ? (int) $prediction['similar_incident_id'] : null;
    $root_cause = $prediction['suggested_root_cause'];
    $confidence = (float) $prediction['confidence_score'];
    $explanation = (string) $prediction['explanation'];

    $stmt->bind_param(
        'idsssisis',
        $incident_id,
        $risk,
        $priority,
        $route,
        $action,
        $similar_id,
        $root_cause,
        $confidence,
        $explanation
    );

    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}
