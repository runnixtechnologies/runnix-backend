# Analytics API Documentation

## Overview
This document provides complete information about all analytics endpoints in the Runnix system, including sample JSON responses and database structure.

---

## Database Structure

### Core Tables Used for Analytics

#### 1. **orders** table
```sql
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    merchant_id INT NOT NULL,
    rider_id INT NULL,
    store_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'preparing', 'ready_for_pickup', 'in_transit', 'delivered', 'cancelled'),
    total_amount DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    final_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded'),
    payment_method ENUM('cash', 'card', 'wallet', 'bank_transfer'),
    rating DECIMAL(2,1) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 2. **order_items** table
```sql
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    item_type ENUM('food_item', 'item') NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
```

#### 3. **food_items** table
```sql
CREATE TABLE food_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    photo VARCHAR(500) NULL,
    short_description TEXT NULL,
    deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 4. **items** table
```sql
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    photo VARCHAR(500) NULL,
    short_description TEXT NULL,
    deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 5. **stores** table
```sql
CREATE TABLE stores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_name VARCHAR(255) NOT NULL,
    store_type_id INT,
    store_type_name VARCHAR(255),
    user_id INT NOT NULL
);
```

---

## Analytics Endpoints

### 1. Get Analytics Summary
**Endpoint:** `GET /backend/api/merchant_analytics.php`  
**Authentication:** Required (Merchant only)  
**Description:** Get weekly analytics summary with comparison to previous week

**Query Parameters:** None

**Sample Request:**
```http
GET /backend/api/merchant_analytics.php
Authorization: Bearer YOUR_JWT_TOKEN
```

**Sample Response:**
```json
{
    "status": "success",
    "data": {
        "current_metrics": {
            "total_revenue": 15750.50,
            "total_orders": 87,
            "total_profile_visits": 245,
            "total_users": 63,
            "avg_response_time": 18.5,
            "total_rating": 4.6
        },
        "growth_percentages": {
            "total_revenue": 12.5,
            "total_orders": 8.75,
            "total_profile_visits": 15.3,
            "total_users": 22.4,
            "avg_response_time": -5.2,
            "total_rating": 2.2
        },
        "store_type": "food"
    }
}
```

---

### 2. Get Merchant Metrics
**Endpoint:** `GET /backend/api/merchant_metrics.php`  
**Authentication:** Required (Merchant only)  
**Description:** Get detailed metrics for a specific time period

**Query Parameters:**
- `period` (optional): One of `today`, `yesterday`, `this_week`, `last_week`, `this_month`, `last_month`, `this_year`, `all_time`, `custom` (default: `this_week`)
- `start_date` (optional): Start date for custom period (format: YYYY-MM-DD)
- `end_date` (optional): End date for custom period (format: YYYY-MM-DD)

**Sample Request:**
```http
GET /backend/api/merchant_metrics.php?period=this_month
Authorization: Bearer YOUR_JWT_TOKEN
```

**Sample Response:**
```json
{
    "status": "success",
    "data": {
        "store_info": {
            "id": 5,
            "name": "Delicious Kitchen",
            "type": "food",
            "store_type": "Restaurant"
        },
        "date_range": {
            "period": "this_month",
            "start_date": "2025-10-01 00:00:00",
            "end_date": "2025-10-01 23:59:59"
        },
        "metrics": {
            "total_revenue": 45230.75,
            "total_orders": 312,
            "total_profile_visits": 1245,
            "total_users": 187,
            "avg_response_time": 22.3,
            "total_rating": 4.5
        }
    }
}
```

**Sample Request (Custom Date Range):**
```http
GET /backend/api/merchant_metrics.php?period=custom&start_date=2025-09-01&end_date=2025-09-30
Authorization: Bearer YOUR_JWT_TOKEN
```

**Sample Response (Custom Date Range):**
```json
{
    "status": "success",
    "data": {
        "store_info": {
            "id": 5,
            "name": "Delicious Kitchen",
            "type": "food",
            "store_type": "Restaurant"
        },
        "date_range": {
            "period": "custom",
            "start_date": "2025-09-01 00:00:00",
            "end_date": "2025-09-30 23:59:59"
        },
        "metrics": {
            "total_revenue": 52180.25,
            "total_orders": 368,
            "total_profile_visits": 1567,
            "total_users": 213,
            "avg_response_time": 19.8,
            "total_rating": 4.6
        }
    }
}
```

---

### 3. Get Orders Analytics (Bar Chart Data)
**Endpoint:** `GET /backend/api/merchant_orders_analytics.php`  
**Authentication:** Required (Merchant only)  
**Description:** Get daily orders and revenue breakdown for bar charts

**Query Parameters:**
- `period` (optional): One of `today`, `yesterday`, `this_week`, `last_week`, `this_month`, `last_month`, `this_year`, `all_time`, `custom` (default: `this_week`)
- `start_date` (optional): Start date for custom period (format: YYYY-MM-DD)
- `end_date` (optional): End date for custom period (format: YYYY-MM-DD)

**Sample Request:**
```http
GET /backend/api/merchant_orders_analytics.php?period=this_week
Authorization: Bearer YOUR_JWT_TOKEN
```

**Sample Response:**
```json
{
    "status": "success",
    "data": {
        "store_info": {
            "id": 5,
            "name": "Delicious Kitchen"
        },
        "date_range": {
            "period": "this_week",
            "start_date": "2025-09-29 00:00:00",
            "end_date": "2025-10-01 23:59:59"
        },
        "orders_analytics": [
            {
                "date": "2025-09-29",
                "orders": 12,
                "revenue": 1875.50
            },
            {
                "date": "2025-09-30",
                "orders": 18,
                "revenue": 2654.75
            },
            {
                "date": "2025-10-01",
                "orders": 15,
                "revenue": 2245.25
            }
        ]
    }
}
```

**Sample Request (Last Month):**
```http
GET /backend/api/merchant_orders_analytics.php?period=last_month
Authorization: Bearer YOUR_JWT_TOKEN
```

**Sample Response (Last Month):**
```json
{
    "status": "success",
    "data": {
        "store_info": {
            "id": 5,
            "name": "Delicious Kitchen"
        },
        "date_range": {
            "period": "last_month",
            "start_date": "2025-09-01 00:00:00",
            "end_date": "2025-09-30 23:59:59"
        },
        "orders_analytics": [
            {
                "date": "2025-09-01",
                "orders": 10,
                "revenue": 1520.00
            },
            {
                "date": "2025-09-02",
                "orders": 14,
                "revenue": 2156.50
            },
            {
                "date": "2025-09-03",
                "orders": 16,
                "revenue": 2487.75
            },
            // ... more daily data
            {
                "date": "2025-09-30",
                "orders": 18,
                "revenue": 2654.75
            }
        ]
    }
}
```

---

### 4. Get Top Performing Items
**Endpoint:** `GET /backend/api/merchant_top_items.php`  
**Authentication:** Required (Merchant only)  
**Description:** Get top selling items/food items based on revenue

**Query Parameters:**
- `period` (optional): One of `today`, `yesterday`, `this_week`, `last_week`, `this_month`, `last_month`, `this_year`, `all_time`, `custom` (default: `this_week`)
- `start_date` (optional): Start date for custom period (format: YYYY-MM-DD)
- `end_date` (optional): End date for custom period (format: YYYY-MM-DD)
- `limit` (optional): Number of top items to return (1-20, default: 5)

**Sample Request:**
```http
GET /backend/api/merchant_top_items.php?period=this_month&limit=5
Authorization: Bearer YOUR_JWT_TOKEN
```

**Sample Response (Food Store):**
```json
{
    "status": "success",
    "data": {
        "store_info": {
            "id": 5,
            "name": "Delicious Kitchen",
            "type": "food",
            "store_type": "Restaurant"
        },
        "date_range": {
            "period": "this_month",
            "start_date": "2025-10-01 00:00:00",
            "end_date": "2025-10-01 23:59:59"
        },
        "top_performing_items": [
            {
                "id": 23,
                "name": "Grilled Chicken Burger",
                "price": 12.99,
                "photo": "https://yourdomain.com/uploads/food-items/chicken-burger.jpg",
                "short_description": "Juicy grilled chicken with fresh vegetables",
                "order_count": 145,
                "total_quantity": 187,
                "total_revenue": 2429.13
            },
            {
                "id": 18,
                "name": "Margherita Pizza",
                "price": 15.99,
                "photo": "https://yourdomain.com/uploads/food-items/margherita-pizza.jpg",
                "short_description": "Classic Italian pizza with fresh mozzarella",
                "order_count": 132,
                "total_quantity": 145,
                "total_revenue": 2318.55
            },
            {
                "id": 42,
                "name": "Caesar Salad",
                "price": 8.99,
                "photo": "https://yourdomain.com/uploads/food-items/caesar-salad.jpg",
                "short_description": "Fresh romaine lettuce with Caesar dressing",
                "order_count": 98,
                "total_quantity": 112,
                "total_revenue": 1006.88
            },
            {
                "id": 56,
                "name": "Chocolate Lava Cake",
                "price": 6.99,
                "photo": "https://yourdomain.com/uploads/food-items/lava-cake.jpg",
                "short_description": "Warm chocolate cake with molten center",
                "order_count": 87,
                "total_quantity": 92,
                "total_revenue": 643.08
            },
            {
                "id": 31,
                "name": "Thai Green Curry",
                "price": 13.99,
                "photo": "https://yourdomain.com/uploads/food-items/green-curry.jpg",
                "short_description": "Authentic Thai curry with vegetables",
                "order_count": 76,
                "total_quantity": 81,
                "total_revenue": 1133.19
            }
        ],
        "limit": 5
    }
}
```

**Sample Response (Non-Food Store):**
```json
{
    "status": "success",
    "data": {
        "store_info": {
            "id": 12,
            "name": "Tech Gadgets Pro",
            "type": "non_food",
            "store_type": "Electronics"
        },
        "date_range": {
            "period": "this_month",
            "start_date": "2025-10-01 00:00:00",
            "end_date": "2025-10-01 23:59:59"
        },
        "top_performing_items": [
            {
                "id": 8,
                "name": "Wireless Earbuds Pro",
                "price": 79.99,
                "photo": "https://yourdomain.com/uploads/items/wireless-earbuds.jpg",
                "short_description": "Premium wireless earbuds with noise cancellation",
                "order_count": 45,
                "total_quantity": 52,
                "total_revenue": 4159.48
            },
            {
                "id": 15,
                "name": "Smart Watch Series 5",
                "price": 249.99,
                "photo": "https://yourdomain.com/uploads/items/smart-watch.jpg",
                "short_description": "Feature-rich smartwatch with health tracking",
                "order_count": 28,
                "total_quantity": 30,
                "total_revenue": 7499.70
            },
            {
                "id": 22,
                "name": "Portable Power Bank 20000mAh",
                "price": 39.99,
                "photo": "https://yourdomain.com/uploads/items/power-bank.jpg",
                "short_description": "High capacity portable charger",
                "order_count": 67,
                "total_quantity": 78,
                "total_revenue": 3119.22
            },
            {
                "id": 33,
                "name": "Bluetooth Speaker",
                "price": 59.99,
                "photo": "https://yourdomain.com/uploads/items/bluetooth-speaker.jpg",
                "short_description": "Waterproof portable speaker",
                "order_count": 41,
                "total_quantity": 43,
                "total_revenue": 2579.57
            },
            {
                "id": 19,
                "name": "Phone Stand & Charger",
                "price": 24.99,
                "photo": "https://yourdomain.com/uploads/items/phone-stand.jpg",
                "short_description": "2-in-1 phone stand with wireless charging",
                "order_count": 56,
                "total_quantity": 61,
                "total_revenue": 1524.39
            }
        ],
        "limit": 5
    }
}
```

**Sample Request (Top 10 Items):**
```http
GET /backend/api/merchant_top_items.php?period=all_time&limit=10
Authorization: Bearer YOUR_JWT_TOKEN
```

---

### 5. Get Logout Analytics (Admin Only)
**Endpoint:** `GET /backend/api/admin/logout_analytics.php`  
**Authentication:** Required (Admin only)  
**Description:** Get system-wide logout analytics and suspicious activity

**Query Parameters:**
- `days` (optional): Number of days to analyze (1-365, default: 30)
- `hours` (optional): Hours to check for suspicious activity (1-168, default: 24)

**Sample Request:**
```http
GET /backend/api/admin/logout_analytics.php?days=30&hours=24
Authorization: Bearer YOUR_JWT_TOKEN
```

**Sample Response:**
```json
{
    "status": "success",
    "message": "Logout analytics retrieved successfully",
    "data": {
        "period_days": 30,
        "suspicious_hours": 24,
        "summary": {
            "total_logouts": 1547,
            "total_unique_users": 342,
            "overall_avg_session_duration": 125.47,
            "suspicious_activity_count": 8
        },
        "analytics_by_role": [
            {
                "role": "customer",
                "total_logouts": 1245,
                "unique_users": 287,
                "avg_session_duration": 95.32,
                "manual_logouts": 1156,
                "token_expired_logouts": 89
            },
            {
                "role": "merchant",
                "total_logouts": 215,
                "unique_users": 42,
                "avg_session_duration": 245.63,
                "manual_logouts": 198,
                "token_expired_logouts": 17
            },
            {
                "role": "rider",
                "total_logouts": 87,
                "unique_users": 13,
                "avg_session_duration": 187.21,
                "manual_logouts": 79,
                "token_expired_logouts": 8
            }
        ],
        "suspicious_activity": [
            {
                "user_id": 156,
                "username": "john_doe",
                "role": "customer",
                "logout_count": 15,
                "last_logout_at": "2025-10-01 14:32:15",
                "reason": "High frequency logouts"
            },
            {
                "user_id": 89,
                "username": "merchant_store5",
                "role": "merchant",
                "logout_count": 12,
                "last_logout_at": "2025-10-01 10:15:42",
                "reason": "High frequency logouts"
            }
        ]
    }
}
```

---

## Error Responses

### Authentication Error
```json
{
    "status": "error",
    "message": "Unauthorized. Please provide a valid token."
}
```
**HTTP Status:** 401

### Authorization Error
```json
{
    "status": "error",
    "message": "Access denied. Only merchants can access analytics."
}
```
**HTTP Status:** 403

### Invalid Period Error
```json
{
    "status": "error",
    "message": "Invalid period. Allowed values: today, yesterday, this_week, last_week, this_month, last_month, this_year, all_time, custom"
}
```
**HTTP Status:** 400

### Missing Store ID Error
```json
{
    "status": "error",
    "message": "Store ID not found. Please ensure your merchant account is properly set up."
}
```
**HTTP Status:** 400

### Custom Date Range Error
```json
{
    "status": "error",
    "message": "Start date and end date are required for custom period"
}
```
**HTTP Status:** 400

### Invalid Date Format Error
```json
{
    "status": "error",
    "message": "Invalid date format. Use YYYY-MM-DD format"
}
```
**HTTP Status:** 400

### Date Range Validation Error
```json
{
    "status": "error",
    "message": "Start date cannot be after end date"
}
```
**HTTP Status:** 400

### Invalid Limit Error
```json
{
    "status": "error",
    "message": "Limit must be between 1 and 20"
}
```
**HTTP Status:** 400

### Store Not Found Error
```json
{
    "status": "error",
    "message": "Store not found"
}
```
**HTTP Status:** 404

### Internal Server Error
```json
{
    "status": "error",
    "message": "Internal server error"
}
```
**HTTP Status:** 500

---

## Metrics Explanation

### Revenue Metrics
- **total_revenue**: Sum of all completed/delivered orders' revenue (quantity × price)
- Calculated from: `order_items.quantity * order_items.price` for orders with status `completed` or `delivered`

### Order Metrics
- **total_orders**: Count of all orders in the specified period
- **order_count**: Number of times an item was ordered
- **total_quantity**: Total quantity of items sold

### User Metrics
- **total_users**: Count of distinct customers who placed orders
- **total_profile_visits**: Count of distinct users who visited the store (currently based on order users)

### Performance Metrics
- **avg_response_time**: Average time (in minutes) from order creation to order completion
- **total_rating**: Average rating from orders (scale: 0-5)

### Growth Metrics
- **growth_percentages**: Percentage change compared to previous period
  - Positive values indicate growth
  - Negative values indicate decline
  - Calculated as: `((current - previous) / previous) * 100`

---

## Period Types

| Period | Description |
|--------|-------------|
| `today` | From 00:00:00 to 23:59:59 today |
| `yesterday` | From 00:00:00 to 23:59:59 yesterday |
| `this_week` | From Monday 00:00:00 to now |
| `last_week` | From Monday to Sunday of last week |
| `this_month` | From 1st day 00:00:00 to now |
| `last_month` | From 1st to last day of last month |
| `this_year` | From January 1st 00:00:00 to now |
| `all_time` | All historical data |
| `custom` | Custom date range (requires start_date and end_date) |

---

## Authentication

All analytics endpoints require JWT authentication. Include the JWT token in the Authorization header:

```http
Authorization: Bearer YOUR_JWT_TOKEN
```

The token must belong to a user with the appropriate role:
- **Merchant endpoints**: Require `merchant` role
- **Admin endpoints**: Require `admin` role

---

## Rate Limiting

Currently, no rate limiting is implemented on analytics endpoints. However, it's recommended to:
- Cache analytics data on the client side
- Avoid making excessive requests
- Use appropriate periods to reduce database load

---

## Best Practices

1. **Use appropriate time periods**: Don't fetch `all_time` data if you only need recent metrics
2. **Cache results**: Analytics data doesn't change frequently, so cache responses
3. **Paginate when possible**: Use reasonable limits for top items (5-10 items)
4. **Handle errors gracefully**: Always check the `status` field in responses
5. **Monitor performance**: Track response times for analytics queries
6. **Use custom periods wisely**: Avoid very large date ranges that could slow down queries

---

## Code Examples

### PHP/cURL Example
```php
<?php
$token = "YOUR_JWT_TOKEN";
$url = "https://yourdomain.com/backend/api/merchant_metrics.php?period=this_month";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

