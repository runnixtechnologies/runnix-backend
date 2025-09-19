-- Migration: Create Orders System Tables
-- This creates the complete orders system for the multivendor marketplace

-- 1. Orders table (main orders)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    merchant_id INT NOT NULL,
    rider_id INT NULL,
    store_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'preparing', 'ready_for_pickup', 'in_transit', 'delivered', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    final_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method ENUM('cash', 'card', 'wallet', 'bank_transfer') DEFAULT 'cash',
    
    -- Delivery Information
    delivery_address TEXT NOT NULL,
    delivery_instructions TEXT NULL,
    delivery_latitude DECIMAL(10,8) NULL,
    delivery_longitude DECIMAL(10,8) NULL,
    
    -- Customer Notes
    customer_note TEXT NULL,
    merchant_note TEXT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,
    ready_at TIMESTAMP NULL,
    picked_up_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    
    -- Foreign Keys
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (merchant_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (rider_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_customer_id (customer_id),
    INDEX idx_merchant_id (merchant_id),
    INDEX idx_rider_id (rider_id),
    INDEX idx_store_id (store_id),
    INDEX idx_status (status),
    INDEX idx_order_number (order_number),
    INDEX idx_created_at (created_at)
);

-- 2. Order Items table (items within each order)
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    
    -- Item Details
    item_image VARCHAR(500) NULL,
    item_description TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    
    INDEX idx_order_id (order_id),
    INDEX idx_item_id (item_id)
);

-- 3. Order Selections table (add-ons, sides, customizations)
CREATE TABLE IF NOT EXISTS order_selections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_item_id INT NOT NULL,
    selection_type ENUM('addon', 'side', 'section', 'customization') NOT NULL,
    selection_name VARCHAR(255) NOT NULL,
    selection_price DECIMAL(10,2) DEFAULT 0.00,
    quantity INT DEFAULT 1,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    
    INDEX idx_order_item_id (order_item_id),
    INDEX idx_selection_type (selection_type)
);

-- 4. Order Status History table (tracking order status changes)
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'preparing', 'ready_for_pickup', 'in_transit', 'delivered', 'cancelled') NOT NULL,
    changed_by INT NULL, -- user_id who changed the status
    change_reason TEXT NULL,
    notes TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_order_id (order_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- 5. Order Notifications table (for push notifications)
CREATE TABLE IF NOT EXISTS order_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    notification_type ENUM('new_order', 'order_accepted', 'order_ready', 'order_picked_up', 'order_delivered', 'order_cancelled') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_order_id (order_id),
    INDEX idx_user_id (user_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- 6. Delivery Tracking table (for real-time tracking)
CREATE TABLE IF NOT EXISTS delivery_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    rider_id INT NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(10,8) NOT NULL,
    address VARCHAR(500) NULL,
    status ENUM('picked_up', 'in_transit', 'near_destination', 'arrived', 'delivered') NOT NULL,
    estimated_delivery_time INT NULL, -- minutes
    actual_delivery_time INT NULL, -- minutes
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (rider_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_order_id (order_id),
    INDEX idx_rider_id (rider_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- 7. Package Delivery table (for send package functionality)
CREATE TABLE IF NOT EXISTS package_deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_number VARCHAR(20) UNIQUE NOT NULL,
    sender_id INT NOT NULL,
    receiver_name VARCHAR(255) NOT NULL,
    receiver_phone VARCHAR(20) NOT NULL,
    receiver_address TEXT NOT NULL,
    receiver_latitude DECIMAL(10,8) NULL,
    receiver_longitude DECIMAL(10,8) NULL,
    rider_id INT NULL,
    
    package_description TEXT NULL,
    package_value DECIMAL(10,2) DEFAULT 0.00,
    delivery_fee DECIMAL(10,2) NOT NULL,
    insurance_fee DECIMAL(10,2) DEFAULT 0.00,
    
    status ENUM('pending', 'accepted', 'picked_up', 'in_transit', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    
    pickup_instructions TEXT NULL,
    delivery_instructions TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    picked_up_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (rider_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_sender_id (sender_id),
    INDEX idx_rider_id (rider_id),
    INDEX idx_package_number (package_number),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Add comments to tables
ALTER TABLE orders COMMENT = 'Main orders table for all store types';
ALTER TABLE order_items COMMENT = 'Items within each order';
ALTER TABLE order_selections COMMENT = 'Add-ons, sides, and customizations for order items';
ALTER TABLE order_status_history COMMENT = 'History of order status changes';
ALTER TABLE order_notifications COMMENT = 'Push notifications for orders';
ALTER TABLE delivery_tracking COMMENT = 'Real-time delivery tracking';
ALTER TABLE package_deliveries COMMENT = 'Package delivery system (send package feature)';
