<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('technician');
require_once __DIR__ . '/../includes/db.php';

$technician_id = (int) $_SESSION['user_id'];
$incident_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($incident_id <= 0) {
    http_response_code(400);
    die('Invalid incident ID.');
}

function tech_safe(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function tech_date(string|null $value): string
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

function fetch_technician_incident(mysqli $conn, int $incident_id, int $technician_id): ?array
{
    $stmt = $conn->prepare(
        "SELECT i.*, c.category_name,
                u.fullname AS reporter_name,
                u.email AS reporter_email
         FROM incidents i
         JOIN categories c ON i.category_id = c.category_id
         JOIN users u ON i.user_id = u.user_id
         WHERE i.incident_id = ?
           AND i.assigned_to = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('ii', $incident_id, $technician_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $incident = $result->fetch_assoc();
    $stmt->close();

    return $incident ?: null;
}

$incident = fetch_technician_incident($conn, $incident_id, $technician_id);

if (!$incident) {
    http_response_code(403);
    die('This incident is not assigned to you.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_note') {
        $note = trim($_POST['note'] ?? '');

        if ($note === '') {
            $error = 'Progress note cannot be empty.';
        } else {
            $comment = 'Technician progress note: ' . $note;

            $stmt = $conn->prepare(
                "INSERT INTO comments (incident_id, user_id, comment)
                 VALUES (?, ?, ?)"
            );

            if ($stmt) {
                $stmt->bind_param('iis', $incident_id, $technician_id, $comment);
                $stmt->execute();
                $stmt->close();

                create_notification(
                    $conn,
                    (int) $incident['user_id'],
                    $incident_id,
                    'A technician added a progress update to your incident.'
                );

                header('Location: ' . SITE_URL . '/technician/view_incident.php?id=' . $incident_id . '&saved=note');
                exit;
            }

            $error = 'Could not save progress note.';
        }
    }

    if ($action === 'update_status') {
        $new_status = $_POST['status'] ?? '';
        $progress_note = trim($_POST['progress_note'] ?? '');

        $allowed = ['Assigned', 'In Progress', 'Resolved'];

        if (!in_array($new_status, $allowed, true)) {
            $error = 'Invalid status selected.';
        } else {
            $root_cause = trim($_POST['root_cause'] ?? '');
            $resolution_summary = trim($_POST['resolution_summary'] ?? '');
            $prevention_recommendation = trim($_POST['prevention_recommendation'] ?? '');

            if ($new_status === 'Resolved' && ($root_cause === '' || $resolution_summary === '')) {
                $error = 'Root cause and resolution summary are required before resolving.';
            } else {
                $previous_status = (string) $incident['status'];

                $stmt = $conn->prepare(
                    "UPDATE incidents
                     SET status = ?, updated_at = NOW()
                     WHERE incident_id = ?
                       AND assigned_to = ?"
                );

                if (!$stmt) {
                    $error = 'Could not update incident status.';
                } else {
                    $stmt->bind_param('sii', $new_status, $incident_id, $technician_id);
                    $stmt->execute();
                    $stmt->close();

                    $comment = "Status changed from {$previous_status} to {$new_status}.";

                    if ($progress_note !== '') {
                        $comment .= "\n\nTechnician note: " . $progress_note;
                    }

                    if ($new_status === 'Resolved') {
                        $comment .= "\n\nRoot cause: " . $root_cause;
                        $comment .= "\n\nResolution: " . $resolution_summary;

                        if ($prevention_recommendation !== '') {
                            $comment .= "\n\nPrevention recommendation: " . $prevention_recommendation;
                        }

                        $learning_signal = strtolower(
                            $incident['category_name'] . ' | ' .
                            $incident['title'] . ' | ' .
                            $root_cause . ' | ' .
                            $resolution_summary
                        );

                        $intel = $conn->prepare(
                            "INSERT INTO incident_intelligence
                             (incident_id, technician_id, category_id, status_before,
                              root_cause, resolution_summary, prevention_recommendation, learning_signal)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                        );

                        if ($intel) {
                            $category_id = (int) $incident['category_id'];

                            $intel->bind_param(
                                'iiisssss',
                                $incident_id,
                                $technician_id,
                                $category_id,
                                $previous_status,
                                $root_cause,
                                $resolution_summary,
                                $prevention_recommendation,
                                $learning_signal
                            );

                            $intel->execute();
                            $intel->close();
                        }
                    }

                    $stmt2 = $conn->prepare(
                        "INSERT INTO comments (incident_id, user_id, comment)
                         VALUES (?, ?, ?)"
                    );

                    if ($stmt2) {
                        $stmt2->bind_param('iis', $incident_id, $technician_id, $comment);
                        $stmt2->execute();
                        $stmt2->close();
                    }

                    create_notification(
                        $conn,
                        (int) $incident['user_id'],
                        $incident_id,
                        'Your incident status was updated to ' . $new_status . '.'
                    );

                    header('Location: ' . SITE_URL . '/technician/view_incident.php?id=' . $incident_id . '&saved=status');
                    exit;
                }
            }
        }
    }
}

if (isset($_GET['saved'])) {
    $success = $_GET['saved'] === 'status'
        ? 'Incident status updated successfully.'
        : 'Progress note saved successfully.';
}

$incident = fetch_technician_incident($conn, $incident_id, $technician_id);

$comments_stmt = $conn->prepare(
    "SELECT cm.*, u.fullname, u.role
     FROM comments cm
     JOIN users u ON cm.user_id = u.user_id
     WHERE cm.incident_id = ?
     ORDER BY cm.created_at ASC"
);

$comments_stmt->bind_param('i', $incident_id);
$comments_stmt->execute();
$comments = $comments_stmt->get_result();

$intel_stmt = $conn->prepare(
    "SELECT *
     FROM incident_intelligence
     WHERE incident_id = ?
     ORDER BY created_at DESC"
);

$intel_stmt->bind_param('i', $incident_id);
$intel_stmt->execute();
$intelligence = $intel_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= tech_safe(SITE_NAME) ?> — Technician Incident</title>
<link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <span class="crest">CG</span>
      <span>CampusGuard</span>
    </div>

    <nav class="sidebar-nav">
      <a href="<?= SITE_URL ?>/technician/dashboard.php">
        <span class="nav-icon">🛠️</span> Dashboard
      </a>
      <a href="<?= SITE_URL ?>/logout.php">
        <span class="nav-icon">🚪</span> Logout
      </a>
    </nav>

    <div class="sidebar-footer">
      Technician<br>
      <strong><?= tech_safe($_SESSION['fullname'] ?? 'Technician') ?></strong>
    </div>
  </aside>

  <main class="main">
    <header class="topstrip">
      <div>
        <h1>Incident #<?= (int) $incident['incident_id'] ?></h1>
        <p class="text-muted">Technician progress, notes, resolution, and intelligence capture</p>
      </div>

      <a class="btn btn-outline btn-sm" href="<?= SITE_URL ?>/technician/dashboard.php">
        ← Back
      </a>
    </header>

    <section class="content">
      <?php if ($error): ?>
        <div class="form-error"><?= tech_safe($error) ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="form-success"><?= tech_safe($success) ?></div>
      <?php endif; ?>

      <div class="detail-grid">
        <div>
          <div class="panel panel-body mb-24">
            <h2 class="detail-title"><?= tech_safe($incident['title']) ?></h2>
            <p class="detail-meta">
              <?= tech_safe($incident['category_name']) ?>
              · <?= tech_safe($incident['location']) ?>
              · Submitted <?= tech_date($incident['created_at']) ?>
            </p>

            <div class="inc-badges mb-16">
              <span class="badge"><?= tech_safe($incident['priority']) ?></span>
              <span class="badge"><?= tech_safe($incident['status']) ?></span>
            </div>

            <p class="detail-body"><?= nl2br(tech_safe($incident['description'])) ?></p>

            <?php if (!empty($incident['attachment'])): ?>
              <p>
                <a class="btn btn-outline btn-sm"
                   href="<?= SITE_URL ?>/uploads/<?= tech_safe($incident['attachment']) ?>"
                   target="_blank">
                  View Attachment
                </a>
              </p>
            <?php endif; ?>
          </div>

          <div class="panel mb-24">
            <div class="panel-head">
              <h3>Progress Notes</h3>
            </div>

            <div class="comment-list">
              <?php if ($comments->num_rows === 0): ?>
                <p class="text-muted">No notes yet.</p>
              <?php else: ?>
                <?php while ($comment = $comments->fetch_assoc()): ?>
                  <div class="comment">
                    <div class="avatar"><?= strtoupper(substr($comment['fullname'], 0, 1)) ?></div>
                    <div class="comment-bubble">
                      <div class="comment-who">
                        <?= tech_safe($comment['fullname']) ?>
                        <span class="comment-role"><?= tech_safe($comment['role']) ?></span>
                      </div>
                      <div><?= nl2br(tech_safe($comment['comment'])) ?></div>
                      <div class="comment-time"><?= tech_date($comment['created_at']) ?></div>
                    </div>
                  </div>
                <?php endwhile; ?>
              <?php endif; ?>
            </div>
          </div>

          <div class="panel">
            <div class="panel-head">
              <h3>Intelligence Capture</h3>
            </div>

            <div class="panel-body">
              <?php if ($intelligence->num_rows === 0): ?>
                <p class="text-muted">
                  No intelligence record yet. Resolving this incident will create one.
                </p>
              <?php else: ?>
                <?php while ($intel = $intelligence->fetch_assoc()): ?>
                  <div class="tip-box mb-16">
                    <h4>Resolution Intelligence</h4>
                    <p><strong>Root Cause:</strong><br><?= nl2br(tech_safe($intel['root_cause'])) ?></p>
                    <p><strong>Resolution:</strong><br><?= nl2br(tech_safe($intel['resolution_summary'])) ?></p>
                    <p><strong>Prevention:</strong><br><?= nl2br(tech_safe($intel['prevention_recommendation'])) ?></p>
                    <p class="text-muted">Captured <?= tech_date($intel['created_at']) ?></p>
                  </div>
                <?php endwhile; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <aside>
          <div class="manage-card mb-24">
            <h3 class="mb-16">Update Progress</h3>

            <form method="post">
              <input type="hidden" name="action" value="add_note">

              <div class="field">
                <label>Progress Note</label>
                <textarea name="note" rows="5" required
                  placeholder="Example: Checked router logs, issue appears to affect AP block B..."></textarea>
              </div>

              <button class="btn btn-primary btn-block" type="submit">
                Save Progress Note
              </button>
            </form>
          </div>

          <div class="manage-card">
            <h3 class="mb-16">Change Status</h3>

            <form method="post">
              <input type="hidden" name="action" value="update_status">

              <div class="field">
                <label>Status</label>
                <select name="status" required>
                  <option value="Assigned" <?= $incident['status'] === 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                  <option value="In Progress" <?= $incident['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                  <option value="Resolved" <?= $incident['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                </select>
              </div>

              <div class="field">
                <label>Technician Note</label>
                <textarea name="progress_note" rows="4"
                  placeholder="What action was taken?"></textarea>
              </div>

              <div class="field">
                <label>Root Cause Required for Resolved</label>
                <textarea name="root_cause" rows="3"
                  placeholder="Example: DHCP pool exhausted in student VLAN."></textarea>
              </div>

              <div class="field">
                <label>Resolution Summary Required for Resolved</label>
                <textarea name="resolution_summary" rows="3"
                  placeholder="Example: Expanded DHCP scope and restarted affected access point."></textarea>
              </div>

              <div class="field">
                <label>Prevention Recommendation</label>
                <textarea name="prevention_recommendation" rows="3"
                  placeholder="Example: Add DHCP monitoring alert and weekly network capacity review."></textarea>
              </div>

              <button class="btn btn-primary btn-block" type="submit">
                Update Incident
              </button>
            </form>
          </div>
        </aside>
      </div>
    </section>
  </main>
</div>
</body>
</html>
