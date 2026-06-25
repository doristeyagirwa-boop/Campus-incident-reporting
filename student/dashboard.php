<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

$page_title = 'My Dashboard';
$uid = (int) $_SESSION['user_id'];

// Stats
$stats = ['open'=>0,'progress'=>0,'resolved'=>0,'total'=>0];
$res = $conn->prepare(
    "SELECT status, COUNT(*) as c FROM incidents WHERE user_id = ? GROUP BY status"
);
$res->bind_param('i', $uid);
$res->execute();
$rows = $res->get_result();
while ($r = $rows->fetch_assoc()) {
    $stats['total'] += $r['c'];
    if ($r['status'] === 'Open')        $stats['open']     += $r['c'];
    if ($r['status'] === 'In Progress') $stats['progress'] += $r['c'];
    if ($r['status'] === 'Resolved')    $stats['resolved'] += $r['c'];
}

// Recent 5
$recent = $conn->prepare(
    "SELECT i.*, c.category_name
     FROM incidents i JOIN categories c ON i.category_id = c.category_id
     WHERE i.user_id = ?
     ORDER BY i.created_at DESC LIMIT 5"
);
$recent->bind_param('i', $uid);
$recent->execute();
$recent_rows = $recent->get_result();

// Status CSS helpers
function status_class(string $s): string {
    return match($s) {
        'Open'        => 's-open',
        'Assigned'    => 's-assigned',
        'In Progress' => 's-progress',
        'Resolved'    => 's-resolved',
        'Closed'      => 's-closed',
        default       => ''
    };
}
function status_badge(string $s): string {
    return match($s) {
        'Open'        => 'b-open',
        'Assigned'    => 'b-assigned',
        'In Progress' => 'b-progress',
        'Resolved'    => 'b-resolved',
        'Closed'      => 'b-closed',
        default       => ''
    };
}
function priority_badge(string $p): string {
    return match($p) {
        'High'   => 'b-high',
        'Medium' => 'b-medium',
        'Low'    => 'b-low',
        default  => ''
    };
}

include __DIR__ . '/../includes/header_student.php';
?>

<!-- Stats -->
<div class="stat-grid">
  <div class="stat-card"><div class="s-label">Total Reports</div><div class="s-value"><?= $stats['total'] ?></div></div>
  <div class="stat-card c-open"><div class="s-label">Open</div><div class="s-value"><?= $stats['open'] ?></div></div>
  <div class="stat-card c-progress"><div class="s-label">In Progress</div><div class="s-value"><?= $stats['progress'] ?></div></div>
  <div class="stat-card c-resolved"><div class="s-label">Resolved</div><div class="s-value"><?= $stats['resolved'] ?></div></div>
</div>

<!-- Recent Incidents -->
<div class="panel">
  <div class="panel-head">
    <h3>Recent Reports</h3>
    <a href="<?= SITE_URL ?>/student/report.php" class="btn btn-primary btn-sm">+ New Report</a>
  </div>

  <?php if ($recent_rows->num_rows === 0): ?>
  <div class="empty-state">
    <span class="empty-icon">📭</span>
    <p>You haven't submitted any reports yet.</p>
    <a href="<?= SITE_URL ?>/student/report.php" class="btn btn-primary mt-16">Report your first incident</a>
  </div>
  <?php else: ?>
  <?php while ($inc = $recent_rows->fetch_assoc()): ?>
  <a class="incident-row clickable <?= status_class($inc['status']) ?>"
     href="<?= SITE_URL ?>/student/view_incident.php?id=<?= $inc['incident_id'] ?>"
     data-title="<?= htmlspecialchars($inc['title']) ?>">
    <span class="inc-id">#<?= $inc['incident_id'] ?></span>
    <div class="inc-info">
      <h4><?= htmlspecialchars($inc['title']) ?></h4>
      <div class="inc-meta">
        <span><?= htmlspecialchars($inc['category_name']) ?></span>
        <span>·</span>
        <span><?= htmlspecialchars($inc['location']) ?></span>
        <span>·</span>
        <span><?= date('d M Y', strtotime($inc['created_at'])) ?></span>
      </div>
    </div>
    <div class="inc-badges">
      <span class="badge <?= priority_badge($inc['priority']) ?>"><?= $inc['priority'] ?></span>
      <span class="badge <?= status_badge($inc['status']) ?>"><?= $inc['status'] ?></span>
    </div>
  </a>
  <?php endwhile; ?>
  <div style="padding:14px 20px;">
    <a href="<?= SITE_URL ?>/student/my_reports.php" class="text-sm" style="color:var(--navy);font-weight:600;">View all my reports →</a>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_student.php'; ?>
