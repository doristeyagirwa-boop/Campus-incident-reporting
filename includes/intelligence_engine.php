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

    if (keyword_score($combined, [
        'class occupied', 'classroom occupied', 'room occupied',
        'lecture room occupied', 'venue conflict', 'room conflict',
        'double booked', 'double-booked', 'room unavailable',
        'classroom unavailable', 'students waiting outside',
        'found another class inside', 'no classroom'
    ], 1) > 0) {
        return 'Lecturer / Course Owner Review';
    }
    if (keyword_score($combined, [
        'attendance', 'absent', 'absence', 'missed class', 'missed lecture',
        'lecturer', 'teacher', 'class register', 'course attendance',
        'unit attendance', 'marked absent', 'wrong attendance',
        'cat eligibility', 'lecture attendance', 'student attended'
    ], 1) > 0) {
        if (keyword_score($combined, [
            'registrar', 'official record', 'academic record', 'student record',
            'cat eligibility', 'exam eligibility', 'transcript', 'registration',
            'unit registration', 'fee clearance'
        ], 1) > 0) {
            return 'Academic Registrar Escalation';
        }

        return 'Lecturer / Course Owner Review';
    }

    if (keyword_score($combined, [
        'registration', 'registrar', 'transcript', 'fee clearance',
        'student record', 'official record', 'unit registration',
        'wrong unit', 'missing unit', 'exam card', 'academic status',
        'billing discrepancy', 'fees', 'finance clearance'
    ], 1) > 0) {
        return 'Academic Registrar Escalation';
    }

    if (keyword_score($combined, [
        'food', 'meal', 'rice', 'beans', 'ugali', 'chapati', 'cafe',
        'cafeteria', 'dining', 'spoiled', 'allergy', 'allergen',
        'dirty table', 'utensil', 'overcharging', 'card terminal',
        'expired', 'out of stock', 'stock finished', 'food finished',
        'kitchen', 'serving line'
    ], 1) > 0) {
        return 'Cafes, Dining & Retail';
    }

    if (keyword_score($combined, [
        'wifi', 'network', 'router', 'server', 'computer', 'computers',
        'internet', 'cyber', 'hacked', 'password', 'phishing', 'login',
        'software', 'data breach', 'email server', 'system outage'
    ], 1) > 0) {
        return 'IT / Cyber Response';
    }

    if (keyword_score($combined, [
        'fight', 'fighting', 'injury', 'hurt', 'blood', 'attack', 'weapon',
        'threat', 'violence', 'suspicious', 'theft', 'vandalism', 'fire',
        'alarm', 'medical', 'emergency', 'harassment', 'bullying'
    ], 1) > 0) {
        return 'Safety, Security & Crime';
    }

    if (keyword_score($combined, [
        'window', 'door', 'floor', 'pipe', 'water leak', 'power outage',
        'electricity', 'heating', 'cooling', 'trash', 'restroom', 'pest',
        'blocked drain', 'chair', 'desk', 'locker', 'light', 'elevator',
        'signage'
    ], 1) > 0) {
        return 'Campus Infrastructure & Maintenance';
    }

    if (keyword_score($combined, [
        'classroom', 'projector', 'audio', 'course material', 'exam',
        'cheating', 'plagiarism', 'library', 'administrative', 'academic'
    ], 1) > 0) {
        return 'Academic & Administrative';
    }

    if (keyword_score($combined, [
        'gym', 'sports', 'fitness', 'track', 'court', 'pool', 'turf',
        'sprain', 'fracture', 'concussion', 'locker room', 'team',
        'hazing'
    ], 1) > 0) {
        return 'Sports, Recreation & Fitness';
    }

    if (keyword_score($combined, [
        'event', 'election', 'crowd', 'overcrowding', 'stampede',
        'sound system', 'speaker', 'performer', 'vip', 'noise',
        'ballot', 'voter', 'shuttle during event'
    ], 1) > 0) {
        return 'Events, Elections & Gatherings';
    }

    if (keyword_score($combined, [
        'vehicle', 'shuttle', 'parking', 'road', 'speed bump',
        'driver', 'bicycle', 'pedestrian', 'collision', 'accident',
        'fire lane'
    ], 1) > 0) {
        return 'Transportation & Logistics';
    }

    if (keyword_score($combined, [
        'dorm', 'residence', 'roommate', 'room mate', 'housekeeping',
        'lost key', 'broken lock', 'tailgating', 'noise complaint',
        'partying', 'plumbing in room'
    ], 1) > 0) {
        return 'Student Residences';
    }

    if (keyword_score($combined, [
        'tree', 'bush', 'irrigation', 'pathway', 'streetlamp',
        'flooded walkway', 'weather damage', 'fallen branch',
        'wildlife', 'monument', 'public art'
    ], 1) > 0) {
        return 'Grounds & Outdoor Spaces';
    }

    if (keyword_score($combined, [
        'accessibility', 'ramp', 'accessible restroom', 'discrimination',
        'bias', 'language barrier', 'translation', 'assistive',
        'screen reader', 'hearing loop', 'disability'
    ], 1) > 0) {
        return 'Inclusion & Accessibility';
    }

    if (keyword_score($combined, [
        'communication', 'policy', 'schedule', 'emergency alert',
        'false alarm', 'announcement', 'email outage', 'app outage',
        'information gap'
    ], 1) > 0) {
        return 'Communication & Feedback';
    }

    return 'General Administration Review';
}

