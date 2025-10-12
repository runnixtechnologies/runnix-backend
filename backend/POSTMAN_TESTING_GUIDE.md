# 🚀 **Postman Testing Guide for Push Notifications**

## 📱 **Yes, You Will Get Push Notifications!**

When you test with Postman, you'll receive push notifications on your device if:
1. ✅ You have the mobile app installed
2. ✅ You're logged in as a merchant
3. ✅ Your FCM token is registered
4. ✅ Push notifications are enabled in your preferences

## 🔧 **Setup Steps**

### **1. First, Register Your FCM Token**

**Endpoint:** `POST /api/fcm_register_token.php`

**Headers:**
```
Authorization: Bearer YOUR_MERCHANT_TOKEN
Content-Type: application/json
```

**Body:**
```json
{
  "token": "YOUR_FCM_TOKEN_FROM_MOBILE_APP",
  "device_type": "android",
  "device_id": "test_device_123",
  "app_version": "1.0.0"
```

### **2. Set Your Notification Preferences**

**Endpoint:** `PUT /api/notification_preferences.php`

**Headers:**
```
Authorization: Bearer YOUR_MERCHANT_TOKEN
Content-Type: application/json
```

**Body:**
```json
{
  "push_notifications_enabled": true,
  "sms_notifications_enabled": true
}
```

## 🧪 **Test All Notification Types**

### **1. Test New Order Notification**

**Endpoint:** `POST /api/test_notification.php`

**Headers:**
```
Authorization: Bearer YOUR_MERCHANT_TOKEN
Content-Type: application/json
```

**Body:**
```json
{
  "merchant_id": 123,
  "notification_type": "new_order",
  "order_id": 456,
  "order_number": "ORD-TEST-001",
  "customer_name": "John Doe",
  "customer_phone": "08012345678",
  "order_total": "2500",
  "delivery_address": "123 Main Street, Lagos",
  "items_count": 3
}
```

**Expected Response:**
```json
{
  "status": "success",
  "message": "Test notification sent successfully",
  "notification_type": "new_order",
  "merchant_id": 123,
  "result": {
    "status": "success",
    "message": "Custom notifications sent successfully",
    "results": {
      "push": {
        "status": "success",
        "message": "Notification sent successfully"
      },
      "sms": {
        "status": "success",
        "message": "SMS sent successfully"
      },
      "email": {
        "status": "success",
        "message": "Email sent successfully"
      }
    }
  }
}
```

### **2. Test Payment Received Notification**

**Body:**
```json
{
  "merchant_id": 123,
  "notification_type": "payment_received",
  "payment_id": 789,
  "amount": "2500",
  "payment_method": "Card",
  "transaction_id": "TXN-TEST-789",
  "order_number": "ORD-TEST-001"
}
```

### **3. Test Account Verification Notification**

**Body:**
```json
{
  "merchant_id": 123,
  "notification_type": "account_verification",
  "verification_status": "approved",
  "admin_notes": "All documents verified successfully"
}
```

### **4. Test Customer Review Notification**

**Body:**
```json
{
  "merchant_id": 123,
  "notification_type": "customer_review",
  "review_id": 101,
  "customer_name": "Jane Smith",
  "rating": 5,
  "review_text": "Excellent food and fast delivery!",
  "order_number": "ORD-TEST-001"
}
```

### **5. Test Customer Message Notification**

**Body:**
```json
{
  "merchant_id": 123,
  "notification_type": "customer_message",
  "message_id": 202,
  "customer_name": "Mike Johnson",
  "message_text": "Can you make the food less spicy?",
  "order_id": 456
}
```

### **6. Test Order Status Update Notification**

**Body:**
```json
{
  "merchant_id": 123,
  "notification_type": "order_status_update",
  "order_id": 456,
  "status": "confirmed",
  "order_number": "ORD-TEST-001",
  "customer_name": "John Doe"
}
```

## 🔧 **Direct API Testing (Alternative)**

