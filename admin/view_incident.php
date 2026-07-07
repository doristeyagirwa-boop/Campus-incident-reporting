<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit_helpers.php';

$id = (int) ($_GET['id'] ?? 0);
$admin_id = (int) $_SESSION['user_id'];

if ($id <= 0) {
    header('Location: ' . SITE_URL . '/admin/incidents.php');
    exit;
}

function admin_incident_safe(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function admin_incident_date(string|null $value): string
{
    if (!$value) {
        return 'N/A';
    }

    $time = strtotime($value);

    if (!$time) {
        return 'N/A';
    }

    return date('d M Y, H:i', $time);
}

function admin_status_badge(string $status): string
{
    return match ($status) {
        'Open' => 'b-open',
        'Assigned' => 'b-assigned',
        'In Progress' => 'b-progress',
        'Resolved' => 'b-resolved',
        'Closed' => 'b-closed',
        default => '',
    };
}

function admin_priority_badge(string $priority): string
{
    return match ($priority) {
        'High' => 'b-high',
        'Medium' => 'b-medium',
        'Low' => 'b-low',
        default => '',
    };
}

function fetch_admin_incident(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare(
        "SELECT i.*, c.category_name,
                reporter.fullname AS reporter,
                reporter.email AS reporter_email,
                reporter.user_id AS reporter_id,
                technician.fullname AS technician_name,
                technician.email AS technician_email
         FROM incidents i
         JOIN categories c ON i.category_id = c.category_id
         JOIN users reporter ON i.user_id = reporter.user_id
         LEFT JOIN users technician ON i.assigned_to = technician.user_id
         WHERE i.incident_id = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $incident = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $incident ?: null;
}

$inc = fetch_admin_incident($conn, $id);

if (!$inc) {
    header('Location: ' . SITE_URL . '/admin/incidents.php');
    exit;
}

$page_title = 'Incident #' . $id;
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_incident') {
        $new_status = $_POST['status'] ?? '';
        $new_priority = $_POST['priority'] ?? '';
        $assigned_to_raw = (int) ($_POST['assigned_to'] ?? 0);
        $assigned_to = $assigned_to_raw > 0 ? $assigned_to_raw : null;
        $admin_note = trim($_POST['admin_note'] ?? '');

        $valid_statuses = ['Open', 'Assigned', 'In Progress', 'Resolved', 'Closed'];
        $valid_priorities = ['Low', 'Medium', 'High'];

        if (!in_array($new_status, $valid_statuses, true)) {
            $error = 'Invalid status selected.';
        } elseif (!in_array($new_priority, $valid_priorities, true)) {
            $error = 'Invalid priority selected.';
        } else {
            $old_status = (string) $inc['status'];
            $old_priority = (string) $inc['priority'];
            $old_assigned_to = $inc['assigned_to'] !== null ? (int) $inc['assigned_to'] : null;

            if ($assigned_to !== null && $new_status === 'Open') {
                $new_status = 'Assigned';
            }

            $stmt = $conn->prepare(
                "UPDATE incidents
                 SET status = ?, priority = ?, assigned_to = ?, updated_at = NOW()
                 WHERE incident_id = ?"
            );

            if (!$stmt) {
                $error = 'Could not prepare incident update.';
            } else {
                $stmt->bind_param('ssii', $new_status, $new_priority, $assigned_to, $id);
                $stmt->execute();
                $stmt->close();

                $changes = [];

                if ($old_status !== $new_status) {
                    $changes[] = "status from {$old_status} to {$new_status}";

                    create_notification(
                        $conn,
                        (int) $inc['reporter_id'],
                        $id,
                        'Your incident "' . $inc['title'] . '" status changed to: ' . $new_status . '.'
                    );
                }

                if ($old_priority !== $new_priority) {
                    $changes[] = "priority from {$old_priority} to {$new_priority}";

                    create_notification(
                        $conn,
                        (int) $inc['reporter_id'],
                        $id,
                        'Your incident "' . $inc['title'] . '" priority changed to: ' . $new_priority . '.'
                    );
                }

                if ($old_assigned_to !== $assigned_to) {
                    $changes[] = 'technician assignment changed';

                    if ($assigned_to !== null) {
                        create_notification(
                            $conn,
                            $assigned_to,
                            $id,
                            'You have been assigned incident #' . $id . ': ' . $inc['title'] . '.'
                        );

                        create_notification(
                            $conn,
                            (int) $inc['reporter_id'],
                            $id,
                            'A technician has been assigned to your incident "' . $inc['title'] . '".'
                        );
                    }
                }

                if ($admin_note !== '') {
                    $note_text = 'Admin command note: ' . $admin_note;

                    $note_stmt = $conn->prepare(
                        "INSERT INTO comments (incident_id, user_id, comment)
                         VALUES (?, ?, ?)"
                    );

                    if ($note_stmt) {
                        $note_stmt->bind_param('iis', $id, $admin_id, $note_text);
                        $note_stmt->execute();
                        $note_stmt->close();
                    }
                }

                if (!$changes && $admin_note === '') {
                    $success = 'No incident changes were needed.';
                } else {
                    $details = $changes
                        ? 'Updated incident #' . $id . ': ' . implode(', ', $changes)
                        : 'Added admin note to incident #' . $id;

                    audit_log(
                        $conn,
                        'INCIDENT_COMMAND_UPDATED',
                        'incident',
                        $id,
                        $details
                    );

                    if ($changes) {
                        $activity = 'Admin updated ' . implode(', ', $changes) . '.';

                        $activity_stmt = $conn->prepare(
                            "INSERT INTO comments (incident_id, user_id, comment)
                             VALUES (?, ?, ?)"
                        );

                        if ($activity_stmt) {
                            $activity_stmt->bind_param('iis', $id, $admin_id, $activity);
                            $activity_stmt->execute();
                            $activity_stmt->close();
                        }
                    }

                    $success = 'Incident command update saved.';
                }

                $inc = fetch_admin_incident($conn, $id);
            }
        }
    }

    if ($action === 'comment') {
        $text = trim($_POST['comment'] ?? '');

        if ($text === '') {
            $error = 'Comment cannot be empty.';
        } else {
            $comment_stmt = $conn->prepare(
                "INSERT INTO comments (incident_id, user_id, comment)
                 VALUES (?, ?, ?)"
            );

            if ($comment_stmt) {
                $comment_stmt->bind_param('iis', $id, $admin_id, $text);
                $comment_stmt->execute();
                $comment_stmt->close();

                audit_log(
                    $conn,
                    'INCIDENT_COMMENT_ADDED',
                    'incident',
                    $id,
                    'Admin added comment to incident #' . $id
                );

                create_notification(
                    $conn,
                    (int) $inc['reporter_id'],
                    $id,
                    'An admin added a note to your incident "' . $inc['title'] . '".'
                );

                header('Location: ' . SITE_URL . '/admin/view_incident.php?id=' . $id . '#comments');
                exit;
            }

            $error = 'Could not save comment.';
        }
    }
}

