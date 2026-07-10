<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Academic Command';

function academic_safe(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$summary = $conn->query(
    "SELECT
        COUNT(*) AS total_relocations
     FROM academic_relocation_recommendations"
)->fetch_assoc();

$room_summary = $conn->query(
    "SELECT
        SUM(CASE WHEN latest.status = 'Available' OR latest.status IS NULL THEN 1 ELSE 0 END) AS available_rooms,
        SUM(CASE WHEN latest.status = 'Occupied' THEN 1 ELSE 0 END) AS occupied_rooms,
        SUM(CASE WHEN latest.status = 'Reserved' THEN 1 ELSE 0 END) AS reserved_rooms,
        SUM(CASE WHEN latest.status = 'Maintenance' THEN 1 ELSE 0 END) AS maintenance_rooms
     FROM classrooms c
     LEFT JOIN (
        SELECT cs1.room_id, cs1.status
        FROM classroom_status cs1
        INNER JOIN (
            SELECT room_id, MAX(status_id) AS latest_status_id
            FROM classroom_status
            GROUP BY room_id
        ) x ON cs1.status_id = x.latest_status_id
     ) latest ON c.room_id = latest.room_id
     WHERE c.is_active = 1"
)->fetch_assoc();

$rooms = $conn->query(
    "SELECT c.room_id, c.room_code, c.building, c.room_name, c.capacity, c.room_type,
            COALESCE(latest.status, 'Available') AS current_status,
            COALESCE(latest.status_note, 'No blocking status recorded.') AS status_note
     FROM classrooms c
     LEFT JOIN (
        SELECT cs1.room_id, cs1.status, cs1.status_note
        FROM classroom_status cs1
        INNER JOIN (
            SELECT room_id, MAX(status_id) AS latest_status_id
            FROM classroom_status
            GROUP BY room_id
        ) x ON cs1.status_id = x.latest_status_id
     ) latest ON c.room_id = latest.room_id
     WHERE c.is_active = 1
     ORDER BY FIELD(COALESCE(latest.status, 'Available'), 'Available','Reserved','Occupied','Maintenance'),
              c.building, c.room_code"
);

$relocations = $conn->query(
    "SELECT arr.recommendation_id, arr.incident_id, arr.from_room_text,
            arr.reason, arr.recommendation_message, arr.created_at,
            c.room_code, c.room_name, c.building, c.capacity,
            i.title, i.status, i.priority
     FROM academic_relocation_recommendations arr
     JOIN classrooms c ON arr.recommended_room_id = c.room_id
     JOIN incidents i ON arr.incident_id = i.incident_id
     ORDER BY arr.created_at DESC
     LIMIT 40"
);

$academic_duties = $conn->query(
    "SELECT id.duty_id, id.incident_id, id.duty_status, id.updated_at,
            d.code, d.name AS department,
            i.title, i.priority, i.status AS incident_status, i.location,
            u.email AS assigned_email,
            p.recommended_route, p.predicted_risk_score, p.confidence_score
     FROM incident_duties id
     JOIN departments d ON id.department_id = d.department_id
     JOIN incidents i ON id.incident_id = i.incident_id
     LEFT JOIN users u ON id.assigned_user_id = u.user_id
     LEFT JOIN incident_predictions p ON id.prediction_id = p.prediction_id
     WHERE d.code IN ('ACADEMIC','REGISTRAR','LECTURER')
     ORDER BY id.updated_at DESC
     LIMIT 50"
);

include __DIR__ . '/../includes/header_admin.php';
?>

<div class="stat-grid">
  <div class="stat-card c-resolved">
    <div class="s-label">Available Rooms</div>
    <div class="s-value"><?= (int) ($room_summary['available_rooms'] ?? 0) ?></div>
  </div>

  <div class="stat-card c-progress">
    <div class="s-label">Occupied Rooms</div>
    <div class="s-value"><?= (int) ($room_summary['occupied_rooms'] ?? 0) ?></div>
  </div>

  <div class="stat-card">
    <div class="s-label">Reserved Rooms</div>
    <div class="s-value"><?= (int) ($room_summary['reserved_rooms'] ?? 0) ?></div>
  </div>

  <div class="stat-card c-critical">
    <div class="s-label">Relocation Recommendations</div>
    <div class="s-value"><?= (int) ($summary['total_relocations'] ?? 0) ?></div>
  </div>
</div>

<div class="panel mb-24">
  <div class="panel-head">
    <h3>Classroom Availability</h3>
  </div>

  <?php if (!$rooms || $rooms->num_rows === 0): ?>
    <div class="empty-state">
      <p>No classrooms configured.</p>
    </div>
  <?php else: ?>
    <?php while ($room = $rooms->fetch_assoc()): ?>
      <div class="incident-row">
        <span class="inc-id"><?= academic_safe($room['room_code']) ?></span>

        <div class="inc-info">
          <h4><?= academic_safe($room['room_name']) ?></h4>
          <div class="inc-meta">
            <span><?= academic_safe($room['building']) ?></span>
            <span>·</span>
            <span><?= academic_safe($room['room_type']) ?></span>
            <span>·</span>
            <span>Capacity <?= (int) $room['capacity'] ?></span>
          </div>
          <p class="text-muted text-sm"><?= academic_safe($room['status_note']) ?></p>
        </div>

        <div class="inc-badges">
          <span class="badge <?= $room['current_status'] === 'Available' ? 'b-low' : ($room['current_status'] === 'Occupied' ? 'b-high' : 'b-medium') ?>">
            <?= academic_safe($room['current_status']) ?>
          </span>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<div class="panel mb-24">
  <div class="panel-head">
    <h3>AIOS Classroom Relocation Recommendations</h3>
  </div>

  <?php if (!$relocations || $relocations->num_rows === 0): ?>
    <div class="empty-state">
      <span class="empty-icon">AC</span>
      <p>No classroom relocation recommendations yet.</p>
    </div>
  <?php else: ?>
    <?php while ($rec = $relocations->fetch_assoc()): ?>
      <div class="intelligence-card">
        <div class="flex-between mb-16">
          <div>
            <h4>#<?= (int) $rec['incident_id'] ?> — <?= academic_safe($rec['title']) ?></h4>
            <p class="text-muted text-sm">
              From: <?= academic_safe($rec['from_room_text']) ?>
              · Recommended: <?= academic_safe($rec['room_code']) ?>
              · Capacity <?= (int) $rec['capacity'] ?>
            </p>
          </div>

          <span class="badge b-progress"><?= academic_safe($rec['priority']) ?></span>
        </div>

        <div class="learning-signal">
          <strong>Instruction:</strong>
          <?= academic_safe($rec['recommendation_message']) ?>
        </div>

        <div class="learning-signal">
          <strong>Reason:</strong>
          <?= academic_safe($rec['reason']) ?>
        </div>

        <p class="mt-16">
          <a class="btn btn-outline btn-sm"
             href="<?= SITE_URL ?>/admin/view_incident.php?id=<?= (int) $rec['incident_id'] ?>">
            Open Incident
          </a>
        </p>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>Academic Duties</h3>
  </div>

  <?php if (!$academic_duties || $academic_duties->num_rows === 0): ?>
    <div class="empty-state">
      <p>No academic duties yet.</p>
    </div>
  <?php else: ?>
    <?php while ($duty = $academic_duties->fetch_assoc()): ?>
      <div class="incident-row">
        <span class="inc-id">#<?= (int) $duty['incident_id'] ?></span>

        <div class="inc-info">
          <h4><?= academic_safe($duty['title']) ?></h4>
          <div class="inc-meta">
            <span><?= academic_safe($duty['department']) ?></span>
            <span>·</span>
            <span><?= academic_safe($duty['assigned_email'] ?: 'Unassigned') ?></span>
            <span>·</span>
            <span><?= academic_safe($duty['recommended_route']) ?></span>
          </div>
        </div>

        <div class="inc-badges">
          <span class="badge"><?= academic_safe($duty['duty_status']) ?></span>
          <a class="btn btn-outline btn-sm"
             href="<?= SITE_URL ?>/admin/view_incident.php?id=<?= (int) $duty['incident_id'] ?>">
            Open
          </a>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
