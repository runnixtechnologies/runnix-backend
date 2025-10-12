<?php
/**
 * Migration Script: Create Riders Database Schema
 * This creates all necessary tables for the riders aspect of the logistics system
 */

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting migration: Creating riders database schema...\n";
    
    // 1. Create riders table
    $createRidersTable = "
    CREATE TABLE IF NOT EXISTS riders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        rider_code VARCHAR(20) UNIQUE NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(255),
        profile_image VARCHAR(255),
        id_card_front VARCHAR(255),
        id_card_back VARCHAR(255),
        vehicle_type ENUM('motorcycle', 'bicycle', 'car', 'van') NOT NULL,
        vehicle_model VARCHAR(100),
        vehicle_color VARCHAR(50),
        license_plate VARCHAR(20),
        license_number VARCHAR(50),
        insurance_number VARCHAR(50),
        emergency_contact_name VARCHAR(100),
        emergency_contact_phone VARCHAR(20),
        emergency_contact_relationship VARCHAR(50),
        current_latitude DECIMAL(10, 8) DEFAULT NULL,
        current_longitude DECIMAL(11, 8) DEFAULT NULL,
        current_address TEXT,
        is_online BOOLEAN DEFAULT FALSE,
        is_available BOOLEAN DEFAULT FALSE,
        is_verified BOOLEAN DEFAULT FALSE,
        verification_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        verification_notes TEXT,
        status ENUM('active', 'inactive', 'suspended', 'blocked') DEFAULT 'active',
        rating DECIMAL(3, 2) DEFAULT 0.00,
        total_orders INT DEFAULT 0,
        total_earnings DECIMAL(10, 2) DEFAULT 0.00,
        total_distance_km DECIMAL(10, 2) DEFAULT 0.00,
        joined_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_active TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_rider_code (rider_code),
        INDEX idx_phone (phone),
        INDEX idx_status (status),
        INDEX idx_verification_status (verification_status),
        INDEX idx_location (current_latitude, current_longitude),
        INDEX idx_online_available (is_online, is_available)
    )";
    
    $conn->exec($createRidersTable);
    echo "✅ Created riders table\n";
    
    // 2. Create delivery_requests table
    $createDeliveryRequestsTable = "
    CREATE TABLE IF NOT EXISTS delivery_requests (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL,
        customer_id INT NOT NULL,
        store_id INT NOT NULL,
        rider_id INT NULL,
        pickup_address TEXT NOT NULL,
        pickup_latitude DECIMAL(10, 8) NOT NULL,
        pickup_longitude DECIMAL(11, 8) NOT NULL,
        delivery_address TEXT NOT NULL,
        delivery_latitude DECIMAL(10, 8) NOT NULL,
        delivery_longitude DECIMAL(11, 8) NOT NULL,
        customer_name VARCHAR(100) NOT NULL,
        customer_phone VARCHAR(20) NOT NULL,
        customer_instructions TEXT,
        estimated_distance_km DECIMAL(10, 2),
        estimated_duration_minutes INT,
        base_fare DECIMAL(10, 2) NOT NULL,
        distance_fare DECIMAL(10, 2) DEFAULT 0.00,
        time_fare DECIMAL(10, 2) DEFAULT 0.00,
        surge_fare DECIMAL(10, 2) DEFAULT 0.00,
        total_fare DECIMAL(10, 2) NOT NULL,
        payment_method ENUM('cash', 'card', 'wallet', 'online') NOT NULL,
        status ENUM('pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'cancelled', 'failed') DEFAULT 'pending',
        priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
        assigned_at TIMESTAMP NULL,
        picked_up_at TIMESTAMP NULL,
        delivered_at TIMESTAMP NULL,
        cancelled_at TIMESTAMP NULL,
        cancelled_by ENUM('customer', 'rider', 'system', 'store') NULL,
        cancellation_reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_rider_id (rider_id),
        INDEX idx_order_id (order_id),
        INDEX idx_customer_id (customer_id),
        INDEX idx_store_id (store_id),
        INDEX idx_priority (priority),
        INDEX idx_created_at (created_at)
    )";
    
    $conn->exec($createDeliveryRequestsTable);
    echo "✅ Created delivery_requests table\n";
    
    // 3. Create rider_earnings table
    $createRiderEarningsTable = "
    CREATE TABLE IF NOT EXISTS rider_earnings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        rider_id INT NOT NULL,
        order_id INT NOT NULL,
        base_fare DECIMAL(10, 2) NOT NULL,
        distance_fare DECIMAL(10, 2) DEFAULT 0.00,
        time_fare DECIMAL(10, 2) DEFAULT 0.00,
        surge_fare DECIMAL(10, 2) DEFAULT 0.00,
        tip_amount DECIMAL(10, 2) DEFAULT 0.00,
        platform_fee DECIMAL(10, 2) DEFAULT 0.00,
        total_earned DECIMAL(10, 2) NOT NULL,
        payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
        payment_method ENUM('wallet', 'bank_transfer', 'cash') DEFAULT 'wallet',
        transaction_reference VARCHAR(100),
        paid_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE CASCADE,
        INDEX idx_rider_earnings (rider_id, created_at),
        INDEX idx_payment_status (payment_status),
        INDEX idx_order_id (order_id)
    )";
    
    $conn->exec($createRiderEarningsTable);
    echo "✅ Created rider_earnings table\n";
    
    // 4. Create delivery_tracking table
    $createDeliveryTrackingTable = "
    CREATE TABLE IF NOT EXISTS delivery_tracking (
        id INT PRIMARY KEY AUTO_INCREMENT,
        delivery_request_id INT NOT NULL,
        rider_id INT NOT NULL,
        status ENUM('assigned', 'heading_to_pickup', 'arrived_at_pickup', 'picked_up', 'heading_to_delivery', 'arrived_at_delivery', 'delivered', 'cancelled') NOT NULL,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        address TEXT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_delivery_tracking (delivery_request_id, created_at),
        INDEX idx_rider_tracking (rider_id, created_at)
    )";
    
    $conn->exec($createDeliveryTrackingTable);
    echo "✅ Created delivery_tracking table\n";
    
    // 5. Create rider_ratings table
    $createRiderRatingsTable = "
    CREATE TABLE IF NOT EXISTS rider_ratings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        rider_id INT NOT NULL,
        order_id INT NOT NULL,
        customer_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        review TEXT,
        is_anonymous BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE CASCADE,
        FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_order_rating (order_id),
        INDEX idx_rider_rating (rider_id, rating),
        INDEX idx_created_at (created_at)
    )";
    
    $conn->exec($createRiderRatingsTable);
    echo "✅ Created rider_ratings table\n";
    
    echo "\n🎉 Core riders database schema migration completed successfully!\n";
    echo "Created 5 core tables for rider management system.\n";
    echo "\nCore tables created:\n";
    echo "1. riders - Main rider profiles and information\n";
    echo "2. delivery_requests - Delivery order management\n";
    echo "3. rider_earnings - Earnings tracking per delivery\n";
    echo "4. delivery_tracking - Real-time delivery tracking\n";
    echo "5. rider_ratings - Customer ratings and reviews\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
