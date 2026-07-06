<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$pdo = get_db();

$total_incidents = (int) $pdo->query("SELECT COUNT(*) as total FROM incidents")->fetch()['total'];
$open_count = (int) $pdo->query("SELECT COUNT(*) as total FROM incidents WHERE status = 'open'")->fetch()['total'];
$in_progress_count = (int) $pdo->query("SELECT COUNT(*) as total FROM incidents WHERE status = 'in_progress'")->fetch()['total'];
$resolved_count = (int) $pdo->query("SELECT COUNT(*) as total FROM incidents WHERE status = 'resolved'")->fetch()['total'];
$total_users = (int) $pdo->query("SELECT COUNT(*) as total FROM users")->fetch()['total'];

$recent_incidents = $pdo->query("
    SELECT incidents.*, categories.name AS category_name,
           reporter.full_name AS reporter_name
    FROM incidents
    LEFT JOIN categories ON incidents.category_id = categories.id
    LEFT JOIN users AS reporter ON incidents.reported_by = reporter.id
    ORDER BY incidents.created_at DESC
    LIMIT 5
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<h2 class="mb-4"><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>

<div class="row mb-4">
    <div class="col-md-2 col-6 mb-3">
        <div class="card text-white bg-primary">
            <div class="card-body text-center">
                <h6 class="card-title">Total Incidents</h6>
                <h2><?= $total_incidents ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card text-white bg-secondary">
            <div class="card-body text-center">
                <h6 class="card-title">Open</h6>
                <h2><?= $open_count ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card text-white bg-warning">
            <div class="card-body text-center">
                <h6 class="card-title">In Progress</h6>
                <h2><?= $in_progress_count ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body text-center">
                <h6 class="card-title">Resolved</h6>
                <h2><?= $resolved_count ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card text-white bg-dark">
            <div class="card-body text-center">
                <h6 class="card-title">Users</h6>
                <h2><?= $total_users ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-clock-history"></i> Recent Incidents
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Reported By</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent_incidents): foreach ($recent_incidents as $incident): ?>
                <?php
                    $status_class = $incident['status'] === 'resolved' ? 'success' : ($incident['status'] === 'in_progress' ? 'warning' : 'secondary');
                    $priority_class = $incident['priority'] === 'high' ? 'danger' : ($incident['priority'] === 'medium' ? 'warning' : 'info');
                ?>
                <tr>
                    <td><?= htmlspecialchars($incident['title']) ?></td>
                    <td><?= htmlspecialchars($incident['category_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($incident['reporter_name'] ?? '—') ?></td>
                    <td>
                        <span class="badge bg-<?= $status_class ?>">
                            <?= htmlspecialchars($incident['status']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $priority_class ?>">
                            <?= htmlspecialchars($incident['priority']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($incident['created_at']) ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="6" class="text-center text-muted py-3">No incidents yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
