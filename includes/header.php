<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tripistry_lsn  Travel Package Management</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Modular CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/variables.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/reset.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/pages.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">
        <a href="<?php echo BASE_URL; ?>/index.php">Tripistry_lsn</a>
    </div>
    <div class="nav-links">
        <?php if (isLoggedIn()): ?>
            <?php if (isTraveller()): ?>
                <a href="<?php echo BASE_URL; ?>/traveller/browse.php">Browse</a>
                <a href="<?php echo BASE_URL; ?>/traveller/packages.php">Packages</a>
                <a href="<?php echo BASE_URL; ?>/traveller/bookings.php">My Bookings</a>
            <?php elseif (isAgency()): ?>
                <a href="<?php echo BASE_URL; ?>/agency/dashboard.php">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>/agency/packages.php">My Packages</a>
                <a href="<?php echo BASE_URL; ?>/agency/group_trips.php">Group Trips</a>
            <?php endif; ?>
            <span class="nav-user"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
            <a href="<?php echo BASE_URL; ?>/logout.php" class="btn-logout">Logout</a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/login.php">Login</a>
            <a href="<?php echo BASE_URL; ?>/register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>
<main class="container">
