# Analytics System Documentation

## Overview
The Analytics System provides comprehensive business intelligence for merchants, users, and riders. This documentation covers the merchant analytics implementation.

## Features Implemented

### 📊 Core Metrics Dashboard
- **Total Revenue**: Sum of all completed orders
- **Total Orders**: Count of all orders in the period
- **Total Profile Visits**: Unique customer visits (simulated via order data)
- **Total Users**: Unique customers who placed orders
- **Average Response Time**: Average order processing time
- **Total Rating**: Average customer rating (simulated)

### 📈 Analytics Features
- **Bar Chart Data**: Daily orders and revenue breakdown
- **Top Performing Items**: Top 5 items by revenue and order count
- **Store Type Detection**: Automatic food vs non-food store detection
- **Date Range Filtering**: Flexible time period selection

### 📅 Date Range Options
- **Quick Filters**: Today, Yesterday, This Week, Last Week, This Month, Last Month, This Year, All Time
- **Custom Range**: Start Date + End Date picker
- **Default Period**: This Week

## API Endpoints

### Separate Analytics Endpoints

The analytics system is designed with separate endpoints for different sections, allowing merchants to:
- **Load sections independently** for better performance
- **Apply different date filters** per section
- **Update specific sections** without affecting others

### GET /api/merchant_metrics.php
Get core metrics for dashboard cards (Total Revenue, Orders, Users, etc.).

#### Query Parameters:
- `period` (string): Time period filter
  - Values: `today`, `yesterday`, `this_week`, `last_week`, `this_month`, `last_month`, `this_year`, `all_time`, `custom`
  - Default: `this_week`
- `start_date` (string): Start date for custom period (YYYY-MM-DD format)
- `end_date` (string): End date for custom period (YYYY-MM-DD format)

#### Example Requests:
```bash
# This week's metrics
GET /api/merchant_metrics.php?period=this_week

# This month's metrics
GET /api/merchant_metrics.php?period=this_month

# Custom date range metrics
GET /api/merchant_metrics.php?period=custom&start_date=2024-01-01&end_date=2024-01-31
```

### GET /api/merchant_orders_analytics.php
Get orders analytics for bar chart visualization.

#### Query Parameters:
- `period` (string): Time period filter (same as above)
- `start_date` (string): Start date for custom period
- `end_date` (string): End date for custom period

#### Example Requests:
```bash
# This week's orders analytics
GET /api/merchant_orders_analytics.php?period=this_week

# This month's orders analytics
GET /api/merchant_orders_analytics.php?period=this_month
```

### GET /api/merchant_top_items.php
Get top performing items for the store.

#### Query Parameters:
- `period` (string): Time period filter (same as above)
- `start_date` (string): Start date for custom period
- `end_date` (string): End date for custom period
- `limit` (int): Number of top items to return (1-20, default: 5)

#### Example Requests:
```bash
# Top 5 items this week
GET /api/merchant_top_items.php?period=this_week

# Top 10 items this month
GET /api/merchant_top_items.php?period=this_month&limit=10

# Top items for custom date range
GET /api/merchant_top_items.php?period=custom&start_date=2024-01-01&end_date=2024-01-31
```

### GET /api/merchant_analytics.php
Get analytics overview/summary with growth comparison.

#### Response Structure:
```json
{
  "status": "success",
  "data": {
    "store_info": {
      "id": 15,
      "name": "My Food Store",
      "type": "food",
      "store_type": "Restaurant"
    },
    "date_range": {
      "period": "this_week",
      "start_date": "2024-01-15 00:00:00",
      "end_date": "2024-01-21 23:59:59"
    },
    "metrics": {
      "total_revenue": 125000.50,
      "total_orders": 45,
      "total_profile_visits": 38,
      "total_users": 32,
      "avg_response_time": 8.5,
      "total_rating": 4.7
    },
    "orders_analytics": [
      {
        "date": "2024-01-15",
        "orders": 8,
        "revenue": 25000.00
      },
      {
        "date": "2024-01-16",
        "orders": 12,
        "revenue": 35000.00
      }
    ],
    "top_performing_items": [
      {
        "id": 25,
        "name": "Jollof Rice + Beef",
        "price": 10000.00,
        "photo": "https://api.runnix.africa/uploads/food-items/item_123.jpg",
        "short_description": "Delicious jollof rice with beef",
        "order_count": 15,
        "total_quantity": 18,
        "total_revenue": 180000.00
      }
    ]
  }
}
```

