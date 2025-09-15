<?php
/**
 * Migration Script: Create User Notification Preferences Table
 * This table stores user preferences for different notification channels
 */

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting migration: Creating user notification preferences table...\n";
    
    // Create user notification preferences table
    $createNotificationPreferencesTable = "
    CREATE TABLE IF NOT EXISTS user_notification_preferences (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        user_type ENUM('merchant', 'user', 'rider') NOT NULL,
        
        -- Push Notification Preferences
        push_notifications_enabled BOOLEAN DEFAULT TRUE,
        push_order_notifications BOOLEAN DEFAULT TRUE,
        push_payment_notifications BOOLEAN DEFAULT TRUE,
        push_delivery_notifications BOOLEAN DEFAULT TRUE,
        push_promotional_notifications BOOLEAN DEFAULT FALSE,
        push_system_notifications BOOLEAN DEFAULT TRUE,
        push_support_notifications BOOLEAN DEFAULT TRUE,
        
        -- SMS Notification Preferences
        sms_notifications_enabled BOOLEAN DEFAULT TRUE,
        sms_order_notifications BOOLEAN DEFAULT TRUE,
        sms_payment_notifications BOOLEAN DEFAULT TRUE,
        sms_delivery_notifications BOOLEAN DEFAULT TRUE,
        sms_promotional_notifications BOOLEAN DEFAULT FALSE,
        sms_system_notifications BOOLEAN DEFAULT TRUE,
        sms_support_notifications BOOLEAN DEFAULT TRUE,
        
        -- Email Notification Preferences (Always ON - cannot be disabled)
        email_notifications_enabled BOOLEAN DEFAULT TRUE COMMENT 'Always TRUE - cannot be disabled',
        email_order_notifications BOOLEAN DEFAULT TRUE,
        email_payment_notifications BOOLEAN DEFAULT TRUE,
        email_delivery_notifications BOOLEAN DEFAULT TRUE,
        email_promotional_notifications BOOLEAN DEFAULT FALSE,
        email_system_notifications BOOLEAN DEFAULT TRUE,
        email_support_notifications BOOLEAN DEFAULT TRUE,
        
        -- Notification Timing Preferences
        quiet_hours_start TIME DEFAULT '22:00:00',
        quiet_hours_end TIME DEFAULT '08:00:00',
        quiet_hours_enabled BOOLEAN DEFAULT FALSE,
        timezone VARCHAR(50) DEFAULT 'Africa/Lagos',
        
        -- SMS Billing Preferences (Future feature)
        sms_billing_enabled BOOLEAN DEFAULT FALSE COMMENT 'Future: Charge for SMS notifications',
        sms_billing_wallet_enabled BOOLEAN DEFAULT FALSE COMMENT 'Future: Pay via wallet',
        sms_billing_paystack_enabled BOOLEAN DEFAULT FALSE COMMENT 'Future: Pay via Paystack',
        
        -- Metadata
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        -- Constraints
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_preferences (user_id, user_type),
        INDEX idx_user_type (user_type),
        INDEX idx_push_enabled (push_notifications_enabled),
        INDEX idx_sms_enabled (sms_notifications_enabled),
        INDEX idx_email_enabled (email_notifications_enabled)
    )";
    
    $conn->exec($createNotificationPreferencesTable);
    echo "✅ Created user notification preferences table\n";
    
    // Create notification templates table
    $createNotificationTemplatesTable = "
    CREATE TABLE IF NOT EXISTS notification_templates (
        id INT PRIMARY KEY AUTO_INCREMENT,
        template_key VARCHAR(100) UNIQUE NOT NULL,
        template_name VARCHAR(255) NOT NULL,
        template_category ENUM('order', 'payment', 'delivery', 'system', 'promotion', 'support') NOT NULL,
        
        -- Template Content
        push_title VARCHAR(255) NOT NULL,
        push_body TEXT NOT NULL,
        sms_message TEXT NOT NULL,
        email_subject VARCHAR(255) NOT NULL,
        email_body TEXT NOT NULL,
        
        -- Template Variables (JSON format)
        variables JSON COMMENT 'Available variables for template substitution',
        
        -- Template Settings
        is_active BOOLEAN DEFAULT TRUE,
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        user_types JSON COMMENT 'Array of user types this template applies to',
        
        -- Metadata
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_template_key (template_key),
        INDEX idx_template_category (template_category),
        INDEX idx_is_active (is_active),
        INDEX idx_user_types (user_types)
    )";
    
    $conn->exec($createNotificationTemplatesTable);
    echo "✅ Created notification templates table\n";
    
    // Create notification history table
    $createNotificationHistoryTable = "
    CREATE TABLE IF NOT EXISTS notification_history (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        user_type ENUM('merchant', 'user', 'rider') NOT NULL,
        template_key VARCHAR(100) NOT NULL,
        
        -- Notification Content
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        notification_type ENUM('push', 'sms', 'email') NOT NULL,
        
        -- Delivery Status
        status ENUM('pending', 'sent', 'delivered', 'failed', 'bounced') DEFAULT 'pending',
        delivery_attempts INT DEFAULT 0,
        last_attempt_at TIMESTAMP NULL,
        delivered_at TIMESTAMP NULL,
        
        -- External References
        external_id VARCHAR(255) NULL COMMENT 'FCM message ID, SMS ID, Email ID',
        external_provider VARCHAR(50) NULL COMMENT 'fcm, termii, smtp',
        
        -- Cost Tracking (for SMS)
        cost_amount DECIMAL(10,4) DEFAULT 0.00,
        cost_currency VARCHAR(3) DEFAULT 'NGN',
        
        -- Reference Data
        reference_id INT NULL COMMENT 'Order ID, Payment ID, etc.',
        reference_type VARCHAR(50) NULL COMMENT 'order, payment, delivery, etc.',
        
        -- Metadata
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_notifications (user_id, user_type, created_at),
        INDEX idx_template_key (template_key),
        INDEX idx_status (status),
        INDEX idx_notification_type (notification_type),
        INDEX idx_reference (reference_id, reference_type),
        INDEX idx_created_at (created_at)
    )";
    
    $conn->exec($createNotificationHistoryTable);
    echo "✅ Created notification history table\n";
    
    // Insert default notification templates
    $insertDefaultTemplates = "
    INSERT INTO notification_templates (template_key, template_name, template_category, push_title, push_body, sms_message, email_subject, email_body, variables, user_types) VALUES
    ('new_order', 'New Order Received', 'order', 'New Order Received', 'You have received a new order #{order_number} from {customer_name}. Total: ₦{order_total}', 'New order #{order_number} from {customer_name}. Total: ₦{order_total}. View details in Runnix app.', 'New Order Received - #{order_number}', '<h2>New Order Received</h2><p>You have received a new order from {customer_name}.</p><p><strong>Order Number:</strong> #{order_number}</p><p><strong>Total Amount:</strong> ₦{order_total}</p><p><strong>Order Time:</strong> {order_time}</p>', '[\"order_number\", \"customer_name\", \"order_total\", \"order_time\"]', '[\"merchant\"]'),
    
    ('order_confirmed', 'Order Confirmed', 'order', 'Order Confirmed', 'Order #{order_number} has been confirmed and is being prepared.', 'Order #{order_number} confirmed and being prepared. Estimated ready time: {estimated_time}.', 'Order Confirmed - #{order_number}', '<h2>Order Confirmed</h2><p>Your order #{order_number} has been confirmed and is being prepared.</p><p><strong>Estimated Ready Time:</strong> {estimated_time}</p>', '[\"order_number\", \"estimated_time\"]', '[\"user\"]'),
    
    ('payment_received', 'Payment Received', 'payment', 'Payment Received', 'Payment of ₦{amount} received for order #{order_number}.', 'Payment of ₦{amount} received for order #{order_number}.', 'Payment Received - #{order_number}', '<h2>Payment Received</h2><p>Payment of ₦{amount} has been received for order #{order_number}.</p><p><strong>Payment Method:</strong> {payment_method}</p><p><strong>Transaction ID:</strong> {transaction_id}</p>', '[\"amount\", \"order_number\", \"payment_method\", \"transaction_id\"]', '[\"merchant\"]'),
    
    ('rider_assigned', 'Rider Assigned', 'delivery', 'Rider Assigned', 'Rider {rider_name} has been assigned to deliver order #{order_number}.', 'Rider {rider_name} assigned to deliver order #{order_number}. Contact: {rider_phone}', 'Rider Assigned - #{order_number}', '<h2>Rider Assigned</h2><p>Rider {rider_name} has been assigned to deliver your order #{order_number}.</p><p><strong>Rider Contact:</strong> {rider_phone}</p><p><strong>Estimated Delivery:</strong> {estimated_delivery}</p>', '[\"rider_name\", \"rider_phone\", \"order_number\", \"estimated_delivery\"]', '[\"user\", \"merchant\"]'),
    
    ('order_delivered', 'Order Delivered', 'delivery', 'Order Delivered', 'Order #{order_number} has been successfully delivered to {customer_name}.', 'Order #{order_number} delivered to {customer_name}. Thank you for using Runnix!', 'Order Delivered - #{order_number}', '<h2>Order Delivered</h2><p>Order #{order_number} has been successfully delivered to {customer_name}.</p><p><strong>Delivery Time:</strong> {delivery_time}</p><p>Thank you for using Runnix!</p>', '[\"order_number\", \"customer_name\", \"delivery_time\"]', '[\"merchant\", \"rider\"]'),
    
    ('system_maintenance', 'System Maintenance', 'system', 'System Maintenance', 'Scheduled maintenance will occur on {maintenance_date} from {start_time} to {end_time}.', 'System maintenance scheduled for {maintenance_date} from {start_time} to {end_time}.', 'System Maintenance Notice', '<h2>System Maintenance Notice</h2><p>Scheduled maintenance will occur on {maintenance_date} from {start_time} to {end_time}.</p><p>We apologize for any inconvenience.</p>', '[\"maintenance_date\", \"start_time\", \"end_time\"]', '[\"merchant\", \"user\", \"rider\"]')
    ";
    
    $conn->exec($insertDefaultTemplates);
    echo "✅ Inserted default notification templates\n";
    
    echo "\n🎉 User notification preferences migration completed successfully!\n";
    echo "Created 3 tables for comprehensive notification management:\n";
    echo "1. user_notification_preferences - User preferences for all notification channels\n";
    echo "2. notification_templates - Reusable notification templates\n";
    echo "3. notification_history - Complete notification delivery history\n";
    echo "\nDefault templates created for:\n";
    echo "- New Order Received (merchants)\n";
    echo "- Order Confirmed (users)\n";
    echo "- Payment Received (merchants)\n";
    echo "- Rider Assigned (users, merchants)\n";
    echo "- Order Delivered (merchants, riders)\n";
    echo "- System Maintenance (all users)\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
