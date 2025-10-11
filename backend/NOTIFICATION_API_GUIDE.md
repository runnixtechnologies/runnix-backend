# Notification System API Guide

## 📋 **Overview**

The notification system supports three channels:
- **Push Notifications** (FCM) - Can be enabled/disabled
- **SMS Notifications** (Termii) - Can be enabled/disabled, free for now
- **Email Notifications** - Always ON (cannot be disabled)

## 🔧 **API Endpoints**

### **1. Notification Preferences**

#### **Get User Preferences**
```http
GET /api/notification_preferences.php
Authorization: Bearer <token>
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "user_id": 123,
    "user_type": "merchant",
    "push_notifications_enabled": true,
    "push_order_notifications": true,
    "push_payment_notifications": true,
    "push_delivery_notifications": true,
    "push_promotional_notifications": false,
    "push_system_notifications": true,
    "push_support_notifications": true,
    "sms_notifications_enabled": true,
    "sms_order_notifications": true,
    "sms_payment_notifications": true,
    "sms_delivery_notifications": true,
    "sms_promotional_notifications": false,
    "sms_system_notifications": true,
    "sms_support_notifications": true,
    "email_notifications_enabled": true,
    "email_order_notifications": true,
    "email_payment_notifications": true,
    "email_delivery_notifications": true,
    "email_promotional_notifications": false,
    "email_system_notifications": true,
    "email_support_notifications": true,
    "quiet_hours_start": "22:00:00",
    "quiet_hours_end": "08:00:00",
    "quiet_hours_enabled": false,
    "timezone": "Africa/Lagos"
  }
}
```

#### **Update Preferences**
```http
PUT /api/notification_preferences.php
Authorization: Bearer <token>
Content-Type: application/json

{
  "push_notifications_enabled": true,
  "push_promotional_notifications": false,
  "sms_notifications_enabled": true,
  "sms_promotional_notifications": false,
  "email_promotional_notifications": false,
  "quiet_hours_enabled": true,
  "quiet_hours_start": "23:00:00",
  "quiet_hours_end": "07:00:00",
  "timezone": "Africa/Lagos"
}
```

#### **Reset to Default**
```http
DELETE /api/notification_preferences.php
Authorization: Bearer <token>
```

### **2. Available Channels**

#### **Get Available Channels**
```http
GET /api/notification_channels.php
Authorization: Bearer <token>
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "channels": {
      "push": {
        "name": "Push Notifications",
        "description": "In-app notifications",
        "can_disable": true,
        "types": ["order", "payment", "delivery", "system", "promotion", "support"]
      },
      "sms": {
        "name": "SMS Notifications",
        "description": "Text message notifications",
        "can_disable": true,
        "types": ["order", "payment", "delivery", "system", "promotion", "support"],
        "note": "SMS notifications may incur charges in the future"
      },
      "email": {
        "name": "Email Notifications",
        "description": "Email notifications",
        "can_disable": false,
        "types": ["order", "payment", "delivery", "system", "promotion", "support"],
        "note": "Email notifications cannot be disabled for security and tracking purposes"
      }
    },
    "notification_types": {
      "order": "Order-related notifications",
      "payment": "Payment and billing notifications",
      "delivery": "Delivery and logistics notifications",
      "system": "System and maintenance notifications",
      "promotion": "Promotional and marketing notifications",
      "support": "Customer support notifications"
    }
  }
}
```

### **3. Notification Check**

#### **Check Notification Enabled**
```http
POST /api/notification_check.php
Authorization: Bearer <token>
Content-Type: application/json

{
  "channel": "push",
  "notification_type": "order"
}
```

#### **Check Quiet Hours**
```http
POST /api/notification_check.php
Authorization: Bearer <token>
Content-Type: application/json

{
  "check_quiet_hours": true
}
```

### **4. Send Notifications**

#### **Send Custom Notification**
```http
POST /api/send_notification.php
Authorization: Bearer <token>
Content-Type: application/json

{
  "user_id": 123,
  "user_type": "merchant",
  "title": "Custom Notification",
  "message": "This is a custom notification message",
  "channels": ["push", "sms", "email"],
  "notification_type": "system",
  "reference_id": 456,
  "reference_type": "order"
}
```

#### **Send Template Notification**
```http
POST /api/send_notification.php
Authorization: Bearer <token>
Content-Type: application/json

{
  "user_id": 123,
  "user_type": "merchant",
  "template_key": "new_order",
  "variables": {
    "order_number": "ORD-001",
    "customer_name": "John Doe",
    "order_total": "2500",
    "order_time": "2024-01-15 14:30:00"
  },
  "reference_id": 456,
  "reference_type": "order"
}
```

### **5. Merchant Notifications**

#### **Send New Order Notification**
```http
POST /api/merchant_notifications.php?type=new_order
Authorization: Bearer <token>
Content-Type: application/json

{
  "merchant_id": 123,
  "order_number": "ORD-001",
  "customer_name": "John Doe",
  "order_total": "2500",
  "order_time": "2024-01-15 14:30:00",
  "order_id": 456
}
```

#### **Send Payment Received Notification**
```http
POST /api/merchant_notifications.php?type=payment_received
Authorization: Bearer <token>
Content-Type: application/json

{
  "merchant_id": 123,
  "amount": "2500",
  "order_number": "ORD-001",
  "payment_method": "Card",
  "transaction_id": "TXN-789",
  "payment_id": 789
}
```

#### **Send Rider Assigned Notification**
```http
POST /api/merchant_notifications.php?type=rider_assigned
Authorization: Bearer <token>
Content-Type: application/json

{
  "merchant_id": 123,
  "rider_name": "Mike Johnson",
  "rider_phone": "08012345678",
  "order_number": "ORD-001",
  "estimated_delivery": "30 minutes",
  "order_id": 456
}
```

