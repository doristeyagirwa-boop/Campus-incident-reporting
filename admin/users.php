<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit_helpers.php';

$page_title = 'User Management';

function admin_safe(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$error = '';
$success = '';

$current_admin_id = (int) $_SESSION['user_id'];

function admin_count_admins(mysqli $conn): int
{
    $result = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    if (!$result) {
        return 0;
    }

    $row = $result->fetch_row();
    return (int) ($row[0] ?? 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $target_user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

    if ($target_user_id <= 0) {
        $error = 'Invalid user selected.';
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, fullname, email, role
             FROM users
             WHERE user_id = ?
             LIMIT 1"
        );

        $stmt->bind_param('i', $target_user_id);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$target) {
            $error = 'User not found.';
        } elseif ($action === 'change_role') {
            $new_role = $_POST['role'] ?? '';
            $allowed_roles = ['student', 'technician', 'admin'];

            if (!in_array($new_role, $allowed_roles, true)) {
                $error = 'Invalid role selected.';
            } elseif ($target['role'] === 'admin' && $new_role !== 'admin' && admin_count_admins($conn) <= 1) {
                $error = 'Cannot remove the last admin account.';
            } elseif ($new_role === $target['role']) {
                $success = 'No role change needed.';
            } else {
                $old_role = (string) $target['role'];

                $stmt = $conn->prepare(
                    "UPDATE users
                     SET role = ?
                     WHERE user_id = ?"
                );

                $stmt->bind_param('si', $new_role, $target_user_id);
                $stmt->execute();
                $stmt->close();

                audit_log(
                    $conn,
                    'USER_ROLE_CHANGED',
                    'user',
                    $target_user_id,
                    'Changed role for ' . $target['email'] . ' from ' . $old_role . ' to ' . $new_role
                );

                $success = 'User role updated successfully.';
            }
        } elseif ($action === 'delete_user') {
            if ($target_user_id === $current_admin_id) {
                $error = 'You cannot delete your own active admin account.';
            } elseif ($target['role'] === 'admin' && admin_count_admins($conn) <= 1) {
                $error = 'Cannot delete the last admin account.';
            } else {
                audit_log(
                    $conn,
                    'USER_DELETED',
                    'user',
                    $target_user_id,
                    'Deleted user ' . $target['email'] . ' with role ' . $target['role']
                );

                $stmt = $conn->prepare(
                    "DELETE FROM users
                     WHERE user_id = ?"
                );

                $stmt->bind_param('i', $target_user_id);
                $stmt->execute();
                $stmt->close();

                $success = 'User deleted successfully.';
            }
        }
    }
}

$role_filter = $_GET['role'] ?? '';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT user_id, fullname, student_no, email, role, created_at FROM users WHERE 1=1";
$params = [];
$types = '';

if ($role_filter !== '' && in_array($role_filter, ['student', 'technician', 'admin'], true)) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if ($search !== '') {
    $sql .= " AND (fullname LIKE ? OR email LIKE ? OR student_no LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

$sql .= " ORDER BY created_at DESC, user_id DESC";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$users = $stmt->get_result();

include __DIR__ . '/../includes/header_admin.php';
?>

<?php if ($error): ?>
  <div class="form-error"><?= admin_safe($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
  <div class="form-success"><?= admin_safe($success) ?></div>
<?php endif; ?>

<form class="filter-bar" method="get">
  <input type="text" name="search" placeholder="Search name, email, student number"
         value="<?= admin_safe($search) ?>">

  <select name="role">
    <option value="">All roles</option>
    <option value="student" <?= $role_filter === 'student' ? 'selected' : '' ?>>Students</option>
    <option value="technician" <?= $role_filter === 'technician' ? 'selected' : '' ?>>Technicians</option>
    <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admins</option>
  </select>

  <button class="btn btn-primary btn-sm" type="submit">Filter</button>
  <a class="btn btn-outline btn-sm" href="<?= SITE_URL ?>/admin/users.php">Reset</a>
</form>

<div class="panel">
  <div class="panel-head">
    <h3>User Management</h3>
  </div>

  <?php if ($users->num_rows === 0): ?>
    <div class="empty-state">
      <p>No users found.</p>
    </div>
  <?php else: ?>
    <?php while ($user = $users->fetch_assoc()): ?>
      <div class="user-admin-row">
        <div class="user-admin-main">
          <div class="avatar"><?= strtoupper(substr($user['fullname'], 0, 1)) ?></div>
          <div>
            <h4><?= admin_safe($user['fullname']) ?></h4>
            <p class="text-muted text-sm">
              <?= admin_safe($user['email']) ?>
              <?php if (!empty($user['student_no'])): ?>
                · <?= admin_safe($user['student_no']) ?>
              <?php endif; ?>
            </p>
          </div>
        </div>

        <div class="user-admin-actions">
          <form method="post" class="inline-form">
            <input type="hidden" name="action" value="change_role">
            <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">

            <select name="role">
              <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Student</option>
              <option value="technician" <?= $user['role'] === 'technician' ? 'selected' : '' ?>>Technician</option>
              <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>

            <button class="btn btn-outline btn-sm" type="submit">Save Role</button>
          </form>

          <form method="post"
                onsubmit="return confirm('Delete this user? This action affects the database immediately.');">
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
            <button class="btn btn-danger btn-sm" type="submit">Delete</button>
          </form>
        </div>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
