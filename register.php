<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . SITE_URL . '/student/dashboard.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_no = trim($_POST['student_no'] ?? '');
    $fullname   = trim($_POST['fullname']   ?? '');
    $email      = trim($_POST['email']      ?? '');
    $password   = $_POST['password']        ?? '';
    $confirm    = $_POST['confirm']         ?? '';

    // Validate
    if (!$student_no || !$fullname || !$email || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check email not already taken
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'That email address is already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "INSERT INTO users (student_no, fullname, email, password) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param('ssss', $student_no, $fullname, $email, $hashed);

            if ($stmt->execute()) {
                $success = 'Account created! You can now log in.';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — <?= SITE_NAME ?></title>
<link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body>
<header class="topbar">
  <div class="topbar-brand"><span class="crest">CG</span> <?= SITE_NAME ?></div>
  <nav class="topbar-nav"><a href="<?= SITE_URL ?>">← Back to Home</a></nav>
</header>

<div class="auth-wrap">
  <div class="auth-card">
    <h2>Create your account</h2>
    <p class="auth-sub">Use your official student or staff email to register.</p>

    <?php if ($error):   ?><div class="form-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="form-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST">
      <div class="field-row">
        <div class="field">
          <label for="student_no">Student / Staff No.</label>
          <input type="text" id="student_no" name="student_no"
                 value="<?= htmlspecialchars($_POST['student_no'] ?? '') ?>"
                 placeholder="e.g. BIT-0231-2023" required>
        </div>
        <div class="field">
          <label for="fullname">Full Name</label>
          <input type="text" id="fullname" name="fullname"
                 value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>"
                 placeholder="Jane Wanjiru" required>
        </div>
      </div>
      <div class="field">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="jane.wanjiru@university.ac.ke" required>
      </div>
      <div class="field-row">
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
        <div class="field">
          <label for="confirm">Confirm Password</label>
          <input type="password" id="confirm" name="confirm" placeholder="••••••••" required>
        </div>
      </div>
      <p class="hint" style="margin-bottom:16px;">Minimum 8 characters.</p>
      <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>
    <?php endif; ?>

    <p class="auth-foot">
      Already registered? <a href="<?= SITE_URL ?>/login.php">Log in</a>
    </p>
  </div>
</div>
<script src="<?= SITE_URL ?>/js/app.js"></script>
</body>
</html>
