<?php
require_once __DIR__ . '/../config/database.php';

$sqlFile = __DIR__ . '/schema.sql';

if (!file_exists($sqlFile)) {
    die("Error: schema.sql not found at $sqlFile");
}

$sql = file_get_contents($sqlFile);

// Strip DROP/CREATE DATABASE and USE lines — PDO is already connected to the correct DB
$lines = explode("\n", $sql);
$filtered = array_filter($lines, function($line) {
    $l = strtoupper(trim($line));
    return !str_starts_with($l, 'DROP DATABASE')
        && !str_starts_with($l, 'CREATE DATABASE')
        && !str_starts_with($l, 'USE ');
});
$sql = implode("\n", $filtered);

// Split into individual statements and run each one
$statements = array_filter(array_map('trim', explode(';', $sql)));
$count = 0;
try {
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $pdo->exec($stmt);
            $count++;
        }
    }
    echo "Schema imported successfully! Ran $count SQL statements.\n";
} catch (PDOException $e) {
    echo "Error importing schema: " . $e->getMessage() . "\n";
}
