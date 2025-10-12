# User Structure Clarification

## Overview
This document clarifies the user structure and relationships in the Orders system based on the actual database design.

## User Hierarchy

### 1. **Users Table** (Primary Table)
- **Contains ALL account types**: customers, merchants, riders
- **Primary Key**: `id` (user_id)
- **Role Field**: `role` enum('user', 'merchant', 'rider')
- **Common Fields**: email, phone, password, status, created_at, etc.

### 2. **Stores Table** (Merchant Store Information)
- **Links to**: `users.id` (merchant's user_id)
- **Purpose**: Store-specific information (name, address, type, etc.)
- **Relationship**: One merchant can have multiple stores

### 3. **Riders Table** (Rider-Specific Information)
- **Links to**: `users.id` (rider's user_id)
- **Purpose**: Rider-specific information (vehicle, license, etc.)
- **Relationship**: One rider profile per user

## Order Relationships

### Orders Table Structure:
```sql
orders:
- id (primary key)
- user_id (customer who placed the order) → users.id
- customer_id (same as user_id, for clarity)
- merchant_id (store owner) → users.id
- rider_id (assigned delivery person) → users.id
- store_id → stores.id
```

### Key Relationships:
1. **Customer**: `orders.user_id` = `orders.customer_id` = `users.id` (where role='user')
2. **Merchant**: `orders.merchant_id` = `users.id` (where role='merchant')
3. **Rider**: `orders.rider_id` = `users.id` (where role='rider')
4. **Store**: `orders.store_id` = `stores.id` (where `stores.user_id` = merchant's user_id)

## Data Migration Logic

### For Existing Orders:
```sql
-- Customer ID (who placed the order)
UPDATE orders SET customer_id = user_id WHERE customer_id IS NULL;

-- Merchant ID (store owner)
UPDATE orders o
JOIN stores s ON o.store_id = s.id
SET o.merchant_id = s.user_id
WHERE o.merchant_id IS NULL;
```

## Permission Matrix

| Action | Customer | Merchant | Rider |
|--------|----------|----------|-------|
| View own orders | ✅ | ❌ | ❌ |
| View store orders | ❌ | ✅ | ❌ |
| View assigned orders | ❌ | ❌ | ✅ |
| Accept orders | ❌ | ✅ | ❌ |
| Update to preparing | ❌ | ✅ | ❌ |
| Update to ready | ❌ | ✅ | ❌ |
| Update to in_transit | ❌ | ❌ | ✅ |
| Update to delivered | ❌ | ❌ | ✅ |
| Cancel order | ✅ | ✅ | ❌ |

## API Endpoint Access

### Merchant Orders (`/api/get-merchant-orders.php`)
- **Access**: Only users with `role = 'merchant'`
- **Returns**: Orders for stores owned by the merchant
- **Filter**: `WHERE merchant_id = user_id`

### Order Details (`/api/get-order-details.php`)
- **Access**: Customer, Merchant (store owner), or Assigned Rider
- **Logic**: 
  ```php
  if ($order['customer_id'] == $user_id || 
      $order['merchant_id'] == $user_id || 
      $order['rider_id'] == $user_id) {
      // Allow access
  }
  ```

### Status Updates (`/api/update-order-status.php`)
- **Merchant Actions**: accepted, preparing, ready_for_pickup
- **Rider Actions**: in_transit, delivered
- **Validation**: Check if user is the merchant or assigned rider

## Example Scenarios

### Scenario 1: Customer Places Order
```sql
-- Customer (user_id: 100, role: 'user') places order
INSERT INTO orders (user_id, customer_id, store_id, ...) 
VALUES (100, 100, 5, ...);

-- Get merchant_id from store
UPDATE orders SET merchant_id = (
    SELECT user_id FROM stores WHERE id = 5
) WHERE id = order_id;
```

### Scenario 2: Merchant Accepts Order
```sql
-- Merchant (user_id: 200, role: 'merchant') accepts order
UPDATE orders 
SET status = 'accepted', accepted_at = NOW() 
WHERE id = order_id AND merchant_id = 200;
```

### Scenario 3: Rider Picks Up Order
```sql
-- Rider (user_id: 300, role: 'rider') picks up order
UPDATE orders 
SET status = 'in_transit', picked_up_at = NOW() 
WHERE id = order_id AND rider_id = 300;
```

## Frontend Integration

### For Merchant Dashboard:
```javascript
// Get merchant's orders
fetch('/api/get-merchant-orders.php?status=pending', {
    headers: { 'Authorization': 'Bearer ' + merchantToken }
})
```

### For Customer App:
```javascript
// Get customer's orders (different endpoint needed)
fetch('/api/get-customer-orders.php', {
    headers: { 'Authorization': 'Bearer ' + customerToken }
})
```

### For Rider App:
```javascript
// Get rider's assigned orders (different endpoint needed)
fetch('/api/get-rider-orders.php', {
    headers: { 'Authorization': 'Bearer ' + riderToken }
})
```

## Database Constraints

### Foreign Key Relationships:
```sql
-- Orders table constraints
ALTER TABLE orders 
ADD CONSTRAINT fk_orders_customer_id FOREIGN KEY (customer_id) REFERENCES users(id),
ADD CONSTRAINT fk_orders_merchant_id FOREIGN KEY (merchant_id) REFERENCES users(id),
ADD CONSTRAINT fk_orders_rider_id FOREIGN KEY (rider_id) REFERENCES users(id),
ADD CONSTRAINT fk_orders_store_id FOREIGN KEY (store_id) REFERENCES stores(id);
```

## Testing Checklist

- [ ] Customer can place orders
- [ ] Merchant can view their store orders
- [ ] Merchant can accept/prepare orders
- [ ] Rider can view assigned orders
- [ ] Rider can update delivery status
- [ ] Customer can view their orders
- [ ] Customer can cancel orders
- [ ] Merchant can cancel orders
- [ ] Proper permission checks for all endpoints

## Notes

1. **All users have user_id**: Whether customer, merchant, or rider
2. **Role-based access**: Check `users.role` for permissions
3. **Store ownership**: `stores.user_id` = merchant's user_id
4. **Order relationships**: All linked through user_id system
5. **Backward compatibility**: Existing `user_id` field preserved

This structure ensures proper separation of concerns while maintaining referential integrity across all user types.