You can also test directly using the merchant notifications API:

### **New Order Notification**
**Endpoint:** `POST /api/merchant_notifications.php?type=new_order`

**Body:**
```json
{
  "merchant_id": 123,
  "order_id": 456,
  "order_number": "ORD-TEST-001",
  "customer_name": "John Doe",
  "customer_phone": "08012345678",
  "order_total": "2500",
  "order_time": "2024-01-15 14:30:00"
}
```

### **Account Verification (Admin Only)**
**Endpoint:** `POST /api/verify_merchant_account.php`

**Body:**
```json
{
  "merchant_id": 123,
  "verification_status": "approved",
  "admin_notes": "All documents verified successfully"
}
```

## 📱 **What You'll See on Your Device**

### **Push Notification Examples:**

1. **New Order:**
   - Title: "New Order Received"
   - Body: "You have received a new order #ORD-TEST-001 from John Doe. Total: ₦2500"

2. **Payment Received:**
   - Title: "Payment Received"
   - Body: "Payment of ₦2500 received via Card"

3. **Account Verification:**
   - Title: "Account Verified!"
   - Body: "Congratulations! Your merchant account has been verified and approved."

4. **Customer Review:**
   - Title: "New Review from Jane Smith"
   - Body: "⭐ 5/5 stars - Excellent food and fast delivery!"

5. **Customer Message:**
   - Title: "New Message from Mike Johnson"
   - Body: "Can you make the food less spicy?"

## 🔍 **Testing Checklist**

- [ ] **FCM Token Registered** - Check `/api/fcm_register_token.php`
- [ ] **Notification Preferences Set** - Check `/api/notification_preferences.php`
- [ ] **Mobile App Installed** - On your test device
- [ ] **Logged in as Merchant** - With valid merchant account
- [ ] **Push Notifications Enabled** - In device settings
- [ ] **Internet Connection** - Stable connection required
- [ ] **Firebase Console** - Check delivery reports

## 🐛 **Troubleshooting**

### **No Push Notifications Received:**

1. **Check FCM Token:**
   ```bash
   GET /api/notification_preferences.php
   ```

2. **Check Notification History:**
   ```bash
   GET /api/merchant_notifications.php?merchant_id=123&limit=10
   ```

3. **Check Firebase Console:**
   - Go to Firebase Console → Cloud Messaging → Reports
   - Look for delivery statistics

4. **Check Device Settings:**
   - Ensure push notifications are enabled for your app
   - Check if app is in battery optimization whitelist

### **Common Issues:**

- **"No FCM tokens found"** - Register FCM token first
- **"Authentication failed"** - Check your Bearer token
- **"Invalid merchant_id"** - Use a valid merchant user ID
- **"Push notifications disabled"** - Enable in preferences

## 📊 **Expected Results**

When testing successfully, you should see:

1. **API Response:** `{"status": "success", "message": "Test notification sent successfully"}`
2. **Push Notification:** Appears on your device
3. **SMS:** Received on merchant's phone (if enabled)
4. **Email:** Sent to merchant's email
5. **Firebase Console:** Shows delivery statistics

## 🎯 **Quick Test Commands**

```bash
# Test all notification types
curl -X POST "http://localhost/api/test_notification.php" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"merchant_id": 123, "notification_type": "new_order"}'

# Check notification preferences
curl -X GET "http://localhost/api/notification_preferences.php" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Register FCM token
curl -X POST "http://localhost/api/fcm_register_token.php" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"token": "YOUR_FCM_TOKEN", "device_type": "android"}'
```

## 🚀 **Ready to Test!**

1. **Install your mobile app** on your test device
2. **Login as a merchant** account
3. **Get your FCM token** from the app
4. **Register the token** using the API
5. **Set notification preferences** to enabled
6. **Test notifications** using Postman
7. **Watch your device** for push notifications! 📱✨

You'll definitely get push notifications on your device when testing with Postman! 🎉
