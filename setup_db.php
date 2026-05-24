<?php
// setup_db.php
// Purpose: Automatically setup the schema and seed all tables for the Tripistry web application.

echo "Tripistry DB Installer & Seeder\n\n";

// 1. Recreate the schema
echo "Step 1: Recreating Database Schema...\n";
require_once __DIR__ . '/database/import_schema.php';

echo "\n";

// 2. Import Seed Data (Destinations, Agencies, Travellers, Accommodations, Restaurants, Attractions, Flights, bookings, reviews)
echo "Step 2: Populating Seed Data...\n";
require_once __DIR__ . '/database/fetch-interactions/fetch_interactions.php';

echo "\nDatabase setup completed successfully!\n";

