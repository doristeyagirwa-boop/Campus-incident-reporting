<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Compliance Command';

function comp_safe(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$ledger_summary = $conn->query(
    "SELECT COUNT(*) AS total_events
     FROM compliance_ledger"
)->fetch_assoc();

$token_summary = $conn->query(
    "SELECT
        COUNT(*) AS total_tokens,
        SUM(CASE WHEN severity = 'Critical' THEN 1 ELSE 0 END) AS critical_tokens,
        SUM(CASE WHEN severity = 'High' THEN 1 ELSE 0 END) AS high_tokens
     FROM compliance_alert_tokens"
)->fetch_assoc();

$integration_summary = $conn->query(
    "SELECT
        COUNT(*) AS total_events,
        SUM(CASE WHEN delivery_state = 'Queued' THEN 1 ELSE 0 END) AS queued_events,
        SUM(CASE WHEN delivery_state = 'Suppressed' THEN 1 ELSE 0 END) AS suppressed_events
     FROM integration_event_queue"
)->fetch_assoc();

$boundaries = $conn->query(
    "SELECT department_code, department_name, production_storage_target, rls_policy_name, kms_key_alias
     FROM department_data_boundaries
     ORDER BY boundary_id"
);

$access = $conn->query(
    "SELECT role_name, domain_code, can_view_finance, can_view_grades,
            can_view_attendance, can_view_medical, access_summary
     FROM role_data_access_matrix
     ORDER BY access_id"
);

$ledger = $conn->query(
    "SELECT ledger_id, event_type, actor_role, entity_type,
            LEFT(entity_hash, 16) AS entity_hash_prefix,
            LEFT(event_hash, 16) AS event_hash_prefix,
            created_at
     FROM compliance_ledger
     ORDER BY ledger_id DESC
     LIMIT 25"
);

$tokens = $conn->query(
    "SELECT token_id, source, severity, entity_type,
            LEFT(entity_hash, 16) AS entity_hash_prefix,
            workflow_state, created_at
     FROM compliance_alert_tokens
     ORDER BY token_id DESC
     LIMIT 25"
);

include __DIR__ . '/../includes/header_admin.php';
?>

<div class="stat-grid">
  <div class="stat-card c-progress">
    <div class="s-label">Ledger Events</div>
    <div class="s-value"><?= (int) ($ledger_summary['total_events'] ?? 0) ?></div>
  </div>

  <div class="stat-card c-critical">
    <div class="s-label">Alert Tokens</div>
    <div class="s-value"><?= (int) ($token_summary['total_tokens'] ?? 0) ?></div>
  </div>

  <div class="stat-card">
    <div class="s-label">Integration Events</div>
    <div class="s-value"><?= (int) ($integration_summary['total_events'] ?? 0) ?></div>
  </div>
</div>

<div class="panel mb-24">
  <div class="panel-head">
    <h3>Department Data Boundaries</h3>
  </div>

  <?php if (!$boundaries || $boundaries->num_rows === 0): ?>
    <div class="empty-state">
      <p>No data boundaries configured.</p>
    </div>
  <?php else: ?>
    <?php while ($row = $boundaries->fetch_assoc()): ?>
      <div class="incident-row">
        <span class="inc-id"><?= comp_safe($row['department_code']) ?></span>

        <div class="inc-info">
          <h4><?= comp_safe($row['department_name']) ?></h4>
          <div class="inc-meta">
            <span><?= comp_safe($row['rls_policy_name']) ?></span>
            <span>·</span>
            <span><?= comp_safe($row['kms_key_alias']) ?></span>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<div class="panel mb-24">
  <div class="panel-head">
    <h3>Role Access Matrix</h3>
  </div>

  <?php if (!$access || $access->num_rows === 0): ?>
    <div class="empty-state">
      <p>No role access matrix configured.</p>
    </div>
  <?php else: ?>
    <?php while ($row = $access->fetch_assoc()): ?>
      <div class="incident-row">
        <span class="inc-id"><?= comp_safe($row['role_name']) ?></span>

        <div class="inc-info">
          <h4><?= comp_safe($row['domain_code']) ?></h4>
          <div class="inc-meta">
            <span>Finance: <?= $row['can_view_finance'] ? 'Yes' : 'No' ?></span>
            <span>·</span>
            <span>Grades: <?= $row['can_view_grades'] ? 'Yes' : 'No' ?></span>
            <span>·</span>
            <span>Attendance: <?= $row['can_view_attendance'] ? 'Yes' : 'No' ?></span>
          </div>
          <p class="text-muted text-sm"><?= comp_safe($row['access_summary']) ?></p>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<div class="panel mb-24">
  <div class="panel-head">
    <h3>Compliance Ledger</h3>
  </div>

  <?php if (!$ledger || $ledger->num_rows === 0): ?>
    <div class="empty-state">
      <p>No ledger events yet.</p>
    </div>
  <?php else: ?>
    <?php while ($row = $ledger->fetch_assoc()): ?>
      <div class="incident-row">
        <span class="inc-id">#<?= (int) $row['ledger_id'] ?></span>

        <div class="inc-info">
          <h4><?= comp_safe($row['event_type']) ?></h4>
          <div class="inc-meta">
            <span><?= comp_safe($row['entity_type']) ?></span>
            <span>·</span>
            <span>Entity <?= comp_safe($row['entity_hash_prefix']) ?></span>
            <span>·</span>
            <span>Event <?= comp_safe($row['event_hash_prefix']) ?></span>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>High-Alert Tokens</h3>
  </div>

  <?php if (!$tokens || $tokens->num_rows === 0): ?>
    <div class="empty-state">
      <p>No high-alert tokens yet.</p>
    </div>
  <?php else: ?>
    <?php while ($row = $tokens->fetch_assoc()): ?>
      <div class="incident-row">
        <span class="inc-id"><?= comp_safe($row['severity']) ?></span>

        <div class="inc-info">
          <h4><?= comp_safe($row['source']) ?></h4>
          <div class="inc-meta">
            <span><?= comp_safe($row['entity_type']) ?></span>
            <span>·</span>
            <span><?= comp_safe($row['entity_hash_prefix']) ?></span>
            <span>·</span>
            <span><?= comp_safe($row['workflow_state']) ?></span>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
