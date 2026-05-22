<?php
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Show tables
    echo "--- TABLES IN DATABASE ---\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    echo "\nTotal tables: " . count($tables) . "\n\n";

    // 2. Check if USER table exists and has rows
    $lowercaseTables = array_map('strtolower', $tables);
    if (in_array('user', $lowercaseTables)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM USER");
        $count = $stmt->fetchColumn();
        echo "USER table exists and has $count row(s).\n";
        if ($count > 0) {
            $stmt = $pdo->query("SELECT UserID, Email, UserType FROM USER");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "  - ID: {$row['UserID']} | Email: {$row['Email']} | Type: {$row['UserType']}\n";
            }
        }
    } else {
        echo "USER table DOES NOT exist!\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
