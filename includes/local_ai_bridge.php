<?php
require_once __DIR__ . '/integration_broker.php';

function local_ai_endpoint_base(): string
{
    return getenv('AIOS_LOCAL_AI_BASE_URL') ?: 'http://127.0.0.1:11434';
}

function local_ai_generate_endpoint(): string
{
    return rtrim(local_ai_endpoint_base(), '/') . '/api/generate';
}

function local_ai_tags_endpoint(): string
{
    return rtrim(local_ai_endpoint_base(), '/') . '/api/tags';
}

function local_ai_configured_model(): ?string
{
    $model = getenv('AIOS_LOCAL_AI_MODEL');

    if (!$model) {
        return null;
    }

    return trim($model);
}

function local_ai_http_json(string $url, ?array $payload = null, int $timeout_seconds = 20): ?array
{
    $body = $payload !== null ? json_encode($payload) : null;

    if ($payload !== null && $body === false) {
        return null;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);

        if (!$ch) {
            return null;
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => $timeout_seconds,
        ];

        if ($payload !== null) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);
            return null;
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            return [
                'error' => true,
                'status' => $status,
                'raw' => $response,
            ];
        }

        $decoded = json_decode($response, true);

        return is_array($decoded) ? $decoded : null;
    }

    $context_options = [
        'http' => [
            'timeout' => $timeout_seconds,
        ],
    ];

    if ($payload !== null) {
        $context_options['http']['method'] = 'POST';
        $context_options['http']['header'] = "Content-Type: application/json\r\n";
        $context_options['http']['content'] = $body;
    }

    $context = stream_context_create($context_options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        return null;
    }

    $decoded = json_decode($response, true);

    return is_array($decoded) ? $decoded : null;
}

function local_ai_available_models(): array
{
    $response = local_ai_http_json(local_ai_tags_endpoint(), null, 5);

    if (!$response || !isset($response['models']) || !is_array($response['models'])) {
        return [];
    }

    $models = [];

    foreach ($response['models'] as $model) {
        if (!empty($model['name'])) {
            $models[] = (string) $model['name'];
        }
    }

    return $models;
}

function local_ai_model_name(): string
{
    $configured = local_ai_configured_model();

    if ($configured) {
        return $configured;
    }

    $available = local_ai_available_models();

    /*
     * Live demo priority:
     * 1B model is fastest and enough for summaries/action briefs.
     * The deterministic AIOS engine remains the authority for risk/routing.
     */
    $preferred = [
        'llama3.2:1b',
        'llama3.2:3b',
        'mistral:7b',
        'deepseek-coder:6.7b',
    ];

    foreach ($preferred as $model) {
        if (in_array($model, $available, true)) {
            return $model;
        }
    }

    return $available[0] ?? 'llama3.2:1b';
}

function local_ai_health(): array
{
    $models = local_ai_available_models();

    return [
        'base_url' => local_ai_endpoint_base(),
        'generate_url' => local_ai_generate_endpoint(),
        'models' => $models,
        'selected_model' => local_ai_model_name(),
        'available' => count($models) > 0,
    ];
}

function local_ai_generate(string $prompt, ?string $model = null, int $timeout_seconds = 25): ?string
{
    global $conn;

    if ($conn instanceof mysqli && !ai_rate_limit_allow($conn, 'local_ollama')) {
        return null;
    }

    $model = $model ?: local_ai_model_name();

    $payload = [
        'model' => $model,
        'prompt' => $prompt,
        'stream' => false,
        'keep_alive' => '30m',
        'options' => [
            'temperature' => 0.12,
            'top_p' => 0.8,
            'num_ctx' => 2048,
            'num_predict' => 320,
        ],
    ];

    $response = local_ai_http_json(local_ai_generate_endpoint(), $payload, $timeout_seconds);

    if (!$response || isset($response['error'])) {
        return null;
    }

    return isset($response['response']) ? trim((string) $response['response']) : null;
}

