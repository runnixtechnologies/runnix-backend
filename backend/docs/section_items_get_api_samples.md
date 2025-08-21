# Food Section Items GET API - Sample Request Bodies

This document provides comprehensive sample request bodies and examples for the food section items GET endpoints.

## Authentication Header

All requests require this header:
```
Authorization: Bearer <your_jwt_token>
Content-Type: application/json
```

## 1. Get Section Item by ID

### Endpoint
```
GET /api/get_section_item_by_id.php?id={item_id}
```

### Sample Request Bodies

#### Basic Request
```bash
curl -X GET "https://api.runnix.africa/api/get_section_item_by_id.php?id=123" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJzdG9yZV9pZCI6NSwicm9sZSI6Im1lcmNoYW50IiwiaWF0IjoxNjM5NzI4MDAwfQ.example" \
  -H "Content-Type: application/json"
```

#### JavaScript/Fetch
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

#### PHP cURL
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

#### Python Requests
```python
import requests

def get_section_item(item_id, token):
    url = f"https://api.runnix.africa/api/get_section_item_by_id.php?id={item_id}"
    headers = {
        'Authorization': f'Bearer {token}',
        'Content-Type': 'application/json'
    }
    
    response = requests.get(url, headers=headers)
    return response.json()

# Usage
item = get_section_item(123, "your_jwt_token")
print(item)
```

### Expected Response
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

## 2. Get All Section Items in Store

### Endpoint
```
GET /api/get_all_section_items_in_store.php?page={page}&limit={limit}
```

### Sample Request Bodies

#### Basic Request (Default Pagination)
```bash
curl -X GET "https://api.runnix.africa/api/get_all_section_items_in_store.php" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJzdG9yZV9pZCI6NSwicm9sZSI6Im1lcmNoYW50IiwiaWF0IjoxNjM5NzI4MDAwfQ.example" \
  -H "Content-Type: application/json"
```

#### Request with Custom Pagination
```bash
curl -X GET "https://api.runnix.africa/api/get_all_section_items_in_store.php?page=2&limit=20" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJzdG9yZV9pZCI6NSwicm9sZSI6Im1lcmNoYW50IiwiaWF0IjoxNjM5NzI4MDAwfQ.example" \
  -H "Content-Type: application/json"
```

#### Request with Maximum Items
```bash
curl -X GET "https://api.runnix.africa/api/get_all_section_items_in_store.php?page=1&limit=50" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJzdG9yZV9pZCI6NSwicm9sZSI6Im1lcmNoYW50IiwiaWF0IjoxNjM5NzI4MDAwfQ.example" \
  -H "Content-Type: application/json"
```

#### JavaScript/Fetch
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

#### PHP cURL
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

#### Python Requests
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

# Usage examples
first_page = get_section_items(page=1, limit=10)
second_page = get_section_items(page=2, limit=20)
max_items = get_section_items(page=1, limit=50)

print("First page:", first_page)
print("Second page:", second_page)
print("Max items:", max_items)
```

### Expected Response
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

## Complete Working Examples

### Example 1: Get Section Item by ID
```bash
# Replace with your actual JWT token
TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJzdG9yZV9pZCI6NSwicm9sZSI6Im1lcmNoYW50IiwiaWF0IjoxNjM5NzI4MDAwfQ.example"

curl -X GET "https://api.runnix.africa/api/get_section_item_by_id.php?id=123" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

### Example 2: Get All Section Items (First Page)
```bash
# Replace with your actual JWT token
TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJzdG9yZV9pZCI6NSwicm9sZSI6Im1lcmNoYW50IiwiaWF0IjoxNjM5NzI4MDAwfQ.example"

curl -X GET "https://api.runnix.africa/api/get_all_section_items_in_store.php?page=1&limit=10" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

### Example 3: Get All Section Items (Custom Pagination)
```bash
# Replace with your actual JWT token
TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJzdG9yZV9pZCI6NSwicm9sZSI6Im1lcmNoYW50IiwiaWF0IjoxNjM5NzI4MDAwfQ.example"

curl -X GET "https://api.runnix.africa/api/get_all_section_items_in_store.php?page=2&limit=25" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

## Error Response Examples

### 400 Bad Request (Invalid ID)
```json
{
    "status": "error",
    "message": "Item ID is required"
}
```

### 403 Forbidden (Not a Merchant)
```json
{
    "status": "error",
    "message": "Access denied. Only merchants can view section items."
}
```

### 404 Not Found (Item Not Found)
```json
{
    "status": "error",
    "message": "Section item not found."
}
```

### 500 Internal Server Error
```json
{
    "status": "error",
    "message": "Internal server error: Database connection failed"
}
```

## Notes

1. **JWT Token**: Replace `your_jwt_token` with your actual JWT token
2. **Pagination**: The `limit` parameter has a maximum value of 50
3. **Authorization**: Only merchants can access these endpoints
4. **Store Isolation**: Merchants can only view items from their own store
5. **Ordering**: Items are ordered by creation date (newest first)
