# Order Items Setup Guide

## Problem Solved
Prevent ID conflicts between `items` and `food_items` tables by using separate columns in `order_items`.

## Database Changes Required

### Step 1: Run the Migration
Execute this SQL in phpMyAdmin:

```sql
-- Make item_id nullable
ALTER TABLE order_items 
MODIFY COLUMN item_id INT NULL;

-- Add food_item_id column
ALTER TABLE order_items 
ADD COLUMN food_item_id INT NULL AFTER item_id;

-- Add foreign key
ALTER TABLE order_items
ADD CONSTRAINT fk_order_items_food_item 
FOREIGN KEY (food_item_id) REFERENCES food_items(id) ON DELETE CASCADE;

-- Add index
ALTER TABLE order_items
ADD INDEX idx_food_item_id (food_item_id);

-- Add check constraint (MySQL 8.0.16+)
ALTER TABLE order_items
ADD CONSTRAINT chk_item_or_food_item 
CHECK (
    (item_id IS NOT NULL AND food_item_id IS NULL) OR 
    (item_id IS NULL AND food_item_id IS NOT NULL)
);
```

**Note:** If the CHECK constraint fails (older MySQL), you can skip it. The application logic will handle the validation.

### Step 2: Verify Structure
After running the migration, verify:

```sql
DESCRIBE order_items;
```

You should see:
- `item_id` INT NULL
- `food_item_id` INT NULL (new column)

## How It Works

### For Food Items (from food_items table):
- `food_item_id` = the item ID
- `item_id` = NULL

### For Regular Items (from items table):
- `item_id` = the item ID
- `food_item_id` = NULL

### Example Order Items Records:

| id | order_id | item_id | food_item_id | quantity | price |
|----|----------|---------|--------------|----------|-------|
| 1  | 100      | 25      | NULL         | 2        | 1500  |
| 2  | 100      | NULL    | 52           | 1        | 5000  |
| 3  | 101      | NULL    | 48           | 3        | 2500  |

## Code Changes Made

### OrderController.php
- `addOrderItem()` now checks item type and uses correct column
- `isFoodItem()` helper determines if item is from food_items table
- Logs which column was used for debugging

### Order.php Model
- `getOrderDetails()` now joins both items and food_items tables
- Uses COALESCE to get name and photo from whichever table has data
- Returns item_type ('food_item' or 'item') for reference

## Testing

### Test Order Creation:
1. Create order with food items → Should use `food_item_id`
2. Create order with regular items → Should use `item_id`
3. Create order with mixed items → Should use correct column for each

### Check Database:
```sql
-- View order items with their types
SELECT 
    oi.id,
    oi.order_id,
    CASE 
        WHEN oi.food_item_id IS NOT NULL THEN CONCAT('Food Item: ', fi.name)
        WHEN oi.item_id IS NOT NULL THEN CONCAT('Regular Item: ', i.name)
    END as item_info,
    oi.quantity,
    oi.price
FROM order_items oi
LEFT JOIN food_items fi ON oi.food_item_id = fi.id
LEFT JOIN items i ON oi.item_id = i.id
ORDER BY oi.order_id DESC;
```

## Rollback (if needed)
If you need to rollback:

```sql
ALTER TABLE order_items 
DROP CONSTRAINT IF EXISTS chk_item_or_food_item;

ALTER TABLE order_items 
DROP FOREIGN KEY fk_order_items_food_item;

ALTER TABLE order_items 
DROP COLUMN food_item_id;

ALTER TABLE order_items 
MODIFY COLUMN item_id INT NOT NULL;
```