function local_ai_warm_model(): bool
{
    $response = local_ai_generate(
        'Reply with exactly: AIOS local reasoning engine ready.',
        local_ai_model_name(),
        45
    );

    return $response !== null;
}

function fetch_incident_ai_context(mysqli $conn, int $incident_id): ?array
{
    $stmt = $conn->prepare(
        "SELECT i.incident_id, i.title, i.description, i.location, i.priority, i.status,
                c.category_name,
                p.prediction_id, p.predicted_risk_score, p.predicted_priority,
                p.recommended_route, p.recommended_action, p.confidence_score,
                GROUP_CONCAT(DISTINCT CONCAT(d.name, ' -> ', COALESCE(u.email, 'unassigned')) SEPARATOR '; ') AS duties
         FROM incidents i
         JOIN categories c ON i.category_id = c.category_id
         LEFT JOIN incident_predictions p ON i.incident_id = p.incident_id
         LEFT JOIN incident_duties id ON i.incident_id = id.incident_id
         LEFT JOIN departments d ON id.department_id = d.department_id
         LEFT JOIN users u ON id.assigned_user_id = u.user_id
         WHERE i.incident_id = ?
         GROUP BY i.incident_id, i.title, i.description, i.location, i.priority, i.status,
                  c.category_name, p.prediction_id, p.predicted_risk_score, p.predicted_priority,
                  p.recommended_route, p.recommended_action, p.confidence_score
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

function build_incident_reasoning_prompt(array $context, string $reasoning_type): string
{
    /*
     * Short prompt = faster generation.
     * The deterministic neural-symbolic engine has already done routing/risk/duties.
     * The local model only converts that into polished incident command language.
     */
    return
        "You are a senior campus incident command analyst.\n" .
        "Use only the facts below. Be concise and operational.\n\n" .
        "Type: {$reasoning_type}\n" .
        "Incident #{$context['incident_id']}: {$context['title']}\n" .
        "Description: {$context['description']}\n" .
        "Location: {$context['location']}\n" .
        "Status/Priority: {$context['status']} / {$context['priority']}\n" .
        "AIOS route: {$context['recommended_route']}\n" .
        "AIOS risk: {$context['predicted_risk_score']} / priority {$context['predicted_priority']} / confidence {$context['confidence_score']}\n" .
        "AIOS action: {$context['recommended_action']}\n" .
        "Duties: " . ($context['duties'] ?: 'No duties assigned') . "\n\n" .
        "Output with these headings only:\n" .
        "Executive Summary\n" .
        "Immediate Actions\n" .
        "Responsible Offices\n" .
        "Follow-up Questions\n" .
        "Closure Criteria\n";
}

function save_ai_reasoning_note(
    mysqli $conn,
    int $incident_id,
    ?int $prediction_id,
    string $model_name,
    string $reasoning_type,
    string $prompt,
    string $response
): bool {
    $stmt = $conn->prepare(
        "INSERT INTO ai_reasoning_notes
         (incident_id, prediction_id, model_name, reasoning_type, prompt_text, response_text)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            prediction_id = VALUES(prediction_id),
            model_name = VALUES(model_name),
            prompt_text = VALUES(prompt_text),
            response_text = VALUES(response_text),
            created_at = NOW()"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        'iissss',
        $incident_id,
        $prediction_id,
        $model_name,
        $reasoning_type,
        $prompt,
        $response
    );

    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function generate_ai_reasoning_for_incident(
    mysqli $conn,
    int $incident_id,
    string $reasoning_type = 'Executive Brief'
): bool {
    $context = fetch_incident_ai_context($conn, $incident_id);

    if (!$context) {
        return false;
    }

    $model = local_ai_model_name();
    $prompt = build_incident_reasoning_prompt($context, $reasoning_type);
    $response = local_ai_generate($prompt, $model);

    if (!$response) {
        return false;
    }

    $prediction_id = isset($context['prediction_id']) ? (int) $context['prediction_id'] : null;

    return save_ai_reasoning_note(
        $conn,
        $incident_id,
        $prediction_id,
        $model,
        $reasoning_type,
        $prompt,
        $response
    );
}
