<?php
session_start();
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (loginUser($email, $password)) {
        header('Location: /dashboard.php');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Tripistry</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand"><a href="/index.php">Tripistry</a></div>
    <div class="nav-links">
        <a href="/login.php">Login</a>
        <a href="/register.php">Register</a>
    </div>
</nav>
<main class="container">
    <div class="auth-form">
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
        <p class="auth-link">Don't have an account? <a href="/register.php">Register here</a></p>
        <p class="demo-credentials">
            <strong>Demo:</strong> traveller@test.com / password &nbsp;|&nbsp; admin@tripistry.com / password
        </p>
    </div>
</main>
<footer class="footer"><p>&copy; <?php echo date('Y'); ?> Tripistry</p></footer>
</body>
</html>
