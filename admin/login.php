<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_admin_logged_in()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role = 'admin'");
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        set_flash('Welcome back, ' . $admin['full_name'] . '!');
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
        exit;
    }

    $error = 'Invalid email or password.';
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-box-arrow-in-right"></i> Admin Login
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Log In</button>
                </form>
                <p class="text-center mt-3 mb-0">
                    <a href="<?= BASE_URL ?>/admin/register.php">Need an admin account? Register</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
