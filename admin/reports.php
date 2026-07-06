<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$pdo = get_db();

$by_category = $pdo->query("
    SELECT categories.name, COUNT(incidents.id) as total
    FROM incidents
    JOIN categories ON incidents.category_id = categories.id
    GROUP BY categories.name
")->fetchAll();

$by_month = $pdo->query("
    SELECT MONTHNAME(created_at) as month, COUNT(id) as total
    FROM incidents
    GROUP BY MONTH(created_at), MONTHNAME(created_at)
    ORDER BY MONTH(created_at)
")->fetchAll();

$total = (int) $pdo->query("SELECT COUNT(*) as total FROM incidents")->fetch()['total'];
$resolved = (int) $pdo->query("SELECT COUNT(*) as resolved FROM incidents WHERE status = 'resolved'")->fetch()['resolved'];
$unresolved = $total - $resolved;

include __DIR__ . '/../includes/header.php';
?>
<h2 class="mb-4"><i class="bi bi-bar-chart-line"></i> Admin Reports</h2>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body text-center">
                <h5 class="card-title">Total Incidents</h5>
                <h1><?= $total ?></h1>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body text-center">
                <h5 class="card-title">Resolved</h5>
                <h1><?= $resolved ?></h1>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-danger">
            <div class="card-body text-center">
                <h5 class="card-title">Unresolved</h5>
                <h1><?= $unresolved ?></h1>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-pie-chart"></i> Incidents by Category
            </div>
            <div class="card-body">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <i class="bi bi-graph-up"></i> Incidents by Month
            </div>
            <div class="card-body">
                <canvas id="monthChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="row mb-4">
    <div class="col-md-6 mx-auto">
        <div class="card shadow">
            <div class="card-header bg-warning text-white">
                <i class="bi bi-check-circle"></i> Resolution Rate
            </div>
            <div class="card-body">
                <canvas id="resolutionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
const reportData = {
    categoryLabels: <?= json_encode(array_column($by_category, 'name')) ?>,
    categoryData: <?= json_encode(array_map('intval', array_column($by_category, 'total'))) ?>,
    monthLabels: <?= json_encode(array_column($by_month, 'month')) ?>,
    monthData: <?= json_encode(array_map('intval', array_column($by_month, 'total'))) ?>,
    resolved: <?= $resolved ?>,
    unresolved: <?= $unresolved ?>
};
</script>

<script src="<?= BASE_URL ?>/static/js/reports.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
