<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Admin Dashboard';

// System-wide stats
$stat_q = $conn->query("
    SELECT
      COUNT(*) AS total,
      SUM(status = 'Open') AS open,
      SUM(status = 'In Progress' OR status = 'Assigned') AS progress,
      SUM(status = 'Resolved') AS resolved,
      SUM(priority = 'High' AND status NOT IN ('Resolved','Closed')) AS critical
    FROM incidents
");
$stats = $stat_q->fetch_assoc();

// Latest 8 incidents
$latest = $conn->query("
    SELECT i.*, c.category_name, u.fullname AS reporter
    FROM incidents i
    JOIN categories c ON i.category_id = c.category_id
    JOIN users u ON i.user_id = u.user_id
    ORDER BY i.created_at DESC LIMIT 8
");

// Incidents by category (for chart)
$chart_data = $conn->query("
    SELECT c.category_name, COUNT(*) AS cnt
    FROM incidents i JOIN categories c ON i.category_id = c.category_id
    GROUP BY c.category_id
");
$chart_labels = $chart_counts = [];
while ($r = $chart_data->fetch_assoc()) {
    $chart_labels[] = $r['category_name'];
    $chart_counts[] = (int) $r['cnt'];
}

function status_class(string $s): string {
    return match($s) { 'Open'=>'s-open','Assigned'=>'s-assigned','In Progress'=>'s-progress','Resolved'=>'s-resolved','Closed'=>'s-closed',default=>'' };
}
function status_badge(string $s): string {
    return match($s) { 'Open'=>'b-open','Assigned'=>'b-assigned','In Progress'=>'b-progress','Resolved'=>'b-resolved','Closed'=>'b-closed',default=>'' };
}
function priority_badge(string $p): string {
    return match($p) { 'High'=>'b-high','Medium'=>'b-medium','Low'=>'b-low',default=>'' };
}

include __DIR__ . '/../includes/header_admin.php';
?>

<!-- Stat cards -->
<div class="stat-grid">
  <div class="stat-card">       <div class="s-label">Total Incidents</div><div class="s-value"><?= $stats['total']    ?></div></div>
  <div class="stat-card c-open"><div class="s-label">Open</div>            <div class="s-value"><?= $stats['open']     ?></div></div>
  <div class="stat-card c-resolved"><div class="s-label">Resolved</div>   <div class="s-value"><?= $stats['resolved'] ?></div></div>
  <div class="stat-card c-critical"><div class="s-label">Critical (open)</div><div class="s-value"><?= $stats['critical'] ?></div></div>
</div>

<!-- Chart + Latest -->
<div class="detail-grid">
  <!-- Latest incidents -->
  <div class="panel">
    <div class="panel-head">
      <h3>Latest Incidents</h3>
      <a href="<?= SITE_URL ?>/admin/incidents.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <?php while ($inc = $latest->fetch_assoc()): ?>
    <a class="incident-row clickable <?= status_class($inc['status']) ?>"
       href="<?= SITE_URL ?>/admin/view_incident.php?id=<?= $inc['incident_id'] ?>">
      <span class="inc-id">#<?= $inc['incident_id'] ?></span>
      <div class="inc-info">
        <h4><?= htmlspecialchars($inc['title']) ?></h4>
        <div class="inc-meta">
          <span><?= htmlspecialchars($inc['reporter']) ?></span>
          <span>·</span><span><?= htmlspecialchars($inc['category_name']) ?></span>
          <span>·</span><span><?= date('d M, H:i', strtotime($inc['created_at'])) ?></span>
        </div>
      </div>
      <div class="inc-badges">
        <span class="badge <?= priority_badge($inc['priority']) ?>"><?= $inc['priority'] ?></span>
        <span class="badge <?= status_badge($inc['status']) ?>"><?= $inc['status'] ?></span>
      </div>
    </a>
    <?php endwhile; ?>
  </div>

  <!-- Chart -->
  <div class="panel panel-body">
    <h3 style="margin-bottom:16px; font-size:15px;">Incidents by Category</h3>
    <canvas id="catChart" height="200"></canvas>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('catChart');
new Chart(ctx, {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($chart_labels) ?>,
    datasets: [{
      data: <?= json_encode($chart_counts) ?>,
      backgroundColor: ['#4472C4','#D64545','#E8A33D','#3F8F5F'],
      borderWidth: 2, borderColor: '#fff'
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'bottom' } }
  }
});
</script>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