function aios_recommended_action(string $route, float $risk_score): string
{
    if ($risk_score >= 80) {
        return 'Immediate escalation required. Notify institutional command, assign responsible department, and monitor until closure.';
    }

    return match ($route) {
        'Academic Registrar Escalation' =>
            'Escalate to Academic Registrar. Validate attendance evidence, official student record, CAT/exam eligibility, unit registration, and required academic correction.',

        'Lecturer / Course Owner Review' =>
            'Notify lecturer or course owner. Validate attendance register, class participation evidence, unit context, and submit academic confirmation to registry if needed.',

        'IT / Cyber Response' =>
            'Assign IT technician. Check network/device/service logs, isolate affected area if needed, and capture root cause.',

        'Safety, Security & Crime' =>
            'Notify security office immediately. Preserve incident details, assign safety officer, and escalate active threats immediately.',

        'Campus Infrastructure & Maintenance' =>
            'Assign maintenance team. Inspect facility asset, isolate hazard if needed, repair, and update closure notes with prevention action.',

        'Academic & Administrative' =>
            'Route to academic administration. Validate records, class impact, exam/course context, and required corrective action.',

        'Cafes, Dining & Retail' =>
            'Route to dining/retail supervisor. Inspect food safety, stock, hygiene, billing, allergen, or service issue and record action taken.',

        'Sports, Recreation & Fitness' =>
            'Assign sports/recreation officer. Inspect equipment or facility, document injuries, and restrict unsafe use if necessary.',

        'Events, Elections & Gatherings' =>
            'Route to events/security team. Review crowd control, logistics, election integrity, noise, or transport impact.',

        'Transportation & Logistics' =>
            'Assign transport/logistics officer. Review vehicle, shuttle, parking, road safety, or pedestrian conflict details.',

        'Student Residences' =>
            'Route to residence office. Assign housing/residence staff and document maintenance, access, noise, or community issue.',

        'Grounds & Outdoor Spaces' =>
            'Assign grounds team. Inspect landscape, lighting, weather damage, wildlife, or outdoor safety concern.',

        'Inclusion & Accessibility' =>
            'Escalate to accessibility/student affairs office. Preserve sensitive details and assign responsible support officer.',

        'Communication & Feedback' =>
            'Route to communications/admin office. Verify alert, system, schedule, or policy clarity issue and issue correction if needed.',

        default =>
            'Review, assign responsible owner, and track progress through normal incident workflow.',
    };
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
    $incident_id = (int) $prediction['incident_id'];

    /*
     * Keep one latest prediction per incident.
     * This prevents duplicate neural rows when admin clicks Analyze multiple times.
     */
    $cleanup = $conn->prepare(
        "DELETE FROM incident_predictions
         WHERE incident_id = ?"
    );

    if ($cleanup) {
        $cleanup->bind_param('i', $incident_id);
        $cleanup->execute();
        $cleanup->close();
    }

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
