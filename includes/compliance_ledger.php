<?php

function compliance_secret(): string
{
    return getenv('COMPLIANCE_LEDGER_SECRET') ?: 'CampusGuardLocalLedgerSecretChangeInProduction';
}

function compliance_hash_identifier(string $type, string|int|null $id): string
{
    return hash_hmac(
        'sha256',
        $type . ':' . (string) $id,
        compliance_secret()
    );
}

function compliance_last_hash(mysqli $conn): string
{
    $result = $conn->query(
        "SELECT event_hash
         FROM compliance_ledger
         ORDER BY ledger_id DESC
         LIMIT 1"
    );

    if (!$result) {
        return str_repeat('0', 64);
    }

    $row = $result->fetch_assoc();

    return $row ? (string) $row['event_hash'] : str_repeat('0', 64);
}

function compliance_log_event(
    mysqli $conn,
    string $event_type,
    string $entity_type,
    string|int|null $entity_id,
    array $metadata = [],
    ?int $actor_user_id = null,
    ?string $actor_role = null
): bool {
    $previous_hash = compliance_last_hash($conn);
    $entity_hash = compliance_hash_identifier($entity_type, $entity_id);

    $metadata_text = json_encode(
        $metadata,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    if ($metadata_text === false) {
        $metadata_text = '{}';
    }

    $actor_user_id = $actor_user_id ?? (isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null);
    $actor_role = $actor_role ?? (isset($_SESSION['role']) ? (string) $_SESSION['role'] : 'system');

    $event_hash = hash(
        'sha256',
        $previous_hash . '|' .
        $event_type . '|' .
        $entity_type . '|' .
        $entity_hash . '|' .
        $metadata_text . '|' .
        microtime(true)
    );

    $stmt = $conn->prepare(
        "INSERT INTO compliance_ledger
         (previous_hash, event_hash, event_type, actor_user_id, actor_role, entity_type, entity_hash, metadata_text)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        'sssissss',
        $previous_hash,
        $event_hash,
        $event_type,
        $actor_user_id,
        $actor_role,
        $entity_type,
        $entity_hash,
        $metadata_text
    );

    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function compliance_issue_alert_token(
    mysqli $conn,
    string $source,
    string $severity,
    string $entity_type,
    string|int|null $entity_id,
    array $payload
): bool {
    $entity_hash = compliance_hash_identifier($entity_type, $entity_id);

    $safe_payload = [
        'source' => $source,
        'severity' => $severity,
        'entity_type' => $entity_type,
        'entity_hash' => $entity_hash,
        'reason' => $payload['reason'] ?? 'threshold breach',
        'created_by' => 'CampusGuard compliance token engine',
    ];

    $token_payload = json_encode(
        $safe_payload,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    if ($token_payload === false) {
        $token_payload = '{}';
    }

    $token_hash = hash(
        'sha256',
        $source . '|' .
        $severity . '|' .
        $entity_type . '|' .
        $entity_hash . '|' .
        $token_payload
    );

    $stmt = $conn->prepare(
        "INSERT INTO compliance_alert_tokens
         (token_hash, source, severity, entity_type, entity_hash, token_payload)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            workflow_state = workflow_state,
            updated_at = NOW()"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        'ssssss',
        $token_hash,
        $source,
        $severity,
        $entity_type,
        $entity_hash,
        $token_payload
    );

    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        compliance_log_event(
            $conn,
            'COMPLIANCE_ALERT_TOKEN_ISSUED',
            $entity_type,
            $entity_id,
            [
                'source' => $source,
                'severity' => $severity,
                'token_hash' => $token_hash,
            ]
        );
    }

    return $ok;
}
