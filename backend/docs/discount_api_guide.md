# Discount API Guide

## Overview
The discount system now supports discounts for all item types in the Runnix marketplace:
- **Food Items** (`item_type: 'food_item'`)
- **General Store Items** (`item_type: 'item'`)
- **Food Sides** (`item_type: 'side'`)
- **Packs** (`item_type: 'pack'`)

## Database Structure

### Discounts Table
```sql
id (Primary Key)
store_id
store_type_id
percentage
start_date
end_date
status
created_at
updated_at
```

### Discount Items Table
```sql
id (Primary Key)
discount_id (Foreign Key to discounts.id)
item_id (ID of the item/side/pack)
item_type (food_item, item, side, pack)
created_at
```

## API Endpoints

### 1. Create Discount
**Endpoint:** `POST /api/create_discount.php`

**Request Body:**
```json
{
    "store_id": 1,
    "store_type_id": 1,
    "percentage": 15.5,
    "start_date": "2024-01-01",
    "end_date": "2024-12-31",
    "items": [
        {
            "item_id": 1,
            "item_type": "food_item"
        },
        {
            "item_id": 5,
            "item_type": "side"
        },
        {
            "item_id": 3,
            "item_type": "pack"
        },
        {
            "item_id": 10,
            "item_type": "item"
        }
    ]
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Discount created",
    "discount_id": 123
}
```

### 2. Get Discounts by Item ID
**Endpoint:** `GET /api/get_discounts_by_item.php?item_id=1`

**Response:**
```json
{
    "status": "success",
    "data": [
        {
            "id": 123,
            "store_id": 1,
            "store_type_id": 1,
            "percentage": 15.5,
            "start_date": "2024-01-01",
            "end_date": "2024-12-31",
            "status": "active"
        }
    ]
}
```

### 3. Get Discounts by Side ID
**Endpoint:** `GET /api/get_discounts_by_side.php?side_id=5`

**Response:**
```json
{
    "status": "success",
    "data": [
        {
            "id": 123,
            "store_id": 1,
            "store_type_id": 1,
            "percentage": 15.5,
            "start_date": "2024-01-01",
            "end_date": "2024-12-31",
            "status": "active"
        }
    ]
}
```

### 4. Get Discounts by Pack ID
**Endpoint:** `GET /api/get_discounts_by_pack.php?pack_id=3`

**Response:**
```json
{
    "status": "success",
    "data": [
        {
            "id": 123,
            "store_id": 1,
            "store_type_id": 1,
            "percentage": 15.5,
            "start_date": "2024-01-01",
            "end_date": "2024-12-31",
            "status": "active"
        }
    ]
}
```

### 5. Get All Discounts by Store
**Endpoint:** `GET /api/get_all_discounts_by_store.php?store_id=1`

**Response:**
```json
{
    "status": "success",
    "data": [
        {
            "id": 123,
            "store_id": 1,
            "store_type_id": 1,
            "percentage": 15.5,
            "start_date": "2024-01-01",
            "end_date": "2024-12-31",
            "status": "active"
        }
    ]
}
```

### 6. Get All Discounts by Store with Details
**Endpoint:** `GET /api/get_all_discounts_by_store_with_details.php?store_id=1`

**Response:**
```json
{
    "status": "success",
    "data": [
        {
            "id": 123,
            "store_id": 1,
            "store_type_id": 1,
            "percentage": 15.5,
            "start_date": "2024-01-01",
            "end_date": "2024-12-31",
            "status": "active",
            "item_id": 1,
            "item_type": "food_item",
            "item_name": "Chicken Burger"
        },
        {
            "id": 123,
            "store_id": 1,
            "store_type_id": 1,
            "percentage": 15.5,
            "start_date": "2024-01-01",
            "end_date": "2024-12-31",
            "status": "active",
            "item_id": 5,
            "item_type": "side",
            "item_name": "French Fries"
        }
    ]
}
```

## Item Type Values

When creating discounts, use these `item_type` values:

| Item Type | Value | Description |
|-----------|-------|-------------|
| Food Items | `food_item` | Items from food_items table |
| General Items | `item` | Items from items table |
| Food Sides | `side` | Items from food_sides table |
| Packs | `pack` | Items from packages table |

## Enhanced Response Data

When retrieving items, sides, and packs, the API now includes discount information:

### Pack Response Example:
```json
{
    "id": 1,
    "store_id": 1,
    "name": "Combo Pack",
    "price": 25.00,
    "discount_price": 0.00,
    "percentage": 0.00,
    "status": "active",
    "total_orders": 15,
    "discount_percentage": 15.5,
    "discount_start_date": "2024-01-01",
    "discount_end_date": "2024-12-31",
    "discount_id": 123,
    "calculated_discount_price": 21.13
}
```

### Food Side Response Example:
```json
{
    "id": 5,
    "store_id": 1,
    "name": "French Fries",
    "price": 5.00,
    "discount_price": 0.00,
    "percentage": 0.00,
    "status": "active",
    "total_orders": 0,
    "discount_percentage": 15.5,
    "discount_start_date": "2024-01-01",
    "discount_end_date": "2024-12-31",
    "discount_id": 123,
    "calculated_discount_price": 4.23
}
```

## Discount Calculation

The system automatically calculates discounted prices using the formula:
```
calculated_discount_price = original_price - (original_price * discount_percentage / 100)
```

## Validation Rules

1. **Percentage**: Must be between 0 and 100
2. **Dates**: start_date must be before end_date
3. **Status**: Only active discounts are applied
4. **Time Range**: Only discounts within their date range are active
5. **Item Types**: Must be one of: `food_item`, `item`, `side`, `pack`

## Error Responses

### Missing Required Fields
```json
{
    "status": "error",
    "message": "Required fields are missing"
}
```

### Invalid Item Type
```json
{
    "status": "error",
    "message": "Invalid item_type. Must be one of: food_item, item, side, pack"
}
```

### Item Not Found
```json
{
    "status": "error",
    "message": "No discounts found for this item"
}
```

## Usage Examples

### Creating a Discount for Multiple Item Types
```javascript
const discountData = {
    store_id: 1,
    store_type_id: 1,
    percentage: 20.0,
    start_date: "2024-01-01",
    end_date: "2024-12-31",
    items: [
        { item_id: 1, item_type: "food_item" },
        { item_id: 5, item_type: "side" },
        { item_id: 3, item_type: "pack" }
    ]
};

fetch('/api/create_discount.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify(discountData)
});
```

### Getting Discounts for a Side
```javascript
fetch('/api/get_discounts_by_side.php?side_id=5', {
    headers: {
        'Authorization': 'Bearer ' + token
    }
});
```

This updated discount system provides comprehensive support for all item types in your marketplace, ensuring consistent discount functionality across food items, general items, sides, and packs.
