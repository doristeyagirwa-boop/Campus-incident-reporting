<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Institution Accounts';

function inst_safe(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$accounts = $conn->query(
    "SELECT u.user_id, u.fullname, u.email, u.role, u.created_at,
            GROUP_CONCAT(CONCAT(d.code, ': ', dm.role_title) ORDER BY d.code SEPARATOR ' | ') AS departments
     FROM users u
     LEFT JOIN department_members dm ON u.user_id = dm.user_id
     LEFT JOIN departments d ON dm.department_id = d.department_id
     WHERE u.role IN (
        'admin','technician','registrar','dean','lecturer','finance',
        'kitchen_manager','dining_manager','security','maintenance'
     )
     GROUP BY u.user_id, u.fullname, u.email, u.role, u.created_at
     ORDER BY FIELD(u.role,
        'admin','dean','registrar','lecturer','finance','technician',
        'security','maintenance','kitchen_manager','dining_manager'
     ), u.fullname"
);

$departments = $conn->query(
    "SELECT d.code, d.name, d.domain, d.severity_level,
            GROUP_CONCAT(CONCAT(u.fullname, ' (', dm.role_title, ')') ORDER BY dm.is_primary DESC, u.fullname SEPARATOR ', ') AS members
     FROM departments d
     LEFT JOIN department_members dm ON d.department_id = dm.department_id
     LEFT JOIN users u ON dm.user_id = u.user_id
     GROUP BY d.department_id, d.code, d.name, d.domain, d.severity_level
     ORDER BY FIELD(d.severity_level, 'Critical','Sensitive','Normal'), d.name"
);

include __DIR__ . '/../includes/header_admin.php';
?>

<div class="panel mb-24">
  <div class="panel-head">
    <h3>Institutional Accounts</h3>
  </div>

  <?php if (!$accounts || $accounts->num_rows === 0): ?>
    <div class="empty-state">
      <p>No institutional accounts found.</p>
    </div>
  <?php else: ?>
    <?php while ($account = $accounts->fetch_assoc()): ?>
      <div class="user-admin-row">
        <div class="user-admin-main">
          <div class="avatar"><?= strtoupper(substr($account['fullname'], 0, 2)) ?></div>
          <div>
            <h4><?= inst_safe($account['fullname']) ?></h4>
            <p class="text-muted text-sm">
              <?= inst_safe($account['email']) ?>
              · <?= inst_safe($account['role']) ?>
            </p>
            <p class="text-muted text-sm">
              <?= inst_safe($account['departments'] ?: 'No department mapping') ?>
            </p>
          </div>
        </div>

        <div class="inc-badges">
          <span class="badge"><?= inst_safe($account['role']) ?></span>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>Department Responsibility Map</h3>
  </div>

  <?php if (!$departments || $departments->num_rows === 0): ?>
    <div class="empty-state">
      <p>No departments configured.</p>
    </div>
  <?php else: ?>
    <?php while ($department = $departments->fetch_assoc()): ?>
      <div class="incident-row">
        <span class="inc-id"><?= inst_safe($department['code']) ?></span>

        <div class="inc-info">
          <h4><?= inst_safe($department['name']) ?></h4>
          <div class="inc-meta">
            <span><?= inst_safe($department['severity_level']) ?></span>
            <span>·</span>
            <span><?= inst_safe($department['domain']) ?></span>
          </div>
          <p class="text-muted text-sm">
            <?= inst_safe($department['members'] ?: 'No members assigned') ?>
          </p>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
