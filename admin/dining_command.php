<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Dining Command';

function dining_safe(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$summary = $conn->query(
    "SELECT
        COUNT(*) AS total_alerts,
        SUM(CASE WHEN stock_status = 'Finished' THEN 1 ELSE 0 END) AS finished_alerts,
        SUM(CASE WHEN stock_status = 'Low' THEN 1 ELSE 0 END) AS low_alerts
     FROM dining_stock_alerts"
)->fetch_assoc();

$locations = $conn->query(
    "SELECT dl.location_id, dl.code, dl.name, dl.location_type,
            u.fullname AS manager_name,
            u.email AS manager_email,
            COUNT(dsa.alert_id) AS alert_count,
            SUM(CASE WHEN dsa.stock_status = 'Finished' THEN 1 ELSE 0 END) AS finished_count
     FROM dining_locations dl
     LEFT JOIN users u ON dl.manager_user_id = u.user_id
     LEFT JOIN dining_stock_alerts dsa ON dl.location_id = dsa.location_id
     WHERE dl.is_active = 1
     GROUP BY dl.location_id, dl.code, dl.name, dl.location_type, u.fullname, u.email
     ORDER BY FIELD(dl.location_type, 'Central Kitchen', 'Cafe', 'Retail Point'), dl.name"
);

$alerts = $conn->query(
    "SELECT dsa.alert_id, dsa.item_name, dsa.stock_status, dsa.alert_message,
            dsa.incident_id, dsa.created_at,
            dl.name AS location_name,
            dl.code AS location_code,
            u.fullname AS manager_name,
            u.email AS manager_email,
            i.title AS incident_title
     FROM dining_stock_alerts dsa
     JOIN dining_locations dl ON dsa.location_id = dl.location_id
     LEFT JOIN users u ON dl.manager_user_id = u.user_id
     LEFT JOIN incidents i ON dsa.incident_id = i.incident_id
     ORDER BY dsa.created_at DESC, dsa.alert_id DESC
     LIMIT 50"
);

$dining_duties = $conn->query(
    "SELECT id.duty_id, id.incident_id, id.duty_status, id.updated_at,
            i.title, i.priority, i.status AS incident_status, i.location,
            u.email AS assigned_email,
            p.predicted_risk_score, p.predicted_priority, p.confidence_score
     FROM incident_duties id
     JOIN departments d ON id.department_id = d.department_id
     JOIN incidents i ON id.incident_id = i.incident_id
     LEFT JOIN users u ON id.assigned_user_id = u.user_id
     LEFT JOIN incident_predictions p ON id.prediction_id = p.prediction_id
     WHERE d.code = 'DINING'
     ORDER BY id.updated_at DESC
     LIMIT 50"
);

include __DIR__ . '/../includes/header_admin.php';
?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="s-label">Dining Alerts</div>
    <div class="s-value"><?= (int) ($summary['total_alerts'] ?? 0) ?></div>
  </div>

  <div class="stat-card c-critical">
    <div class="s-label">Finished Stock</div>
    <div class="s-value"><?= (int) ($summary['finished_alerts'] ?? 0) ?></div>
  </div>

  <div class="stat-card c-progress">
    <div class="s-label">Low Stock</div>
    <div class="s-value"><?= (int) ($summary['low_alerts'] ?? 0) ?></div>
  </div>

  <div class="stat-card c-resolved">
    <div class="s-label">Dining Duties</div>
    <div class="s-value"><?= $dining_duties ? (int) $dining_duties->num_rows : 0 ?></div>
  </div>
</div>

<div class="panel mb-24">
  <div class="panel-head">
    <h3>Dining Locations</h3>
  </div>

  <?php if (!$locations || $locations->num_rows === 0): ?>
    <div class="empty-state">
      <p>No dining locations configured.</p>
    </div>
  <?php else: ?>
    <?php while ($location = $locations->fetch_assoc()): ?>
      <div class="incident-row">
        <span class="inc-id"><?= dining_safe($location['code']) ?></span>

        <div class="inc-info">
          <h4><?= dining_safe($location['name']) ?></h4>
          <div class="inc-meta">
            <span><?= dining_safe($location['location_type']) ?></span>
            <span>·</span>
            <span><?= dining_safe($location['manager_email'] ?: 'No manager') ?></span>
          </div>
        </div>

        <div class="inc-badges">
          <span class="badge">Alerts <?= (int) $location['alert_count'] ?></span>
          <span class="badge b-high">Finished <?= (int) $location['finished_count'] ?></span>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<div class="panel mb-24">
  <div class="panel-head">
    <h3>Food Stock Alert Feed</h3>
  </div>

  <?php if (!$alerts || $alerts->num_rows === 0): ?>
    <div class="empty-state">
      <span class="empty-icon">☕</span>
      <p>No dining stock alerts yet.</p>
    </div>
  <?php else: ?>
    <?php while ($alert = $alerts->fetch_assoc()): ?>
      <div class="intelligence-card">
        <div class="flex-between mb-16">
          <div>
            <h4><?= dining_safe($alert['item_name']) ?> — <?= dining_safe($alert['location_name']) ?></h4>
            <p class="text-muted text-sm">
              Manager: <?= dining_safe($alert['manager_email'] ?: 'Unassigned') ?>
              · <?= dining_safe($alert['created_at']) ?>
            </p>
          </div>

          <span class="badge <?= $alert['stock_status'] === 'Finished' ? 'b-high' : 'b-medium' ?>">
            <?= dining_safe($alert['stock_status']) ?>
          </span>
        </div>

        <div class="learning-signal">
          <?= dining_safe($alert['alert_message']) ?>
        </div>

        <?php if (!empty($alert['incident_id'])): ?>
          <p class="mt-16">
            <a class="btn btn-outline btn-sm"
               href="<?= SITE_URL ?>/admin/view_incident.php?id=<?= (int) $alert['incident_id'] ?>">
              Open Linked Incident #<?= (int) $alert['incident_id'] ?>
            </a>
          </p>
        <?php endif; ?>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>Dining Incident Duties</h3>
  </div>

  <?php if (!$dining_duties || $dining_duties->num_rows === 0): ?>
    <div class="empty-state">
      <p>No dining duties yet.</p>
    </div>
  <?php else: ?>
    <?php while ($duty = $dining_duties->fetch_assoc()): ?>
      <div class="incident-row">
        <span class="inc-id">#<?= (int) $duty['incident_id'] ?></span>

        <div class="inc-info">
          <h4><?= dining_safe($duty['title']) ?></h4>
          <div class="inc-meta">
            <span><?= dining_safe($duty['location']) ?></span>
            <span>·</span>
            <span><?= dining_safe($duty['assigned_email'] ?: 'Unassigned') ?></span>
            <span>·</span>
            <span>Risk <?= number_format((float) $duty['predicted_risk_score'], 1) ?></span>
          </div>
        </div>

        <div class="inc-badges">
          <span class="badge"><?= dining_safe($duty['duty_status']) ?></span>
          <a class="btn btn-outline btn-sm"
             href="<?= SITE_URL ?>/admin/view_incident.php?id=<?= (int) $duty['incident_id'] ?>">
            Open
          </a>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
