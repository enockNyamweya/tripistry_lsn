<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/chat_functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tripistry — Travel Package Management</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
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
                <?php $tUnread = getUnreadCount($_SESSION['user_id']); ?>
                <a href="<?php echo BASE_URL; ?>/traveller/messages.php" class="msg-link">Messages<?php if ($tUnread): ?> <span class="msg-badge"><?php echo $tUnread; ?></span><?php endif; ?></a>
            <?php elseif (isAgency()): ?>
                <a href="<?php echo BASE_URL; ?>/agency/dashboard.php">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>/agency/packages.php">My Packages</a>
                <a href="<?php echo BASE_URL; ?>/agency/bookings.php">Bookings</a>
                <a href="<?php echo BASE_URL; ?>/agency/group_trips.php">Group Trips</a>
                <?php $aUnread = getUnreadCount($_SESSION['user_id']); ?>
                <a href="<?php echo BASE_URL; ?>/agency/messages.php" class="msg-link">Messages<?php if ($aUnread): ?> <span class="msg-badge"><?php echo $aUnread; ?></span><?php endif; ?></a>
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
