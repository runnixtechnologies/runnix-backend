# Food Section API Guide

## Get Food Section by ID

### Endpoint
```
GET /api/get_foodsectionby_id.php
```

### Description
Retrieves a specific food section by its ID. This endpoint is restricted to merchant users only and will only return sections that belong to the authenticated merchant's store.

### Authentication
- **Required**: JWT token in Authorization header
- **Role**: Merchant only
- **Header**: `Authorization: Bearer <your_jwt_token>`

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the food section to retrieve |

### Request Example
```bash
curl -X GET "https://yourdomain.com/api/get_foodsectionby_id.php?id=123" \
  -H "Authorization: Bearer your_jwt_token_here"
```

### Response Format

#### Success Response (200)
```json
{
  "status": "success",
  "data": {
    "id": 123,
    "store_id": 456,
    "name": "Toppings",
    "max_quantity": 3,
    "required": false,
    "price": "0.00",
    "status": "active",
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00",
    "total_orders": 25,
    "percentage": 10.5,
    "discount_id": 789,
    "discount_start_date": "2024-01-01",
    "discount_end_date": "2024-01-31"
  }
}
```

#### Error Responses

**400 Bad Request - Missing ID**
```json
{
  "status": "error",
  "message": "Section ID is required"
}
```

**400 Bad Request - Invalid ID Format**
```json
{
  "status": "error",
  "message": "Section ID must be a valid number"
}
```

**401 Unauthorized - Missing Token**
```json
{
  "status": "error",
  "message": "Authorization token missing"
}
```

**401 Unauthorized - Invalid Token**
```json
{
  "status": "error",
  "message": "Invalid or expired token"
}
```

**403 Forbidden - Wrong Role**
```json
{
  "status": "error",
  "message": "Only merchants can access food sections."
}
```

**403 Forbidden - No Store ID**
```json
{
  "status": "error",
  "message": "Store ID not found. Please ensure you are logged in as a merchant with a store setup."
}
```

**403 Forbidden - Unauthorized Access**
```json
{
  "status": "error",
  "message": "Unauthorized to access this food section."
}
```

**404 Not Found - Section Not Found**
```json
{
  "status": "error",
  "message": "Food section not found"
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Unique identifier for the food section |
| `store_id` | integer | ID of the store this section belongs to |
| `name` | string | Name of the food section |
| `max_quantity` | integer | Maximum number of items that can be selected from this section |
| `required` | boolean | Whether this section is required when ordering |
| `price` | decimal | Base price for this section (usually 0.00) |
| `status` | string | Current status: 'active' or 'inactive' |
| `created_at` | timestamp | When the section was created |
| `updated_at` | timestamp | When the section was last updated |
| `total_orders` | integer | Total number of orders that included this section |
| `percentage` | decimal | Discount percentage (only if discount exists) |
| `discount_id` | integer | Discount ID (only if discount exists) |
| `discount_start_date` | date | When discount starts (only if discount exists) |
| `discount_end_date` | date | When discount ends (only if discount exists) |

### Notes
- The endpoint automatically includes discount information if an active discount exists for the section
- Only sections belonging to the authenticated merchant's store can be accessed
- The `total_orders` field represents the count of orders that included items from this section
- Discount fields are only included when there's an active discount for the section

### Related Endpoints
- `GET /api/get_all_foodsections.php` - Get all food sections for a store
- `POST /api/create_food_section.php` - Create a new food section
- `PUT /api/update_food_section.php` - Update an existing food section
- `DELETE /api/deleteFoodSection.php` - Delete a food section
