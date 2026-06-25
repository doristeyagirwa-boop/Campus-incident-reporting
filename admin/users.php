<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Manage Users';
$success = $error = '';

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_id  = (int) ($_POST['user_id'] ?? 0);
    $new_role   = $_POST['role'] ?? '';
    $valid_roles = ['student', 'technician', 'admin'];

    if (!$target_id || !in_array($new_role, $valid_roles)) {
        $error = 'Invalid request.';
    } elseif ($target_id === (int) $_SESSION['user_id']) {
        $error = 'You cannot change your own role.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $stmt->bind_param('si', $new_role, $target_id);
        $stmt->execute() ? $success = 'User role updated.' : $error = 'Update failed.';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    if ($del_id && $del_id !== (int) $_SESSION['user_id']) {
        $conn->prepare("DELETE FROM users WHERE user_id = ?")->execute();
        $ds = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $ds->bind_param('i', $del_id);
        $ds->execute();
        $success = 'User deleted.';
    }
}

$search = trim($_GET['q'] ?? '');
$filter_role = $_GET['role'] ?? '';

$where  = ['1=1'];
$params = [];
$types  = '';

if ($search)      { $where[] = '(fullname LIKE ? OR email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $types .= 'ss'; }
if ($filter_role) { $where[] = 'role = ?'; $params[] = $filter_role; $types .= 's'; }

$sql = "SELECT *, (SELECT COUNT(*) FROM incidents WHERE user_id = users.user_id) AS inc_count
        FROM users WHERE " . implode(' AND ', $where) . " ORDER BY role, fullname";

if ($types) {
    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param($types, ...$params);
    $stmt2->execute();
    $users = $stmt2->get_result();
} else {
    $users = $conn->query($sql);
}

include __DIR__ . '/../includes/header_admin.php';
?>

<?php if ($error):   ?><div class="form-error mb-16"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="form-success mb-16"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form method="GET" class="filter-bar">
  <input type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email…">
  <select name="role" onchange="this.form.submit()">
    <option value="">All Roles</option>
    <option value="student"    <?= $filter_role === 'student'    ? 'selected' : '' ?>>Student</option>
    <option value="technician" <?= $filter_role === 'technician' ? 'selected' : '' ?>>Technician</option>
    <option value="admin"      <?= $filter_role === 'admin'      ? 'selected' : '' ?>>Admin</option>
  </select>
  <button type="submit" class="btn btn-outline btn-sm">Search</button>
  <?php if ($search || $filter_role): ?>
  <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-sm" style="background:var(--line);">Clear</a>
  <?php endif; ?>
</form>

<div class="panel">
  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Student / Staff No.</th>
        <th>Email</th>
        <th>Role</th>
        <th>Incidents</th>
        <th>Joined</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($users->num_rows === 0): ?>
    <tr><td colspan="8" style="text-align:center; padding:28px; color:var(--muted);">No users found.</td></tr>
    <?php else: ?>
    <?php while ($u = $users->fetch_assoc()): ?>
    <tr>
      <td><?= $u['user_id'] ?></td>
      <td>
        <div class="flex-center">
          <div class="avatar" style="width:28px;height:28px;font-size:11px;
            <?= $u['role']==='admin' ? 'background:var(--navy);color:#fff;' : ($u['role']==='technician'?'background:var(--status-resolved);color:#fff;':'') ?>">
            <?= strtoupper(substr($u['fullname'], 0, 2)) ?>
          </div>
          <?= htmlspecialchars($u['fullname']) ?>
        </div>
      </td>
      <td><?= htmlspecialchars($u['student_no']) ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
      <td>
        <?php if ($u['user_id'] == $_SESSION['user_id']): ?>
          <span class="badge b-progress"><?= ucfirst($u['role']) ?> (you)</span>
        <?php else: ?>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
          <select name="role" onchange="this.form.submit()" style="font-size:13px;padding:4px 8px;border:1px solid var(--line);border-radius:4px;">
            <option value="student"    <?= $u['role']==='student'    ? 'selected':'' ?>>Student</option>
            <option value="technician" <?= $u['role']==='technician' ? 'selected':'' ?>>Technician</option>
            <option value="admin"      <?= $u['role']==='admin'      ? 'selected':'' ?>>Admin</option>
          </select>
        </form>
        <?php endif; ?>
      </td>
      <td><?= $u['inc_count'] ?></td>
      <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
      <td>
        <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
        <a href="?delete=<?= $u['user_id'] ?>&<?= http_build_query(['q'=>$search,'role'=>$filter_role]) ?>"
           class="btn btn-danger btn-sm"
           data-confirm="Delete <?= htmlspecialchars($u['fullname']) ?>? This also deletes all their incidents.">
          Delete
        </a>
        <?php else: ?>
        <span class="text-muted text-sm">—</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/footer_admin.php'; ?>
