# Create Food Item API Guide

## Endpoint
```
POST /api/create_food_item.php
```

## Description
Creates a new food item with optional sides, packs, and sections. The endpoint supports both JSON and multipart/form-data content types for flexibility.

## Authentication
- **Required**: JWT Token in Authorization header
- **Role**: Merchant only
- **Header**: `Authorization: Bearer <your_jwt_token>`

## Request Format

### Content-Type Options
1. **application/json** - For JSON data
2. **multipart/form-data** - For file uploads with form data

## Request Body

### Required Fields
| Field | Type | Description | Example |
|-------|------|-------------|---------|
| `name` | string | Food item name | "Ultimate Combo Deluxe" |
| `price` | number | Item price | 25.99 |
| `category_id` | integer | Category ID | 1 |
| `short_description` | string | Brief description | "Ultimate combo with customizations" |
| `photo` | file | Food item image | [file upload] |

### Optional Fields

#### Sides Configuration
Two formats are supported:

**Format 1: Simple Array (Legacy)**
```json
"sides": [15, 17, 18]
```

**Format 2: Structured Object (Recommended)**
```json
"sides": {
    "required": true,
    "max_quantity": 3,
    "items": [15, 17, 18]
}
```

#### Packs Configuration
Two formats are supported:

**Format 1: Simple Array (Legacy)**
```json
"packs": [15, 16]
```

**Format 2: Structured Object (Recommended)**
```json
"packs": {
    "required": false,
    "max_quantity": 2,
    "items": [15, 16]
}
```

#### Sections Configuration
Three formats are supported:

**Format 1: Simple Array (Legacy)**
```json
"sections": [
    {"id": 9, "selected_items": [16, 17]}
]
```

**Format 2: Single Section Object (Legacy)**
```json
"sections": {
    "required": true,
    "max_quantity": 5,
    "items": [9],
    "item_ids": [16, 17]
}
```

**Format 3: Multiple Sections Array (NEW - Recommended)**
```json
"sections": [
    {
        "section_id": 1,
        "required": true,
        "max_quantity": 5,
        "item_ids": [16, 17]
    },
    {
        "section_id": 2,
        "required": false,
        "max_quantity": 3,
        "item_ids": [18, 19]
    },
    {
        "section_id": 3,
        "required": true,
        "max_quantity": 2,
        "item_ids": [20, 21]
    }
]
```

## Sample Request Bodies

### Using JSON Content-Type
```json
{
    "name": "Ultimate Combo Deluxe",
    "price": 25.99,
    "category_id": 1,
    "short_description": "Ultimate combo with customizations and premium sides",
    "sides": {
        "required": true,
        "max_quantity": 3,
        "items": [15, 17, 18]
    },
    "packs": {
        "required": false,
        "max_quantity": 2,
        "items": [15, 16]
    },
    "sections": [
        {
            "section_id": 1,
            "required": true,
            "max_quantity": 5,
            "item_ids": [16, 17]
        },
        {
            "section_id": 2,
            "required": false,
            "max_quantity": 3,
            "item_ids": [18, 19]
        }
    ]
}
```

### Using Form-Data Content-Type
```
name: Ultimate Combo Deluxe
price: 25.99
category_id: 1
short_description: Ultimate combo with customizations and premium sides
photo: [file upload]
sides: {"required":true,"max_quantity":3,"items":[15,17,18]}
packs: {"required":false,"max_quantity":2,"items":[15,16]}
sections: [{"section_id":1,"required":true,"max_quantity":5,"item_ids":[16,17]},{"section_id":2,"required":false,"max_quantity":3,"item_ids":[18,19]}]
```

## Response Format

### Success Response (201 Created)
```json
{
    "status": "success",
    "message": "Food item created successfully with all options",
    "data": {
        "id": 123,
        "name": "Ultimate Combo Deluxe",
        "price": "25.99",
        "category_id": 1,
        "short_description": "Ultimate combo with customizations and premium sides",
        "photo": "https://api.runnix.africa/uploads/food-items/item_abc123.jpg",
        "status": "active",
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00",
        "sides": [...],
        "packs": [...],
        "sections": [...]
    }
}
```

### Error Responses

#### 400 Bad Request
```json
{
    "status": "error",
    "message": "Food item name is required"
}
```

