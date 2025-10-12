# Orders System Documentation

## Overview
The Orders System provides comprehensive order management for the multivendor marketplace and logistics app. It supports all store types (food, retail, services) with real-time tracking, notifications, and delivery management.

## Features
- **Multi-Store Support**: Works with all store types (food, retail, services)
- **Order Status Management**: Pending → Accepted → Preparing → Ready → In Transit → Delivered
- **Real-time Tracking**: Live delivery tracking with GPS coordinates
- **Notifications**: Push notifications for all order status changes
- **Pagination**: Efficient handling of large order lists
- **Package Delivery**: Send package functionality for logistics
- **Order History**: Complete audit trail of status changes

## Database Schema

### Core Tables
1. **orders** - Main orders table
2. **order_items** - Items within each order
3. **order_selections** - Add-ons, sides, customizations
4. **order_status_history** - Status change tracking
5. **order_notifications** - Push notifications
6. **delivery_tracking** - Real-time GPS tracking
7. **package_deliveries** - Package delivery system

## API Endpoints

### 1. Get Merchant Orders
**Endpoint**: `GET /api/get-merchant-orders.php`

**Query Parameters**:
- `status` (optional): pending, accepted, preparing, ready_for_pickup, in_transit, delivered, cancelled
- `page` (optional): Page number (default: 1)
- `limit` (optional): Items per page (default: 20, max: 50)

