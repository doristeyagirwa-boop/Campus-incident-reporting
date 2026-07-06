<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$status_filter = $_GET['status'] ?? '';
$allowed_statuses = ['open', 'in_progress', 'resolved'];

$pdo = get_db();

$query = "
    SELECT incidents.*, categories.name AS category_name,
           reporter.full_name AS reporter_name,
           assignee.full_name AS assignee_name
    FROM incidents
    LEFT JOIN categories ON incidents.category_id = categories.id
    LEFT JOIN users AS reporter ON incidents.reported_by = reporter.id
    LEFT JOIN users AS assignee ON incidents.assigned_to = assignee.id
";
$params = [];
if (in_array($status_filter, $allowed_statuses, true)) {
    $query .= " WHERE incidents.status = :status";
    $params['status'] = $status_filter;
}
$query .= " ORDER BY incidents.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$incidents = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<h2 class="mb-4"><i class="bi bi-exclamation-triangle"></i> Manage Incidents</h2>

<ul class="nav nav-pills mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $status_filter === '' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/incidents.php">All</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $status_filter === 'open' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/incidents.php?status=open">Open</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $status_filter === 'in_progress' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/incidents.php?status=in_progress">In Progress</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $status_filter === 'resolved' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/incidents.php?status=resolved">Resolved</a>
    </li>
</ul>

<div class="card shadow">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Reported By</th>
                    <th>Assigned To</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($incidents): foreach ($incidents as $incident): ?>
                <?php
                    $status_class = $incident['status'] === 'resolved' ? 'success' : ($incident['status'] === 'in_progress' ? 'warning' : 'secondary');
                    $priority_class = $incident['priority'] === 'high' ? 'danger' : ($incident['priority'] === 'medium' ? 'warning' : 'info');
                ?>
                <tr>
                    <td><?= htmlspecialchars($incident['title']) ?></td>
                    <td><?= htmlspecialchars($incident['category_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($incident['reporter_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($incident['assignee_name'] ?? '—') ?></td>
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
                    <td>
                        <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/comments.php?id=<?= $incident['id'] ?>">
                            <i class="bi bi-chat-left-text"></i> Comments
                        </a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="8" class="text-center text-muted py-3">No incidents found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
