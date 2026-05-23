<?php
require_once __DIR__ . '/env.php';

$basePath = '';
if (stripos($_SERVER['REQUEST_URI'] ?? '', '/tripistry_lsn') === 0) {
    $basePath = '/tripistry_lsn';
}
define('BASE_URL', $basePath);
$host = env('DB_HOST', 'localhost');
$dbname = env('DB_NAME', 'tripistry_lsn');
$username = env('DB_USER', 'root');
$password = env('DB_PASS', '');
$charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$charset",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