**Response**:
```json
{
    "status": "success",
    "data": [
        {
            "id": 123,
            "order_number": "ORD202401011234",
            "status": "pending",
            "total_amount": 2500.00,
            "final_amount": 2800.00,
            "item_count": 2,
            "items_summary": "Jollof Rice + Beef, Fried Chicken",
            "delivery_address": "201 Marina Road, Ikoyi, Lagos",
            "customer_note": "Please add extra spice",
            "created_at": "2024-01-01 12:00:00",
            "time_ago": "5 mins ago",
            "can_cancel": true,
            "can_accept": true,
            "can_prepare": false,
            "can_ready": false
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

### 2. Get Order Details
**Endpoint**: `GET /api/get-order-details.php`

**Query Parameters**:
- `order_id` (required): Order ID

**Response**:
```json
{
    "status": "success",
    "data": {
        "id": 123,
        "order_number": "ORD202401011234",
        "status": "pending",
        "total_amount": 2500.00,
        "delivery_fee": 300.00,
        "tax_amount": 0.00,
        "final_amount": 2800.00,
        "payment_status": "pending",
        "payment_method": "cash",
        "delivery_address": "201 Marina Road, Ikoyi, Lagos",
        "delivery_instructions": "Call me once you arrive",
        "customer_note": "Please add extra spice",
        "merchant_note": null,
        "store_name": "Tasty Foods",
        "customer": {
            "name": "John Doe",
            "phone": "2341234567890",
            "email": "john@example.com"
        },
        "rider": null,
        "items": [
            {
                "id": 1,
                "item_name": "Jollof Rice + Beef",
                "item_price": 1200.00,
                "quantity": 1,
                "total_price": 1200.00,
                "item_image": "https://api.runnix.africa/uploads/items/jollof.jpg",
                "selections": [
                    {
                        "type": "addon",
                        "name": "Big Pack",
                        "price": 200.00,
                        "quantity": 1
                    },
                    {
                        "type": "side",
                        "name": "Plantain",
                        "price": 300.00,
                        "quantity": 1
                    }
                ]
            }
        ],
        "status_history": [
            {
                "status": "pending",
                "changed_by": 123,
                "change_reason": "Order created",
                "created_at": "2024-01-01 12:00:00"
            }
        ],
        "created_at": "2024-01-01 12:00:00",
        "time_ago": "5 mins ago",
        "timestamps": {
            "accepted_at": null,
            "ready_at": null,
            "picked_up_at": null,
            "delivered_at": null,
            "cancelled_at": null
        }
    }
}
```

### 3. Update Order Status
**Endpoint**: `POST /api/update-order-status.php`

**Request Body**:
```json
{
    "order_id": 123,
    "status": "accepted",
    "reason": "Order accepted by merchant",
    "notes": "Will be ready in 20 minutes"
}
```

**Response**:
```json
{
    "status": "success",
    "message": "Order status updated successfully"
}
```

### 4. Cancel Order
**Endpoint**: `POST /api/cancel-order.php`

**Request Body**:
```json
{
    "order_id": 123,
    "reason": "Customer requested cancellation"
}
```

### 5. Get Delivery Tracking
**Endpoint**: `GET /api/get-delivery-tracking.php`

**Query Parameters**:
- `order_id` (required): Order ID

**Response**:
```json
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "latitude": "6.5244",
            "longitude": "3.3792",
            "address": "Victoria Island, Lagos",
            "status": "in_transit",
            "estimated_delivery_time": 15,
            "created_at": "2024-01-01 12:30:00",
            "first_name": "James",
            "last_name": "Saturn",
            "phone": "2341234567890"
        }
    ]
}
```

### 6. Get Delivery Summary
**Endpoint**: `GET /api/get-delivery-summary.php`

**Query Parameters**:
- `order_id` (required): Order ID

**Response**:
```json
{
    "status": "success",
    "data": {
        "order_id": 123,
        "order_number": "ORD202401011234",
        "status": "in_transit",
        "store_name": "Tasty Foods",
        "delivery_address": "201 Marina Road, Ikoyi, Lagos",
        "customer": {
            "name": "John Doe",
            "phone": "2341234567890"
        },
        "rider": {
            "name": "James Saturn",
            "phone": "2341234567890"
        },
        "timeline": [
            {
                "status": "Order Placed",
                "time": "2024-01-01 12:00:00",
                "completed": true,
                "description": "Order was placed successfully"
            },
            {
                "status": "Order Accepted",
                "time": "2024-01-01 12:05:00",
                "completed": true,
                "description": "Merchant accepted the order"
            },
            {
                "status": "Ready for Pickup",
                "time": "2024-01-01 12:25:00",
                "completed": true,
                "description": "Order is ready for pickup"
            },
            {
                "status": "Picked Up",
                "time": "2024-01-01 12:30:00",
                "completed": true,
                "description": "Order has been picked up by rider"
            },
            {
                "status": "Delivered",
                "time": null,
                "completed": false,
                "description": "Order is on its way"
            }
        ],
        "tracking": [...],
        "estimated_delivery": 10,
        "created_at": "2024-01-01 12:00:00"
    }
}
```

### 7. Get Order Notifications
**Endpoint**: `GET /api/get-order-notifications.php`

**Query Parameters**:
- `page` (optional): Page number
- `limit` (optional): Items per page
- `unread_only` (optional): true/false

**Response**:
```json
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "order_id": 123,
            "order_number": "ORD202401011234",
            "order_status": "pending",
            "notification_type": "new_order",
            "title": "New Order Received",
            "message": "You have received a new order #ORD202401011234",
            "is_read": false,
            "created_at": "2024-01-01 12:00:00",
            "read_at": null
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total_count": 5,
        "total_pages": 1,
        "has_next": false,
        "has_prev": false
    }
}
```

### 8. Mark Notifications as Read
**Endpoint**: `POST /api/get-order-notifications.php`

**Request Body**:
```json
{
    "notification_ids": [1, 2, 3]
}
```

### 9. Send Package
**Endpoint**: `POST /api/send-package.php`

**Request Body**:
```json
{
    "receiver_name": "Jane Doe",
    "receiver_phone": "2341234567890",
    "receiver_address": "201 Marina Road, Ikoyi, Lagos",
    "receiver_latitude": "6.5244",
    "receiver_longitude": "3.3792",
    "package_description": "Documents",
    "package_value": 5000.00,
    "delivery_fee": 500.00,
    "insurance_fee": 50.00,
    "pickup_instructions": "Call before pickup",
    "delivery_instructions": "Leave at reception"
}
```

### 10. Get Package Deliveries
**Endpoint**: `GET /api/get-package-deliveries.php`

**Query Parameters**:
- `status` (optional): pending, accepted, picked_up, in_transit, delivered, cancelled
- `page` (optional): Page number
- `limit` (optional): Items per page

## Order Status Flow

```
pending → accepted → preparing → ready_for_pickup → in_transit → delivered
   ↓
