# Order Management API Documentation

## Overview
This document provides complete information about all order management endpoints in the Runnix system, with a focus on the order status update and cancellation endpoints.

---

## Order Status Endpoints

### 1. Update Order Status
**Endpoint:** `POST /backend/api/update-order-status.php`  
**Authentication:** Required (Merchant, Rider, or Customer based on status)  
**Description:** Update the status of an existing order

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `order_id` | integer | **Yes** | The unique order ID (must be positive integer) |
| `status` | string | **Yes** | New status for the order |
| `reason` | string | No | Reason for status change (optional) |
| `notes` | string | No | Additional notes (optional) |

#### Valid Status Values

| Status | Description | Who Can Set |
|--------|-------------|-------------|
| `pending` | Order placed, awaiting acceptance | System (on creation) |
| `accepted` | Order accepted by merchant | Merchant only |
| `preparing` | Order is being prepared | Merchant only |
| `ready_for_pickup` | Order ready for rider pickup | Merchant only |
| `in_transit` | Order picked up and in delivery | Assigned Rider only |
| `delivered` | Order delivered to customer | Assigned Rider only |
| `cancelled` | Order cancelled | Use cancel endpoint instead |

#### Permission Rules

- **Merchant (Store Owner)** can update to: `accepted`, `preparing`, `ready_for_pickup`
- **Rider (Assigned)** can update to: `in_transit`, `delivered`
- **Customer** cannot directly update order status (use cancel endpoint)

#### Sample Request

```http
POST /backend/api/update-order-status.php
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN

{
    "order_id": 12345,
    "status": "accepted"
}
```

#### Sample Request with Optional Fields

```http
POST /backend/api/update-order-status.php
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN

{
    "order_id": 12345,
    "status": "preparing",
    "reason": "Started food preparation",
    "notes": "Customer requested extra spicy"
}
```

#### Success Response

```json
{
    "status": "success",
    "message": "Order status updated successfully"
}
```
**HTTP Status:** 200

#### Error Responses

**Missing Order ID:**
```json
{
    "status": "error",
    "message": "Order ID is required."
}
```
**HTTP Status:** 400

**Invalid Order ID Format:**
```json
{
    "status": "error",
    "message": "Invalid order ID. Must be a positive integer."
}
```
**HTTP Status:** 400

**Missing Status:**
```json
{
    "status": "error",
    "message": "Status is required."
}
```
**HTTP Status:** 400

**Invalid Status:**
```json
{
    "status": "error",
    "message": "Invalid status. Valid statuses: pending, accepted, preparing, ready_for_pickup, in_transit, delivered, cancelled"
}
```
**HTTP Status:** 400

**Order Not Found:**
```json
{
    "status": "error",
    "message": "Order not found"
}
```
**HTTP Status:** 404

**Permission Denied:**
```json
{
    "status": "error",
    "message": "Only the store owner can update order to this status"
}
```
**HTTP Status:** 403

**Permission Denied (Rider):**
```json
{
    "status": "error",
    "message": "Only the assigned rider can update order to this status"
}
```
**HTTP Status:** 403

**Server Error:**
```json
{
    "status": "error",
    "message": "Failed to update order status"
}
```
**HTTP Status:** 500

---

### 2. Cancel Order
**Endpoint:** `POST /backend/api/cancel-order.php`  
**Authentication:** Required (Customer or Merchant only)  
**Description:** Cancel an existing order

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `order_id` | integer | **Yes** | The unique order ID (must be positive integer) - ONLY REQUIRED FIELD |
| `reason` | string | No | Reason for cancellation (optional) |

**Note:** This endpoint accepts **ONLY** `order_id` as the required parameter. All other parameters are optional.

#### Cancellation Rules

- Orders with status `delivered` or `cancelled` cannot be cancelled
- Only the **customer** (who placed the order) or **merchant** (store owner) can cancel
- Riders cannot cancel orders
- Status automatically changes to `cancelled` when successful

#### Sample Request (Minimal - Only Required Field)

```http
POST /backend/api/cancel-order.php
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN

{
    "order_id": 12345
}
```

#### Sample Request with Optional Reason

```http
POST /backend/api/cancel-order.php
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN

{
    "order_id": 12345,
    "reason": "Customer changed their mind"
}
```

#### Success Response

```json
{
    "status": "success",
    "message": "Order cancelled successfully"
}
```
**HTTP Status:** 200

#### Error Responses

**Missing Order ID:**
```json
{
    "status": "error",
    "message": "Order ID is required."
}
```
**HTTP Status:** 400

**Invalid Order ID Format:**
```json
{
    "status": "error",
    "message": "Invalid order ID. Must be a positive integer."
}
```
**HTTP Status:** 400

