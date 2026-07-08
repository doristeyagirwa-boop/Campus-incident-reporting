<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit_helpers.php';
require_once __DIR__ . '/../includes/intelligence_engine.php';
require_once __DIR__ . '/../includes/department_allocator.php';
require_once __DIR__ . '/../includes/dining_alerts.php';
require_once __DIR__ . '/../includes/academic_space_engine.php';
require_once __DIR__ . '/../includes/phoenix_engine.php';

$page_title = 'Neural Intelligence';

function neural_safe(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'analyze_one') {
        $incident_id = (int) ($_POST['incident_id'] ?? 0);

        if ($incident_id <= 0) {
            $error = 'Invalid incident selected.';
        } else {
            $prediction = analyze_incident_aios($conn, $incident_id);

            if (!$prediction) {
                $error = 'Could not analyze incident.';
            } elseif (save_incident_prediction($conn, $prediction)) {
                allocate_incident_duty($conn, $prediction);

                audit_log(
                    $conn,
                    'NEURAL_INCIDENT_ANALYZED',
                    'incident',
                    $incident_id,
                    'AIOS neuro-symbolic analysis generated for incident #' . $incident_id
                );

                $success = 'Incident analyzed successfully.';
            } else {
                $error = 'Could not save prediction.';
            }
        }
    }

    if ($action === 'analyze_open') {
        $result = $conn->query(
            "SELECT incident_id
             FROM incidents
             WHERE status IN ('Open', 'Assigned', 'In Progress')
             ORDER BY created_at DESC"
        );

        $count = 0;

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $prediction = analyze_incident_aios($conn, (int) $row['incident_id']);

                if ($prediction && save_incident_prediction($conn, $prediction)) {
                    allocate_incident_duty($conn, $prediction);
                    $count++;
                }
            }
        }

        audit_log(
            $conn,
            'NEURAL_OPEN_INCIDENTS_ANALYZED',
            'incident_prediction',
            null,
            'AIOS neuro-symbolic analysis generated for ' . $count . ' active incidents'
        );

        $success = $count . ' active incidents analyzed.';
    }
}

$incidents = $conn->query(
    "SELECT i.incident_id, i.title, i.status, i.priority, i.created_at,
            c.category_name,
            u.fullname AS reporter
     FROM incidents i
     JOIN categories c ON i.category_id = c.category_id
     JOIN users u ON i.user_id = u.user_id
     ORDER BY i.created_at DESC"
);

$predictions = $conn->query(
    "SELECT p.*, i.title, i.status, i.priority, c.category_name
     FROM incident_predictions p
     JOIN incidents i ON p.incident_id = i.incident_id
     JOIN categories c ON i.category_id = c.category_id
     ORDER BY p.created_at DESC
     LIMIT 30"
);

$summary = $conn->query(
    "SELECT
        COUNT(*) AS total_predictions,
        AVG(predicted_risk_score) AS avg_risk,
        AVG(confidence_score) AS avg_confidence,
        SUM(CASE WHEN predicted_priority = 'High' THEN 1 ELSE 0 END) AS high_predictions
     FROM incident_predictions"
)->fetch_assoc();

include __DIR__ . '/../includes/header_admin.php';
?>

