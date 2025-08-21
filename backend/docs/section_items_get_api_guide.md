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

**Parameters:**
- `id` (required): The ID of the section item to retrieve

**Example Request:**
```
GET /api/get_section_item_by_id.php?id=123
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

**Description:** Retrieves all food section items in the authenticated merchant's store with pagination support.

**Parameters:**
- `page` (optional): Page number (default: 1)
- `limit` (optional): Number of items per page (default: 10, max: 50)

**Example Request:**
```
GET /api/get_all_section_items_in_store.php?page=1&limit=20
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

## Notes

1. **Authorization**: Only merchants can access these endpoints
2. **Store Isolation**: Merchants can only view section items from their own store
3. **Pagination**: The pagination system provides metadata to help with frontend navigation
4. **Ordering**: Items are ordered by creation date (newest first)
5. **Section Information**: Both endpoints include the section name for better context

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
const getAllSectionItems = async (page = 1, limit = 10) => {
    const response = await fetch(`/api/get_all_section_items_in_store.php?page=${page}&limit=${limit}`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        }
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
```
