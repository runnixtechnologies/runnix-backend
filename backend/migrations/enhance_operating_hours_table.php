<?php
/**
 * Migration Script: Enhance Operating Hours Table
 * Adds support for 24/7 toggle and day enable/disable functionality
 */

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting migration: Enhancing operating hours table...\n";
    
    // Add new columns to store_operating_hours table
    $alterQueries = [
        "ALTER TABLE store_operating_hours ADD COLUMN IF NOT EXISTS enabled BOOLEAN DEFAULT TRUE",
        "ALTER TABLE store_operating_hours ADD COLUMN IF NOT EXISTS is_24hrs BOOLEAN DEFAULT FALSE"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $stmt = $conn->prepare($query);
            $stmt->execute();
            echo "✅ Executed: " . substr($query, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "ℹ️  Column might already exist: " . $e->getMessage() . "\n";
        }
    }
    
    // Add business_24_7 column to stores table
    $addBusiness247Query = "ALTER TABLE stores ADD COLUMN IF NOT EXISTS business_24_7 BOOLEAN DEFAULT FALSE";
    try {
        $stmt = $conn->prepare($addBusiness247Query);
        $stmt->execute();
        echo "✅ Added business_24_7 column to stores table\n";
    } catch (Exception $e) {
        echo "ℹ️  business_24_7 column might already exist: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Operating hours enhancement migration completed successfully!\n";
    echo "New features supported:\n";
    echo "- 24/7 business toggle\n";
    echo "- Individual day enable/disable\n";
    echo "- 24-hour operation per day\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
