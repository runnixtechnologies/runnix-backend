# Create Food Item API Guide

This document describes the endpoint for creating food items with optional sides, packs, and sections.

## Endpoint

**URL:** `POST /api/create_food_item.php`

**Description:** Creates a new food item with optional sides, packs, and sections configuration.

## Authentication

Requires authentication using a valid JWT token in the Authorization header:
```
Authorization: Bearer <your_jwt_token>
```

## Request Format

The endpoint supports both JSON and multipart/form-data formats for flexibility with file uploads.

### Headers
```
Authorization: Bearer <your_jwt_token>
Content-Type: application/json
```
OR
```
Authorization: Bearer <your_jwt_token>
Content-Type: multipart/form-data
```

## Request Body

### Required Fields
- `name` (string): Name of the food item
- `price` (numeric): Price of the food item
- `category_id` (integer): ID of the category this item belongs to

### Optional Fields
- `photo` (file): Image file for the food item (max 3MB, JPG/PNG)
- `short_description` (string): Brief description of the food item
- `section_id` (integer): ID of the section this item belongs to (optional)
- `sides` (array): Configuration for food sides
- `packs` (array): Configuration for food packs
- `sections` (array): Configuration for food sections

### Sides Configuration
```json
{
  "sides": {
    "required": true,
    "max_quantity": 3,
    "items": [
      {
        "id": 1,
        "extra_price": 2.50
      },
      {
        "id": 2,
        "extra_price": 1.75
      }
    ]
  }
}
```

### Packs Configuration
```json
{
  "packs": {
    "required": false,
    "max_quantity": 2,
    "items": [
      {
        "id": 1,
        "extra_price": 5.00
      }
    ]
  }
}
```

### Sections Configuration
```json
{
  "sections": [
    {
      "id": 1,
      "selected_items": [1, 2, 3]
    },
    {
      "id": 2,
      "selected_items": [4, 5]
    }
  ]
}
```

## Sample Request Bodies

### 1. Basic Food Item (JSON)
```json
{
  "name": "Margherita Pizza",
  "price": 15.99,
  "category_id": 1,
  "short_description": "Classic tomato and mozzarella pizza"
}
```

### 2. Food Item with Photo (Multipart Form Data)
```
name: Margherita Pizza
price: 15.99
category_id: 1
short_description: Classic tomato and mozzarella pizza
photo: [file upload]
```

### 3. Food Item with Sides (JSON)
```json
{
  "name": "Burger Deluxe",
  "price": 12.99,
  "category_id": 2,
  "short_description": "Juicy beef burger with fresh vegetables",
  "sides": {
    "required": true,
    "max_quantity": 2,
    "items": [
      {
        "id": 1,
        "extra_price": 2.50
      },
      {
        "id": 2,
        "extra_price": 1.75
      }
    ]
  }
}
```

### 4. Food Item with Packs (JSON)
```json
{
  "name": "Combo Meal",
  "price": 18.99,
  "category_id": 3,
  "short_description": "Main dish with drink and side",
  "packs": {
    "required": false,
    "max_quantity": 1,
    "items": [
      {
        "id": 1,
        "extra_price": 3.00
      }
    ]
  }
}
```

### 5. Food Item with Sections (JSON)
```json
{
  "name": "Custom Pizza",
  "price": 20.99,
  "category_id": 1,
  "short_description": "Build your own pizza",
  "sections": [
    {
      "id": 1,
      "selected_items": [1, 2, 3]
    },
    {
      "id": 2,
      "selected_items": [4, 5]
    }
  ]
}
```

### 6. Complete Food Item (JSON)
```json
{
  "name": "Premium Burger Combo",
  "price": 25.99,
  "category_id": 2,
  "short_description": "Deluxe burger with customizable options",
  "sides": {
    "required": true,
    "max_quantity": 2,
    "items": [
      {
        "id": 1,
        "extra_price": 2.50
      },
      {
        "id": 2,
        "extra_price": 1.75
      }
    ]
  },
  "packs": {
    "required": false,
    "max_quantity": 1,
    "items": [
      {
        "id": 1,
        "extra_price": 3.00
      }
    ]
  },
  "sections": [
    {
      "id": 1,
      "selected_items": [1, 2, 3]
    }
  ]
}
```

