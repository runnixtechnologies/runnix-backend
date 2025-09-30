-- Add item_type column to order_items table
-- This explicitly tracks whether the item is from food_items or items table

ALTER TABLE order_items 
ADD COLUMN item_type ENUM('item', 'food_item') NOT NULL DEFAULT 'item' AFTER food_item_id;

-- Add index for faster filtering by type
ALTER TABLE order_items
ADD INDEX idx_item_type (item_type);

