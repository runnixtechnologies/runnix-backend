<?php
/**
 * Migration Script: Add business_url field to stores table
 * This adds the business URL/website field to the stores table for merchants
 */

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting migration: Adding business_url field to stores table...\n";
    
    // Check if business_url column already exists
    $checkColumnQuery = "SHOW COLUMNS FROM stores LIKE 'biz_url'";
    $checkStmt = $conn->prepare($checkColumnQuery);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        // Add business_url column to stores table
        $addBusinessUrlQuery = "ALTER TABLE stores 
                                ADD COLUMN biz_url VARCHAR(255) NULL 
                                AFTER biz_reg_number";
        
        $stmt = $conn->prepare($addBusinessUrlQuery);
        $stmt->execute();
        echo "✅ Added biz_url column to stores table\n";
    } else {
        echo "ℹ️  biz_url column already exists in stores table\n";
    }
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "Merchants can now add their business website URL to their store profile.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
?>
