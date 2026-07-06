<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$pdo = get_db();
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<h2 class="mb-4"><i class="bi bi-people"></i> Manage Users</h2>

<div class="card shadow">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users): foreach ($users as $user): ?>
                <tr>
                    <td><?= (int) $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="badge bg-<?= $user['role'] === 'admin' ? 'primary' : 'secondary' ?>">
                            <?= htmlspecialchars($user['role']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
