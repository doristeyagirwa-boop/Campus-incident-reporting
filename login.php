<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    redirect_after_login($_SESSION['role']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, fullname, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = 'Login is temporarily unavailable.';
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int) $user['user_id'];
                $_SESSION['fullname'] = (string) $user['fullname'];
                $_SESSION['email'] = (string) $user['email'];
                $_SESSION['role'] = (string) $user['role'];

                redirect_after_login($_SESSION['role']);
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}

$page_title = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(SITE_NAME) ?> — Login</title>
<link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-brand">
      <span class="crest">CG</span>
      <div>
        <h1><?= htmlspecialchars(SITE_NAME) ?></h1>
        <p>Campus incident reporting portal</p>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="form-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="on">
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required placeholder="admin@school.ac.ke">
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required placeholder="Password1!">
      </div>

      <button class="btn btn-primary btn-block" type="submit">Login</button>
    </form>

    <div class="auth-demo">
      <strong>Demo accounts</strong><br>
      Admin: admin@school.ac.ke<br>
      Student: 190439@student.school.ac.ke<br>
      Technician: technician@school.ac.ke<br>
      Password: <strong>Password1!</strong>
    </div>

    <p class="auth-link">
      No account? <a href="<?= SITE_URL ?>/register.php">Create student account</a>
    </p>
  </div>
</div>
</body>
</html>
