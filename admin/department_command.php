<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Department Command';

function dept_safe(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$summary = $conn->query(
    "SELECT
        COUNT(*) AS total_duties,
        SUM(CASE WHEN duty_status IN ('Queued','Assigned','In Progress') THEN 1 ELSE 0 END) AS active_duties,
        SUM(CASE WHEN duty_status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_duties,
        SUM(CASE WHEN duty_status = 'Closed' THEN 1 ELSE 0 END) AS closed_duties
     FROM incident_duties"
)->fetch_assoc();

$departments = $conn->query(
    "SELECT d.department_id, d.code, d.name, d.domain, d.severity_level,
            COUNT(id.duty_id) AS total_duties,
            SUM(CASE WHEN id.duty_status IN ('Queued','Assigned','In Progress') THEN 1 ELSE 0 END) AS active_duties
     FROM departments d
     LEFT JOIN incident_duties id ON d.department_id = id.department_id
     WHERE d.is_active = 1
     GROUP BY d.department_id, d.code, d.name, d.domain, d.severity_level
     ORDER BY
        FIELD(d.severity_level, 'Critical', 'Sensitive', 'Normal'),
        d.name ASC"
);

$duties = $conn->query(
    "SELECT id.*, d.name AS department_name, d.code AS department_code,
            i.title, i.status AS incident_status, i.priority,
            u.fullname AS assigned_name,
            p.predicted_risk_score, p.predicted_priority, p.recommended_route, p.confidence_score
     FROM incident_duties id
     JOIN departments d ON id.department_id = d.department_id
     JOIN incidents i ON id.incident_id = i.incident_id
     LEFT JOIN users u ON id.assigned_user_id = u.user_id
     LEFT JOIN incident_predictions p ON id.prediction_id = p.prediction_id
     ORDER BY id.updated_at DESC
     LIMIT 40"
);

include __DIR__ . '/../includes/header_admin.php';
?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="s-label">Total Duties</div>
    <div class="s-value"><?= (int) ($summary['total_duties'] ?? 0) ?></div>
  </div>

  <div class="stat-card c-progress">
    <div class="s-label">Active Duties</div>
    <div class="s-value"><?= (int) ($summary['active_duties'] ?? 0) ?></div>
  </div>

  <div class="stat-card c-resolved">
    <div class="s-label">Resolved Duties</div>
    <div class="s-value"><?= (int) ($summary['resolved_duties'] ?? 0) ?></div>
  </div>

  <div class="stat-card">
    <div class="s-label">Closed Duties</div>
    <div class="s-value"><?= (int) ($summary['closed_duties'] ?? 0) ?></div>
  </div>
</div>

<div class="panel mb-24">
  <div class="panel-head">
    <h3>Institutional Departments</h3>
  </div>

  <?php if (!$departments || $departments->num_rows === 0): ?>
    <div class="empty-state">
      <p>No departments configured.</p>
    </div>
  <?php else: ?>
    <?php while ($department = $departments->fetch_assoc()): ?>
      <div class="incident-row">
        <span class="inc-id"><?= dept_safe($department['code']) ?></span>

        <div class="inc-info">
          <h4><?= dept_safe($department['name']) ?></h4>
          <div class="inc-meta">
            <span><?= dept_safe($department['domain']) ?></span>
            <span>·</span>
            <span><?= dept_safe($department['severity_level']) ?></span>
          </div>
        </div>

        <div class="inc-badges">
          <span class="badge">Total <?= (int) $department['total_duties'] ?></span>
          <span class="badge b-progress">Active <?= (int) $department['active_duties'] ?></span>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>AIOS Duty Allocation Feed</h3>
  </div>

  <?php if (!$duties || $duties->num_rows === 0): ?>
    <div class="empty-state">
      <span class="empty-icon">🏢</span>
      <p>No department duties yet. Run Neural Engine analysis to allocate duties.</p>
    </div>
  <?php else: ?>
    <?php while ($duty = $duties->fetch_assoc()): ?>
      <div class="intelligence-card">
        <div class="flex-between mb-16">
          <div>
            <h4>#<?= (int) $duty['incident_id'] ?> — <?= dept_safe($duty['title']) ?></h4>
            <p class="text-muted text-sm">
              <?= dept_safe($duty['department_name']) ?>
              · Incident: <?= dept_safe($duty['incident_status']) ?>
              · Priority: <?= dept_safe($duty['priority']) ?>
              · Assigned: <?= dept_safe($duty['assigned_name'] ?: 'Department queue') ?>
            </p>
          </div>

          <span class="badge b-progress"><?= dept_safe($duty['duty_status']) ?></span>
        </div>

        <div class="intel-grid">
          <div>
            <strong>Route</strong>
            <p><?= dept_safe($duty['recommended_route']) ?></p>
          </div>

          <div>
            <strong>Risk</strong>
            <p><?= number_format((float) $duty['predicted_risk_score'], 2) ?>/100</p>
          </div>

          <div>
            <strong>Confidence</strong>
            <p><?= number_format((float) $duty['confidence_score'], 2) ?>%</p>
          </div>
        </div>

        <div class="learning-signal">
          <strong>Duty Summary:</strong>
          <?= dept_safe($duty['duty_summary']) ?>
        </div>

        <p class="mt-16">
          <a class="btn btn-outline btn-sm"
             href="<?= SITE_URL ?>/admin/view_incident.php?id=<?= (int) $duty['incident_id'] ?>">
            Open Incident
          </a>
        </p>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
