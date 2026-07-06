<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

$id  = (int) ($_GET['id'] ?? 0);
$uid = (int) $_SESSION['user_id'];

// Fetch incident
$stmt = $conn->prepare(
    "SELECT i.*, c.category_name, u.fullname AS reporter, u.user_id AS reporter_id,
            t.fullname AS technician_name
     FROM incidents i
     JOIN categories c ON i.category_id = c.category_id
     JOIN users u ON i.user_id = u.user_id
     LEFT JOIN users t ON i.assigned_to = t.user_id
     WHERE i.incident_id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$inc = $stmt->get_result()->fetch_assoc();

if (!$inc) {
    header('Location: ' . SITE_URL . '/admin/incidents.php');
    exit;
}

$page_title = 'Incident #' . $id;
$success = $error = '';

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $new_status   = $_POST['status']      ?? '';
        $new_priority = $_POST['priority']    ?? '';
        $assigned_to  = (int)($_POST['assigned_to'] ?? 0) ?: null;

        $valid_statuses  = ['Open','Assigned','In Progress','Resolved','Closed'];
        $valid_priorities = ['Low','Medium','High'];

        if (!in_array($new_status, $valid_statuses) || !in_array($new_priority, $valid_priorities)) {
            $error = 'Invalid status or priority.';
        } else {
            $old_status = $inc['status'];
            $upd = $conn->prepare(
                "UPDATE incidents SET status = ?, priority = ?, assigned_to = ? WHERE incident_id = ?"
            );
            $upd->bind_param('ssii', $new_status, $new_priority, $assigned_to, $id);
            if ($upd->execute()) {
                // Notify reporter if status changed
                if ($new_status !== $old_status) {
                    create_notification(
                        $conn,
                        $inc['reporter_id'],
                        $id,
                        "Your incident \"" . $inc['title'] . "\" status changed to: $new_status."
                    );
                }
                $success = 'Incident updated.';
                // Refresh
                $stmt->execute();
                $inc = $stmt->get_result()->fetch_assoc();
            } else {
                $error = 'Update failed.';
            }
        }
    }

    if ($action === 'comment') {
        $text = trim($_POST['comment'] ?? '');
        if ($text) {
            $cs = $conn->prepare("INSERT INTO comments (incident_id, user_id, comment) VALUES (?, ?, ?)");
            $cs->bind_param('iis', $id, $uid, $text);
            $cs->execute();
            header('Location: ' . SITE_URL . '/admin/view_incident.php?id=' . $id . '#comments');
            exit;
        }
    }
}

// Fetch technicians for assignment
$techs = $conn->query("SELECT user_id, fullname FROM users WHERE role IN ('technician','admin') ORDER BY fullname");

// Fetch comments
$comments = $conn->prepare(
    "SELECT cm.*, u.fullname, u.role
     FROM comments cm JOIN users u ON cm.user_id = u.user_id
     WHERE cm.incident_id = ? ORDER BY cm.created_at ASC"
);
$comments->bind_param('i', $id);
$comments->execute();
$comment_rows = $comments->get_result();

function status_badge(string $s): string { return match($s) { 'Open'=>'b-open','Assigned'=>'b-assigned','In Progress'=>'b-progress','Resolved'=>'b-resolved','Closed'=>'b-closed',default=>'' }; }
function priority_badge(string $p): string { return match($p) { 'High'=>'b-high','Medium'=>'b-medium','Low'=>'b-low',default=>'' }; }

include __DIR__ . '/../includes/header_admin.php';
?>

<?php if ($error):   ?><div class="form-error mb-16"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="form-success mb-16"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="detail-grid">
  <!-- Left: detail + comments -->
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
        Reported by <strong><?= htmlspecialchars($inc['reporter']) ?></strong>
        &nbsp;·&nbsp; <?= htmlspecialchars($inc['category_name']) ?>
        &nbsp;·&nbsp; <?= htmlspecialchars($inc['location']) ?>
        &nbsp;·&nbsp; <?= date('d M Y, H:i', strtotime($inc['created_at'])) ?>
      </p>
      <p class="detail-body"><?= nl2br(htmlspecialchars($inc['description'])) ?></p>

      <?php if ($inc['attachment']): ?>
      <div class="mt-16">
        <a href="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($inc['attachment']) ?>"
           target="_blank" class="btn btn-outline btn-sm">📎 View Attachment</a>
      </div>
      <?php endif; ?>
    </div>

    <!-- Comments -->
    <div class="panel" id="comments">
      <div class="panel-head"><h3>Comments & Activity</h3></div>

      <?php if ($comment_rows->num_rows === 0): ?>
      <div class="empty-state" style="padding:28px;"><p>No comments yet.</p></div>
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

      <div class="panel-body" style="border-top:1px solid var(--line);">
        <form method="POST">
          <input type="hidden" name="action" value="comment">
          <div class="field">
            <label for="comment">Reply / Internal Note</label>
            <textarea id="comment" name="comment" rows="3" placeholder="Add a note for the reporter or team…"></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">Post Comment</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Right: manage -->
  <div class="manage-card">
    <h4>Manage This Incident</h4>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
          <?php foreach (['Open','Assigned','In Progress','Resolved','Closed'] as $s): ?>
          <option <?= $inc['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="priority">Priority</label>
        <select id="priority" name="priority">
          <?php foreach (['High','Medium','Low'] as $p): ?>
          <option <?= $inc['priority'] === $p ? 'selected' : '' ?>><?= $p ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="assigned_to">Assign Technician</label>
        <select id="assigned_to" name="assigned_to">
          <option value="">— Unassigned —</option>
          <?php while ($tech = $techs->fetch_assoc()): ?>
          <option value="<?= $tech['user_id'] ?>"
            <?= $inc['assigned_to'] == $tech['user_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($tech['fullname']) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
    </form>
    <hr style="border:none;border-top:1px solid var(--line);margin:16px 0;">
    <a href="<?= SITE_URL ?>/admin/incidents.php" class="btn btn-outline btn-sm btn-block">← All Incidents</a>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
