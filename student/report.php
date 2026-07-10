<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit_helpers.php';
require_once __DIR__ . '/../includes/intelligence_engine.php';
require_once __DIR__ . '/../includes/department_allocator.php';
require_once __DIR__ . '/../includes/campus_assistant_widget.php';
require_once __DIR__ . '/../includes/dining_alerts.php';
require_once __DIR__ . '/../includes/academic_space_engine.php';
require_once __DIR__ . '/../includes/phoenix_engine.php';
require_once __DIR__ . '/../includes/backend_bridge.php';

$page_title = 'Report an Incident';
$error = $success = '';

$cats = $conn->query("SELECT * FROM categories ORDER BY category_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $cat_id      = (int) ($_POST['category_id'] ?? 0);
    $priority    = $_POST['priority'] ?? '';
    $location    = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $uid         = (int) $_SESSION['user_id'];

    $allowed_priorities = ['Low', 'Medium', 'High'];

    if (!$title || !$cat_id || !$priority || !$location || !$description) {
        $error = 'Please fill in all required fields.';
    } elseif (!in_array($priority, $allowed_priorities, true)) {
        $error = 'Invalid priority selected.';
    } else {
        $attachment = null;

        if (!empty($_FILES['attachment']['name'])) {
            $ext       = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $size      = (int) $_FILES['attachment']['size'];
            $max_bytes = UPLOAD_MAX_MB * 1024 * 1024;

            if (!in_array($ext, UPLOAD_ALLOWED, true)) {
                $error = 'File type not allowed. Use JPG, PNG, PDF or GIF.';
            } elseif ($size > $max_bytes) {
                $error = 'File is too large. Maximum ' . UPLOAD_MAX_MB . 'MB.';
            } else {
                $filename = uniqid('attach_', true) . '.' . $ext;
                $dest     = UPLOAD_DIR . $filename;

                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $dest)) {
                    $attachment = $filename;
                } else {
                    $error = 'File upload failed. Check that the uploads/ folder is writable.';
                }
            }
        }

        if (!$error) {
            $stmt = $conn->prepare(
                "INSERT INTO incidents
                 (user_id, category_id, title, description, location, priority, attachment)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                'iisssss',
                $uid,
                $cat_id,
                $title,
                $description,
                $location,
                $priority,
                $attachment
            );

            if ($stmt->execute()) {
                $new_id = (int) $stmt->insert_id;

                $prediction = analyze_incident_aios($conn, $new_id);

                if ($prediction && save_incident_prediction($conn, $prediction)) {
                    allocate_incident_duty($conn, $prediction);

                    audit_log(
                        $conn,
                        'NEURAL_INCIDENT_AUTO_ANALYZED',
                        'incident',
                        $new_id,
                        'System automatically analyzed and routed student-submitted incident #' . $new_id
                    );
                }

                $backend_analysis = backend_bridge_analyze_incident(
                    $title,
                    $description,
                    (string) $cat_id,
                    $location
                );

                if ($backend_analysis && backend_bridge_save_advisory($conn, $new_id, $backend_analysis)) {
                    audit_log(
                        $conn,
                        'FASTAPI_BACKEND_BRIDGE_ANALYZED',
                        'incident',
                        $new_id,
                        'Optional backend service analyzed student-submitted incident #' . $new_id
                    );
                }

                create_notification(
                    $conn,
                    $uid,
                    $new_id,
                    "Your incident \"$title\" has been received and is under review."
                );

                $stmt->close();

                header('Location: ' . SITE_URL . '/student/view_incident.php?id=' . $new_id . '&submitted=1');
                exit;
            }

            $error = 'Could not save your report. Please try again.';
            $stmt->close();
        }
    }
}

include __DIR__ . '/../includes/header_student.php';
?>

<?php if ($error): ?>
<div class="form-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
  <div class="detail-grid">

    <div class="panel panel-body">
      <div class="field">
        <label for="title">Incident Title <span style="color:var(--status-open)">*</span></label>
        <input type="text" id="title" name="title"
               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
               placeholder="e.g. WiFi unavailable in LT4" required>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="category_id">Category <span style="color:var(--status-open)">*</span></label>
          <select id="category_id" name="category_id" required>
            <option value="">Select category…</option>
            <?php if ($cats): ?>
              <?php $cats->data_seek(0); while ($cat = $cats->fetch_assoc()): ?>
              <option value="<?= $cat['category_id'] ?>"
                <?= (($_POST['category_id'] ?? '') == $cat['category_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['category_name']) ?>
              </option>
              <?php endwhile; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="field">
          <label for="priority">Priority <span style="color:var(--status-open)">*</span></label>
          <select id="priority" name="priority" required>
            <option value="">Select priority…</option>
            <option value="Low" <?= (($_POST['priority'] ?? '') === 'Low') ? 'selected' : '' ?>>Low</option>
            <option value="Medium" <?= (($_POST['priority'] ?? '') === 'Medium') ? 'selected' : '' ?>>Medium</option>
            <option value="High" <?= (($_POST['priority'] ?? '') === 'High') ? 'selected' : '' ?>>High</option>
          </select>
        </div>
      </div>

      <div class="field">
        <label for="location">Location <span style="color:var(--status-open)">*</span></label>
        <input type="text" id="location" name="location"
               value="<?= htmlspecialchars($_POST['location'] ?? '') ?>"
               placeholder="e.g. LT4, Block C, Lab 2" required>
      </div>

      <div class="field">
        <label for="description">Description <span style="color:var(--status-open)">*</span></label>
        <textarea id="description" name="description" rows="6"
                  placeholder="Describe what happened, when it started, and who is affected…" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      </div>

      <div class="field">
        <label>Attachment <span class="hint">(optional — JPG, PNG, PDF, max <?= UPLOAD_MAX_MB ?>MB)</span></label>
        <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.gif" style="display:none;">
        <div class="upload-zone" id="upload-zone">
          <span style="font-size:24px; display:block; margin-bottom:8px;">📎</span>
          <span class="upload-label">Click to attach a screenshot or document</span>
        </div>
      </div>

      <button type="submit" class="btn btn-primary mt-16">Submit Report</button>
    </div>

    <div class="tip-box">
      <h4>Tips for a faster resolution</h4>
      <ul>
        <li>Be specific about the location — building, room, floor.</li>
        <li>Attach a screenshot where possible.</li>
        <li>Set priority honestly. High = blocking work for many people.</li>
        <li>Network and Security issues are routed to IT within 1 business day.</li>
        <li>You can add comments to your report after submission.</li>
      </ul>
    </div>

  </div>
</form>

<?php render_campus_assistant_widget(); ?>
<?php include __DIR__ . '/../includes/footer_student.php'; ?>