$technicians = $conn->query(
    "SELECT user_id, fullname, email
     FROM users
     WHERE role = 'technician'
     ORDER BY fullname"
);

$comments_stmt = $conn->prepare(
    "SELECT cm.*, u.fullname, u.role
     FROM comments cm
     JOIN users u ON cm.user_id = u.user_id
     WHERE cm.incident_id = ?
     ORDER BY cm.created_at ASC"
);

$comments_stmt->bind_param('i', $id);
$comments_stmt->execute();
$comment_rows = $comments_stmt->get_result();

$audit_stmt = $conn->prepare(
    "SELECT a.*, u.fullname AS actor_name
     FROM audit_logs a
     LEFT JOIN users u ON a.actor_user_id = u.user_id
     WHERE a.entity_type = 'incident'
       AND a.entity_id = ?
     ORDER BY a.created_at DESC
     LIMIT 10"
);

$audit_stmt->bind_param('i', $id);
$audit_stmt->execute();
$audit_rows = $audit_stmt->get_result();

$intelligence_stmt = $conn->prepare(
    "SELECT *
     FROM incident_intelligence
     WHERE incident_id = ?
     ORDER BY created_at DESC"
);

$intelligence_stmt->bind_param('i', $id);
$intelligence_stmt->execute();
$intelligence_rows = $intelligence_stmt->get_result();

