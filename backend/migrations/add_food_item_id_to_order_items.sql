-- Add food_item_id column to order_items table
-- This allows separate tracking of food items vs regular items

-- Step 1: Make item_id nullable (since we'll use either item_id OR food_item_id)
ALTER TABLE order_items 
MODIFY COLUMN item_id INT NULL;

-- Step 2: Add food_item_id column
ALTER TABLE order_items 
ADD COLUMN food_item_id INT NULL AFTER item_id;

-- Step 3: Add foreign key for food_item_id
-- Note: Only add this if your food_items table exists and has proper structure
ALTER TABLE order_items
ADD CONSTRAINT fk_order_items_food_item 
FOREIGN KEY (food_item_id) REFERENCES food_items(id) ON DELETE CASCADE;

-- Step 4: Add index for better performance
ALTER TABLE order_items
ADD INDEX idx_food_item_id (food_item_id);

-- Step 5: Add check constraint to ensure only one of item_id or food_item_id is set
-- MySQL 8.0.16+ supports CHECK constraints
ALTER TABLE order_items
ADD CONSTRAINT chk_item_or_food_item 
CHECK (
    (item_id IS NOT NULL AND food_item_id IS NULL) OR 
    (item_id IS NULL AND food_item_id IS NOT NULL)
);

