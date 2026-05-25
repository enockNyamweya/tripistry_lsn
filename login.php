<?php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // CSRF check
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid form submission. Please refresh and try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // Rate limit check
        $rateLimitMsg = check_login_rate_limit($email);
        if ($rateLimitMsg) {
            $error = $rateLimitMsg;
        } elseif (loginUser($email, $password)) {
            secure_session_start();
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        } else {
            record_login_attempt();
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php renderViewportMeta(); ?>
    <title>Login — Tripistry</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand"><a href="<?php echo BASE_URL; ?>/index.php">Tripistry</a></div>
    <div class="nav-links">
        <a href="<?php echo BASE_URL; ?>/login.php">Login</a>
        <a href="<?php echo BASE_URL; ?>/register.php">Register</a>
    </div>
</nav>
<main class="container">
    <div class="auth-form">
        <h1>Login</h1>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
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
        <p class="demo-credentials">
            <strong>Demo:</strong> traveller@test.com / password &nbsp;|&nbsp; admin@tripistry.com / password
        </p>
    </div>
</main>
<footer class="footer"><p>&copy; <?php echo date('Y'); ?> Tripistry</p></footer>
</body>
</html>
