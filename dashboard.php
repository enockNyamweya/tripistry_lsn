<?php
require_once __DIR__ . '/includes/auth.php';
session_start();
requireLogin();

if (isTraveller()) {
    header('Location: ' . BASE_URL . '/traveller/browse.php');
} else {
    header('Location: ' . BASE_URL . '/agency/dashboard.php');
}
exit;