if ($data['status'] === 'success') {
    echo "Total Revenue: $" . $data['data']['metrics']['total_revenue'];
}
?>
```

### JavaScript/Fetch Example
```javascript
const token = "YOUR_JWT_TOKEN";
const period = "this_week";

fetch(`https://yourdomain.com/backend/api/merchant_metrics.php?period=${period}`, {
    method: 'GET',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    if (data.status === 'success') {
        console.log('Total Revenue:', data.data.metrics.total_revenue);
        console.log('Total Orders:', data.data.metrics.total_orders);
    }
})
.catch(error => console.error('Error:', error));
```

### React Example
```javascript
import { useState, useEffect } from 'react';

const AnalyticsDashboard = () => {
    const [metrics, setMetrics] = useState(null);
    const [loading, setLoading] = useState(true);
    const token = localStorage.getItem('jwt_token');

    useEffect(() => {
        fetchMetrics();
    }, []);

    const fetchMetrics = async () => {
        try {
            const response = await fetch(
                'https://yourdomain.com/backend/api/merchant_analytics.php',
                {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                }
            );
            const data = await response.json();
            
            if (data.status === 'success') {
                setMetrics(data.data);
            }
        } catch (error) {
            console.error('Error fetching metrics:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <div>Loading...</div>;

    return (
        <div>
            <h1>Analytics Dashboard</h1>
            <div className="metrics">
                <div>Revenue: ${metrics.current_metrics.total_revenue}</div>
                <div>Orders: {metrics.current_metrics.total_orders}</div>
                <div>Growth: {metrics.growth_percentages.total_revenue}%</div>
            </div>
        </div>
    );
};
```

---

## Notes

1. **Store Type Detection**: The system automatically detects if a store is a food store or non-food store based on:
   - Store type name containing food-related keywords
   - Presence of food_items in the database

2. **Date Handling**: All dates are stored in MySQL `TIMESTAMP` format (YYYY-MM-DD HH:MM:SS)

3. **Revenue Calculation**: Revenue is calculated only from orders with status `completed` or `delivered`

4. **Top Items**: Items are ranked by total revenue first, then by order count

5. **Performance**: Large date ranges (all_time) may take longer to process. Consider pagination or limiting results.

---

## Version History

- **v1.0** (2025-10-01): Initial release with all analytics endpoints

---

## Support

For questions or issues related to analytics endpoints, please contact the development team or refer to the main API documentation.

