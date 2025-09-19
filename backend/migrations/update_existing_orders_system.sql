-- Migration: Update Existing Orders System Tables
-- This extends your existing orders and order_items tables with new features

-- 1. Update existing orders table to add missing columns
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS order_number VARCHAR(20) UNIQUE AFTER id,
ADD COLUMN IF NOT EXISTS customer_id INT AFTER user_id,
ADD COLUMN IF NOT EXISTS merchant_id INT AFTER customer_id,
ADD COLUMN IF NOT EXISTS delivery_fee DECIMAL(10,2) DEFAULT 0.00 AFTER total_amount,
ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(10,2) DEFAULT 0.00 AFTER delivery_fee,
ADD COLUMN IF NOT EXISTS final_amount DECIMAL(10,2) AFTER tax_amount,
ADD COLUMN IF NOT EXISTS delivery_instructions TEXT AFTER delivery_address,
ADD COLUMN IF NOT EXISTS delivery_latitude DECIMAL(10,8) AFTER delivery_instructions,
ADD COLUMN IF NOT EXISTS delivery_longitude DECIMAL(10,8) AFTER delivery_latitude,
ADD COLUMN IF NOT EXISTS customer_note TEXT AFTER delivery_longitude,
ADD COLUMN IF NOT EXISTS merchant_note TEXT AFTER customer_note,
ADD COLUMN IF NOT EXISTS accepted_at TIMESTAMP NULL AFTER updated_at,
ADD COLUMN IF NOT EXISTS ready_at TIMESTAMP NULL AFTER accepted_at,
ADD COLUMN IF NOT EXISTS picked_up_at TIMESTAMP NULL AFTER ready_at,
ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL AFTER picked_up_at,
ADD COLUMN IF NOT EXISTS cancelled_at TIMESTAMP NULL AFTER delivered_at;

-- Add foreign key constraints for the new columns
ALTER TABLE orders 
ADD CONSTRAINT fk_orders_customer_id FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
ADD CONSTRAINT fk_orders_merchant_id FOREIGN KEY (merchant_id) REFERENCES users(id) ON DELETE CASCADE;

-- Update status enum to include new statuses
ALTER TABLE orders 
MODIFY COLUMN status ENUM('pending', 'accepted', 'preparing', 'ready_for_pickup', 'in_transit', 'delivered', 'cancelled', 'processing', 'completed') DEFAULT 'pending';

-- Add indexes for new columns
ALTER TABLE orders 
ADD INDEX IF NOT EXISTS idx_customer_id (customer_id),
ADD INDEX IF NOT EXISTS idx_merchant_id (merchant_id),
ADD INDEX IF NOT EXISTS idx_order_number (order_number);

-- 2. Update existing order_items table to add missing columns
ALTER TABLE order_items 
ADD COLUMN IF NOT EXISTS item_name VARCHAR(255) AFTER item_id,
ADD COLUMN IF NOT EXISTS item_image VARCHAR(500) AFTER item_name,
ADD COLUMN IF NOT EXISTS item_description TEXT AFTER item_image,
ADD COLUMN IF NOT EXISTS total_price DECIMAL(10,2) AFTER price,
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER side_id;

-- Add indexes for order_items
ALTER TABLE order_items 
ADD INDEX IF NOT EXISTS idx_order_id (order_id),
ADD INDEX IF NOT EXISTS idx_item_id (order_id);

-- 3. Create new tables for enhanced functionality

-- Order Selections table (add-ons, sides, customizations)
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

-- Order Status History table (tracking order status changes)
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'preparing', 'ready_for_pickup', 'in_transit', 'delivered', 'cancelled', 'processing', 'completed') NOT NULL,
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

-- Order Notifications table (for push notifications)
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

-- Delivery Tracking table (for real-time tracking)
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

-- Package Delivery table (for send package functionality)
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

-- 4. Data migration: Populate missing fields in existing orders
-- Generate order numbers for existing orders
UPDATE orders 
SET order_number = CONCAT('ORD', DATE_FORMAT(created_at, '%Y%m%d'), LPAD(id, 4, '0'))
WHERE order_number IS NULL;

-- Set customer_id = user_id (user_id is the customer who placed the order)
UPDATE orders 
SET customer_id = user_id
WHERE customer_id IS NULL;

-- Set merchant_id from stores table (store owner's user_id)
UPDATE orders o
JOIN stores s ON o.store_id = s.id
SET o.merchant_id = s.user_id
WHERE o.merchant_id IS NULL;

-- Set final_amount = total_amount for existing orders
UPDATE orders 
SET final_amount = total_amount 
WHERE final_amount IS NULL;

-- 5. Data migration: Populate missing fields in existing order_items
-- Get item names from items table
UPDATE order_items oi
JOIN items i ON oi.item_id = i.id AND oi.item_type = 'item'
SET oi.item_name = i.name,
    oi.item_image = i.photo,
    oi.item_description = i.description,
    oi.total_price = oi.quantity * oi.price
WHERE oi.item_name IS NULL;

-- Get item names from food_items table
UPDATE order_items oi
JOIN food_items fi ON oi.item_id = fi.id AND oi.item_type = 'food_item'
SET oi.item_name = fi.name,
    oi.item_image = fi.photo,
    oi.item_description = fi.short_description,
    oi.total_price = oi.quantity * oi.price
WHERE oi.item_name IS NULL;

-- Add comments to tables
ALTER TABLE orders COMMENT = 'Main orders table for all store types (extended)';
ALTER TABLE order_items COMMENT = 'Items within each order (extended)';
ALTER TABLE order_selections COMMENT = 'Add-ons, sides, and customizations for order items';
ALTER TABLE order_status_history COMMENT = 'History of order status changes';
ALTER TABLE order_notifications COMMENT = 'Push notifications for orders';
ALTER TABLE delivery_tracking COMMENT = 'Real-time delivery tracking';
ALTER TABLE package_deliveries COMMENT = 'Package delivery system (send package feature)';
