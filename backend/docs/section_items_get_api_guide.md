# Food Section Items GET API Guide

This document describes the GET endpoints available for managing food section items.

## Authentication

All endpoints require authentication using a valid JWT token in the Authorization header:
```
Authorization: Bearer <your_jwt_token>
```

## Endpoints

### 1. Get Section Item by ID

**Endpoint:** `GET /api/get_section_item_by_id.php`

**Description:** Retrieves a specific food section item by its ID.

**Method:** GET or POST

**Parameters:**
- `id` (required): The ID of the section item to retrieve

**Example Requests:**
```
# GET method
GET /api/get_section_item_by_id.php?id=123

# POST method
POST /api/get_section_item_by_id.php
Content-Type: application/json

{
    "id": 123
}
```

**Example Response:**
```json
{
    "status": "success",
    "data": {
        "id": "123",
        "section_id": "5",
        "name": "Extra Cheese",
        "price": "2.50",
        "status": "active",
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00",
        "section_name": "Toppings",
        "store_id": "10"
    }
}
```

**Error Responses:**
- `400`: Invalid item ID
- `403`: Access denied (not a merchant or item doesn't belong to your store)
- `404`: Section item not found
- `500`: Internal server error

### 2. Get All Section Items in Store

**Endpoint:** `GET /api/get_all_section_items_in_store.php`

**Description:** Retrieves all food section items in the authenticated merchant's store with pagination support. Can optionally filter by section ID.

**Method:** GET or POST

**Parameters:**
- `page` (optional): Page number (default: 1)
- `limit` (optional): Number of items per page (default: 10, max: 50)
- `section_id` (optional): Filter items by specific section ID

**Example Requests:**
```
# GET method - Get all section items in store
GET /api/get_all_section_items_in_store.php?page=1&limit=20

# GET method - Get section items from a specific section
GET /api/get_all_section_items_in_store.php?section_id=5&page=1&limit=20

# POST method
POST /api/get_all_section_items_in_store.php
Content-Type: application/json

{
    "page": 1,
    "limit": 20,
    "section_id": 5
}
```

**Example Response:**
```json
{
    "status": "success",
    "data": {
        "items": [
            {
                "id": "123",
                "section_id": "5",
                "name": "Extra Cheese",
                "price": "2.50",
                "status": "active",
                "created_at": "2024-01-15 10:30:00",
                "updated_at": "2024-01-15 10:30:00",
                "section_name": "Toppings"
            },
            {
                "id": "124",
                "section_id": "5",
                "name": "Bacon Bits",
                "price": "3.00",
                "status": "active",
                "created_at": "2024-01-15 10:35:00",
                "updated_at": "2024-01-15 10:35:00",
                "section_name": "Toppings"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total_items": 45,
            "total_pages": 3,
            "has_next_page": true,
            "has_prev_page": false
        }
    }
}
```

**Error Responses:**
- `403`: Access denied (not a merchant)
- `500`: Internal server error

### 3. Bulk Delete Section Items

**Endpoint:** `DELETE /api/bulk_delete_section_items.php`

**Description:** Deletes multiple section items in bulk (merchants only).

**Headers:**
```
Authorization: Bearer <your_jwt_token>
Content-Type: application/json
```

**Sample Request Body:**
```json
{
    "item_ids": [123, 124, 125]
}
```

**Sample cURL Request:**
```bash
curl -X DELETE "https://api.runnix.africa/api/bulk_delete_section_items.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "item_ids": [123, 124, 125]
  }'
```

**Sample Response:**
```json
{
    "status": "success",
    "message": "3 section item(s) deleted successfully"
}
```

**Error Responses:**
- `400`: Invalid item IDs or missing array
- `403`: Access denied (not a merchant or items don't belong to your store)
- `405`: Method not allowed (use DELETE method)
- `500`: Internal server error

## Notes

1. **Authorization**: Only merchants can access these endpoints
2. **Store Isolation**: Merchants can only view/delete section items from their own store
3. **Pagination**: The pagination system provides metadata to help with frontend navigation
4. **Ordering**: Items are ordered by creation date (newest first)
5. **Section Information**: Both endpoints include the section name for better context
6. **Bulk Operations**: Bulk delete supports multiple item IDs in a single request

## Usage Examples

### JavaScript/Fetch API

```javascript
// Get section item by ID
const getSectionItem = async (itemId) => {
    const response = await fetch(`/api/get_section_item_by_id.php?id=${itemId}`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        }
    });
    return await response.json();
};

// Get all section items with pagination
const getAllSectionItems = async (page = 1, limit = 10, sectionId = null) => {
    const params = new URLSearchParams({
        page: page.toString(),
        limit: limit.toString()
    });
    
    if (sectionId) {
        params.append('section_id', sectionId.toString());
    }
    
    const response = await fetch(`/api/get_all_section_items_in_store.php?${params}`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        }
    });
    return await response.json();
};

// Bulk delete section items
const bulkDeleteSectionItems = async (itemIds) => {
    const response = await fetch('/api/bulk_delete_section_items.php', {
        method: 'DELETE',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ item_ids: itemIds })
    });
    return await response.json();
};
```

### cURL Examples

```bash
# Get section item by ID
curl -X GET "https://api.runnix.africa/api/get_section_item_by_id.php?id=123" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Get all section items with pagination
curl -X GET "https://api.runnix.africa/api/get_all_section_items_in_store.php?page=1&limit=20" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Get section items from a specific section
curl -X GET "https://api.runnix.africa/api/get_all_section_items_in_store.php?section_id=5&page=1&limit=20" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Bulk delete section items
curl -X DELETE "https://api.runnix.africa/api/bulk_delete_section_items.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"item_ids": [123, 124, 125]}'
```
