# Discount and Percentage API Guide

## Overview
Merchants can now set discount amounts and percentage discounts for both food sides and food packs.

## Database Changes
- Added `discount` column (DECIMAL(10,2)) to `food_sides` table
- Added `percentage` column (DECIMAL(5,2)) to `food_sides` table
- Added `discount` column (DECIMAL(10,2)) to `packages` table
- Added `percentage` column (DECIMAL(5,2)) to `packages` table

## Food Sides Discount API

### Create Food Side with Discount
**POST** `/api/add_side.php`

```json
{
    "name": "French Fries",
    "price": "5.00",
    "discount": "1.00",
    "percentage": "20.00",
    "store_id": 123
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Food side created successfully"
}
```

### Update Food Side with Discount
**POST** `/api/update_food_side.php`

```json
{
    "id": 1,
    "name": "French Fries",
    "price": "5.00",
    "discount": "1.50",
    "percentage": "25.00",
    "store_id": 123
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Food side updated successfully"
}
```

### Get Food Side (includes discount info)
**GET** `/api/get_foodside_by_id.php?id=1`

**Response:**
```json
{
    "status": "success",
    "data": {
        "id": 1,
        "name": "French Fries",
        "price": "5.00",
        "discount": "1.00",
        "percentage": "20.00",
        "store_id": 123,
        "status": "active",
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00",
        "total_orders": 8
    }
}
```

## Food Packs Discount API

### Create Pack with Discount
**POST** `/api/create_pack.php`

```json
{
    "name": "Combo Meal",
    "price": "15.00",
    "discount": "3.00",
    "percentage": "15.00",
    "store_id": 123
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Pack created successfully"
}
```

### Update Pack with Discount
**POST** `/api/update_pack.php`

```json
{
    "id": 1,
    "name": "Combo Meal",
    "price": "15.00",
    "discount": "4.00",
    "percentage": "20.00"
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Pack updated successfully"
}
```

### Get Pack (includes discount info)
**GET** `/api/get_packby_id.php?id=1`

**Response:**
```json
{
    "status": "success",
    "data": {
        "id": 1,
        "name": "Combo Meal",
        "price": "15.00",
        "discount": "3.00",
        "percentage": "15.00",
        "store_id": 123,
        "status": "active",
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00",
        "total_orders": 12
    }
}
```

## Field Descriptions

### Discount Field
- **Type**: DECIMAL(10,2)
- **Default**: 0.00
- **Description**: Fixed discount amount in currency (e.g., $1.50 off)
- **Validation**: Must be non-negative number

### Percentage Field
- **Type**: DECIMAL(5,2)
- **Default**: 0.00
- **Description**: Percentage discount (e.g., 20.00 = 20% off)
- **Validation**: Must be between 0 and 100

## Usage Examples

### Example 1: Fixed Discount Only
```json
{
    "name": "Side Salad",
    "price": "8.00",
    "discount": "2.00",
    "percentage": 0
}
```
**Result**: $8.00 - $2.00 = $6.00 final price

### Example 2: Percentage Discount Only
```json
{
    "name": "Combo Pack",
    "price": "20.00",
    "discount": 0,
    "percentage": "25.00"
}
```
**Result**: $20.00 - 25% = $15.00 final price

### Example 3: Both Discount and Percentage
```json
{
    "name": "Premium Meal",
    "price": "25.00",
    "discount": "5.00",
    "percentage": "10.00"
}
```
**Result**: $25.00 - $5.00 - 10% = $18.00 final price

## Error Responses

### Invalid Discount
```json
{
    "status": "error",
    "message": "Discount must be a non-negative number"
}
```

### Invalid Percentage
```json
{
    "status": "error",
    "message": "Percentage must be between 0 and 100"
}
```

## Migration
Run the migration script to add the new columns:
```bash
php backend/migrations/add_discount_columns.php
```