**Order Not Found:**
```json
{
    "status": "error",
    "message": "Order not found"
}
```
**HTTP Status:** 404

**Order Cannot Be Cancelled:**
```json
{
    "status": "error",
    "message": "Order cannot be cancelled"
}
```
**HTTP Status:** 400  
*This occurs when order is already `delivered` or `cancelled`*

**Permission Denied:**
```json
{
    "status": "error",
    "message": "Only the customer or store owner can cancel this order"
}
```
**HTTP Status:** 403

**Server Error:**
```json
{
    "status": "error",
    "message": "Failed to cancel order"
}
```
**HTTP Status:** 500

---

## Additional Order Endpoints

### 3. Get Merchant Orders
**Endpoint:** `GET /backend/api/get-merchant-orders.php`  
**Authentication:** Required (Merchant only)  
**Description:** Get all orders for merchant's store with pagination

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `status` | string | No | Filter by order status |
| `page` | integer | No | Page number (default: 1) |
| `limit` | integer | No | Items per page (default: 20, max: 50) |

#### Sample Request

```http
GET /backend/api/get-merchant-orders.php?status=pending&page=1&limit=20
Authorization: Bearer YOUR_JWT_TOKEN
```

#### Success Response

```json
{
    "status": "success",
    "data": [
        {
            "order_number": "ORD-20251001-001",
            "status": "pending",
            "date_time": "2025-10-01 14:30:00",
            "customer": {
                "name": "John Doe",
                "phone": "+1234567890",
                "email": "john@example.com",
                "delivery_address": "123 Main St, City"
            },
            "items": [
                {
                    "item_photo": "https://api.runnix.africa/uploads/food-items/burger.jpg",
                    "item_name": "Grilled Chicken Burger",
                    "item_price": 12.99,
                    "item_quantity": 2,
                    "item_total_price": 25.98,
                    "item_selections": [
                        {
                            "selection_type": "side",
                            "selection_name": "French Fries",
                            "selection_price": 2.99,
                            "selection_quantity": 1,
                            "selection_total_price": 2.99
                        }
                    ]
                }
            ],
            "note_for_restaurant": "Extra crispy please",
            "store_name": "Delicious Kitchen",
            "total_amount": 28.97,
            "delivery_fee": 5.00,
            "tax_amount": 1.45,
            "final_amount": 35.42,
            "payment_status": "pending",
            "payment_method": "cash",
            "time_ago": "30 mins ago"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total_count": 45,
        "total_pages": 3,
        "has_next": true,
        "has_prev": false
    }
}
```

---

### 4. Get Order Details
**Endpoint:** `GET /backend/api/get-order-details.php`  
**Authentication:** Required (Customer, Merchant, or Rider)  
**Description:** Get detailed information about a specific order

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `order_id` | integer | **Yes** | The unique order ID |

#### Sample Request

```http
GET /backend/api/get-order-details.php?order_id=12345
Authorization: Bearer YOUR_JWT_TOKEN
```

#### Success Response

```json
{
    "status": "success",
    "data": {
        "order_number": "ORD-20251001-001",
        "status": "preparing",
        "date_time": "2025-10-01 14:30:00",
        "customer": {
            "name": "John Doe",
            "phone": "+1234567890",
            "email": "john@example.com",
            "delivery_address": "123 Main St, City"
        },
        "items": [
            {
                "item_photo": "https://api.runnix.africa/uploads/food-items/burger.jpg",
                "item_name": "Grilled Chicken Burger",
                "item_price": 12.99,
                "item_quantity": 2,
                "item_total_price": 25.98,
                "item_selections": [
                    {
                        "selection_type": "side",
                        "selection_name": "French Fries",
                        "selection_price": 2.99,
                        "selection_quantity": 1,
                        "selection_total_price": 2.99
                    }
                ]
            }
        ],
        "note_for_restaurant": "Extra crispy please",
        "store_name": "Delicious Kitchen",
        "total_amount": 28.97,
        "delivery_fee": 5.00,
        "tax_amount": 1.45,
        "final_amount": 35.42,
        "payment_status": "pending",
        "payment_method": "cash",
        "delivery_instructions": "Ring doorbell twice",
        "merchant_note": "Preparing now",
        "rider": {
            "name": "Mike Rider",
            "phone": "+1234567891"
        },
        "status_history": [
            {
                "status": "pending",
                "timestamp": "2025-10-01 14:30:00"
            },
            {
                "status": "accepted",
                "timestamp": "2025-10-01 14:32:00"
            },
            {
                "status": "preparing",
                "timestamp": "2025-10-01 14:35:00"
            }
        ],
        "time_ago": "30 mins ago",
        "timestamps": {
            "accepted_at": "2025-10-01 14:32:00",
            "ready_at": null,
            "picked_up_at": null,
            "delivered_at": null,
            "cancelled_at": null
        }
    }
}
```

