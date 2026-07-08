<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    header('Location: ' . dashboard_for_role((string) ($_SESSION['role'] ?? '')));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Enter your email and password.';
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, fullname, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = 'Login service unavailable.';
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($password, (string) $user['password'])) {
                $error = 'Invalid email or password.';
            } else {
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int) $user['user_id'];
                $_SESSION['fullname'] = (string) $user['fullname'];
                $_SESSION['email'] = (string) $user['email'];
                $_SESSION['role'] = (string) $user['role'];

                header('Location: ' . dashboard_for_role((string) $user['role']));
                exit;
            }
        }
    }
}

function login_safe(string|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CampusGuard Login</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body class="login-shell">
    <div class="login-panel">
        <div class="login-brand">
            <div class="login-mark">CG</div>
            <div>
                <h1>CampusGuard</h1>
                <p>Campus incident reporting and response.</p>
            </div>
        </div>

        <div class="login-copy">
            <h2>Sign in</h2>
            <p>Sign in to report, review, or resolve campus issues.</p>
        </div>

        <?php if ($error): ?>
            <div class="form-error mb-16"><?= login_safe($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" placeholder="you@school.ac.ke" required autofocus>
            </div>

            <div class="field">
                <label>Password</label>
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button class="btn btn-primary btn-block" type="submit">
                Continue
            </button>
        </form>

        <div class="login-foot">
            Secure campus access · Role-based dashboards
        </div>
    </div>

    <div class="login-side">
        <div class="login-side-card">
            <span>Live routing</span>
            <strong>Campus services in one secure place</strong>
            <p>Report issues, follow progress, and help the right office respond faster.</p>
        </div>
    </div>
</body>
</html>
