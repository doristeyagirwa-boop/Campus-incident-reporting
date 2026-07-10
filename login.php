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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login — <?= login_safe(SITE_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body class="login-shell su-login-shell">
    <main class="su-login-stage">
        <section class="su-login-card">
            <div class="su-login-brand">
                <div class="su-login-mark">CG</div>
                <div>
                    <h1><?= login_safe(SITE_NAME) ?></h1>
                    <p>Sign in to continue</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="form-error mb-16"><?= login_safe($error) ?></div>
            <?php endif; ?>

            <form method="post" class="su-login-form">
                <div class="field">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="you@school.ac.ke" required autofocus>
                </div>

                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Password" required>
                </div>

                <button class="btn btn-primary btn-block" type="submit">
                    Login
                </button>
            </form>

            <div class="su-login-links">
                <a href="<?= SITE_URL ?>/register.php">Create account</a>
                <span></span>
                <a href="<?= SITE_URL ?>/index.php">Back home</a>
            </div>
        </section>

        <section class="su-login-visual" aria-hidden="true">
            <div class="su-orb su-orb-one"></div>
            <div class="su-orb su-orb-two"></div>
            <div class="su-orb su-orb-three"></div>
            <div class="su-line su-line-one"></div>
            <div class="su-line su-line-two"></div>
            <div class="su-glass-card"></div>
        </section>
    </main>
</body>
</html>
