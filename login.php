<?php
session_start();
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (loginUser($email, $password)) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

    <div class="auth-form" style="max-width: 400px; margin: 40px auto; background: var(--glass-bg); padding: 2rem; border-radius: var(--border-radius); border: 1px solid var(--glass-border); box-shadow: var(--shadow-md);">
        <h1>Login</h1>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <p class="auth-link">Don't have an account? <a href="<?php echo BASE_URL; ?>/register.php">Register here</a></p>
        <p class="demo-credentials" style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-muted);">
            <strong>Demo:</strong> traveller@test.com / password &nbsp;|&nbsp; admin@tripistry_lsn.com / password
        </p>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>
