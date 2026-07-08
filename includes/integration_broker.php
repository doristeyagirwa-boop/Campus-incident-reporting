<?php
require_once __DIR__ . '/compliance_ledger.php';

function integration_token_hash(string $source, string|int|null $entity_id): string
{
    return hash_hmac(
        'sha256',
        $source . ':' . (string) $entity_id,
        compliance_secret()
    );
}

function integration_queue_token_event(
    mysqli $conn,
    string $endpoint_code,
    string $event_type,
    string $severity,
    string $source,
    string|int|null $entity_id,
    array $payload
): bool {
    $stmt = $conn->prepare(
        "SELECT endpoint_id, is_enabled
         FROM integration_endpoints
         WHERE endpoint_code = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $endpoint_code);
    $stmt->execute();
    $endpoint = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$endpoint) {
        return false;
    }

    $token_hash = integration_token_hash($source, $entity_id);

    $safe_payload = [
        'event_type' => $event_type,
        'severity' => $severity,
        'source' => $source,
        'token_hash' => $token_hash,
        'metadata' => $payload,
        'note' => 'Tokenized payload only. No raw protected student data.',
    ];

    $payload_text = json_encode(
        $safe_payload,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    if ($payload_text === false) {
        $payload_text = '{}';
    }

    $delivery_state = ((int) $endpoint['is_enabled'] === 1) ? 'Queued' : 'Suppressed';

    $insert = $conn->prepare(
        "INSERT INTO integration_event_queue
         (endpoint_id, event_type, severity, token_hash, payload_text, delivery_state)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    if (!$insert) {
        return false;
    }

    $endpoint_id = (int) $endpoint['endpoint_id'];

    $insert->bind_param(
        'isssss',
        $endpoint_id,
        $event_type,
        $severity,
        $token_hash,
        $payload_text,
        $delivery_state
    );

    $ok = $insert->execute();
    $insert->close();

    if ($ok) {
        compliance_log_event(
            $conn,
            'INTEGRATION_EVENT_QUEUED',
            'integration_event',
            $token_hash,
            [
                'endpoint_code' => $endpoint_code,
                'event_type' => $event_type,
                'severity' => $severity,
                'delivery_state' => $delivery_state,
            ]
        );
    }

    return $ok;
}

function ai_rate_limit_allow(mysqli $conn, string $client_key): bool
{
    $now = date('Y-m-d H:i:s');

    $stmt = $conn->prepare(
        "SELECT limit_id, window_start, request_count, max_requests_per_minute, blocked_until
         FROM ai_inference_rate_limits
         WHERE client_key = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return true;
    }

    $stmt->bind_param('s', $client_key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        $insert = $conn->prepare(
            "INSERT INTO ai_inference_rate_limits
             (client_key, window_start, request_count)
             VALUES (?, ?, 1)"
        );

        if (!$insert) {
            return true;
        }

        $insert->bind_param('ss', $client_key, $now);
        $insert->execute();
        $insert->close();

        return true;
    }

    if (!empty($row['blocked_until']) && strtotime((string) $row['blocked_until']) > time()) {
        return false;
    }

    $window_start = strtotime((string) $row['window_start']);
    $request_count = (int) $row['request_count'];
    $max = (int) $row['max_requests_per_minute'];

    if ($window_start === false || (time() - $window_start) >= 60) {
        $reset = $conn->prepare(
            "UPDATE ai_inference_rate_limits
             SET window_start = ?,
                 request_count = 1,
                 blocked_until = NULL,
                 updated_at = NOW()
             WHERE client_key = ?"
        );

        if ($reset) {
            $reset->bind_param('ss', $now, $client_key);
            $reset->execute();
            $reset->close();
        }

        return true;
    }

    if ($request_count >= $max) {
        $blocked_until = date('Y-m-d H:i:s', time() + 60);

        $block = $conn->prepare(
            "UPDATE ai_inference_rate_limits
             SET blocked_until = ?,
                 updated_at = NOW()
             WHERE client_key = ?"
        );

        if ($block) {
            $block->bind_param('ss', $blocked_until, $client_key);
            $block->execute();
            $block->close();
        }

        return false;
    }

    $update = $conn->prepare(
        "UPDATE ai_inference_rate_limits
         SET request_count = request_count + 1,
             updated_at = NOW()
         WHERE client_key = ?"
    );

    if ($update) {
        $update->bind_param('s', $client_key);
        $update->execute();
        $update->close();
    }

    return true;
}
