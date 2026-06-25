<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

$page_title = 'My Reports';
$uid = (int) $_SESSION['user_id'];

// Filters from GET
$filter_status   = $_GET['status']      ?? '';
$filter_cat      = (int)($_GET['cat']   ?? 0);
$filter_priority = $_GET['priority']    ?? '';
$search          = trim($_GET['q']      ?? '');

// Build query dynamically
$where  = ['i.user_id = ?'];
$params = [$uid];
$types  = 'i';

if ($filter_status)   { $where[] = 'i.status = ?';      $params[] = $filter_status;   $types .= 's'; }
if ($filter_cat)      { $where[] = 'i.category_id = ?'; $params[] = $filter_cat;      $types .= 'i'; }
if ($filter_priority) { $where[] = 'i.priority = ?';    $params[] = $filter_priority; $types .= 's'; }
if ($search)          { $where[] = 'i.title LIKE ?';    $params[] = "%$search%";      $types .= 's'; }

$sql = "SELECT i.*, c.category_name
        FROM incidents i JOIN categories c ON i.category_id = c.category_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY i.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result();

$cats = $conn->query("SELECT * FROM categories ORDER BY category_name");

function status_class(string $s): string {
    return match($s) { 'Open'=>'s-open','Assigned'=>'s-assigned','In Progress'=>'s-progress','Resolved'=>'s-resolved','Closed'=>'s-closed',default=>'' };
}
function status_badge(string $s): string {
    return match($s) { 'Open'=>'b-open','Assigned'=>'b-assigned','In Progress'=>'b-progress','Resolved'=>'b-resolved','Closed'=>'b-closed',default=>'' };
}
function priority_badge(string $p): string {
    return match($p) { 'High'=>'b-high','Medium'=>'b-medium','Low'=>'b-low',default=>'' };
}

include __DIR__ . '/../includes/header_student.php';
?>

<!-- Filters -->
<form method="GET" class="filter-bar">
  <input type="search" name="q" id="live-search"
         value="<?= htmlspecialchars($search) ?>" placeholder="Search my reports…">
  <select name="cat" onchange="this.form.submit()">
    <option value="">All Categories</option>
    <?php $cats->data_seek(0); while ($cat = $cats->fetch_assoc()): ?>
    <option value="<?= $cat['category_id'] ?>" <?= $filter_cat == $cat['category_id'] ? 'selected' : '' ?>>
      <?= htmlspecialchars($cat['category_name']) ?>
    </option>
    <?php endwhile; ?>
  </select>
  <select name="status" onchange="this.form.submit()">
    <option value="">All Statuses</option>
    <?php foreach (['Open','Assigned','In Progress','Resolved','Closed'] as $s): ?>
    <option <?= $filter_status === $s ? 'selected' : '' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <select name="priority" onchange="this.form.submit()">
    <option value="">All Priorities</option>
    <?php foreach (['High','Medium','Low'] as $p): ?>
    <option <?= $filter_priority === $p ? 'selected' : '' ?>><?= $p ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-outline btn-sm">Filter</button>
  <?php if ($search || $filter_status || $filter_cat || $filter_priority): ?>
  <a href="<?= SITE_URL ?>/student/my_reports.php" class="btn btn-sm" style="background:var(--line);">Clear</a>
  <?php endif; ?>
</form>

<!-- Results -->
<div class="panel">
  <?php if ($rows->num_rows === 0): ?>
  <div class="empty-state">
    <span class="empty-icon">🔍</span>
    <p>No reports found<?= $search ? ' matching "' . htmlspecialchars($search) . '"' : '' ?>.</p>
    <a href="<?= SITE_URL ?>/student/report.php" class="btn btn-primary mt-16">Submit a new report</a>
  </div>
  <?php else: ?>
  <?php while ($inc = $rows->fetch_assoc()): ?>
  <a class="incident-row clickable <?= status_class($inc['status']) ?>"
     href="<?= SITE_URL ?>/student/view_incident.php?id=<?= $inc['incident_id'] ?>"
     data-title="<?= htmlspecialchars($inc['title']) ?>">
    <span class="inc-id">#<?= $inc['incident_id'] ?></span>
    <div class="inc-info">
      <h4><?= htmlspecialchars($inc['title']) ?></h4>
      <div class="inc-meta">
        <span><?= htmlspecialchars($inc['category_name']) ?></span>
        <span>·</span><span><?= htmlspecialchars($inc['location']) ?></span>
        <span>·</span><span><?= date('d M Y, H:i', strtotime($inc['created_at'])) ?></span>
      </div>
    </div>
    <div class="inc-badges">
      <span class="badge <?= priority_badge($inc['priority']) ?>"><?= $inc['priority'] ?></span>
      <span class="badge <?= status_badge($inc['status']) ?>"><?= $inc['status'] ?></span>
    </div>
  </a>
  <?php endwhile; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_student.php'; ?>