$prediction_stmt = $conn->prepare(
    "SELECT *
     FROM incident_predictions
     WHERE incident_id = ?
     ORDER BY created_at DESC
     LIMIT 1"
);

$prediction_stmt->bind_param('i', $id);
$prediction_stmt->execute();
$latest_prediction = $prediction_stmt->get_result()->fetch_assoc();

include __DIR__ . '/../includes/header_admin.php';
?>

<?php if ($error): ?>
  <div class="form-error mb-16"><?= admin_incident_safe($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
  <div class="form-success mb-16"><?= admin_incident_safe($success) ?></div>
<?php endif; ?>

<div class="detail-grid">
  <div>
    <div class="panel panel-body mb-24">
      <div class="flex-between mb-16">
        <div>
          <h2 class="detail-title"><?= admin_incident_safe($inc['title']) ?></h2>
          <p class="detail-meta">
            Reported by <strong><?= admin_incident_safe($inc['reporter']) ?></strong>
            · <?= admin_incident_safe($inc['category_name']) ?>
            · <?= admin_incident_safe($inc['location']) ?>
            · <?= admin_incident_date($inc['created_at']) ?>
          </p>
        </div>

        <div class="inc-badges">
          <span class="badge <?= admin_priority_badge($inc['priority']) ?>">
            <?= admin_incident_safe($inc['priority']) ?> Priority
          </span>
          <span class="badge <?= admin_status_badge($inc['status']) ?>">
            <?= admin_incident_safe($inc['status']) ?>
          </span>
        </div>
      </div>

      <p class="detail-body"><?= nl2br(admin_incident_safe($inc['description'])) ?></p>

      <div class="tracking-meta mt-16">
        <span>Reporter email: <?= admin_incident_safe($inc['reporter_email']) ?></span>
        <span>Assigned technician: <?= admin_incident_safe($inc['technician_name'] ?: 'Unassigned') ?></span>
        <span>Last updated: <?= admin_incident_date($inc['updated_at']) ?></span>
      </div>

      <?php if (!empty($inc['attachment'])): ?>
        <div class="mt-16">
          <a href="<?= SITE_URL ?>/uploads/<?= admin_incident_safe($inc['attachment']) ?>"
             target="_blank"
             class="btn btn-outline btn-sm">
            📎 View Attachment
          </a>
        </div>
      <?php endif; ?>
    </div>

    <div class="panel mb-24">
      <div class="panel-head">
        <h3>Latest Neural Prediction</h3>
      </div>

      <?php if (!$latest_prediction): ?>
        <div class="empty-state" style="padding:28px;">
          <p>No neural prediction has been generated for this incident yet.</p>
          <p class="text-muted text-sm">
            Go to Neural Engine and run analysis to generate a prediction.
          </p>
        </div>
      <?php else: ?>
        <div class="panel-body">
          <div class="intel-grid">
            <div>
              <strong>Risk Score</strong>
              <p><?= number_format((float) $latest_prediction['predicted_risk_score'], 2) ?>/100</p>
            </div>

            <div>
              <strong>Predicted Priority</strong>
              <p><?= admin_incident_safe($latest_prediction['predicted_priority']) ?></p>
            </div>

            <div>
              <strong>Confidence</strong>
              <p><?= number_format((float) $latest_prediction['confidence_score'], 2) ?>%</p>
            </div>
          </div>

          <div class="learning-signal">
            <strong>Recommended Route:</strong>
            <?= admin_incident_safe($latest_prediction['recommended_route']) ?>
          </div>

          <div class="learning-signal">
            <strong>Recommended Action:</strong>
            <?= admin_incident_safe($latest_prediction['recommended_action']) ?>
          </div>

          <?php if (!empty($latest_prediction['suggested_root_cause'])): ?>
            <div class="learning-signal">
              <strong>Suggested Root Cause:</strong>
              <?= admin_incident_safe($latest_prediction['suggested_root_cause']) ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($latest_prediction['similar_incident_id'])): ?>
            <div class="learning-signal">
              <strong>Similar Case:</strong>
              Incident #<?= (int) $latest_prediction['similar_incident_id'] ?>
            </div>
          <?php endif; ?>

          <div class="learning-signal">
            <strong>Explanation:</strong>
            <?= admin_incident_safe($latest_prediction['explanation']) ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="panel mb-24">
      <div class="panel-head">
        <h3>Latest Neural Prediction</h3>
      </div>

      <?php if (!$latest_prediction): ?>
        <div class="empty-state" style="padding:28px;">
          <p>No neural prediction has been generated for this incident yet.</p>
          <p class="text-muted text-sm">
            Go to Neural Engine and run analysis to generate a prediction.
          </p>
        </div>
      <?php else: ?>
        <div class="panel-body">
          <div class="intel-grid">
            <div>
              <strong>Risk Score</strong>
              <p><?= number_format((float) $latest_prediction['predicted_risk_score'], 2) ?>/100</p>
            </div>

            <div>
              <strong>Predicted Priority</strong>
              <p><?= admin_incident_safe($latest_prediction['predicted_priority']) ?></p>
            </div>

            <div>
              <strong>Confidence</strong>
              <p><?= number_format((float) $latest_prediction['confidence_score'], 2) ?>%</p>
            </div>
          </div>

          <div class="learning-signal">
            <strong>Recommended Route:</strong>
            <?= admin_incident_safe($latest_prediction['recommended_route']) ?>
          </div>

          <div class="learning-signal">
            <strong>Recommended Action:</strong>
            <?= admin_incident_safe($latest_prediction['recommended_action']) ?>
          </div>

          <?php if (!empty($latest_prediction['suggested_root_cause'])): ?>
            <div class="learning-signal">
              <strong>Suggested Root Cause:</strong>
              <?= admin_incident_safe($latest_prediction['suggested_root_cause']) ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($latest_prediction['similar_incident_id'])): ?>
            <div class="learning-signal">
              <strong>Similar Case:</strong>
              Incident #<?= (int) $latest_prediction['similar_incident_id'] ?>
            </div>
          <?php endif; ?>

          <div class="learning-signal">
            <strong>Explanation:</strong>
            <?= admin_incident_safe($latest_prediction['explanation']) ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="panel mb-24" id="comments">
      <div class="panel-head">
        <h3>Comments & Activity</h3>
      </div>

      <?php if ($comment_rows->num_rows === 0): ?>
        <div class="empty-state" style="padding:28px;">
          <p>No comments yet.</p>
        </div>
      <?php else: ?>
        <div class="comment-list">
          <?php while ($comment = $comment_rows->fetch_assoc()): ?>
            <div class="comment">
              <div class="avatar">
                <?= strtoupper(substr($comment['fullname'], 0, 2)) ?>
              </div>
              <div class="comment-bubble">
                <div class="comment-who">
                  <?= admin_incident_safe($comment['fullname']) ?>
                  <span class="comment-role"><?= admin_incident_safe($comment['role']) ?></span>
                </div>
                <div class="comment-text"><?= nl2br(admin_incident_safe($comment['comment'])) ?></div>
                <div class="comment-time"><?= admin_incident_date($comment['created_at']) ?></div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>

      <div class="panel-body" style="border-top:1px solid var(--line);">
        <form method="post">
          <input type="hidden" name="action" value="comment">

          <div class="field">
            <label for="comment">Reply / Internal Note</label>
            <textarea id="comment" name="comment" rows="3"
              placeholder="Add a note for the reporter or team..."></textarea>
          </div>

          <button type="submit" class="btn btn-primary btn-sm">Post Comment</button>
        </form>
      </div>
    </div>

    <div class="panel mb-24">
      <div class="panel-head">
        <h3>Audit Trail</h3>
      </div>

      <?php if ($audit_rows->num_rows === 0): ?>
        <div class="empty-state" style="padding:28px;">
          <p>No audit activity yet for this incident.</p>
        </div>
      <?php else: ?>
        <?php while ($audit = $audit_rows->fetch_assoc()): ?>
          <div class="incident-row">
            <span class="inc-id">#<?= (int) $audit['audit_id'] ?></span>
            <div class="inc-info">
              <h4><?= admin_incident_safe($audit['action']) ?></h4>
              <div class="inc-meta">
                <span><?= admin_incident_safe($audit['actor_name'] ?: 'System') ?></span>
                <span>·</span>
                <span><?= admin_incident_safe($audit['actor_role']) ?></span>
                <span>·</span>
                <span><?= admin_incident_date($audit['created_at']) ?></span>
              </div>
              <p class="text-muted text-sm"><?= admin_incident_safe($audit['details']) ?></p>
            </div>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h3>Linked Intelligence</h3>
      </div>

      <?php if ($intelligence_rows->num_rows === 0): ?>
        <div class="empty-state" style="padding:28px;">
          <p>No resolution intelligence captured for this incident yet.</p>
        </div>
      <?php else: ?>
        <div class="panel-body">
          <?php while ($intel = $intelligence_rows->fetch_assoc()): ?>
            <div class="tip-box mb-16">
              <h4>Resolution Intelligence</h4>
              <p><strong>Root Cause:</strong><br><?= nl2br(admin_incident_safe($intel['root_cause'])) ?></p>
              <p><strong>Resolution:</strong><br><?= nl2br(admin_incident_safe($intel['resolution_summary'])) ?></p>
              <p><strong>Prevention:</strong><br><?= nl2br(admin_incident_safe($intel['prevention_recommendation'])) ?></p>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <aside>
    <div class="manage-card">
      <h3 class="mb-16">Command Center</h3>

      <form method="post">
        <input type="hidden" name="action" value="update_incident">

        <div class="field">
          <label for="status">Status</label>
          <select id="status" name="status">
            <?php foreach (['Open', 'Assigned', 'In Progress', 'Resolved', 'Closed'] as $status): ?>
              <option value="<?= admin_incident_safe($status) ?>"
                <?= $inc['status'] === $status ? 'selected' : '' ?>>
                <?= admin_incident_safe($status) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="priority">Priority</label>
          <select id="priority" name="priority">
            <?php foreach (['High', 'Medium', 'Low'] as $priority): ?>
              <option value="<?= admin_incident_safe($priority) ?>"
                <?= $inc['priority'] === $priority ? 'selected' : '' ?>>
                <?= admin_incident_safe($priority) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="assigned_to">Assign Technician</label>
          <select id="assigned_to" name="assigned_to">
            <option value="">— Unassigned —</option>
            <?php if ($technicians): ?>
              <?php while ($tech = $technicians->fetch_assoc()): ?>
                <option value="<?= (int) $tech['user_id'] ?>"
                  <?= (int) $inc['assigned_to'] === (int) $tech['user_id'] ? 'selected' : '' ?>>
                  <?= admin_incident_safe($tech['fullname']) ?> — <?= admin_incident_safe($tech['email']) ?>
                </option>
              <?php endwhile; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="field">
          <label for="admin_note">Admin Command Note</label>
          <textarea id="admin_note" name="admin_note" rows="4"
            placeholder="Reason for assignment, escalation, priority change, or closure..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
          Save Command Update
        </button>
      </form>

      <hr style="border:none;border-top:1px solid var(--line);margin:16px 0;">

      <a href="<?= SITE_URL ?>/admin/incidents.php"
         class="btn btn-outline btn-sm btn-block">
        ← All Incidents
      </a>
    </div>
  </aside>
</div>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
