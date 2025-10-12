# Section Items API Guide

## Overview

The food item creation API now supports selective section item choice, allowing users to choose specific items from within sections rather than being forced to include all items from a section.

## Database Tables

### New Tables Added:
1. **`food_item_section_items`** - Links food items to specific section items
2. **`food_item_section_items_config`** - Configuration for section items (required, max_quantity)

## API Formats

### 1. Enhanced Section Format (Recommended)

```json
{
  "name": "Custom Burger",
  "price": 15.99,
  "category_id": 1,
  "sections": [
    {
      "id": 1,
      "selected_items": [1, 2, 3]  // Only specific items from section 1
    },
    {
      "id": 2,
      "selected_items": [5, 6]     // Only specific items from section 2
    }
  ]
}
```

### 2. Direct Section Items Format

```json
{
  "name": "Custom Burger",
  "price": 15.99,
  "category_id": 1,
  "section_items": [1, 2, 3, 5, 6]  // Direct section item IDs
}
```

### 3. Enhanced Section Items with Extra Price

```json
{
  "name": "Custom Burger",
  "price": 15.99,
  "category_id": 1,
  "section_items": [
    {
      "id": 1,
      "extra_price": 2.00
    },
    {
      "id": 2,
      "extra_price": 1.50
    }
  ]
}
```

### 4. Backward Compatible Format

```json
{
  "name": "Custom Burger",
  "price": 15.99,
  "category_id": 1,
  "sections": [1, 2]  // Entire sections (old format still works)
}
```

## Response Format

When retrieving a food item, the response now includes both sections and section items:

```json
{
  "status": "success",
  "data": {
    "id": 1,
    "name": "Custom Burger",
    "price": 15.99,
    "sections": {
      "required": false,
      "max_quantity": 0,
      "items": [
        {
          "id": 1,
          "section_name": "Toppings",
          "max_quantity": 3,
          "required": false
        }
      ]
    },
    "section_items": {
      "required": false,
      "max_quantity": 0,
      "items": [
        {
          "id": 1,
          "name": "Cheese",
          "price": 1.00,
          "extra_price": 0.00,
          "status": "active"
        },
        {
          "id": 2,
          "name": "Bacon",
          "price": 2.00,
          "extra_price": 0.00,
          "status": "active"
        }
      ]
    }
  }
}
```

## Use Cases

### Scenario 1: Selective Toppings
- **Section**: "Toppings" (ID: 1)
- **Available Items**: Cheese, Bacon, Lettuce, Tomato, Onion
- **User Choice**: Only Cheese and Bacon
- **API Call**:
```json
{
  "sections": [
    {
      "id": 1,
      "selected_items": [1, 2]  // Cheese and Bacon only
    }
  ]
}
```

### Scenario 2: Mixed Approach
- **Entire Section**: "Sauces" (all items included)
- **Selective Items**: Specific toppings
- **API Call**:
```json
{
  "sections": [
    3,  // Entire sauces section
    {
      "id": 1,
      "selected_items": [1, 2]  // Only cheese and bacon from toppings
    }
  ]
}
```

## Validation Rules

1. **Section IDs**: Must be valid and belong to the user's store
2. **Section Item IDs**: Must be valid and belong to the specified section
3. **Extra Price**: Optional, defaults to 0.00
4. **Backward Compatibility**: Old format still works

## Error Messages

- `"Each section must be an object"` - Invalid section format
- `"Each section must have a valid id"` - Missing or invalid section ID
- `"Each selected item ID must be a valid number"` - Invalid section item ID
- `"Each section item ID must be a valid number"` - Invalid direct section item ID

## Migration Notes

- Existing food items continue to work with the old format
- New selective functionality is additive and doesn't break existing integrations
- Both `sections` and `section_items` can be used together
