<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

$uid = (int) $_SESSION['user_id'];
$id  = (int) ($_GET['id'] ?? 0);

// Fetch incident (students can only see their own)
$stmt = $conn->prepare(
    "SELECT i.*, c.category_name, u.fullname as reporter_name,
            t.fullname as technician_name
     FROM incidents i
     JOIN categories c ON i.category_id = c.category_id
     JOIN users u ON i.user_id = u.user_id
     LEFT JOIN users t ON i.assigned_to = t.user_id
     WHERE i.incident_id = ? AND i.user_id = ?"
);
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$inc = $stmt->get_result()->fetch_assoc();

if (!$inc) {
    header('Location: ' . SITE_URL . '/student/my_reports.php');
    exit;
}

$page_title = 'Incident #' . $id;

// Handle comment POST
$comment_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $text = trim($_POST['comment'] ?? '');
    if (!$text) {
        $comment_error = 'Comment cannot be empty.';
    } else {
        $cs = $conn->prepare("INSERT INTO comments (incident_id, user_id, comment) VALUES (?, ?, ?)");
        $cs->bind_param('iis', $id, $uid, $text);
        $cs->execute();
        $cs->close();
        header('Location: ' . SITE_URL . '/student/view_incident.php?id=' . $id . '#comments');
        exit;
    }
}

// Fetch comments
$comments = $conn->prepare(
    "SELECT cm.*, u.fullname, u.role
     FROM comments cm JOIN users u ON cm.user_id = u.user_id
     WHERE cm.incident_id = ? ORDER BY cm.created_at ASC"
);
$comments->bind_param('i', $id);
$comments->execute();
$comment_rows = $comments->get_result();

function status_badge(string $s): string {
    return match($s) { 'Open'=>'b-open','Assigned'=>'b-assigned','In Progress'=>'b-progress','Resolved'=>'b-resolved','Closed'=>'b-closed',default=>'' };
}
function priority_badge(string $p): string {
    return match($p) { 'High'=>'b-high','Medium'=>'b-medium','Low'=>'b-low',default=>'' };
}

include __DIR__ . '/../includes/header_student.php';
?>

<?php if (isset($_GET['submitted'])): ?>
<div class="form-success mb-16">Your report has been submitted successfully. We'll review it shortly.</div>
<?php endif; ?>

<div class="detail-grid">
  <!-- Incident detail -->
  <div>
    <div class="panel panel-body">
      <div class="flex-between mb-16">
        <h2 class="detail-title"><?= htmlspecialchars($inc['title']) ?></h2>
        <div class="inc-badges">
          <span class="badge <?= priority_badge($inc['priority']) ?>"><?= $inc['priority'] ?> Priority</span>
          <span class="badge <?= status_badge($inc['status']) ?>"><?= $inc['status'] ?></span>
        </div>
      </div>
      <p class="detail-meta">
        <?= htmlspecialchars($inc['category_name']) ?> &nbsp;·&nbsp;
        <?= htmlspecialchars($inc['location']) ?> &nbsp;·&nbsp;
        <?= date('d M Y, H:i', strtotime($inc['created_at'])) ?>
        <?php if ($inc['technician_name']): ?>
          &nbsp;·&nbsp; Assigned to: <strong><?= htmlspecialchars($inc['technician_name']) ?></strong>
        <?php endif; ?>
      </p>
      <p class="detail-body"><?= nl2br(htmlspecialchars($inc['description'])) ?></p>

      <?php if ($inc['attachment']): ?>
      <div class="mt-16">
        <span class="text-sm text-muted" style="font-weight:600;">ATTACHMENT</span><br>
        <a href="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($inc['attachment']) ?>"
           target="_blank" class="btn btn-outline btn-sm mt-8">📎 View Attachment</a>
      </div>
      <?php endif; ?>
    </div>

    <!-- Comments -->
    <div class="panel" id="comments">
      <div class="panel-head"><h3>Comments & Activity</h3></div>

      <?php if ($comment_rows->num_rows === 0): ?>
      <div class="empty-state" style="padding:28px;">
        <p>No comments yet.</p>
      </div>
      <?php else: ?>
      <div class="comment-list">
      <?php while ($c = $comment_rows->fetch_assoc()): ?>
        <div class="comment">
          <div class="avatar" style="<?= $c['role'] === 'admin' ? 'background:var(--navy);color:#fff;' : ($c['role']==='technician'?'background:var(--status-resolved);color:#fff;':'') ?>">
            <?= strtoupper(substr($c['fullname'], 0, 2)) ?>
          </div>
          <div class="comment-bubble">
            <div class="comment-who">
              <?= htmlspecialchars($c['fullname']) ?>
              <?php if ($c['role'] !== 'student'): ?>
              <span class="comment-role"><?= ucfirst($c['role']) ?></span>
              <?php endif; ?>
            </div>
            <div class="comment-text"><?= nl2br(htmlspecialchars($c['comment'])) ?></div>
            <div class="comment-time"><?= date('d M Y, H:i', strtotime($c['created_at'])) ?></div>
          </div>
        </div>
      <?php endwhile; ?>
      </div>
      <?php endif; ?>

      <!-- Add comment -->
      <div class="panel-body" style="border-top:1px solid var(--line);">
        <?php if ($comment_error): ?><div class="form-error mb-16"><?= htmlspecialchars($comment_error) ?></div><?php endif; ?>
        <form method="POST">
          <div class="field">
            <label for="comment">Add a comment</label>
            <textarea id="comment" name="comment" rows="3" placeholder="Provide an update or ask a question…"></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">Post Comment</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Sidebar info -->
  <div class="manage-card">
    <h4>Incident Details</h4>
    <div class="field"><label>Status</label>
      <span class="badge <?= status_badge($inc['status']) ?>" style="font-size:13px; padding:6px 14px;"><?= $inc['status'] ?></span>
    </div>
    <div class="field"><label>Priority</label>
      <span class="badge <?= priority_badge($inc['priority']) ?>" style="font-size:13px; padding:6px 14px;"><?= $inc['priority'] ?></span>
    </div>
    <div class="field"><label>Category</label><p><?= htmlspecialchars($inc['category_name']) ?></p></div>
    <div class="field"><label>Location</label><p><?= htmlspecialchars($inc['location']) ?></p></div>
    <div class="field"><label>Submitted</label><p><?= date('d M Y, H:i', strtotime($inc['created_at'])) ?></p></div>
    <?php if ($inc['updated_at'] !== $inc['created_at']): ?>
    <div class="field"><label>Last Updated</label><p><?= date('d M Y, H:i', strtotime($inc['updated_at'])) ?></p></div>
    <?php endif; ?>
    <hr style="border:none;border-top:1px solid var(--line);margin:16px 0;">
    <a href="<?= SITE_URL ?>/student/my_reports.php" class="btn btn-outline btn-sm btn-block">← Back to My Reports</a>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer_student.php'; ?>
