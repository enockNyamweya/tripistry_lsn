<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tripistry — Travel Package Management</title>
    <!--Was: -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css"> 
    <!--Now includes: -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/reset.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/variables.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/pages.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">
        <a href="<?php echo BASE_URL; ?>/index.php">Tripistry</a>
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
                <a href="<?php echo BASE_URL; ?>/agency/bookings.php">Bookings</a>
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
