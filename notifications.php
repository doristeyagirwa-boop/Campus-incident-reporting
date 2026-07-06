<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = get_db();
$user_id = $_SESSION['user_id'] ?? 1;

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute(['user_id' => $user_id]);
$notifications = $stmt->fetchAll();

$stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);

include __DIR__ . '/includes/header.php';
?>
<h2 class="mb-4"><i class="bi bi-bell"></i> Notifications</h2>

<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-bell-fill"></i> Your Notifications (<?= count($notifications) ?>)
    </div>
    <div class="card-body">
        <?php if ($notifications): ?>
            <?php foreach ($notifications as $notification): ?>
            <div class="card mb-3 <?= !$notification['is_read'] ? 'border-primary' : '' ?>"
                 style="cursor: pointer;"
                 data-message="<?= htmlspecialchars($notification['message'], ENT_QUOTES) ?>"
                 data-time="<?= htmlspecialchars($notification['created_at'], ENT_QUOTES) ?>"
                 onclick="showNotification(this.dataset.message, this.dataset.time)">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <?php if (!$notification['is_read']): ?>
                                <span class="badge bg-primary me-2">New</span>
                            <?php endif; ?>
                            <i class="bi bi-bell text-primary"></i>
                            <?= htmlspecialchars($notification['message']) ?>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-clock"></i> <?= htmlspecialchars($notification['created_at']) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                <i class="bi bi-bell-slash" style="font-size: 2rem;"></i>
                <p class="mt-2">No notifications yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Notification Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-bell"></i> Notification
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="modalMessage" class="fs-5"></p>
                <small class="text-muted">
                    <i class="bi bi-clock"></i> <span id="modalTime"></span>
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    <i class="bi bi-check"></i> OK
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function showNotification(message, time) {
        document.getElementById('modalMessage').textContent = message;
        document.getElementById('modalTime').textContent = time;
        var modal = new bootstrap.Modal(document.getElementById('notificationModal'));
        modal.show();
    }
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
