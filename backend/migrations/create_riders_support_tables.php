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
    
    // 1. Create rider_vehicles table
    $createRiderVehiclesTable = "
    CREATE TABLE IF NOT EXISTS rider_vehicles (
        id INT PRIMARY KEY AUTO_INCREMENT,
        rider_id INT NOT NULL,
        vehicle_type ENUM('motorcycle', 'bicycle', 'car', 'van') NOT NULL,
        brand VARCHAR(100),
        model VARCHAR(100),
        year INT,
        color VARCHAR(50),
        license_plate VARCHAR(20),
        engine_number VARCHAR(50),
        chassis_number VARCHAR(50),
        registration_number VARCHAR(50),
        insurance_number VARCHAR(50),
        insurance_expiry DATE,
        registration_expiry DATE,
        is_primary BOOLEAN DEFAULT FALSE,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE CASCADE,
        INDEX idx_rider_vehicles (rider_id, is_active),
        INDEX idx_vehicle_type (vehicle_type)
    )";
    
    $conn->exec($createRiderVehiclesTable);
    echo "✅ Created rider_vehicles table\n";

    
    // 2. Create rider_withdrawal_requests table
    $createRiderWithdrawalRequestsTable = "
    CREATE TABLE IF NOT EXISTS rider_withdrawal_requests (
        id INT PRIMARY KEY AUTO_INCREMENT,
        rider_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        withdrawal_method ENUM('bank_transfer', 'mobile_money', 'cash_pickup') NOT NULL,
        bank_name VARCHAR(100),
        account_number VARCHAR(50),
        account_name VARCHAR(100),
        mobile_number VARCHAR(20),
        pickup_location VARCHAR(255),
        status ENUM('pending', 'approved', 'rejected', 'processed', 'completed') DEFAULT 'pending',
        processed_at TIMESTAMP NULL,
        processed_by INT NULL,
        rejection_reason TEXT,
        wallet_transaction_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE CASCADE,
        FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_rider_withdrawals (rider_id, status),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    )";
    
    $conn->exec($createRiderWithdrawalRequestsTable);
    echo "✅ Created rider_withdrawal_requests table\n";
    
    // 3. Create rider_promotions table
    $createRiderPromotionsTable = "
    CREATE TABLE IF NOT EXISTS rider_promotions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        promotion_type ENUM('bonus_per_delivery', 'bonus_per_distance', 'bonus_per_rating', 'weekly_bonus', 'monthly_bonus', 'referral_bonus') NOT NULL,
        bonus_amount DECIMAL(10, 2) NOT NULL,
        conditions JSON,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_promotion_active (is_active),
        INDEX idx_date_range (start_date, end_date)
    )";
    
    $conn->exec($createRiderPromotionsTable);
    echo "✅ Created rider_promotions table\n";
    
    // 4. Create rider_promotion_claims table
    $createRiderPromotionClaimsTable = "
    CREATE TABLE IF NOT EXISTS rider_promotion_claims (
        id INT PRIMARY KEY AUTO_INCREMENT,
        rider_id INT NOT NULL,
        promotion_id INT NOT NULL,
        claim_amount DECIMAL(10, 2) NOT NULL,
        claim_data JSON,
        status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
        approved_by INT NULL,
        approved_at TIMESTAMP NULL,
        rejection_reason TEXT,
        paid_at TIMESTAMP NULL,
        wallet_transaction_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE CASCADE,
        FOREIGN KEY (promotion_id) REFERENCES rider_promotions(id) ON DELETE CASCADE,
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_rider_claims (rider_id, status),
        INDEX idx_promotion_claims (promotion_id, status),
        INDEX idx_status (status)
    )";
    
    $conn->exec($createRiderPromotionClaimsTable);
    echo "✅ Created rider_promotion_claims table\n";
    
    // 5. Create rider_referrals table
    $createRiderReferralsTable = "
    CREATE TABLE IF NOT EXISTS rider_referrals (
        id INT PRIMARY KEY AUTO_INCREMENT,
        referrer_rider_id INT NOT NULL,
        referred_rider_id INT NOT NULL,
        referral_code VARCHAR(20) NOT NULL,
        bonus_amount DECIMAL(10, 2) DEFAULT 0.00,
        status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
        completed_at TIMESTAMP NULL,
        wallet_transaction_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (referrer_rider_id) REFERENCES riders(id) ON DELETE CASCADE,
        FOREIGN KEY (referred_rider_id) REFERENCES riders(id) ON DELETE CASCADE,
        UNIQUE KEY unique_referral (referred_rider_id),
        INDEX idx_referrer (referrer_rider_id, status),
        INDEX idx_referral_code (referral_code)
    )";
    
    $conn->exec($createRiderReferralsTable);
    echo "✅ Created rider_referrals table\n";
    
    // 6. Create rider_incidents table
    $createRiderIncidentsTable = "
    CREATE TABLE IF NOT EXISTS rider_incidents (
        id INT PRIMARY KEY AUTO_INCREMENT,
        rider_id INT NOT NULL,
        incident_type ENUM('accident', 'theft', 'harassment', 'vehicle_breakdown', 'weather', 'other') NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        location_latitude DECIMAL(10, 8),
        location_longitude DECIMAL(11, 8),
        location_address TEXT,
        severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
        status ENUM('reported', 'investigating', 'resolved', 'closed') DEFAULT 'reported',
        reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP NULL,
        resolution_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE CASCADE,
        INDEX idx_rider_incidents (rider_id, status),
        INDEX idx_incident_type (incident_type),
        INDEX idx_severity (severity)
    )";
    
    $conn->exec($createRiderIncidentsTable);
    echo "✅ Created rider_incidents table\n";
    
    // 7. Create rider_incident_attachments table
    $createRiderIncidentAttachmentsTable = "
    CREATE TABLE IF NOT EXISTS rider_incident_attachments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        incident_id INT NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_type VARCHAR(100),
        file_size INT,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (incident_id) REFERENCES rider_incidents(id) ON DELETE CASCADE,
        INDEX idx_incident_attachments (incident_id)
    )";
    
    $conn->exec($createRiderIncidentAttachmentsTable);
    echo "✅ Created rider_incident_attachments table\n";
    
    echo "\n🎉 Riders support and vehicle tables migration completed successfully!\n";
    echo "Created 7 additional tables for comprehensive rider management.\n";
    echo "\nSupport and vehicle tables created:\n";
    echo "1. rider_vehicles - Vehicle management\n";
    echo "2. rider_withdrawal_requests - Withdrawal requests (integrates with wallet)\n";
    echo "3. rider_promotions - Incentive programs\n";
    echo "4. rider_promotion_claims - Promotion claims\n";
    echo "5. rider_referrals - Referral system\n";
    echo "6. rider_incidents - Incident reporting\n";
    echo "7. rider_incident_attachments - Incident documentation\n";
    echo "\nNote: Payment handling uses existing wallet system for all users.\n";
    echo "Note: Support tickets and notifications are now global for all user types.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