<?php if ($error): ?>
  <div class="form-error mb-16"><?= neural_safe($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
  <div class="form-success mb-16"><?= neural_safe($success) ?></div>
<?php endif; ?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="s-label">Predictions</div>
    <div class="s-value"><?= (int) ($summary['total_predictions'] ?? 0) ?></div>
  </div>

  <div class="stat-card c-progress">
    <div class="s-label">Average Risk</div>
    <div class="s-value"><?= number_format((float) ($summary['avg_risk'] ?? 0), 1) ?></div>
  </div>

  <div class="stat-card c-resolved">
    <div class="s-label">Average Confidence</div>
    <div class="s-value"><?= number_format((float) ($summary['avg_confidence'] ?? 0), 1) ?>%</div>
  </div>

  <div class="stat-card c-critical">
    <div class="s-label">High Priority Predictions</div>
    <div class="s-value"><?= (int) ($summary['high_predictions'] ?? 0) ?></div>
  </div>
</div>

<div class="detail-grid">
  <div class="panel panel-body">
    <h3 class="mb-16">Analyze Incident</h3>

    <form method="post">
      <input type="hidden" name="action" value="analyze_one">

      <div class="field">
        <label>Select Incident</label>
        <select name="incident_id" required>
          <option value="">Choose incident...</option>
          <?php if ($incidents): ?>
            <?php while ($incident = $incidents->fetch_assoc()): ?>
              <option value="<?= (int) $incident['incident_id'] ?>">
                #<?= (int) $incident['incident_id'] ?>
                — <?= neural_safe($incident['title']) ?>
                — <?= neural_safe($incident['status']) ?>
                — <?= neural_safe($incident['priority']) ?>
              </option>
            <?php endwhile; ?>
          <?php endif; ?>
        </select>
      </div>

      <button class="btn btn-primary btn-block" type="submit">
        Run Neural Analysis
      </button>
    </form>
  </div>

  <div class="panel panel-body">
    <h3 class="mb-16">Analyze Active Incidents</h3>

    <p class="text-muted">
      Runs the AIOS neuro-symbolic engine on all Open, Assigned, and In Progress incidents.
    </p>

    <form method="post">
      <input type="hidden" name="action" value="analyze_open">

      <button class="btn btn-outline btn-block" type="submit">
        Analyze All Active Incidents
      </button>
    </form>
  </div>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>Prediction Feed</h3>
  </div>

  <?php if (!$predictions || $predictions->num_rows === 0): ?>
    <div class="empty-state">
      <span class="empty-icon">🧠</span>
      <p>No neural intelligence predictions yet.</p>
    </div>
  <?php else: ?>
    <?php while ($prediction = $predictions->fetch_assoc()): ?>
      <div class="intelligence-card">
        <div class="flex-between mb-16">
          <div>
            <h4>#<?= (int) $prediction['incident_id'] ?> — <?= neural_safe($prediction['title']) ?></h4>
            <p class="text-muted text-sm">
              <?= neural_safe($prediction['category_name']) ?>
              · Current: <?= neural_safe($prediction['status']) ?>
              · Current Priority: <?= neural_safe($prediction['priority']) ?>
              · <?= neural_safe($prediction['created_at']) ?>
            </p>
          </div>

          <span class="badge <?= $prediction['predicted_priority'] === 'High' ? 'b-high' : ($prediction['predicted_priority'] === 'Medium' ? 'b-medium' : 'b-low') ?>">
            Predicted <?= neural_safe($prediction['predicted_priority']) ?>
          </span>
        </div>

        <div class="intel-grid">
          <div>
            <strong>Risk Score</strong>
            <p><?= number_format((float) $prediction['predicted_risk_score'], 2) ?>/100</p>
          </div>

          <div>
            <strong>Route</strong>
            <p><?= neural_safe($prediction['recommended_route']) ?></p>
          </div>

          <div>
            <strong>Confidence</strong>
            <p><?= number_format((float) $prediction['confidence_score'], 2) ?>%</p>
          </div>
        </div>

        <div class="learning-signal">
          <strong>Recommended Action:</strong>
          <?= neural_safe($prediction['recommended_action']) ?>
        </div>

        <?php if (!empty($prediction['suggested_root_cause'])): ?>
          <div class="learning-signal">
            <strong>Suggested Root Cause:</strong>
            <?= neural_safe($prediction['suggested_root_cause']) ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($prediction['similar_incident_id'])): ?>
          <div class="learning-signal">
            <strong>Similar Case:</strong>
            Incident #<?= (int) $prediction['similar_incident_id'] ?>
          </div>
        <?php endif; ?>

        <div class="learning-signal">
          <strong>Explanation:</strong>
          <?= neural_safe($prediction['explanation']) ?>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
