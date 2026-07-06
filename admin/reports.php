<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/report_helpers.php';

$page_title = 'Reports & Analytics';

$total_incidents = report_count($conn, "SELECT COUNT(*) FROM incidents");
$total_users = report_count($conn, "SELECT COUNT(*) FROM users");
$total_students = report_count($conn, "SELECT COUNT(*) FROM users WHERE role = 'student'");
$total_technicians = report_count($conn, "SELECT COUNT(*) FROM users WHERE role = 'technician'");

$status_stats = report_status_breakdown($conn);
$priority_stats = report_priority_breakdown($conn);
$category_stats = report_category_breakdown($conn);

$recent = $conn->query(
    "SELECT i.*, c.category_name, u.fullname AS reporter_name
     FROM incidents i
     JOIN categories c ON i.category_id = c.category_id
     JOIN users u ON i.user_id = u.user_id
     ORDER BY i.created_at DESC
     LIMIT 10"
);

include __DIR__ . '/../includes/header_admin.php';
?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="s-label">Total Incidents</div>
    <div class="s-value"><?= $total_incidents ?></div>
  </div>
  <div class="stat-card">
    <div class="s-label">Total Users</div>
    <div class="s-value"><?= $total_users ?></div>
  </div>
  <div class="stat-card">
    <div class="s-label">Students</div>
    <div class="s-value"><?= $total_students ?></div>
  </div>
  <div class="stat-card">
    <div class="s-label">Technicians</div>
    <div class="s-value"><?= $total_technicians ?></div>
  </div>
</div>

<div class="detail-grid">
  <div class="panel panel-body">
    <h3 class="mb-16">Status Breakdown</h3>
    <?php foreach ($status_stats as $status => $count): ?>
      <div class="report-row">
        <span><?= report_safe($status) ?></span>
        <strong><?= (int) $count ?></strong>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="panel panel-body">
    <h3 class="mb-16">Priority Breakdown</h3>
    <?php foreach ($priority_stats as $priority => $count): ?>
      <div class="report-row">
        <span><?= report_safe($priority) ?></span>
        <strong><?= (int) $count ?></strong>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="panel panel-body">
  <h3 class="mb-16">Category Breakdown</h3>
  <?php if (!$category_stats): ?>
    <p class="text-muted">No category data available.</p>
  <?php else: ?>
    <?php foreach ($category_stats as $row): ?>
      <div class="report-row">
        <span><?= report_safe($row['category_name']) ?></span>
        <strong><?= (int) $row['total'] ?></strong>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>Recent Incident Activity</h3>
  </div>

  <?php if (!$recent || $recent->num_rows === 0): ?>
    <div class="empty-state">
      <p>No recent incidents found.</p>
    </div>
  <?php else: ?>
    <?php while ($inc = $recent->fetch_assoc()): ?>
      <a class="incident-row clickable"
         href="<?= SITE_URL ?>/admin/view_incident.php?id=<?= (int) $inc['incident_id'] ?>">
        <span class="inc-id">#<?= (int) $inc['incident_id'] ?></span>
        <div class="inc-info">
          <h4><?= report_safe($inc['title']) ?></h4>
          <div class="inc-meta">
            <span><?= report_safe($inc['category_name']) ?></span>
            <span>·</span>
            <span><?= report_safe($inc['reporter_name']) ?></span>
            <span>·</span>
            <span><?= report_safe($inc['status']) ?></span>
          </div>
        </div>
        <div class="inc-badges">
          <span class="badge"><?= report_safe($inc['priority']) ?></span>
        </div>
      </a>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
