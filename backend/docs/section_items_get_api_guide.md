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

## Sample Request Bodies & Examples

### 1. Get Section Item by ID

#### Request Headers
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json
```

#### Request URL Examples
```
GET /api/get_section_item_by_id.php?id=123
GET /api/get_section_item_by_id.php?id=456
GET /api/get_section_item_by_id.php?id=789
```

#### Sample cURL Request
```bash
curl -X GET "https://api.runnix.africa/api/get_section_item_by_id.php?id=123" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." \
  -H "Content-Type: application/json"
```

#### Sample JavaScript Request
```javascript
const getSectionItem = async (itemId) => {
    const response = await fetch(`/api/get_section_item_by_id.php?id=${itemId}`, {
        method: 'GET',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        }
    });
    return await response.json();
};

// Usage
const item = await getSectionItem(123);
console.log(item);
```

#### Sample PHP Request
```php
<?php
$itemId = 123;
$token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...';

$url = "https://api.runnix.africa/api/get_section_item_by_id.php?id=" . $itemId;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
print_r($data);
?>
```

### 2. Get All Section Items in Store

#### Request Headers
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json
```

#### Request URL Examples
```
GET /api/get_all_section_items_in_store.php
GET /api/get_all_section_items_in_store.php?page=1
GET /api/get_all_section_items_in_store.php?page=2&limit=20
GET /api/get_all_section_items_in_store.php?page=1&limit=50
```

#### Sample cURL Requests
```bash
# Get first page with default limit (10 items)
curl -X GET "https://api.runnix.africa/api/get_all_section_items_in_store.php" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." \
  -H "Content-Type: application/json"

# Get second page with 20 items per page
curl -X GET "https://api.runnix.africa/api/get_all_section_items_in_store.php?page=2&limit=20" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." \
  -H "Content-Type: application/json"

# Get maximum items per page
curl -X GET "https://api.runnix.africa/api/get_all_section_items_in_store.php?page=1&limit=50" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." \
  -H "Content-Type: application/json"
```

#### Sample JavaScript Request
```javascript
const getAllSectionItems = async (page = 1, limit = 10) => {
    const params = new URLSearchParams({
        page: page.toString(),
        limit: limit.toString()
    });
    
    const response = await fetch(`/api/get_all_section_items_in_store.php?${params}`, {
        method: 'GET',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        }
    });
    return await response.json();
};

// Usage examples
const firstPage = await getAllSectionItems(1, 10);
const secondPage = await getAllSectionItems(2, 20);
const maxItems = await getAllSectionItems(1, 50);

console.log('First page:', firstPage);
console.log('Second page:', secondPage);
console.log('Max items:', maxItems);
```

#### Sample PHP Request
```php
<?php
$page = 1;
$limit = 20;
$token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...';

$url = "https://api.runnix.africa/api/get_all_section_items_in_store.php?page={$page}&limit={$limit}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

// Access pagination data
if ($data['status'] === 'success') {
    $items = $data['data']['items'];
    $pagination = $data['data']['pagination'];
    
    echo "Total items: " . $pagination['total_items'] . "\n";
    echo "Current page: " . $pagination['current_page'] . "\n";
    echo "Total pages: " . $pagination['total_pages'] . "\n";
    
    foreach ($items as $item) {
        echo "Item: " . $item['name'] . " - $" . $item['price'] . "\n";
    }
}
?>
```

#### Sample Python Request
```python
import requests

def get_section_items(page=1, limit=10, token="your_jwt_token"):
    url = "https://api.runnix.africa/api/get_all_section_items_in_store.php"
    params = {
        'page': page,
        'limit': limit
    }
    headers = {
        'Authorization': f'Bearer {token}',
        'Content-Type': 'application/json'
    }
    
    response = requests.get(url, params=params, headers=headers)
    return response.json()

# Usage
items_data = get_section_items(page=1, limit=20)
print(items_data)
```

## Complete Request Examples

### Example 1: Get Section Item by ID
```bash
curl -X GET "https://api.runnix.africa/api/get_section_item_by_id.php?id=123" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJzdG9yZV9pZCI6NSwicm9sZSI6Im1lcmNoYW50IiwiaWF0IjoxNjM5NzI4MDAwfQ.example" \
  -H "Content-Type: application/json"
```

### Example 2: Get All Section Items (First Page)
```bash
curl -X GET "https://api.runnix.africa/api/get_all_section_items_in_store.php?page=1&limit=10" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJzdG9yZV9pZCI6NSwicm9sZSI6Im1lcmNoYW50IiwiaWF0IjoxNjM5NzI4MDAwfQ.example" \
  -H "Content-Type: application/json"
```

### Example 3: Get All Section Items (Custom Pagination)
```bash
curl -X GET "https://api.runnix.africa/api/get_all_section_items_in_store.php?page=2&limit=25" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJzdG9yZV9pZCI6NSwicm9sZSI6Im1lcmNoYW50IiwiaWF0IjoxNjM5NzI4MDAwfQ.example" \
  -H "Content-Type: application/json"
```
