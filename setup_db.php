<?php
// setup_db.php
// Purpose: Automatically setup the schema and seed all tables for the Tripistry web application.

echo "Tripistry DB Installer & Seeder\n\n";

// 1. Recreate the schema
echo "Step 1: Recreating Database Schema...\n";
require_once __DIR__ . '/database/import_schema.php';

echo "\n";

// 2. Import Seed Data
echo "Step 2: Populating Seed Data...\n";
require_once __DIR__ . '/database/fetch-interactions/fetch_interactions.php';

echo "\n";

// 3. Chat migration
echo "Step 3: Setting up chat messaging...\n";
require_once __DIR__ . '/config/database.php';
$pdo->exec("CREATE TABLE IF NOT EXISTS MESSAGE (
    MessageID INT AUTO_INCREMENT PRIMARY KEY,
    SenderID INT NOT NULL,
    ReceiverID INT NOT NULL,
    Message TEXT NOT NULL,
    SentAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    IsRead TINYINT(1) DEFAULT 0,
    FOREIGN KEY (SenderID) REFERENCES USER(UserID) ON DELETE CASCADE,
    FOREIGN KEY (ReceiverID) REFERENCES USER(UserID) ON DELETE CASCADE
)");
echo "Chat messaging ready.\n";

echo "\nDatabase setup completed successfully!\n";

