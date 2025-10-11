<?php
/**
 * Migration Script: Create Additional Riders Tables
 * This creates additional tables for comprehensive rider management
 */

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting migration: Creating additional riders tables...\n";
    
    // 1. Create rider_documents table
    $createRiderDocumentsTable = "
    CREATE TABLE IF NOT EXISTS rider_documents (
        id INT PRIMARY KEY AUTO_INCREMENT,
        rider_id INT NOT NULL,
        document_type ENUM('id_card', 'license', 'insurance', 'vehicle_registration', 'background_check', 'other') NOT NULL,
        document_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT,
        mime_type VARCHAR(100),
        is_verified BOOLEAN DEFAULT FALSE,
        verified_by INT,
        verified_at TIMESTAMP NULL,
        verification_notes TEXT,
        status ENUM('pending', 'approved', 'rejected', 'expired') DEFAULT 'pending',
        expiry_date DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE CASCADE,
        FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_rider_document_type (rider_id, document_type),
        INDEX idx_status (status)
    )";
    
    $conn->exec($createRiderDocumentsTable);
    echo "✅ Created rider_documents table\n";
    
    // 2. Create rider_schedules table
    $createRiderSchedulesTable = "
    CREATE TABLE IF NOT EXISTS rider_schedules (
        id INT PRIMARY KEY AUTO_INCREMENT,
        rider_id INT NOT NULL,
        day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE CASCADE,
        UNIQUE KEY unique_rider_schedule (rider_id, day_of_week),
        INDEX idx_rider_active (rider_id, is_active)
    )";
    
    $conn->exec($createRiderSchedulesTable);
    echo "✅ Created rider_schedules table\n";
    
    // 3. Create rider_availability table
    $createRiderAvailabilityTable = "
    CREATE TABLE IF NOT EXISTS rider_availability (
        id INT PRIMARY KEY AUTO_INCREMENT,
        rider_id INT NOT NULL,
        is_online BOOLEAN DEFAULT FALSE,
        is_available BOOLEAN DEFAULT FALSE,
        current_status ENUM('online', 'offline', 'busy', 'break', 'maintenance') DEFAULT 'offline',
        latitude DECIMAL(10, 8) DEFAULT NULL,
        longitude DECIMAL(11, 8) DEFAULT NULL,
        address TEXT,
        battery_level INT DEFAULT 100,
        last_location_update TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE CASCADE,
        UNIQUE KEY unique_rider_availability (rider_id),
        INDEX idx_online_available (is_online, is_available),
        INDEX idx_status (current_status),
        INDEX idx_location (latitude, longitude)
    )";
    
    $conn->exec($createRiderAvailabilityTable);
    echo "✅ Created rider_availability table\n";
    
    // 4. Create delivery_zones table
    $createDeliveryZonesTable = "
    CREATE TABLE IF NOT EXISTS delivery_zones (
        id INT PRIMARY KEY AUTO_INCREMENT,
        zone_name VARCHAR(100) NOT NULL,
        description TEXT,
        base_fare DECIMAL(10, 2) NOT NULL,
        per_km_rate DECIMAL(10, 2) NOT NULL,
        per_minute_rate DECIMAL(10, 2) NOT NULL,
        minimum_fare DECIMAL(10, 2) NOT NULL,
        maximum_distance_km DECIMAL(10, 2),
        estimated_delivery_time_minutes INT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_zone_active (is_active)
    )";
    
    $conn->exec($createDeliveryZonesTable);
    echo "✅ Created delivery_zones table\n";
    
    // 5. Create zone_coordinates table
    $createZoneCoordinatesTable = "
    CREATE TABLE IF NOT EXISTS zone_coordinates (
        id INT PRIMARY KEY AUTO_INCREMENT,
        zone_id INT NOT NULL,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        sequence_order INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (zone_id) REFERENCES delivery_zones(id) ON DELETE CASCADE,
        INDEX idx_zone_coordinates (zone_id, sequence_order)
    )";
    
    $conn->exec($createZoneCoordinatesTable);
    echo "✅ Created zone_coordinates table\n";
    
    // 6. Create rider_performance table
    $createRiderPerformanceTable = "
    CREATE TABLE IF NOT EXISTS rider_performance (
        id INT PRIMARY KEY AUTO_INCREMENT,
        rider_id INT NOT NULL,
        date DATE NOT NULL,
        total_deliveries INT DEFAULT 0,
        completed_deliveries INT DEFAULT 0,
        cancelled_deliveries INT DEFAULT 0,
        total_earnings DECIMAL(10, 2) DEFAULT 0.00,
        total_distance_km DECIMAL(10, 2) DEFAULT 0.00,
        total_time_hours DECIMAL(5, 2) DEFAULT 0.00,
        average_rating DECIMAL(3, 2) DEFAULT 0.00,
        on_time_deliveries INT DEFAULT 0,
        late_deliveries INT DEFAULT 0,
        customer_complaints INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE CASCADE,
        UNIQUE KEY unique_rider_date (rider_id, date),
        INDEX idx_rider_performance (rider_id, date),
        INDEX idx_date (date)
    )";
    
    $conn->exec($createRiderPerformanceTable);
    echo "✅ Created rider_performance table\n";
    
    echo "\n🎉 Additional riders tables migration completed successfully!\n";
    echo "Created 6 additional tables for comprehensive rider management.\n";
    echo "\nAdditional tables created:\n";
    echo "1. rider_documents - Document management for verification\n";
    echo "2. rider_schedules - Working schedules\n";
    echo "3. rider_availability - Real-time availability status\n";
    echo "4. delivery_zones - Service area definitions\n";
    echo "5. zone_coordinates - Geographic boundaries\n";
    echo "6. rider_performance - Performance analytics\n";
    echo "\nNote: Payment handling uses existing wallet system for all users.\n";
    echo "Note: Notifications and support tables are now global for all user types.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