---

### 5. Create Customer Order
**Endpoint:** `POST /backend/api/user/create_order.php`  
**Authentication:** Required (Customer only)  
**Description:** Create a new order

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `store_id` | integer | **Yes** | ID of the store |
| `items` | array | **Yes** | Array of order items |
| `delivery_address` | string | **Yes** | Delivery address |
| `delivery_instructions` | string | No | Special delivery instructions |
| `customer_note` | string | No | Note for restaurant/merchant |
| `payment_method` | string | No | Payment method (default: `cash_on_delivery`) |

#### Items Array Structure

```json
{
    "item_id": 123,
    "quantity": 2,
    "selections": [
        {
            "selection_id": 45,
            "selection_type": "side",
            "quantity": 1
        }
    ]
}
```

#### Sample Request

```http
POST /backend/api/user/create_order.php
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN

{
    "store_id": 5,
    "items": [
        {
            "item_id": 23,
            "quantity": 2,
            "selections": [
                {
                    "selection_id": 12,
                    "selection_type": "side",
                    "quantity": 1
                },
                {
                    "selection_id": 8,
                    "selection_type": "pack",
                    "quantity": 1
                }
            ]
        }
    ],
    "delivery_address": "123 Main Street, City, State",
    "delivery_instructions": "Ring doorbell twice",
    "customer_note": "Extra crispy please",
    "payment_method": "cash_on_delivery"
}
```

#### Success Response

```json
{
    "status": "success",
    "message": "Order created successfully",
    "data": {
        "order_id": 12345,
        "order_number": "ORD-20251001-001",
        "status": "pending",
        "total_amount": 28.97,
        "final_amount": 35.42,
        "payment_status": "pending",
        "created_at": "2025-10-01 14:30:00"
    }
}
```
**HTTP Status:** 201

---

## Code Examples

### JavaScript/Fetch - Update Order Status

```javascript
const updateOrderStatus = async (orderId, status) => {
    const token = localStorage.getItem('jwt_token');
    
    try {
        const response = await fetch('https://api.runnix.africa/backend/api/update-order-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                order_id: orderId,
                status: status
            })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            console.log('Order status updated successfully');
            return data;
        } else {
            console.error('Error:', data.message);
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Request failed:', error);
        throw error;
    }
};

// Usage
updateOrderStatus(12345, 'accepted')
    .then(() => console.log('Order accepted'))
    .catch(error => console.error('Failed to accept order:', error));
```

### JavaScript/Fetch - Cancel Order

```javascript
const cancelOrder = async (orderId, reason = null) => {
    const token = localStorage.getItem('jwt_token');
    
    const payload = { order_id: orderId };
    if (reason) {
        payload.reason = reason;
    }
    
    try {
        const response = await fetch('https://api.runnix.africa/backend/api/cancel-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            console.log('Order cancelled successfully');
            return data;
        } else {
            console.error('Error:', data.message);
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Request failed:', error);
        throw error;
    }
};

// Usage - with only order_id (minimal)
cancelOrder(12345)
    .then(() => console.log('Order cancelled'))
    .catch(error => console.error('Failed to cancel order:', error));

// Usage - with reason
cancelOrder(12345, 'Customer changed their mind')
    .then(() => console.log('Order cancelled with reason'))
    .catch(error => console.error('Failed to cancel order:', error));
```

### React Hook Example

```javascript
import { useState } from 'react';

const useOrderManagement = () => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    
    const updateStatus = async (orderId, status, reason = null, notes = null) => {
        setLoading(true);
        setError(null);
        
        try {
            const token = localStorage.getItem('jwt_token');
            const payload = { order_id: orderId, status: status };
            
            if (reason) payload.reason = reason;
            if (notes) payload.notes = notes;
            
            const response = await fetch('https://api.runnix.africa/backend/api/update-order-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(payload)
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                return data;
            } else {
                throw new Error(data.message);
            }
        } catch (err) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };
    
    const cancelOrder = async (orderId, reason = null) => {
        setLoading(true);
        setError(null);
        
        try {
            const token = localStorage.getItem('jwt_token');
            const payload = { order_id: orderId };
            
            if (reason) payload.reason = reason;
            
            const response = await fetch('https://api.runnix.africa/backend/api/cancel-order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(payload)
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                return data;
            } else {
                throw new Error(data.message);
            }
        } catch (err) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };
    
    return { updateStatus, cancelOrder, loading, error };
};

// Usage in component
const OrderManagement = ({ orderId }) => {
    const { updateStatus, cancelOrder, loading, error } = useOrderManagement();
    
    const handleAcceptOrder = async () => {
        try {
            await updateStatus(orderId, 'accepted');
            alert('Order accepted!');
        } catch (err) {
            alert(`Failed: ${err.message}`);
        }
    };
    
    const handleCancelOrder = async () => {
        try {
            await cancelOrder(orderId, 'Out of stock');
            alert('Order cancelled!');
        } catch (err) {
            alert(`Failed: ${err.message}`);
        }
    };
    
    return (
        <div>
            <button onClick={handleAcceptOrder} disabled={loading}>
                Accept Order
            </button>
            <button onClick={handleCancelOrder} disabled={loading}>
                Cancel Order
            </button>
            {error && <p className="error">{error}</p>}
        </div>
    );
};
```

