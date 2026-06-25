<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . SITE_URL . (is_role('admin') || is_role('technician') ? '/admin/dashboard.php' : '/student/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role']     = $user['role'];
            $_SESSION['email']    = $user['email'];

            if ($user['role'] === 'admin' || $user['role'] === 'technician') {
                header('Location: ' . SITE_URL . '/admin/dashboard.php');
            } else {
                header('Location: ' . SITE_URL . '/student/dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= SITE_NAME ?></title>
<link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body>
<header class="topbar">
  <div class="topbar-brand"><span class="crest">CG</span> <?= SITE_NAME ?></div>
  <nav class="topbar-nav"><a href="<?= SITE_URL ?>">← Back to Home</a></nav>
</header>

<div class="auth-wrap">
  <div class="auth-card">
    <h2>Welcome back</h2>
    <p class="auth-sub">Log in to report or track incidents.</p>

    <?php if ($error): ?>
    <div class="form-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="field">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="your.email@university.ac.ke" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block mt-16">Log In</button>
    </form>

    <p class="auth-foot" style="margin-top:16px; font-size:12px; color:var(--muted);">
      Demo accounts (password: <strong>Password1!</strong>)<br>
      admin@campusguard.ac.ke · jane@campusguard.ac.ke
    </p>
    <p class="auth-foot">
      No account? <a href="<?= SITE_URL ?>/register.php">Register here</a>
    </p>
  </div>
</div>
<script src="<?= SITE_URL ?>/js/app.js"></script>
</body>
</html>
