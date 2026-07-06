<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('technician');
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Technician Dashboard';
$uid = (int) $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT i.*, c.category_name, u.fullname AS reporter_name
     FROM incidents i
     JOIN categories c ON i.category_id = c.category_id
     JOIN users u ON i.user_id = u.user_id
     WHERE i.assigned_to = ?
     ORDER BY i.updated_at DESC, i.created_at DESC"
);

$stmt->bind_param('i', $uid);
$stmt->execute();
$assigned = $stmt->get_result();

$total_assigned = $assigned->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(SITE_NAME) ?> — Technician</title>
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
      <a href="<?= SITE_URL ?>/technician/dashboard.php" class="active">
        <span class="nav-icon">🛠️</span> Technician Dashboard
      </a>
      <a href="<?= SITE_URL ?>/logout.php">
        <span class="nav-icon">🚪</span> Logout
      </a>
    </nav>

    <div class="sidebar-footer">
      Signed in as<br>
      <strong><?= htmlspecialchars($_SESSION['fullname'] ?? 'Technician') ?></strong>
    </div>
  </aside>

  <main class="main">
    <header class="topstrip">
      <div>
        <h1>Technician Dashboard</h1>
        <p class="text-muted">Assigned incident workload and response queue</p>
      </div>
      <span class="user-chip">
        <span class="avatar">TC</span>
        <?= htmlspecialchars($_SESSION['fullname'] ?? 'Technician') ?>
      </span>
    </header>

    <section class="content">
      <div class="stat-grid">
        <div class="stat-card">
          <div class="s-label">Assigned Incidents</div>
          <div class="s-value"><?= (int) $total_assigned ?></div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head">
          <h3>My Assigned Incidents</h3>
        </div>

        <?php if ($assigned->num_rows === 0): ?>
          <div class="empty-state">
            <span class="empty-icon">🛠️</span>
            <p>No incidents assigned to you yet.</p>
          </div>
        <?php else: ?>
          <?php while ($inc = $assigned->fetch_assoc()): ?>
            <a class="incident-row clickable"
               href="<?= SITE_URL ?>/technician/view_incident.php?id=<?= (int) $inc['incident_id'] ?>">
              <span class="inc-id">#<?= (int) $inc['incident_id'] ?></span>
              <div class="inc-info">
                <h4><?= htmlspecialchars($inc['title']) ?></h4>
                <div class="inc-meta">
                  <span><?= htmlspecialchars($inc['category_name']) ?></span>
                  <span>·</span>
                  <span><?= htmlspecialchars($inc['reporter_name']) ?></span>
                  <span>·</span>
                  <span><?= htmlspecialchars($inc['location']) ?></span>
                </div>
              </div>
              <div class="inc-badges">
                <span class="badge"><?= htmlspecialchars($inc['priority']) ?></span>
                <span class="badge"><?= htmlspecialchars($inc['status']) ?></span>
              </div>
            </a>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>
    </section>
  </main>
</div>
</body>
</html>
