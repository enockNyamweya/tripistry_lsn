<?php
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isTraveller() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Traveller';
}

function isAgency() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Agency';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireTraveller() {
    requireLogin();
    if (!isTraveller()) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

function requireAgency() {
    requireLogin();
    if (!isAgency()) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

function loginUser($email, $password) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT UserID, Email, Password, UserType FROM USER WHERE Email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['Password'])) {
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['user_type'] = $user['UserType'];
        $_SESSION['email'] = $user['Email'];
        return true;
    }
    return false;
}

function registerUser($email, $password, $userType, $extra) {
    global $pdo;
    $pdo->beginTransaction();
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO USER (Email, Password, UserType) VALUES (?, ?, ?)');
        $stmt->execute([$email, $hash, $userType]);
        $userId = $pdo->lastInsertId();

        if ($userType === 'Traveller') {
            $stmt = $pdo->prepare('INSERT INTO TRAVELLER (UserID, FirstName, LastName) VALUES (?, ?, ?)');
            $stmt->execute([$userId, $extra['first_name'], $extra['last_name']]);
        } elseif ($userType === 'Agency') {
            $stmt = $pdo->prepare('INSERT INTO TRAVEL_AGENCY (UserID, AgencyName, VerificationStatus, CommissionRate) VALUES (?, ?, ?, ?)');
            $stmt->execute([$userId, $extra['agency_name'], 'Pending', 10.00]);
        }

        $pdo->commit();
        return $userId;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function logoutUser() {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

function getUserInfo($userId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT u.*, t.FirstName, t.LastName, NULL as PassportNum, ta.AgencyName, ta.VerificationStatus FROM USER u LEFT JOIN TRAVELLER t ON u.UserID = t.UserID LEFT JOIN TRAVEL_AGENCY ta ON u.UserID = ta.UserID WHERE u.UserID = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function getAgencyInfo($userId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT u.UserID, ta.AgencyName, ta.VerificationStatus, ta.CommissionRate FROM USER u JOIN TRAVEL_AGENCY ta ON u.UserID = ta.UserID WHERE u.UserID = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function getTravellerInfo($userId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT u.UserID, t.FirstName, t.LastName, NULL as PassportNum FROM USER u JOIN TRAVELLER t ON u.UserID = t.UserID WHERE u.UserID = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

//Show name:
function getUserFirstName(PDO $pdo, $userId) {
    $stmt = $pdo->prepare("SELECT FirstName FROM TRAVELLER WHERE UserID = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

/** Compact revenue for stat cards (R7.8M) : full amount in title attribute. */
function formatRevenueShort($amount) {
    $amount = (float)$amount;
    if ($amount >= 1000000000) {
        return 'R' . rtrim(rtrim(number_format($amount / 1000000000, 2, '.', ''), '0'), '.') . 'B';
    }
    if ($amount >= 1000000) {
        return 'R' . rtrim(rtrim(number_format($amount / 1000000, 2, '.', ''), '0'), '.') . 'M';
    }
    if ($amount >= 1000) {
        return 'R' . rtrim(rtrim(number_format($amount / 1000, 1, '.', ''), '0'), '.') . 'K';
    }
    return 'R' . number_format($amount, 0, '.', ',');
}

function formatRevenueFull($amount) {
    return 'R' . number_format((float)$amount, 2, '.', ',');
}

/**
 * Viewport + early script: phones see layout at 1024px (desktop-style, horizontal scroll).
 */
function renderViewportMeta() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0" id="viewport-meta">' . "\n";
    echo '<script>(function(){if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)){document.documentElement.classList.add(\'force-desktop-view\');var m=document.querySelector(\'meta[name=viewport]\');if(m)m.setAttribute(\'content\',\'width=1024\');}})();</script>' . "\n";
}

/** Normalise price filter input for HTML number fields (dot decimal, non-negative). */
function normalizeFilterPrice($value) {
    if ($value === '' || $value === null) {
        return '';
    }
    $normalized = str_replace(',', '.', trim((string)$value));
    if ($normalized === '' || !is_numeric($normalized)) {
        return '';
    }
    $amount = (float)$normalized;
    if ($amount <= 0) {
        return '';
    }
    return rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');
}