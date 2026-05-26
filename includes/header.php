<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.php';

/**
 * FIX: Use full path instead of basename to avoid collisions
 */
$currentPath = $_SERVER['REQUEST_URI'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php renderViewportMeta(); ?>
    <title>Tripistry — Travel Package Management</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/reset.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/variables.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/components.css">
    <?php $pagesCss = __DIR__ . '/../assets/css/pages.css'; ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/pages.css?v=<?php echo file_exists($pagesCss) ? filemtime($pagesCss) : '1'; ?>">
</head>

<body>

<nav class="navbar">
    <div class="nav-brand">
        <a href="<?php echo BASE_URL; ?>/index.php">Tripistry</a>
    </div>

    <div class="nav-links">

        <?php if (isLoggedIn()): ?>

            <?php if (isTraveller()): ?>

                <a href="<?php echo BASE_URL; ?>/traveller/dashboard.php"
                   class="<?= str_contains($currentPath, '/traveller/dashboard') ? 'active-nav' : '' ?>">
                    Dashboard
                </a>

                <a href="<?php echo BASE_URL; ?>/traveller/browse.php"
                   class="<?= str_contains($currentPath, '/traveller/browse') ? 'active-nav' : '' ?>">
                    Browse
                </a>

                <a href="<?php echo BASE_URL; ?>/traveller/packages.php"
                   class="<?= str_contains($currentPath, '/traveller/packages') ? 'active-nav' : '' ?>">
                    Packages
                </a>

                <a href="<?php echo BASE_URL; ?>/traveller/bookings.php"
                   class="<?= str_contains($currentPath, '/traveller/bookings') ? 'active-nav' : '' ?>">
                    My Bookings
                </a>

                <a href="<?php echo BASE_URL; ?>/traveller/chat.php"
                   class="<?= str_contains($currentPath, '/traveller/chat') ? 'active-nav' : '' ?>">
                    Chat
                </a>

            <?php elseif (isAgency()): ?>

                <a href="<?php echo BASE_URL; ?>/agency/dashboard.php"
                   class="<?= str_contains($currentPath, '/agency/dashboard') ? 'active-nav' : '' ?>">
                    Dashboard
                </a>

                <a href="<?php echo BASE_URL; ?>/agency/packages.php"
                   class="<?= str_contains($currentPath, '/agency/packages') ? 'active-nav' : '' ?>">
                    My Packages
                </a>

                <a href="<?php echo BASE_URL; ?>/agency/bookings.php"
                   class="<?= str_contains($currentPath, '/agency/bookings') ? 'active-nav' : '' ?>">
                    Bookings
                </a>

                <a href="<?php echo BASE_URL; ?>/agency/group_trips.php"
                   class="<?= str_contains($currentPath, '/agency/group_trips') ? 'active-nav' : '' ?>">
                    Group Trips
                </a>

                <a href="<?php echo BASE_URL; ?>/agency/chat.php"
                   class="<?= str_contains($currentPath, '/agency/chat') ? 'active-nav' : '' ?>">
                    Chat
                </a>

            <?php endif; ?>

            <!-- USER DISPLAY -->
            <span class="nav-user">
                <span class="user-icon">👤</span>
                <?php echo htmlspecialchars($_SESSION['email']); ?>
            </span>

            <a href="<?php echo BASE_URL; ?>/logout.php" class="btn-logout">Logout</a>

        <?php else: ?>

            <?php if (!str_contains($currentPath, '/index.php') && $currentPath !== '/' && !str_ends_with($currentPath, '/')): ?>
            <a href="<?php echo BASE_URL; ?>/login.php">Login</a>
            <a href="<?php echo BASE_URL; ?>/register.php">Register</a>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</nav>

<main class="container">