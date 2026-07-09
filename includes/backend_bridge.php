<?php

function backend_bridge_base_url(): string
{
    $value = getenv('FASTAPI_BACKEND_URL');

    if (!$value) {
        $value = 'http://127.0.0.1:8000';
    }

    return rtrim($value, '/');
}

function backend_bridge_post_json(string $path, array $payload, int $timeout_seconds = 2): ?array
{
    $url = backend_bridge_base_url() . '/' . ltrim($path, '/');

    $json = json_encode($payload);

    if ($json === false) {
        return null;
    }

    if (!function_exists('curl_init')) {
        return null;
    }

    $ch = curl_init($url);

    if (!$ch) {
        return null;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_CONNECTTIMEOUT => $timeout_seconds,
        CURLOPT_TIMEOUT => $timeout_seconds,
    ]);

    $raw = curl_exec($ch);

    if ($raw === false) {
        curl_close($ch);
        return null;
    }

    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        return null;
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : null;
}

function backend_bridge_analyze_incident(
    string $title,
    string $description,
    string $category,
    string $location
): ?array {
    return backend_bridge_post_json(
        '/bridge/analyze',
        [
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'location' => $location,
        ],
        2
    );
}

function backend_bridge_save_advisory(
    mysqli $conn,
    int $incident_id,
    array $analysis
): bool {
    $domain = (string) ($analysis['domain'] ?? 'General');
    $priority = (string) ($analysis['priority_hint'] ?? 'Low');
    $risk = (int) ($analysis['risk_hint'] ?? 0);
    $confidence = (float) ($analysis['confidence'] ?? 0.00);
    $recommended_action = (string) ($analysis['recommended_action'] ?? 'Review incident.');

    $matched_terms = json_encode(
        $analysis['matched_terms'] ?? [],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    if ($matched_terms === false) {
        $matched_terms = '[]';
    }

    $raw_payload = json_encode(
        $analysis,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    if ($raw_payload === false) {
        $raw_payload = '{}';
    }

    $stmt = $conn->prepare(
        "INSERT INTO backend_bridge_advisories
         (incident_id, domain_hint, priority_hint, risk_hint, confidence, recommended_action, matched_terms, raw_payload)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        'issidsss',
        $incident_id,
        $domain,
        $priority,
        $risk,
        $confidence,
        $recommended_action,
        $matched_terms,
        $raw_payload
    );

    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}