#### **Send Order Delivered Notification**
```http
POST /api/merchant_notifications.php?type=order_delivered
Authorization: Bearer <token>
Content-Type: application/json

{
  "merchant_id": 123,
  "order_number": "ORD-001",
  "customer_name": "John Doe",
  "delivery_time": "2024-01-15 15:30:00",
  "order_id": 456
}
```

#### **Send Custom Notification**
```http
POST /api/merchant_notifications.php?type=custom
Authorization: Bearer <token>
Content-Type: application/json

{
  "merchant_id": 123,
  "title": "Store Update",
  "message": "Your store will be closed for maintenance tomorrow",
  "channels": ["push", "email"],
  "notification_type": "system",
  "reference_id": 789,
  "reference_type": "maintenance"
}
```

#### **Send Bulk Notification**
```http
POST /api/merchant_notifications.php?type=bulk
Authorization: Bearer <token>
Content-Type: application/json

{
  "merchant_ids": [123, 124, 125],
  "template_key": "system_maintenance",
  "variables": {
    "maintenance_date": "2024-01-20",
    "start_time": "02:00:00",
    "end_time": "04:00:00"
  },
  "reference_id": 999,
  "reference_type": "maintenance"
}
```

#### **Send Promotional Notification**
```http
POST /api/merchant_notifications.php?type=promotional
Authorization: Bearer <token>
Content-Type: application/json

{
  "title": "Special Offer",
  "message": "Get 20% off on all orders this weekend!",
  "merchant_ids": [123, 124, 125],
  "reference_id": 888,
  "reference_type": "promotion"
}
```

#### **Get Notification History**
```http
GET /api/merchant_notifications.php?merchant_id=123&limit=20&offset=0
Authorization: Bearer <token>
```

## 📱 **Available Templates**

### **Template Keys:**
- `new_order` - New order received (merchants)
- `order_confirmed` - Order confirmed (users)
- `payment_received` - Payment received (merchants)
- `rider_assigned` - Rider assigned (users, merchants)
- `order_delivered` - Order delivered (merchants, riders)
- `system_maintenance` - System maintenance (all users)

### **Template Variables:**
Each template supports specific variables for customization:

**new_order:**
- `order_number` - Order number
- `customer_name` - Customer name
- `order_total` - Order total amount
- `order_time` - Order time

**payment_received:**
- `amount` - Payment amount
- `order_number` - Order number
- `payment_method` - Payment method
- `transaction_id` - Transaction ID

**rider_assigned:**
- `rider_name` - Rider name
- `rider_phone` - Rider phone
- `order_number` - Order number
- `estimated_delivery` - Estimated delivery time

## 🔔 **Notification Types**

- **order** - Order-related notifications
- **payment** - Payment and billing notifications
- **delivery** - Delivery and logistics notifications
- **system** - System and maintenance notifications
- **promotion** - Promotional and marketing notifications
- **support** - Customer support notifications

## ⚙️ **User Preferences**

### **Push Notifications:**
- Can be enabled/disabled globally
- Can be enabled/disabled by notification type
- Respects quiet hours settings

### **SMS Notifications:**
- Can be enabled/disabled globally
- Can be enabled/disabled by notification type
- Free for now (future billing support ready)
- Respects quiet hours settings

### **Email Notifications:**
- Always enabled (cannot be disabled)
- Can disable promotional emails only
- Used for security and tracking purposes

### **Quiet Hours:**
- Set do-not-disturb times
- Only urgent notifications (system, support) sent during quiet hours
- Timezone-aware

## 🚀 **Usage Examples**

### **Example 1: New Order Flow**
```json
{
  "user_id": 123,
  "user_type": "merchant",
  "template_key": "new_order",
  "variables": {
    "order_number": "ORD-001",
    "customer_name": "John Doe",
    "order_total": "2500",
    "order_time": "2024-01-15 14:30:00"
  },
  "reference_id": 456,
  "reference_type": "order"
}
```

### **Example 2: Payment Confirmation**
```json
{
  "user_id": 123,
  "user_type": "merchant",
  "template_key": "payment_received",
  "variables": {
    "amount": "2500",
    "order_number": "ORD-001",
    "payment_method": "Card",
    "transaction_id": "TXN-789"
  },
  "reference_id": 789,
  "reference_type": "payment"
}
```

### **Example 3: Custom System Notification**
```json
{
  "user_id": 123,
  "user_type": "merchant",
  "title": "System Maintenance",
  "message": "Scheduled maintenance will occur tonight from 2 AM to 4 AM",
  "channels": ["push", "email"],
  "notification_type": "system",
  "reference_id": 999,
  "reference_type": "maintenance"
}
```

## 🔒 **Security Notes**

- All endpoints require authentication
- Users can only access their own notification preferences
- Admins can send bulk notifications
- Email notifications cannot be disabled for security
- All notifications are logged for audit purposes

## 📊 **Response Format**

All API responses follow this format:

**Success:**
```json
{
  "status": "success",
  "message": "Operation completed successfully",
  "data": { ... }
}
```

**Error:**
```json
{
  "status": "error",
  "message": "Error description"
}
```

## 🧪 **Testing**

Use the test endpoints to verify notification functionality:

```bash
# Test FCM setup
php backend/test_fcm_setup.php

# Test notification preferences
curl -X GET "http://localhost/api/notification_preferences.php" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Test sending notification
curl -X POST "http://localhost/api/send_notification.php" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"user_id": 123, "user_type": "merchant", "title": "Test", "message": "Test message"}'
```
