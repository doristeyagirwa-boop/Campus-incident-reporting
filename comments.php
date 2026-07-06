<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

$incident_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$pdo = get_db();

$stmt = $pdo->prepare("SELECT * FROM incidents WHERE id = :id");
$stmt->execute(['id' => $incident_id]);
$incident = $stmt->fetch();

if (!$incident) {
    http_response_code(404);
    die('Incident not found.');
}

$stmt = $pdo->prepare("
    SELECT comments.*, users.full_name
    FROM comments
    JOIN users ON comments.user_id = users.id
    WHERE comments.incident_id = :id
    ORDER BY comments.created_at DESC
");
$stmt->execute(['id' => $incident_id]);
$comments = $stmt->fetchAll();

$status_class = $incident['status'] === 'resolved' ? 'success' : ($incident['status'] === 'in_progress' ? 'warning' : 'danger');
$priority_class = $incident['priority'] === 'high' ? 'danger' : ($incident['priority'] === 'medium' ? 'warning' : 'success');

include __DIR__ . '/includes/header.php';
?>
<h2 class="mb-4"><i class="bi bi-chat-dots"></i> Comments</h2>

<!-- Incident Details -->
<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-exclamation-circle"></i> Incident Details
    </div>
    <div class="card-body">
        <h5><?= htmlspecialchars($incident['title']) ?></h5>
        <p><?= htmlspecialchars($incident['description']) ?></p>
        <span class="badge bg-<?= $status_class ?>">
            <?= htmlspecialchars($incident['status']) ?>
        </span>
        <span class="badge bg-<?= $priority_class ?>">
            <?= htmlspecialchars($incident['priority']) ?> priority
        </span>
    </div>
</div>

<!-- Add Comment Form -->
<div class="card shadow mb-4">
    <div class="card-header bg-success text-white">
        <i class="bi bi-plus-circle"></i> Add a Comment
    </div>
    <div class="card-body">
        <form action="<?= BASE_URL ?>/add_comment.php?id=<?= $incident_id ?>" method="POST">
            <div class="mb-3">
                <textarea
                    name="comment_text"
                    class="form-control"
                    rows="3"
                    placeholder="Write your comment here..."
                    required></textarea>
            </div>
            <button type="submit" class="btn btn-success">
                <i class="bi bi-send"></i> Submit Comment
            </button>
        </form>
    </div>
</div>

<!-- Comments List -->
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-chat-left-text"></i> All Comments (<?= count($comments) ?>)
    </div>
    <div class="card-body">
        <?php if ($comments): ?>
            <?php foreach ($comments as $comment): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h6 class="card-subtitle text-primary">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($comment['full_name']) ?>
                        </h6>
                        <small class="text-muted">
                            <i class="bi bi-clock"></i> <?= htmlspecialchars($comment['created_at']) ?>
                        </small>
                    </div>
                    <p class="card-text mt-2"><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                <i class="bi bi-chat-slash" style="font-size: 2rem;"></i>
                <p class="mt-2">No comments yet. Be the first to comment!</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