### PHP/cURL Example

```php
<?php
// Update Order Status
function updateOrderStatus($orderId, $status, $token, $reason = null, $notes = null) {
    $url = "https://api.runnix.africa/backend/api/update-order-status.php";
    
    $payload = [
        'order_id' => $orderId,
        'status' => $status
    ];
    
    if ($reason) $payload['reason'] = $reason;
    if ($notes) $payload['notes'] = $notes;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $token"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

// Cancel Order
function cancelOrder($orderId, $token, $reason = null) {
    $url = "https://api.runnix.africa/backend/api/cancel-order.php";
    
    $payload = ['order_id' => $orderId];
    if ($reason) $payload['reason'] = $reason;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $token"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

// Usage
$token = "YOUR_JWT_TOKEN";

// Update status
$result = updateOrderStatus(12345, 'accepted', $token);
if ($result['http_code'] === 200 && $result['response']['status'] === 'success') {
    echo "Order updated successfully\n";
} else {
    echo "Error: " . $result['response']['message'] . "\n";
}

// Cancel order (only order_id required)
$result = cancelOrder(12345, $token);
if ($result['http_code'] === 200 && $result['response']['status'] === 'success') {
    echo "Order cancelled successfully\n";
} else {
    echo "Error: " . $result['response']['message'] . "\n";
}

// Cancel order with reason
$result = cancelOrder(12345, $token, 'Customer changed their mind');
?>
```

---

## Order Status Flow

```
┌─────────┐
│ pending │  ← Order created
└────┬────┘
     │
     ↓
┌──────────┐
│ accepted │  ← Merchant accepts
└────┬─────┘
     │
     ↓
┌───────────┐
│ preparing │  ← Merchant starts preparation
└────┬──────┘
     │
     ↓
┌──────────────────┐
│ ready_for_pickup │  ← Order ready
└────┬─────────────┘
     │
     ↓
┌────────────┐
│ in_transit │  ← Rider picked up
└────┬───────┘
     │
     ↓
┌───────────┐
│ delivered │  ← Successfully delivered
└───────────┘

Note: Orders can be cancelled from any status except 'delivered' or 'cancelled'
```

---

## Best Practices

1. **Validation**: Always validate `order_id` on the client side before making requests
2. **Error Handling**: Implement proper error handling for all possible HTTP status codes
3. **Status Transitions**: Respect the order status flow - don't skip statuses
4. **Permissions**: Check user role before allowing status updates
5. **Real-time Updates**: Consider implementing WebSocket or polling for real-time order updates
6. **Logging**: Log all order status changes for audit purposes
7. **Notifications**: Send push notifications to relevant parties on status changes

---

## Security Considerations

- All endpoints require JWT authentication
- Users can only update orders they have permission to modify
- Order IDs must be validated to prevent SQL injection
- Rate limiting should be implemented to prevent abuse
- Sensitive information (payment details) should not be exposed in responses

---

## Common Issues & Solutions

### Issue: "Invalid order ID"
**Solution:** Ensure you're sending the order_id as an integer, not a string

### Issue: "Permission denied"
**Solution:** Check that the authenticated user has the correct role and is associated with the order

### Issue: "Order cannot be cancelled"
**Solution:** Check the order status - only non-delivered, non-cancelled orders can be cancelled

### Issue: "Invalid status"
**Solution:** Use only the valid status values listed in the documentation

---

## Version History

- **v1.0** (2025-10-01): Initial release with order management endpoints
- **v1.1** (2025-10-01): Refined validation for `order_id` parameter, clarified that cancel endpoint only requires `order_id`

---

## Support

For questions or issues related to order management endpoints, please contact the development team or refer to the main API documentation.

