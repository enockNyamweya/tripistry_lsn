<?php
require_once __DIR__ . '/includes/auth.php';
session_start();
requireLogin();

if (isTraveller()) {
    header('Location: /traveller/browse.php');
} else {
    header('Location: /agency/dashboard.php');
}
exit;
