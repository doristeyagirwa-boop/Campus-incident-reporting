<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_admin_logged_in()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($full_name === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (:full_name, :email, :password, 'admin')");
            $stmt->execute([
                'full_name' => $full_name,
                'email' => $email,
                'password' => $hash,
            ]);

            set_flash('Admin account created. You can now log in.');
            header('Location: ' . BASE_URL . '/admin/login.php');
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <i class="bi bi-person-plus"></i> Admin Registration
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required
                               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-success w-100">Register</button>
                </form>
                <p class="text-center mt-3 mb-0">
                    <a href="<?= BASE_URL ?>/admin/login.php">Already have an account? Log in</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
