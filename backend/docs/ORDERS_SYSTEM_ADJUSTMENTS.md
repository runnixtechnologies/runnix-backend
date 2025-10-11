# Orders System Adjustments for Existing Database

## Overview
This document outlines the adjustments made to the Orders system to work with your existing `orders` and `order_items` tables.

## Existing Table Structure Analysis

### Your Current `orders` Table:
```sql
- id (int, primary key, auto_increment)
- user_id (int, indexed) - Customer ID
- rider_id (int, indexed, nullable)
- store_id (int, indexed)
- total_amount (decimal)
- status (enum: 'pending', 'processing', 'completed', 'cancel...')
- payment_method (varchar)
- payment_status (enum: 'paid', 'unpaid')
- delivery_address (text)
- created_at (datetime)
- updated_at (datetime)
```

### Your Current `order_items` Table:
```sql
- id (int, primary key, auto_increment)
- order_id (int, indexed)
- item_id (int, indexed)
- item_type (enum: 'item', 'food_item')
- quantity (int)
- price (decimal)
- discount (decimal, default 0.00)
- side_id (int, nullable)
```

## Adjustments Made

### 1. Database Migration (`update_existing_orders_system.sql`)
- **Extends existing tables** instead of creating new ones
- **Adds missing columns** to support new features:
  - `order_number` - Unique order identifier
  - `customer_id` - Separate from user_id for clarity
  - `merchant_id` - Store owner ID
  - `delivery_fee`, `tax_amount`, `final_amount` - Financial fields
  - `delivery_instructions`, `customer_note`, `merchant_note` - Communication fields
  - `delivery_latitude`, `delivery_longitude` - GPS coordinates
  - Status timestamps: `accepted_at`, `ready_at`, `picked_up_at`, `delivered_at`, `cancelled_at`

- **Updates status enum** to include new statuses:
  ```sql
  ENUM('pending', 'accepted', 'preparing', 'ready_for_pickup', 'in_transit', 'delivered', 'cancelled', 'processing', 'completed')
  ```

- **Adds missing columns to order_items**:
  - `item_name`, `item_image`, `item_description` - Item details
  - `total_price` - Calculated total
  - `created_at` - Timestamp

- **Creates new supporting tables**:
  - `order_selections` - Add-ons, sides, customizations
  - `order_status_history` - Status change tracking
  - `order_notifications` - Push notifications
  - `delivery_tracking` - Real-time GPS tracking
  - `package_deliveries` - Package delivery system

### 2. Data Migration
- **Generates order numbers** for existing orders: `ORD202401010001`
- **Populates customer_id and merchant_id** from existing data
- **Sets final_amount = total_amount** for existing orders
- **Fetches item details** from `items` and `food_items` tables

### 3. Model Updates (`Order.php`)
- **Modified createOrder()** to work with existing structure:
  ```php
  // Uses both user_id (for compatibility) and customer_id (for clarity)
  $stmt->bindParam(':user_id', $data['customer_id']);
  $stmt->bindParam(':customer_id', $data['customer_id']);
  ```

- **Updated addOrderItem()** to include existing fields:
  ```php
  // Includes item_type, discount, side_id from existing structure
  $stmt->bindParam(':item_type', $item['item_type'] ?? 'item');
  $stmt->bindParam(':discount', $item['discount'] ?? 0.00);
  $stmt->bindParam(':side_id', $item['side_id'] ?? null);
  ```

- **Modified queries** to handle both old and new field structures:
  ```php
  // Supports both merchant_id and user_id for backward compatibility
  WHERE (o.merchant_id = :merchant_id OR o.user_id = :merchant_id)
  ```

## Migration Instructions

### Step 1: Run the Migration
```bash
mysql -u username -p database_name < backend/migrations/update_existing_orders_system.sql
```

### Step 2: Verify Data Migration
```sql
-- Check that order numbers were generated
SELECT id, order_number, user_id, customer_id, merchant_id FROM orders LIMIT 5;

-- Check that item details were populated
SELECT order_id, item_id, item_name, item_type, total_price FROM order_items LIMIT 5;

-- Check that new tables were created
SHOW TABLES LIKE 'order_%';
```

### Step 3: Test the System
- Create a test order using the new API endpoints
- Verify that existing orders still work
- Test status updates and notifications

## Backward Compatibility

### What's Preserved:
- ✅ All existing orders remain functional
- ✅ Existing `user_id` field continues to work
- ✅ Current `item_type` enum values preserved
- ✅ Existing `side_id` relationships maintained
- ✅ All existing indexes and constraints

### What's Enhanced:
- ✅ New order numbering system
- ✅ Enhanced status tracking
- ✅ Real-time delivery tracking
- ✅ Push notifications
- ✅ Package delivery system
- ✅ Better financial tracking

## API Compatibility

### Existing Endpoints:
- All existing order-related endpoints continue to work
- No breaking changes to current functionality

### New Endpoints:
- `GET /api/get-merchant-orders.php` - Enhanced order listing
- `GET /api/get-order-details.php` - Detailed order view
- `POST /api/update-order-status.php` - Status management
- `GET /api/get-delivery-tracking.php` - Real-time tracking
- `GET /api/get-order-notifications.php` - Notifications
- `POST /api/send-package.php` - Package delivery

## Testing Checklist

- [ ] Run migration successfully
- [ ] Verify existing orders are accessible
- [ ] Test new order creation
- [ ] Test status updates
- [ ] Test delivery tracking
- [ ] Test notifications
- [ ] Test package delivery
- [ ] Verify analytics still work
- [ ] Test with different store types

## Rollback Plan

If issues arise, you can:
1. **Remove new columns** (they're nullable, so safe to remove)
2. **Drop new tables** (they don't affect existing functionality)
3. **Revert model changes** (keep existing Order.php backup)

## Support for Different Store Types

The system now supports:
- **Food Stores**: Uses `food_items` table with `item_type = 'food_item'`
- **Retail Stores**: Uses `items` table with `item_type = 'item'`
- **Service Stores**: Can use either table based on service type
- **Package Delivery**: Uses separate `package_deliveries` table

## Next Steps

1. **Run the migration** on your development environment first
2. **Test thoroughly** with existing data
3. **Update frontend** to use new API endpoints
4. **Deploy to production** after testing
5. **Monitor** for any issues with existing functionality

The system is designed to be backward-compatible while adding powerful new features for order management, tracking, and delivery.
