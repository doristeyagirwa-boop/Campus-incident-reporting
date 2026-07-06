<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/student_helpers.php';

$page_title = 'Track My Reports';
$uid = (int) $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT i.*, c.category_name,
            t.fullname AS technician_name
     FROM incidents i
     JOIN categories c ON i.category_id = c.category_id
     LEFT JOIN users t ON i.assigned_to = t.user_id
     WHERE i.user_id = ?
     ORDER BY i.updated_at DESC, i.created_at DESC"
);

$stmt->bind_param('i', $uid);
$stmt->execute();
$rows = $stmt->get_result();

function tracking_step_active(string $current, string $step): string
{
    $order = [
        'Open' => 1,
        'Assigned' => 2,
        'In Progress' => 3,
        'Resolved' => 4,
        'Closed' => 5,
    ];

    $current_level = $order[$current] ?? 1;
    $step_level = $order[$step] ?? 1;

    return $current_level >= $step_level ? 'active' : '';
}

include __DIR__ . '/../includes/header_student.php';
?>

<div class="panel">
  <div class="panel-head">
    <h3>Incident Tracking</h3>
    <a href="<?= SITE_URL ?>/student/report.php" class="btn btn-primary btn-sm">+ New Report</a>
  </div>

  <?php if ($rows->num_rows === 0): ?>
    <div class="empty-state">
      <span class="empty-icon">📍</span>
      <p>No reports available for tracking.</p>
      <a href="<?= SITE_URL ?>/student/report.php" class="btn btn-primary mt-16">Submit a report</a>
    </div>
  <?php else: ?>
    <?php while ($inc = $rows->fetch_assoc()): ?>
      <div class="tracking-card">
        <div class="flex-between mb-16">
          <div>
            <h4><?= student_safe($inc['title']) ?></h4>
            <p class="text-sm text-muted">
              #<?= (int) $inc['incident_id'] ?>
              · <?= student_safe($inc['category_name']) ?>
              · <?= student_safe($inc['location']) ?>
            </p>
          </div>

          <div class="inc-badges">
            <span class="badge <?= student_priority_badge($inc['priority']) ?>">
              <?= student_safe($inc['priority']) ?>
            </span>
            <span class="badge <?= student_status_badge($inc['status']) ?>">
              <?= student_safe($inc['status']) ?>
            </span>
          </div>
        </div>

        <div class="tracking-steps">
          <div class="track-step <?= tracking_step_active($inc['status'], 'Open') ?>">
            <span>1</span>
            <p>Submitted</p>
          </div>
          <div class="track-line"></div>
          <div class="track-step <?= tracking_step_active($inc['status'], 'Assigned') ?>">
            <span>2</span>
            <p>Assigned</p>
          </div>
          <div class="track-line"></div>
          <div class="track-step <?= tracking_step_active($inc['status'], 'In Progress') ?>">
            <span>3</span>
            <p>In Progress</p>
          </div>
          <div class="track-line"></div>
          <div class="track-step <?= tracking_step_active($inc['status'], 'Resolved') ?>">
            <span>4</span>
            <p>Resolved</p>
          </div>
          <div class="track-line"></div>
          <div class="track-step <?= tracking_step_active($inc['status'], 'Closed') ?>">
            <span>5</span>
            <p>Closed</p>
          </div>
        </div>

        <div class="tracking-meta">
          <span>Submitted: <?= student_format_date($inc['created_at']) ?></span>
          <span>Last updated: <?= student_format_date($inc['updated_at']) ?></span>
          <?php if (!empty($inc['technician_name'])): ?>
            <span>Assigned to: <?= student_safe($inc['technician_name']) ?></span>
          <?php else: ?>
            <span>Assigned to: Pending assignment</span>
          <?php endif; ?>
        </div>

        <div class="mt-16">
          <a href="<?= SITE_URL ?>/student/view_incident.php?id=<?= (int) $inc['incident_id'] ?>"
             class="btn btn-outline btn-sm">
            View Full Details
          </a>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_student.php'; ?>