### POST /api/merchant_analytics.php
Get analytics summary with growth comparison.

#### Request Body:
```json
{
  "action": "summary"
}
```

#### Response Structure:
```json
{
  "status": "success",
  "data": {
    "current_metrics": {
      "total_revenue": 125000.50,
      "total_orders": 45,
      "total_profile_visits": 38,
      "total_users": 32,
      "avg_response_time": 8.5,
      "total_rating": 4.7
    },
    "growth_percentages": {
      "total_revenue": 15.5,
      "total_orders": 12.3,
      "total_profile_visits": 8.7,
      "total_users": 5.2,
      "avg_response_time": -2.1,
      "total_rating": 0.3
    },
    "store_type": "food"
  }
}
```

## Authentication
- **Required**: Valid JWT token in Authorization header
- **Role**: Must be `merchant`
- **Store Access**: Only access to merchant's own store data

## Store Type Detection

### Food Stores
- **Detection**: Store type name contains food keywords OR has food_items
- **Data Source**: `food_items` table
- **Top Items**: Top 5 food items by revenue and order count

### Non-Food Stores
- **Detection**: Store type name doesn't contain food keywords AND no food_items
- **Data Source**: `items` table
- **Top Items**: Top 5 regular items by revenue and order count

## Database Tables Used

### Core Tables:
- `stores` - Store information
- `orders` - Order data
- `order_items` - Order item details
- `food_items` - Food items (for food stores)
- `items` - Regular items (for non-food stores)

### Supporting Tables:
- `users` - Customer data
- `store_types` - Store type information

## Error Handling

### Common Error Responses:
```json
{
  "status": "error",
  "message": "Access denied. Only merchants can access analytics."
}
```

```json
{
  "status": "error",
  "message": "Invalid period. Allowed values: today, yesterday, this_week, last_week, this_month, last_month, this_year, all_time, custom"
}
```

```json
{
  "status": "error",
  "message": "Store ID not found. Please ensure your merchant account is properly set up."
}
```

## Testing

### Test Endpoint:
- **URL**: `/api/test_analytics.php`
- **Purpose**: Verify system setup and show available endpoints
- **Access**: No authentication required (development only)

### Sample Test Data:
The system works with existing order and item data. No special test data setup required.

## Future Enhancements

### Planned Features:
1. **User Analytics**: Customer behavior and preferences
2. **Rider Analytics**: Delivery performance and earnings
3. **Advanced Filtering**: Category-based, item-based filtering
4. **Export Functionality**: CSV/PDF report generation
5. **Real-time Updates**: WebSocket integration for live data
6. **Custom Metrics**: User-defined KPI tracking

### Performance Optimizations:
1. **Caching**: Redis integration for frequently accessed data
2. **Database Indexing**: Optimized queries for large datasets
3. **Data Aggregation**: Pre-calculated metrics for faster responses
4. **Pagination**: Large dataset handling

## Security Considerations

### Data Privacy:
- Merchants can only access their own store data
- No cross-store data leakage
- Sensitive customer data is aggregated/anonymized

### Performance:
- Database queries are optimized with proper indexing
- Date range validation prevents excessive data queries
- Error handling prevents system overload

## Support

For issues or questions regarding the analytics system:
1. Check the test endpoint for system status
2. Verify authentication and store setup
3. Review error messages for specific issues
4. Contact development team for advanced features