cancelled (can happen at any stage before delivered)
```

### Status Descriptions
- **pending**: Order placed, waiting for merchant acceptance
- **accepted**: Merchant accepted the order
- **preparing**: Order is being prepared
- **ready_for_pickup**: Order ready for rider pickup
- **in_transit**: Order picked up and on the way
- **delivered**: Order delivered successfully
- **cancelled**: Order cancelled

## Permissions

### Merchant Permissions
- View all orders for their store
- Accept/reject orders
- Update order status (accepted → preparing → ready_for_pickup)
- Add merchant notes
- Cancel orders

### Customer Permissions
- View their own orders
- Cancel orders (before acceptance)
- Add delivery instructions
- Track order delivery

### Rider Permissions
- View assigned orders
- Update delivery status (in_transit → delivered)
- Add delivery tracking

## Frontend Integration Examples

### 1. Orders List with Pagination
```javascript
// Get pending orders
fetch('/api/get-merchant-orders.php?status=pending&page=1&limit=20', {
    headers: {
        'Authorization': 'Bearer ' + token
    }
})
.then(response => response.json())
.then(data => {
    if (data.status === 'success') {
        displayOrders(data.data);
        updatePagination(data.meta);
    }
});
```

### 2. Accept Order
```javascript
// Accept an order
fetch('/api/update-order-status.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify({
        order_id: 123,
        status: 'accepted',
        reason: 'Order accepted',
        notes: 'Will be ready in 20 minutes'
    })
})
.then(response => response.json())
.then(data => {
    if (data.status === 'success') {
        showSuccessMessage('Order accepted successfully');
        refreshOrdersList();
    }
});
```

### 3. Real-time Tracking
```javascript
// Get delivery tracking
function trackOrder(orderId) {
    fetch(`/api/get-delivery-tracking.php?order_id=${orderId}`, {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            updateMapWithTracking(data.data);
        }
    });
}

// Poll for updates every 30 seconds
setInterval(() => trackOrder(orderId), 30000);
```

## Migration Instructions

1. **Run Database Migration**:
   ```bash
   mysql -u username -p database_name < backend/migrations/create_orders_system.sql
   ```

2. **Test the System**:
   - Create test orders
   - Test status updates
   - Verify notifications
   - Test delivery tracking

## Error Handling

### Common Error Responses
- `400 Bad Request`: Invalid input or missing required fields
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Order or resource not found
- `500 Internal Server Error`: Database or server errors

### Error Response Format
```json
{
    "status": "error",
    "message": "Error description"
}
```

## Best Practices

1. **Pagination**: Always use pagination for order lists to improve performance
2. **Status Validation**: Validate status transitions on both frontend and backend
3. **Real-time Updates**: Use WebSockets or polling for real-time order updates
4. **Notifications**: Implement push notifications for important status changes
5. **Error Handling**: Provide clear error messages for better user experience
6. **Security**: Validate user permissions for all order operations
7. **Performance**: Use database indexes for efficient queries

## Future Enhancements

1. **WebSocket Integration**: Real-time order updates
2. **Advanced Analytics**: Order performance metrics
3. **Bulk Operations**: Accept/reject multiple orders
4. **Order Templates**: Save common order configurations
5. **Integration APIs**: Connect with external delivery services
6. **Mobile Push Notifications**: Native mobile notifications
7. **Order Scheduling**: Schedule orders for future delivery
