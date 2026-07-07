<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit_helpers.php';
require_once __DIR__ . '/../includes/local_ai_bridge.php';

$page_title = 'AI Reasoning';

function ai_safe(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$success = '';
$error = '';
$ai_health = local_ai_health();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'generate_reasoning';

    if ($action === 'warm_model') {
        $start = microtime(true);
        $ok = local_ai_warm_model();
        $elapsed = round(microtime(true) - $start, 2);

        if ($ok) {
            $success = 'Local AI model warmed successfully in ' . $elapsed . ' seconds.';
        } else {
            $error = 'Local AI warmup failed. Confirm Ollama is running and the model exists.';
        }

        $ai_health = local_ai_health();
    } else {
        $incident_id = (int) ($_POST['incident_id'] ?? 0);
        $reasoning_type = $_POST['reasoning_type'] ?? 'Executive Brief';

        $allowed_types = ['Summary', 'Action Plan', 'Root Cause', 'Executive Brief'];

        if ($incident_id <= 0) {
            $error = 'Invalid incident selected.';
        } elseif (!in_array($reasoning_type, $allowed_types, true)) {
            $error = 'Invalid reasoning type.';
        } else {
            $start = microtime(true);
            $ok = generate_ai_reasoning_for_incident($conn, $incident_id, $reasoning_type);
            $elapsed = round(microtime(true) - $start, 2);

            if ($ok) {
                audit_log(
                    $conn,
                    'LOCAL_AI_REASONING_GENERATED',
                    'incident',
                    $incident_id,
                    'Generated local AI reasoning note for incident #' . $incident_id . ' using ' . local_ai_model_name()
                );

                $success = 'Local AI reasoning generated successfully in ' . $elapsed . ' seconds.';
            } else {
                $error = 'Local AI did not respond. Keep deterministic AIOS active, then check Ollama runtime/model.';
            }
        }
    }
}

$incidents = $conn->query(
    "SELECT i.incident_id, i.title, i.status, i.priority, p.recommended_route
     FROM incidents i
     LEFT JOIN incident_predictions p ON i.incident_id = p.incident_id
     ORDER BY i.created_at DESC
     LIMIT 100"
);

$notes = $conn->query(
    "SELECT arn.*, i.title, i.status, i.priority,
            p.recommended_route, p.predicted_risk_score
     FROM ai_reasoning_notes arn
     JOIN incidents i ON arn.incident_id = i.incident_id
     LEFT JOIN incident_predictions p ON arn.prediction_id = p.prediction_id
     ORDER BY arn.created_at DESC
     LIMIT 30"
);

include __DIR__ . '/../includes/header_admin.php';
?>

<?php if ($error): ?>
  <div class="form-error mb-16"><?= ai_safe($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
  <div class="form-success mb-16"><?= ai_safe($success) ?></div>
<?php endif; ?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="s-label">Ollama Status</div>
    <div class="s-value"><?= $ai_health['available'] ? 'Online' : 'Offline' ?></div>
  </div>

  <div class="stat-card c-progress">
    <div class="s-label">Selected Model</div>
    <div class="s-value" style="font-size:18px;"><?= ai_safe($ai_health['selected_model']) ?></div>
  </div>

  <div class="stat-card c-resolved">
    <div class="s-label">Models Found</div>
    <div class="s-value"><?= count($ai_health['models']) ?></div>
  </div>

  <div class="stat-card">
    <div class="s-label">Token Cost</div>
    <div class="s-value">0</div>
  </div>
</div>

<div class="detail-grid">
  <div class="panel panel-body">
    <h3 class="mb-16">Runtime Control</h3>

    <p class="text-muted">
      Warm the local model before presenting. First call loads the model; later calls are faster.
    </p>

    <form method="post" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerText='Warming Local AI...';">
      <input type="hidden" name="action" value="warm_model">

      <button class="btn btn-outline btn-block" type="submit">
        Warm Local AI Model
      </button>
    </form>
  </div>

  <div class="panel panel-body">
    <h3 class="mb-16">Local AI Runtime</h3>

    <p><strong>Base URL:</strong> <?= ai_safe($ai_health['base_url']) ?></p>
    <p><strong>Generate URL:</strong> <?= ai_safe($ai_health['generate_url']) ?></p>
    <p><strong>Available Models:</strong> <?= ai_safe(implode(', ', $ai_health['models'])) ?></p>
  </div>
</div>

<div class="panel mb-24">
  <div class="panel-head">
    <h3>Local AI Reasoning Generator</h3>
  </div>

  <div class="panel-body">
    <p class="text-muted">
      The deterministic AIOS neural engine handles risk, route, duties, classrooms, dining, and academic escalation instantly.
      Ollama adds a polished executive brief with zero paid tokens.
    </p>

    <form method="post" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerText='Generating Local AI Brief...';">
      <input type="hidden" name="action" value="generate_reasoning">

      <div class="field">
        <label>Incident</label>
        <select name="incident_id" required>
          <option value="">Choose incident...</option>
          <?php if ($incidents): ?>
            <?php while ($incident = $incidents->fetch_assoc()): ?>
              <option value="<?= (int) $incident['incident_id'] ?>">
                #<?= (int) $incident['incident_id'] ?>
                — <?= ai_safe($incident['title']) ?>
                — <?= ai_safe($incident['recommended_route'] ?: 'No route yet') ?>
              </option>
            <?php endwhile; ?>
          <?php endif; ?>
        </select>
      </div>

      <div class="field">
        <label>Reasoning Type</label>
        <select name="reasoning_type" required>
          <option value="Executive Brief">Executive Brief</option>
          <option value="Action Plan">Action Plan</option>
          <option value="Root Cause">Root Cause</option>
          <option value="Summary">Summary</option>
        </select>
      </div>

      <button class="btn btn-primary" type="submit">
        Generate Local AI Reasoning
      </button>
    </form>
  </div>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>AI Reasoning Notes</h3>
  </div>

  <?php if (!$notes || $notes->num_rows === 0): ?>
    <div class="empty-state">
      <span class="empty-icon">🤖</span>
      <p>No AI reasoning notes yet.</p>
    </div>
  <?php else: ?>
    <?php while ($note = $notes->fetch_assoc()): ?>
      <div class="intelligence-card">
        <div class="flex-between mb-16">
          <div>
            <h4>#<?= (int) $note['incident_id'] ?> — <?= ai_safe($note['title']) ?></h4>
            <p class="text-muted text-sm">
              <?= ai_safe($note['reasoning_type']) ?>
              · Model: <?= ai_safe($note['model_name']) ?>
              · Route: <?= ai_safe($note['recommended_route'] ?: 'No route') ?>
              · <?= ai_safe($note['created_at']) ?>
            </p>
          </div>

          <span class="badge b-progress"><?= ai_safe($note['priority']) ?></span>
        </div>

        <div class="learning-signal" style="white-space: pre-wrap; line-height: 1.6;">
<?= ai_safe($note['response_text']) ?>
        </div>

        <p class="mt-16">
          <a class="btn btn-outline btn-sm"
             href="<?= SITE_URL ?>/admin/view_incident.php?id=<?= (int) $note['incident_id'] ?>">
            Open Incident
          </a>
        </p>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
