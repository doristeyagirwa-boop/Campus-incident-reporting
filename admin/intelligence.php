<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Incident Intelligence';

function intel_safe(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function intel_count(mysqli $conn, string $sql): int
{
    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_row();

    return (int) ($row[0] ?? 0);
}

$total_intelligence = intel_count($conn, "SELECT COUNT(*) FROM incident_intelligence");
$total_resolved = intel_count($conn, "SELECT COUNT(*) FROM incidents WHERE status IN ('Resolved', 'Closed')");
$total_categories = intel_count($conn, "SELECT COUNT(DISTINCT category_id) FROM incident_intelligence");

$latest = $conn->query(
    "SELECT ii.*, i.title, i.location, c.category_name, t.fullname AS technician_name
     FROM incident_intelligence ii
     JOIN incidents i ON ii.incident_id = i.incident_id
     JOIN categories c ON ii.category_id = c.category_id
     JOIN users t ON ii.technician_id = t.user_id
     ORDER BY ii.created_at DESC
     LIMIT 20"
);

$root_causes = $conn->query(
    "SELECT root_cause, COUNT(*) AS total
     FROM incident_intelligence
     GROUP BY root_cause
     ORDER BY total DESC, root_cause ASC
     LIMIT 10"
);

$category_patterns = $conn->query(
    "SELECT c.category_name, COUNT(*) AS total
     FROM incident_intelligence ii
     JOIN categories c ON ii.category_id = c.category_id
     GROUP BY c.category_name
     ORDER BY total DESC"
);

include __DIR__ . '/../includes/header_admin.php';
?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="s-label">Captured Intelligence</div>
    <div class="s-value"><?= (int) $total_intelligence ?></div>
  </div>

  <div class="stat-card c-resolved">
    <div class="s-label">Resolved / Closed Incidents</div>
    <div class="s-value"><?= (int) $total_resolved ?></div>
  </div>

  <div class="stat-card c-progress">
    <div class="s-label">Learned Categories</div>
    <div class="s-value"><?= (int) $total_categories ?></div>
  </div>

  <div class="stat-card">
    <div class="s-label">AI Readiness</div>
    <div class="s-value"><?= $total_intelligence > 0 ? 'ON' : 'OFF' ?></div>
  </div>
</div>

<div class="detail-grid">
  <div class="panel panel-body">
    <h3 class="mb-16">Common Root Causes</h3>

    <?php if (!$root_causes || $root_causes->num_rows === 0): ?>
      <p class="text-muted">No root cause intelligence captured yet.</p>
    <?php else: ?>
      <?php while ($row = $root_causes->fetch_assoc()): ?>
        <div class="report-row">
          <span><?= intel_safe($row['root_cause']) ?></span>
          <strong><?= (int) $row['total'] ?></strong>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>

  <div class="panel panel-body">
    <h3 class="mb-16">Category Intelligence</h3>

    <?php if (!$category_patterns || $category_patterns->num_rows === 0): ?>
      <p class="text-muted">No category intelligence captured yet.</p>
    <?php else: ?>
      <?php while ($row = $category_patterns->fetch_assoc()): ?>
        <div class="report-row">
          <span><?= intel_safe($row['category_name']) ?></span>
          <strong><?= (int) $row['total'] ?></strong>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>Resolution Intelligence Feed</h3>
  </div>

  <?php if (!$latest || $latest->num_rows === 0): ?>
    <div class="empty-state">
      <span class="empty-icon">🧠</span>
      <p>No intelligence records yet. Resolved technician incidents will appear here.</p>
    </div>
  <?php else: ?>
    <?php while ($row = $latest->fetch_assoc()): ?>
      <div class="intelligence-card">
        <div class="flex-between mb-16">
          <div>
            <h4><?= intel_safe($row['title']) ?></h4>
            <p class="text-muted text-sm">
              <?= intel_safe($row['category_name']) ?>
              · <?= intel_safe($row['location']) ?>
              · Technician: <?= intel_safe($row['technician_name']) ?>
            </p>
          </div>

          <span class="badge b-resolved">Learned</span>
        </div>

        <div class="intel-grid">
          <div>
            <strong>Root Cause</strong>
            <p><?= nl2br(intel_safe($row['root_cause'])) ?></p>
          </div>

          <div>
            <strong>Resolution</strong>
            <p><?= nl2br(intel_safe($row['resolution_summary'])) ?></p>
          </div>

          <div>
            <strong>Prevention</strong>
            <p><?= nl2br(intel_safe($row['prevention_recommendation'])) ?></p>
          </div>
        </div>

        <div class="learning-signal">
          <strong>Learning Signal:</strong>
          <?= intel_safe($row['learning_signal']) ?>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
