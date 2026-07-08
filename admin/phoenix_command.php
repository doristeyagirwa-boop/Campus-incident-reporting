<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit_helpers.php';
require_once __DIR__ . '/../includes/phoenix_engine.php';

$page_title = 'Phoenix AI Command';

function phoenix_safe(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'run_sweep') {
        $count = 0;

        $result = $conn->query(
            "SELECT incident_id
             FROM incidents
             WHERE status IN ('Open','Assigned','In Progress')
             ORDER BY incident_id ASC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $count += phoenix_run_incident_intelligence(
                    $conn,
                    (int) $row['incident_id'],
                    'phoenix_command_sweep'
                );
            }
        }

        $success = 'Phoenix AI sweep completed. Intelligence records touched: ' . $count;
    }
}

$domain_summary = $conn->query(
    "SELECT pd.code, pd.name,
            COUNT(pis.signal_id) AS signal_count,
            MAX(pis.signal_score) AS max_score
     FROM phoenix_domains pd
     LEFT JOIN phoenix_intelligence_signals pis ON pd.domain_id = pis.domain_id
     WHERE pd.is_active = 1
     GROUP BY pd.domain_id, pd.code, pd.name
     ORDER BY pd.domain_id"
);

$signals = $conn->query(
    "SELECT pis.signal_id, pis.incident_id, pis.signal_type, pis.signal_score,
            pis.summary, pis.recommended_action, pis.visibility, pis.created_at,
            pd.name AS domain_name,
            i.title, i.priority, i.status
     FROM phoenix_intelligence_signals pis
     JOIN phoenix_domains pd ON pis.domain_id = pd.domain_id
     JOIN incidents i ON pis.incident_id = i.incident_id
     ORDER BY pis.created_at DESC, pis.signal_score DESC
     LIMIT 60"
);

$advisories = $conn->query(
    "SELECT ppa.advisory_id, ppa.incident_id, ppa.advisory_type, ppa.message,
            ppa.is_read, ppa.created_at,
            pd.name AS domain_name,
            u.fullname, u.email, u.role,
            i.title
     FROM phoenix_private_advisories ppa
     JOIN phoenix_domains pd ON ppa.domain_id = pd.domain_id
     JOIN users u ON ppa.target_user_id = u.user_id
     JOIN incidents i ON ppa.incident_id = i.incident_id
     ORDER BY ppa.created_at DESC
     LIMIT 60"
);

include __DIR__ . '/../includes/header_admin.php';
?>

<?php if ($error): ?>
  <div class="form-error mb-16"><?= phoenix_safe($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
  <div class="form-success mb-16"><?= phoenix_safe($success) ?></div>
<?php endif; ?>

<div class="panel mb-24">
  <div class="panel-head">
    <h3>Phoenix AI Cognitive Sweep</h3>
  </div>

  <div class="panel-body">
    <p class="text-muted">
      Phoenix AI reads active incidents, classifies them across five institutional domains,
      creates private staff advisories, and records cognitive intelligence signals.
    </p>

    <form method="post" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerText='Phoenix AI Running...';">
      <input type="hidden" name="action" value="run_sweep">
      <button class="btn btn-primary" type="submit">
        Run Phoenix AI Sweep
      </button>
    </form>
  </div>
</div>

<div class="stat-grid">
  <?php if ($domain_summary): ?>
    <?php while ($domain = $domain_summary->fetch_assoc()): ?>
      <div class="stat-card">
        <div class="s-label"><?= phoenix_safe($domain['name']) ?></div>
        <div class="s-value"><?= (int) $domain['signal_count'] ?></div>
        <p class="text-muted text-sm">
          Max score: <?= number_format((float) ($domain['max_score'] ?? 0), 1) ?>
        </p>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<div class="panel mb-24">
  <div class="panel-head">
    <h3>Phoenix Intelligence Signals</h3>
  </div>

  <?php if (!$signals || $signals->num_rows === 0): ?>
    <div class="empty-state">
      <span class="empty-icon">🔥</span>
      <p>No Phoenix intelligence signals yet.</p>
    </div>
  <?php else: ?>
    <?php while ($signal = $signals->fetch_assoc()): ?>
      <div class="intelligence-card">
        <div class="flex-between mb-16">
          <div>
            <h4>#<?= (int) $signal['incident_id'] ?> — <?= phoenix_safe($signal['title']) ?></h4>
            <p class="text-muted text-sm">
              <?= phoenix_safe($signal['domain_name']) ?>
              · <?= phoenix_safe($signal['signal_type']) ?>
              · Visibility: <?= phoenix_safe($signal['visibility']) ?>
              · <?= phoenix_safe($signal['created_at']) ?>
            </p>
          </div>

          <span class="badge b-progress">
            Score <?= number_format((float) $signal['signal_score'], 1) ?>
          </span>
        </div>

        <div class="learning-signal">
          <?= phoenix_safe($signal['summary']) ?>
        </div>

        <div class="learning-signal">
          <strong>Recommended Action:</strong>
          <?= phoenix_safe($signal['recommended_action']) ?>
        </div>

        <p class="mt-16">
          <a class="btn btn-outline btn-sm"
             href="<?= SITE_URL ?>/admin/view_incident.php?id=<?= (int) $signal['incident_id'] ?>">
            Open Incident
          </a>
        </p>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>Private Institutional Advisories</h3>
  </div>

  <?php if (!$advisories || $advisories->num_rows === 0): ?>
    <div class="empty-state">
      <p>No private Phoenix advisories yet.</p>
    </div>
  <?php else: ?>
    <?php while ($advisory = $advisories->fetch_assoc()): ?>
      <div class="incident-row">
        <span class="inc-id">#<?= (int) $advisory['incident_id'] ?></span>

        <div class="inc-info">
          <h4><?= phoenix_safe($advisory['title']) ?></h4>
          <div class="inc-meta">
            <span><?= phoenix_safe($advisory['domain_name']) ?></span>
            <span>·</span>
            <span><?= phoenix_safe($advisory['advisory_type']) ?></span>
            <span>·</span>
            <span><?= phoenix_safe($advisory['email']) ?></span>
          </div>
          <p class="text-muted text-sm"><?= phoenix_safe($advisory['message']) ?></p>
        </div>

        <div class="inc-badges">
          <span class="badge"><?= $advisory['is_read'] ? 'Read' : 'Unread' ?></span>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
