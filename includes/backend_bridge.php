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