#### 401 Unauthorized
```json
{
    "status": "error",
    "message": "Invalid or expired token"
}
```

#### 403 Forbidden
```json
{
    "status": "error",
    "message": "Access denied. Only merchants can create food items."
}
```

#### 413 Payload Too Large
```json
{
    "status": "error",
    "message": "Image exceeds max size of 3MB."
}
```

#### 415 Unsupported Media Type
```json
{
    "status": "error",
    "message": "Unsupported image format."
}
```

#### 409 Conflict
```json
{
    "status": "error",
    "message": "Food item with this name already exists in this store. Please choose a different name."
}
```

#### 500 Internal Server Error
```json
{
    "status": "error",
    "message": "Failed to create food item"
}
```

## cURL Examples

### JSON Request
```bash
curl -X POST https://api.runnix.africa/api/create_food_item.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Ultimate Combo Deluxe",
    "price": 25.99,
    "category_id": 1,
    "short_description": "Ultimate combo with customizations",
    "sides": {"required":true,"max_quantity":3,"items":[15,17,18]},
    "packs": {"required":false,"max_quantity":2,"items":[15,16]},
    "sections": [{"section_id":1,"required":true,"max_quantity":5,"item_ids":[16,17]}]
  }'
```

### Form-Data Request with File Upload
```bash
curl -X POST https://api.runnix.africa/api/create_food_item.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "name=Ultimate Combo Deluxe" \
  -F "price=25.99" \
  -F "category_id=1" \
  -F "short_description=Ultimate combo with customizations" \
  -F "photo=@/path/to/image.jpg" \
  -F "sides={\"required\":true,\"max_quantity\":3,\"items\":[15,17,18]}" \
  -F "packs={\"required\":false,\"max_quantity\":2,\"items\":[15,16]}" \
  -F "sections=[{\"section_id\":1,\"required\":true,\"max_quantity\":5,\"item_ids\":[16,17]}]"
```

## JavaScript Examples

### Using Fetch API (JSON)
```javascript
const createFoodItem = async (foodItemData) => {
    try {
        const response = await fetch('https://api.runnix.africa/api/create_food_item.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(foodItemData)
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error creating food item:', error);
        throw error;
    }
};

// Usage
const foodItemData = {
    name: "Ultimate Combo Deluxe",
    price: 25.99,
    category_id: 1,
    short_description: "Ultimate combo with customizations",
    sides: {
        required: true,
        max_quantity: 3,
        items: [15, 17, 18]
    },
    packs: {
        required: false,
        max_quantity: 2,
        items: [15, 16]
    },
    sections: [
        {
            section_id: 1,
            required: true,
            max_quantity: 5,
            item_ids: [16, 17]
        }
    ]
};

createFoodItem(foodItemData);
```

### Using FormData (File Upload)
```javascript
const createFoodItemWithImage = async (foodItemData, imageFile) => {
    try {
        const formData = new FormData();
        formData.append('name', foodItemData.name);
        formData.append('price', foodItemData.price);
        formData.append('category_id', foodItemData.category_id);
        formData.append('short_description', foodItemData.short_description);
        formData.append('photo', imageFile);
        formData.append('sides', JSON.stringify(foodItemData.sides));
        formData.append('packs', JSON.stringify(foodItemData.packs));
        formData.append('sections', JSON.stringify(foodItemData.sections));
        
        const response = await fetch('https://api.runnix.africa/api/create_food_item.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            body: formData
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error creating food item:', error);
        throw error;
    }
};
```

## Notes

1. **Image Requirements**: 
   - Supported formats: JPEG, JPG, PNG
   - Maximum size: 3MB
   - Images are automatically uploaded to the server

2. **Sections Format Priority**:
   - The new multiple sections format (Format 3) is recommended for better flexibility
   - Legacy formats are still supported for backward compatibility

3. **Validation**:
   - All IDs (category_id, side IDs, pack IDs, section IDs, item IDs) must exist in the database
   - Prices must be non-negative numbers
   - Required fields cannot be empty

4. **Database Storage**:
   - Each section configuration is stored separately with its own required/max_quantity settings
   - Section items are linked to specific sections within the food item
   - All relationships maintain referential integrity
