# Firebase Cloud Messaging (FCM) Setup Guide

## 🔧 **Configuration Steps**

### **1. Get Firebase Server Keys**

#### **Option A: Service Account Key (Recommended)**
1. Go to Firebase Console → Project Settings → Service Accounts
2. Click "Generate new private key"
3. Download the JSON file
4. Place it in your backend directory (e.g., `backend/firebase-service-account.json`)

#### **Option B: Legacy Server Key**
1. Go to Firebase Console → Project Settings → Cloud Messaging
2. Enable "Cloud Messaging API (Legacy)" if disabled
3. Copy the "Server key" from the Legacy section

### **2. Environment Configuration**

Add these variables to your existing `.env` file in the project root directory:

```env
# Firebase Cloud Messaging Configuration
FCM_PROJECT_ID=runnixafrica
FCM_SERVICE_ACCOUNT_PATH=firebase-service-account.json
```

### **3. Database Setup**

The FCM system will automatically create the `fcm_tokens` table when first used.

## 📱 **API Endpoints**

### **Register FCM Token**
```
POST /api/fcm_register_token.php
Authorization: Bearer <token>

{
  "token": "fcm_token_from_mobile_app",
  "device_type": "android|ios|web",
  "device_id": "optional_device_id",
  "app_version": "1.0.0"
}
```

### **Send Notification to User**
```
POST /api/fcm_send_notification.php
Authorization: Bearer <token>

{
  "title": "Notification Title",
  "body": "Notification message",
  "data": {
    "key1": "value1",
    "key2": "value2"
  },
  "target_user_id": 123
}
```

### **Send Bulk Notifications**
```
POST /api/fcm_send_bulk_notification.php
Authorization: Bearer <token>

{
  "title": "Bulk Notification",
  "body": "Message for multiple users",
  "data": {
    "type": "announcement"
  },
  "user_ids": [1, 2, 3, 4, 5]
}
```

## 🧪 **Testing**

### **Test Script**
```php
<?php
// Test FCM functionality
require_once 'vendor/autoload.php';

use Service\FCMService;

try {
    $fcm = new FCMService();
    
    // Test single device
    $result = $fcm->sendToDevice(
        'your_fcm_token_here',
        'Test Notification',
        'This is a test message',
        ['test' => 'data']
    );
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
```

## 📋 **Mobile App Integration**

### **Android (Kotlin/Java)**
```kotlin
// Get FCM token
FirebaseMessaging.getInstance().token.addOnCompleteListener { task ->
    if (!task.isSuccessful) {
        Log.w(TAG, "Fetching FCM registration token failed", task.exception)
        return@addOnCompleteListener
    }

    // Get new FCM registration token
    val token = task.result
    Log.d(TAG, "FCM Token: $token")
    
    // Send token to your backend
    sendTokenToServer(token)
}
```

### **iOS (Swift)**
```swift
// Get FCM token
Messaging.messaging().token { token, error in
    if let error = error {
        print("Error fetching FCM registration token: \(error)")
    } else if let token = token {
        print("FCM registration token: \(token)")
        
        // Send token to your backend
        sendTokenToServer(token: token)
    }
}
```

## 🔍 **Troubleshooting**

### **Common Issues**

1. **"FCM is not properly configured"**
   - Check your `.env` file
   - Verify FCM_PROJECT_ID and FCM_SERVER_KEY are set

2. **"Failed to initialize Firebase"**
   - Check service account JSON file path
   - Verify Firebase project ID is correct

3. **"Token is invalid"**
   - Token might be expired
   - Check if app is properly configured with Firebase

4. **Notifications not received**
   - Check device FCM token is valid
   - Verify app has notification permissions
   - Check Firebase console for delivery reports

### **Debug Logging**

Enable detailed logging by checking:
- `backend/php-error.log`
- Firebase Console → Cloud Messaging → Reports

## 🚀 **Usage Examples**

### **Order Notifications**
```php
// Notify customer about order status
$fcmController->sendNotification([
    'title' => 'Order Update',
    'body' => 'Your order #12345 is ready for pickup',
    'data' => [
        'order_id' => '12345',
        'status' => 'ready',
        'type' => 'order_update'
    ],
    'target_user_id' => $customerId
], $user);
```

### **Rider Notifications**
```php
// Notify riders about new delivery
$fcmController->sendBulkNotification([
    'title' => 'New Delivery Available',
    'body' => 'A new delivery is available in your area',
    'data' => [
        'delivery_id' => '67890',
        'distance' => '2.5km',
        'type' => 'new_delivery'
    ],
    'user_ids' => $riderIds
], $user);
```

### **Promotional Notifications**
```php
// Send promotional message to all users
$fcmController->sendTopicNotification([
    'title' => 'Special Offer!',
    'body' => 'Get 20% off your next order',
    'data' => [
        'discount' => '20',
        'type' => 'promotion'
    ],
    'topic' => 'all_users'
], $user);
```

## 📊 **Monitoring**

### **Token Statistics**
```php
// Get FCM token statistics (admin only)
$stats = $fcmController->getStats($user);
// Returns: total_tokens, active_tokens, android_tokens, ios_tokens, web_tokens
```

### **Cleanup Old Tokens**
```php
// Clean up inactive tokens older than 30 days
$fcmTokenModel->cleanupOldTokens(30);
```

## 🔐 **Security Notes**

- Keep your Firebase service account JSON file secure
- Never commit `.env` files to version control
- Use HTTPS for all API calls
- Validate FCM tokens before sending notifications
- Implement rate limiting for notification endpoints
