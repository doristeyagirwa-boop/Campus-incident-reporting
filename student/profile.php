<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

$page_title = 'My Profile';
$uid    = (int) $_SESSION['user_id'];
$error  = $success = '';

// Fetch current user
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullname  = trim($_POST['fullname'] ?? '');
        $student_no = trim($_POST['student_no'] ?? '');
        if (!$fullname || !$student_no) {
            $error = 'Name and student number are required.';
        } else {
            $stmt2 = $conn->prepare("UPDATE users SET fullname = ?, student_no = ? WHERE user_id = ?");
            $stmt2->bind_param('ssi', $fullname, $student_no, $uid);
            if ($stmt2->execute()) {
                $_SESSION['fullname'] = $fullname;
                $user['fullname']     = $fullname;
                $user['student_no']   = $student_no;
                $success = 'Profile updated.';
            } else {
                $error = 'Update failed.';
            }
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new_pw  = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$current || !$new_pw || !$confirm) {
            $error = 'All password fields are required.';
        } elseif (!password_verify($current, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new_pw) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new_pw !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
            $stmt3  = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt3->bind_param('si', $hashed, $uid);
            $stmt3->execute();
            $success = 'Password changed successfully.';
        }
    }
}

// Incident count for this user
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM incidents WHERE user_id = ?");
$count_stmt->bind_param('i', $uid);
$count_stmt->execute();
$count_stmt->bind_result($total_incidents);
$count_stmt->fetch();

include __DIR__ . '/../includes/header_student.php';
?>

<?php if ($error):   ?><div class="form-error mb-16"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="form-success mb-16"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="profile-grid">

  <!-- Profile Info -->
  <div class="panel panel-body">
    <div class="profile-avatar-block">
      <div class="profile-avatar"><?= strtoupper(substr($user['fullname'], 0, 2)) ?></div>
      <div>
        <strong style="font-size:17px;"><?= htmlspecialchars($user['fullname']) ?></strong><br>
        <span class="text-muted text-sm"><?= htmlspecialchars($user['email']) ?></span><br>
        <span class="badge b-assigned" style="margin-top:6px;"><?= ucfirst($user['role']) ?></span>
      </div>
    </div>
    <p class="text-sm text-muted mb-24">Total incidents submitted: <strong><?= $total_incidents ?></strong></p>

    <form method="POST">
      <input type="hidden" name="action" value="update_profile">
      <div class="field">
        <label>Full Name</label>
        <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required>
      </div>
      <div class="field">
        <label>Student / Staff Number</label>
        <input type="text" name="student_no" value="<?= htmlspecialchars($user['student_no']) ?>" required>
      </div>
      <div class="field">
        <label>Email Address</label>
        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
        <span class="hint">Email cannot be changed.</span>
      </div>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
  </div>

  <!-- Change Password -->
  <div class="panel panel-body">
    <h3 style="margin-bottom:18px; font-size:16px;">Change Password</h3>
    <form method="POST">
      <input type="hidden" name="action" value="change_password">
      <div class="field">
        <label>Current Password</label>
        <input type="password" name="current_password" required>
      </div>
      <div class="field">
        <label>New Password</label>
        <input type="password" name="new_password" required>
      </div>
      <div class="field">
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required>
      </div>
      <button type="submit" class="btn btn-outline">Change Password</button>
    </form>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer_student.php'; ?>
