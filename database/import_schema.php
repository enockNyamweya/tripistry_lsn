<?php
require_once __DIR__ . '/../config/database.php';

$sqlFile = __DIR__ . '/schema.sql';

if (!file_exists($sqlFile)) {
    die("Error: schema.sql not found at $sqlFile");
}

$sql = file_get_contents($sqlFile);

try {
    // Execute the SQL file contents
    $pdo->exec($sql);
    echo "Schema imported successfully! The 'USER' table and all other 21 tables have been created.\n";
} catch (PDOException $e) {
    echo "Error importing schema: " . $e->getMessage() . "\n";
}
