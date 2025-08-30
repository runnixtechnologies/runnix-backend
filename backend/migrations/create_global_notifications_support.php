<?php
/**
 * Migration Script: Create Global Notifications and Support Tables
 * These tables are for all user types: merchants, users, and riders
 */

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting migration: Creating global notifications and support tables...\n";
    
    // 1. Create global notifications table
    $createGlobalNotificationsTable = "
    CREATE TABLE IF NOT EXISTS notifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        user_type ENUM('merchant', 'user', 'rider') NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('order', 'payment', 'system', 'promotion', 'delivery', 'support', 'security') NOT NULL,
        reference_id INT NULL,
        reference_type VARCHAR(50) NULL,
        is_read BOOLEAN DEFAULT FALSE,
        read_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_notifications (user_id, user_type, is_read),
        INDEX idx_type (type),
        INDEX idx_created_at (created_at)
    )";
    
    $conn->exec($createGlobalNotificationsTable);
    echo "✅ Created global notifications table\n";
    
    // 2. Create global support tickets table
    $createGlobalSupportTicketsTable = "
    CREATE TABLE IF NOT EXISTS support_tickets (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        user_type ENUM('merchant', 'user', 'rider') NOT NULL,
        ticket_number VARCHAR(20) UNIQUE NOT NULL,
        subject VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        category ENUM('technical', 'payment', 'order', 'delivery', 'account', 'billing', 'other') NOT NULL,
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
        assigned_to INT NULL,
        resolved_at TIMESTAMP NULL,
        resolution_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_tickets (user_id, user_type, status),
        INDEX idx_ticket_number (ticket_number),
        INDEX idx_status (status),
        INDEX idx_category (category)
    )";
    
    $conn->exec($createGlobalSupportTicketsTable);
    echo "✅ Created global support tickets table\n";
    
    // 3. Create global support messages table
    $createGlobalSupportMessagesTable = "
    CREATE TABLE IF NOT EXISTS support_messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        ticket_id INT NOT NULL,
        sender_id INT NOT NULL,
        sender_type ENUM('merchant', 'user', 'rider', 'support_agent', 'system') NOT NULL,
        message TEXT NOT NULL,
        attachments JSON,
        is_internal BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
        INDEX idx_ticket_messages (ticket_id, created_at),
        INDEX idx_sender (sender_id, sender_type)
    )";
    
    $conn->exec($createGlobalSupportMessagesTable);
    echo "✅ Created global support messages table\n";
    
    echo "\n🎉 Global notifications and support tables migration completed successfully!\n";
    echo "Created 3 global tables for all user types.\n";
    echo "\nGlobal tables created:\n";
    echo "1. notifications - Push notifications for all users\n";
    echo "2. support_tickets - Customer support for all users\n";
    echo "3. support_messages - Support conversations for all users\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}

