<?php
// CSRF Protection - generates and validates tokens for all forms

function csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('CSRF validation failed. Please refresh the page and try again.');
    }
    return true;
}

// Login Rate Limiting : blocks brute force attempts by IP
function check_login_rate_limit($email) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $now = time();
    $window = 300; // 5 minutes
    $max_attempts = 5;

    $key = 'login_attempts_' . $ip;
    $attempts = $_SESSION[$key] ?? [];

    // Remove old attempts outside the window
    $attempts = array_filter($attempts, function($t) use ($now, $window) {
        return $t > ($now - $window);
    });

    if (count($attempts) >= $max_attempts) {
        $waitTime = $window - ($now - min($attempts));
        return "Too many login attempts. Please wait " . ceil($waitTime / 60) . " minute(s).";
    }

    return false; // Not rate limited
}

function record_login_attempt() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $key = 'login_attempts_' . $ip;
    $_SESSION[$key][] = time();
}

// Session security : regenerate ID on login to prevent fixation
function secure_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}

// XSS-safe output : wrapper that always escapes
function safe_html($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
