<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$pdo = get_db();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<h2 class="mb-4"><i class="bi bi-gear"></i> Settings</h2>

<div class="row">
    <div class="col-md-7 mb-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-tags"></i> Incident Categories
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($categories): foreach ($categories as $category): ?>
                        <tr>
                            <td><?= htmlspecialchars($category['name']) ?></td>
                            <td><?= htmlspecialchars($category['description'] ?? '—') ?></td>
                            <td class="text-end">
                                <form method="POST" action="<?= BASE_URL ?>/admin/delete_category.php" class="d-inline">
                                    <input type="hidden" name="category_id" value="<?= (int) $category['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?');">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">No categories yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-5 mb-4">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <i class="bi bi-plus-circle"></i> Add Category
            </div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/admin/add_category.php">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Add Category</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
