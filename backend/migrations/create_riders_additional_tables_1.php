<?php
/**
 * Migration Script: Create Riders Support and Vehicle Tables
 * This creates support, vehicle management, and promotion tables
 */

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting migration: Creating riders support and vehicle tables...\n";
    
     $createRiderVehicleAttachmentsTable = "
    CREATE TABLE IF NOT EXISTS rider_vehicle_attachments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        rider_id INT NOT NULL,
        vehicle_id INT NOT NULL,
        attachment_type VARCHAR(255) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_type VARCHAR(100),
        file_size INT,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (vehicle_id) REFERENCES rider_vehicles(id) ON DELETE CASCADE,
        FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE CASCADE
    )";
    
    
    $conn->exec($createRiderVehicleAttachmentsTable);
    echo "✅ Created rider_vehicle_attachments table\n";
    
    // echo "\n🎉 Riders support and vehicle tables migration completed successfully!\n";
    // echo "Created 7 additional tables for comprehensive rider management.\n";
    // echo "\nSupport and vehicle tables created:\n";
    // echo "1. rider_vehicles - Vehicle management\n";
    // echo "2. rider_withdrawal_requests - Withdrawal requests (integrates with wallet)\n";
    // echo "3. rider_promotions - Incentive programs\n";
    // echo "4. rider_promotion_claims - Promotion claims\n";
    // echo "5. rider_referrals - Referral system\n";
    // echo "6. rider_incidents - Incident reporting\n";
    // echo "7. rider_incident_attachments - Incident documentation\n";
    // echo "\nNote: Payment handling uses existing wallet system for all users.\n";
    // echo "Note: Support tickets and notifications are now global for all user types.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