## Sample cURL Requests

### 1. Basic Food Item
```bash
curl -X POST "https://api.runnix.africa/api/create_food_item.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Margherita Pizza",
    "price": 15.99,
    "category_id": 1,
    "short_description": "Classic tomato and mozzarella pizza"
  }'
```

### 2. Food Item with Photo
```bash
curl -X POST "https://api.runnix.africa/api/create_food_item.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "name=Margherita Pizza" \
  -F "price=15.99" \
  -F "category_id=1" \
  -F "short_description=Classic tomato and mozzarella pizza" \
  -F "photo=@/path/to/pizza.jpg"
```

### 3. Food Item with Sides
```bash
curl -X POST "https://api.runnix.africa/api/create_food_item.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Burger Deluxe",
    "price": 12.99,
    "category_id": 2,
    "short_description": "Juicy beef burger with fresh vegetables",
    "sides": {
      "required": true,
      "max_quantity": 2,
      "items": [
        {
          "id": 1,
          "extra_price": 2.50
        },
        {
          "id": 2,
          "extra_price": 1.75
        }
      ]
    }
  }'
```

## Sample Responses

### Success Response
```json
{
  "status": "success",
  "message": "Food item created successfully",
  "data": {
    "id": 123,
    "name": "Margherita Pizza",
    "price": "15.99",
    "category_id": "1",
    "sides": {
      "required": true,
      "max_quantity": 2,
      "items": [
        {
          "id": 1,
          "name": "French Fries",
          "price": "3.50",
          "extra_price": "2.50"
        }
      ]
    }
  }
}
```

### Error Responses

#### 400 - Bad Request
```json
{
  "status": "error",
  "message": "Food item name is required"
}
```

#### 403 - Forbidden
```json
{
  "status": "error",
  "message": "Store ID not found. Please ensure you are logged in as a merchant with a store setup."
}
```

#### 409 - Conflict
```json
{
  "status": "error",
  "message": "Item with this name already exists in this store. Please choose a different name."
}
```

#### 413 - Payload Too Large
```json
{
  "status": "error",
  "message": "Image exceeds max size of 3MB."
}
```

#### 415 - Unsupported Media Type
```json
{
  "status": "error",
  "message": "Unsupported image format."
}
```

## JavaScript Examples

### Basic Food Item Creation
```javascript
const createFoodItem = async (foodItemData) => {
  const response = await fetch('/api/create_food_item.php', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(foodItemData)
  });
  return await response.json();
};

// Usage
const newItem = await createFoodItem({
  name: 'Margherita Pizza',
  price: 15.99,
  category_id: 1,
  short_description: 'Classic tomato and mozzarella pizza'
});
```

### Food Item with File Upload
```javascript
const createFoodItemWithPhoto = async (foodItemData, photoFile) => {
  const formData = new FormData();
  
  // Add text fields
  Object.keys(foodItemData).forEach(key => {
    formData.append(key, foodItemData[key]);
  });
  
  // Add photo file
  if (photoFile) {
    formData.append('photo', photoFile);
  }
  
  const response = await fetch('/api/create_food_item.php', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData
  });
  return await response.json();
};

// Usage
const fileInput = document.getElementById('photo');
const newItem = await createFoodItemWithPhoto({
  name: 'Margherita Pizza',
  price: 15.99,
  category_id: 1
}, fileInput.files[0]);
```

## Important Notes

1. **Authentication**: Only merchants can create food items
2. **Store Isolation**: Items are automatically associated with the merchant's store
3. **Photo Upload**: Supports JPG and PNG formats, max 3MB
4. **Name Uniqueness**: Item names must be unique within the store
5. **Category Validation**: Category must exist and be active
6. **Sides/Packs/Sections**: These are optional configurations that allow customers to customize their orders
7. **Max Quantity**: The `max_qty` field is only applicable for sides, packs, and sections, not for the food item itself

## Field Descriptions

- **name**: The display name of the food item
- **price**: The base price of the food item
- **category_id**: The category this item belongs to
- **short_description**: Optional description for the item
- **section_id**: Optional section grouping
- **sides**: Optional side dish configurations with max quantity limits
- **packs**: Optional package configurations with max quantity limits
- **sections**: Optional section configurations with selected items
