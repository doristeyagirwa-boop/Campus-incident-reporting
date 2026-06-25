<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Notifications';
$uid = (int) $_SESSION['user_id'];

// Mark all as read when page is visited
$conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute() ||
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $uid");

// Properly mark read
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->bind_param('i', $uid);
$stmt->execute();

// Fetch all notifications
$stmt2 = $conn->prepare(
    "SELECT n.*, i.title as incident_title
     FROM notifications n
     LEFT JOIN incidents i ON n.incident_id = i.incident_id
     WHERE n.user_id = ?
     ORDER BY n.created_at DESC"
);
$stmt2->bind_param('i', $uid);
$stmt2->execute();
$notifs = $stmt2->get_result();

include __DIR__ . '/../includes/header_student.php';
?>

<div class="panel">
  <div class="panel-head"><h3>All Notifications</h3></div>
  <?php if ($notifs->num_rows === 0): ?>
  <div class="empty-state">
    <span class="empty-icon">🔔</span>
    <p>No notifications yet.</p>
  </div>
  <?php else: ?>
  <?php while ($n = $notifs->fetch_assoc()): ?>
  <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
    <span class="notif-dot <?= $n['is_read'] ? 'read' : '' ?>"></span>
    <div style="flex:1;">
      <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
      <?php if ($n['incident_title']): ?>
      <a href="<?= SITE_URL ?>/student/view_incident.php?id=<?= $n['incident_id'] ?>"
         class="text-sm" style="color:var(--navy); font-weight:600;">
        View Incident #<?= $n['incident_id'] ?> →
      </a>
      <?php endif; ?>
    </div>
    <span class="notif-time"><?= date('d M, H:i', strtotime($n['created_at'])) ?></span>
  </div>
  <?php endwhile; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_student.php'; ?>
